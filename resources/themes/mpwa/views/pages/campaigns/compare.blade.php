<x-layout-dashboard title="{{ __('Campaign Comparison') }}">

    <x-page-header
        title="{{ __('Campaign Comparison') }}"
        subtitle="{{ __('Compare up to 5 campaigns side-by-side') }}"
        :breadcrumb="[__('Reports'), __('Campaign Comparison')]"
    >
        <a href="{{ route('campaigns') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>{{ __('Back to Campaigns') }}
        </a>
    </x-page-header>

    <div class="container-fluid">

        {{-- ── Campaign selector ────────────────────────────────────────────── --}}
        <div class="card mb-4">
            <div class="card-body">
                <label class="form-label fw-semibold">{{ __('Select 2–5 campaigns to compare') }}</label>
                <select id="campaignPicker" class="form-select" multiple style="height:160px">
                    @foreach($campaigns as $c)
                        <option value="{{ $c->id }}"
                            {{ in_array($c->id, $selected) ? 'selected' : '' }}>
                            {{ $c->name }}
                            ({{ ucfirst($c->status) }} · {{ $c->created_at->format('d M Y') }})
                        </option>
                    @endforeach
                </select>
                <div class="mt-2 d-flex gap-2 align-items-center">
                    <button id="btnCompare" class="btn btn-primary btn-sm">
                        <i class="bi bi-bar-chart-line-fill me-1"></i>{{ __('Compare') }}
                    </button>
                    <small class="text-muted">{{ __('Hold Ctrl/Cmd to select multiple') }}</small>
                </div>
            </div>
        </div>

        {{-- ── Loading spinner ──────────────────────────────────────────────── --}}
        <div id="compareSpinner" class="text-center py-5 d-none">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2 text-muted">{{ __('Loading comparison…') }}</p>
        </div>

        {{-- ── Results ──────────────────────────────────────────────────────── --}}
        <div id="compareResults" class="d-none">

            {{-- stat cards --}}
            <div id="statCards" class="row g-3 mb-4"></div>

            {{-- charts row --}}
            <div class="row g-4 mb-4">
                <div class="col-lg-8">
                    <div class="card h-100">
                        <div class="card-header fw-semibold">{{ __('Delivery / Read / Click Rate (%)') }}</div>
                        <div class="card-body">
                            <canvas id="barChart" height="90"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card h-100">
                        <div class="card-header fw-semibold">{{ __('Best Metric Summary') }}</div>
                        <div class="card-body" id="bestMetrics"></div>
                    </div>
                </div>
            </div>

            {{-- comparison table --}}
            <div class="card mb-4">
                <div class="card-header fw-semibold">{{ __('Side-by-Side Comparison') }}</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm mb-0" id="compareTable" style="font-size:0.85rem"></table>
                    </div>
                </div>
            </div>

            {{-- heatmap --}}
            <div class="card mb-4">
                <div class="card-header fw-semibold">
                    {{ __('Send-Time Heatmap') }}
                    <small class="text-muted ms-2">{{ __('Read rate % by day × hour across selected campaigns') }}</small>
                </div>
                <div class="card-body p-2">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm mb-0" id="heatmapTable" style="font-size:0.75rem; min-width:960px"></table>
                    </div>
                    <div class="d-flex align-items-center gap-2 mt-2 ps-2" style="font-size:0.78rem">
                        <span>{{ __('Low') }}</span>
                        <div style="background:linear-gradient(to right,#e8f5e9,#1b5e20);width:120px;height:14px;border-radius:4px"></div>
                        <span>{{ __('High read rate') }}</span>
                        <span class="ms-3 text-muted">{{ __('— = no data') }}</span>
                    </div>
                </div>
            </div>
        </div>

    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
    (function () {
        const DAYS  = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
        const HOURS = Array.from({length: 24}, (_, i) => i.toString().padStart(2,'0') + ':00');
        const PALETTE = ['#6366f1','#f97316','#22c55e','#ef4444','#a855f7'];

        let barChartInst = null;

        document.getElementById('btnCompare').addEventListener('click', runCompare);

        // Auto-run if pre-selected
        @if(count($selected) >= 2)
        runCompare();
        @endif

        function selectedIds() {
            return Array.from(document.getElementById('campaignPicker').selectedOptions)
                        .map(o => o.value);
        }

        function runCompare() {
            const ids = selectedIds();
            if (ids.length < 2 || ids.length > 5) {
                showError('{{ __("Select 2–5 campaigns.") }}');
                return;
            }
            hideError();
            setLoading(true);

            fetch('{{ route("campaigns.compare.data") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ ids })
            })
            .then(r => r.json())
            .then(data => {
                if (data.error) { showError(data.error); return; }
                render(data);
            })
            .catch(() => showError('{{ __("Request failed.") }}'))
            .finally(() => setLoading(false));
        }

        function render(data) {
            const { campaigns, heatmap } = data;
            renderStatCards(campaigns);
            renderBarChart(campaigns);
            renderBestMetrics(campaigns);
            renderTable(campaigns);
            renderHeatmap(heatmap);
            document.getElementById('compareResults').classList.remove('d-none');
        }

        // ── Stat cards ───────────────────────────────────────────────────────
        function renderStatCards(campaigns) {
            const wrap = document.getElementById('statCards');
            wrap.innerHTML = '';
            campaigns.forEach((c, i) => {
                const col = document.createElement('div');
                col.className = `col-sm-6 col-lg-${Math.floor(12 / campaigns.length) || 2}`;
                col.innerHTML = `
                    <div class="card border-0 shadow-sm h-100" style="border-top:4px solid ${PALETTE[i]}!important">
                        <div class="card-body">
                            <div class="fw-bold text-truncate mb-1" title="${esc(c.name)}">${esc(c.name)}</div>
                            <small class="text-muted d-block mb-2">${esc(c.phonebook)} · ${statusBadge(c.status)}</small>
                            <div class="d-flex flex-wrap gap-3 mt-2">
                                ${metricPill('Audience', c.audience)}
                                ${metricPill('Delivered', c.delivery_rate + '%')}
                                ${metricPill('Read', c.read_rate + '%')}
                                ${metricPill('Clicks', c.click_rate + '%')}
                            </div>
                        </div>
                    </div>`;
                wrap.appendChild(col);
            });
        }

        function metricPill(label, val) {
            return `<div class="text-center"><div class="fw-semibold">${val}</div><div class="text-muted" style="font-size:0.72rem">${label}</div></div>`;
        }

        // ── Bar chart ────────────────────────────────────────────────────────
        function renderBarChart(campaigns) {
            if (barChartInst) barChartInst.destroy();
            const labels   = campaigns.map(c => truncate(c.name, 18));
            const delivery = campaigns.map(c => c.delivery_rate);
            const read     = campaigns.map(c => c.read_rate);
            const click    = campaigns.map(c => c.click_rate);

            barChartInst = new Chart(document.getElementById('barChart'), {
                type: 'bar',
                data: {
                    labels,
                    datasets: [
                        { label: '{{ __("Delivery %") }}', data: delivery, backgroundColor: '#6366f1' },
                        { label: '{{ __("Read %") }}',     data: read,     backgroundColor: '#22c55e' },
                        { label: '{{ __("Click %") }}',    data: click,    backgroundColor: '#f97316' },
                    ]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: { beginAtZero: true, max: 100, title: { display: true, text: '%' } }
                    },
                    plugins: { legend: { position: 'bottom' } }
                }
            });
        }

        // ── Best metric summary ──────────────────────────────────────────────
        function renderBestMetrics(campaigns) {
            const best = (metric, label, icon, color) => {
                const top = campaigns.reduce((a, b) => a[metric] >= b[metric] ? a : b);
                return `
                    <div class="d-flex align-items-center gap-2 mb-3 p-2 rounded" style="background:${color}22">
                        <i class="bi ${icon} fs-4" style="color:${color}"></i>
                        <div>
                            <div class="fw-semibold" style="font-size:0.82rem">${label}</div>
                            <div class="text-truncate" style="max-width:160px;font-size:0.8rem" title="${esc(top.name)}">${esc(top.name)}</div>
                            <div style="color:${color};font-weight:bold">${top[metric]}%</div>
                        </div>
                    </div>`;
            };

            document.getElementById('bestMetrics').innerHTML =
                best('delivery_rate', '{{ __("Best Delivery") }}',   'bi-send-check-fill',    '#6366f1') +
                best('read_rate',     '{{ __("Best Read Rate") }}',   'bi-eye-fill',           '#22c55e') +
                best('click_rate',    '{{ __("Most Clicks") }}',      'bi-cursor-fill',        '#f97316');
        }

        // ── Side-by-side table ───────────────────────────────────────────────
        function renderTable(campaigns) {
            const rows = [
                ['{{ __("Status") }}',         c => statusBadge(c.status)],
                ['{{ __("Type") }}',            c => `<span class="badge bg-secondary">${esc(c.type)}</span>`],
                ['{{ __("Phonebook") }}',       c => esc(c.phonebook)],
                ['{{ __("Template") }}',        c => c.template ? esc(c.template) : '<span class="text-muted">—</span>'],
                ['{{ __("Scheduled") }}',       c => c.schedule ? esc(c.schedule) : '<span class="text-muted">—</span>'],
                ['{{ __("Audience") }}',        c => c.audience.toLocaleString()],
                ['{{ __("Total (events)") }}',  c => c.total.toLocaleString()],
                ['{{ __("Delivered") }}',       c => `${c.delivered.toLocaleString()} <span class="text-muted">(${c.delivery_rate}%)</span>`],
                ['{{ __("Read") }}',            c => `${c.read_count.toLocaleString()} <span class="text-muted">(${c.read_rate}%)</span>`],
                ['{{ __("Failed") }}',          c => `<span class="text-danger">${c.failed.toLocaleString()}</span>`],
                ['{{ __("Clicks") }}',          c => `${c.clicks.toLocaleString()} <span class="text-muted">(${c.click_rate}%)</span>`],
            ];

            const table = document.getElementById('compareTable');
            const thead = `<thead class="table-light"><tr><th style="min-width:120px">{{ __("Metric") }}</th>${
                campaigns.map((c, i) => `<th style="border-top:3px solid ${PALETTE[i]}" class="text-truncate" style="max-width:160px" title="${esc(c.name)}">${truncate(c.name, 20)}</th>`).join('')
            }</tr></thead>`;

            const tbody = '<tbody>' + rows.map(([label, fn]) => {
                return `<tr><td class="fw-semibold">${label}</td>${campaigns.map(c => `<td>${fn(c)}</td>`).join('')}</tr>`;
            }).join('') + '</tbody>';

            table.innerHTML = thead + tbody;
        }

        // ── Send-time heatmap ────────────────────────────────────────────────
        function renderHeatmap(heatmap) {
            // Build [dow][hour] = read_rate
            const grid = Array.from({length:7}, () => Array(24).fill(null));
            let maxRate = 0;
            heatmap.forEach(({ dow, hour, read_rate }) => {
                grid[dow][hour] = read_rate;
                if (read_rate > maxRate) maxRate = read_rate;
            });

            const table = document.getElementById('heatmapTable');

            let html = '<thead><tr><th style="min-width:45px"></th>';
            HOURS.forEach(h => { html += `<th style="min-width:34px;padding:2px;text-align:center;font-size:0.68rem">${h.split(':')[0]}</th>`; });
            html += '</tr></thead><tbody>';

            DAYS.forEach((day, d) => {
                html += `<tr><td class="fw-semibold" style="white-space:nowrap">${day}</td>`;
                for (let h = 0; h < 24; h++) {
                    const val = grid[d][h];
                    if (val === null) {
                        html += `<td style="background:#f8f9fa;text-align:center;color:#adb5bd;font-size:0.68rem">—</td>`;
                    } else {
                        const intensity = maxRate > 0 ? val / maxRate : 0;
                        const bg = heatColor(intensity);
                        const fg = intensity > 0.6 ? '#fff' : '#333';
                        html += `<td style="background:${bg};color:${fg};text-align:center;font-size:0.68rem;padding:2px" title="${day} ${HOURS[h]}: ${val}%">${val}</td>`;
                    }
                }
                html += '</tr>';
            });

            html += '</tbody>';
            table.innerHTML = html;
        }

        // ── Helpers ──────────────────────────────────────────────────────────
        function heatColor(t) {
            // green gradient: #e8f5e9 → #1b5e20
            const r = Math.round(232 - (232-27)  * t);
            const g = Math.round(245 - (245-94)  * t);
            const b = Math.round(233 - (233-32)  * t);
            return `rgb(${r},${g},${b})`;
        }

        function statusBadge(s) {
            const map = { waiting:'warning', processing:'info', completed:'success', failed:'danger', paused:'secondary', draft:'light' };
            return `<span class="badge bg-${map[s]||'secondary'}">${s}</span>`;
        }

        function esc(str) {
            if (str == null) return '';
            return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
        }

        function truncate(str, len) {
            return str && str.length > len ? str.slice(0, len) + '…' : str;
        }

        function setLoading(on) {
            document.getElementById('compareSpinner').classList.toggle('d-none', !on);
            if (on) document.getElementById('compareResults').classList.add('d-none');
        }

        function showError(msg) {
            toastr.error(msg);
        }

        function hideError() {}
    })();
    </script>
    @endpush

</x-layout-dashboard>
