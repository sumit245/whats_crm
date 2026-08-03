<x-layout-dashboard title="{{ $creative->name }}">

<x-page-header title="{{ $creative->name }}"
    subtitle="{{ ucfirst($creative->format) }} · {{ ucfirst($creative->status) }}"
    :breadcrumb="[__('Ads Manager'), __('Creatives'), $creative->name]" />

<div class="row g-4">

{{-- Left: details + edit --}}
<div class="col-lg-5">
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header border-0 d-flex align-items-center gap-2">
            <i class="bi {{ $creative->formatIcon() }} text-primary"></i>
            <span class="fw-semibold">{{ __('Creative Details') }}</span>
            <span class="ms-auto badge bg-{{ $creative->status === 'ready' ? 'success' : ($creative->status === 'draft' ? 'warning' : 'danger') }}">
                {{ ucfirst($creative->status) }}
            </span>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('ads.creatives.update', $creative) }}" enctype="multipart/form-data">
                @csrf @method('PATCH')

                <div class="mb-3">
                    <label class="form-label fw-semibold">{{ __('Name') }}</label>
                    <input type="text" name="name" class="form-control" value="{{ $creative->name }}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">{{ __('Format') }}</label>
                    <input type="text" class="form-control" value="{{ ucfirst($creative->format) }}" disabled>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">{{ __('Headline') }}</label>
                    <input type="text" name="headline" class="form-control" value="{{ $creative->headline }}" maxlength="255"
                           id="editHeadline" oninput="updatePreview()">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">{{ __('Body / Caption') }}</label>
                    <textarea name="body" class="form-control" rows="4" maxlength="2000"
                              id="editBody" oninput="updatePreview()">{{ $creative->body }}</textarea>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">{{ __('CTA Text') }}</label>
                        <input type="text" name="cta_text" class="form-control" value="{{ $creative->cta_text }}" maxlength="50"
                               id="editCta" oninput="updatePreview()">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">{{ __('CTA URL') }}</label>
                        <input type="url" name="cta_url" class="form-control" value="{{ $creative->cta_url }}">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">{{ __('Status') }}</label>
                    <select name="status" class="form-select">
                        <option value="draft"    {{ $creative->status === 'draft'    ? 'selected' : '' }}>Draft</option>
                        <option value="ready"    {{ $creative->status === 'ready'    ? 'selected' : '' }}>Ready</option>
                        <option value="rejected" {{ $creative->status === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check2 me-1"></i>{{ __('Save Changes') }}
                    </button>
                    <a href="{{ route('ads.creatives.index') }}" class="btn btn-outline-secondary">{{ __('Back') }}</a>
                    <form method="POST" action="{{ route('ads.creatives.destroy', $creative) }}" class="ms-auto"
                          onsubmit="return confirm('{{ __('Delete this creative?') }}')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash3"></i></button>
                    </form>
                </div>
            </form>
        </div>
    </div>

    {{-- Media files --}}
    @if($creative->media_paths)
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h6 class="fw-semibold mb-3">{{ __('Media') }}</h6>
            <div class="d-flex flex-wrap gap-2">
                @foreach($creative->media_paths as $path)
                @php $url = asset('storage/'.$path); $ext = strtolower(pathinfo($path,PATHINFO_EXTENSION)); @endphp
                @if(in_array($ext,['mp4','mov','webm','avi']))
                <video src="{{ $url }}" controls style="max-width:180px;border-radius:8px;border:1px solid #ddd"></video>
                @else
                <img src="{{ $url }}" style="max-width:180px;border-radius:8px;border:1px solid #ddd;object-fit:cover" alt="">
                @endif
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- Carousel cards --}}
    @if($creative->carousel_cards)
    <div class="card border-0 shadow-sm mt-3">
        <div class="card-body">
            <h6 class="fw-semibold mb-3">{{ __('Carousel Cards') }}</h6>
            @foreach($creative->carousel_cards as $i => $card)
            <div class="d-flex gap-2 mb-2 align-items-center">
                <div class="fw-bold text-muted small" style="width:20px">{{ $i+1 }}</div>
                @if(!empty($card['image']))
                <img src="{{ $card['image'] }}" style="width:48px;height:48px;object-fit:cover;border-radius:4px;border:1px solid #ddd" alt="">
                @endif
                <div class="small">
                    <div class="fw-semibold">{{ $card['headline'] ?? '' }}</div>
                    <div class="text-muted">{{ $card['description'] ?? '' }}</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>

{{-- Right: multi-platform preview --}}
<div class="col-lg-7">
    <div class="card border-0 shadow-sm" style="position:sticky;top:70px">
        <div class="card-header border-0 bg-light d-flex align-items-center flex-wrap gap-2">
            <i class="bi bi-eye text-muted"></i>
            <span class="fw-semibold small">{{ __('Platform Previews') }}</span>
            <div class="ms-auto d-flex gap-1 flex-wrap">
                @php
                $previewChannels = [
                    ['key'=>'whatsapp',  'icon'=>'bi-whatsapp',    'label'=>'WhatsApp CTWA'],
                    ['key'=>'facebook',  'icon'=>'bi-facebook',    'label'=>'Facebook Feed'],
                    ['key'=>'instagram', 'icon'=>'bi-instagram',   'label'=>'Instagram'],
                    ['key'=>'linkedin',  'icon'=>'bi-linkedin',    'label'=>'LinkedIn'],
                    ['key'=>'telegram',  'icon'=>'bi-telegram',    'label'=>'Telegram'],
                    ['key'=>'email',     'icon'=>'bi-envelope-fill','label'=>'Email'],
                ];
                @endphp
                @foreach($previewChannels as $pch)
                <button type="button" class="btn btn-xs preview-tab {{ $loop->first ? 'btn-primary' : 'btn-outline-secondary' }}"
                    data-channel="{{ $pch['key'] }}" title="{{ $pch['label'] }}"
                    style="font-size:12px;padding:2px 8px">
                    <i class="bi {{ $pch['icon'] }}"></i>
                </button>
                @endforeach
            </div>
        </div>
        <div class="card-body p-3" id="previewArea" style="min-height:400px"></div>
    </div>
</div>

</div>

</x-layout-dashboard>

<script>
const CREATIVE = {
    headline: @json($creative->headline ?? ''),
    body:     @json($creative->body ?? ''),
    cta:      @json($creative->cta_text ?? ''),
    format:   @json($creative->format),
    media:    @json($creative->media_paths ? asset('storage/'.$creative->media_paths[0]) : ''),
};

let activeChannel = 'whatsapp';

document.querySelectorAll('.preview-tab').forEach(tab => {
    tab.addEventListener('click', function () {
        document.querySelectorAll('.preview-tab').forEach(t => {
            t.classList.remove('btn-primary'); t.classList.add('btn-outline-secondary');
        });
        this.classList.remove('btn-outline-secondary'); this.classList.add('btn-primary');
        activeChannel = this.dataset.channel;
        renderPreview();
    });
});

function updatePreview() {
    CREATIVE.headline = document.getElementById('editHeadline')?.value || CREATIVE.headline;
    CREATIVE.body     = document.getElementById('editBody')?.value     || CREATIVE.body;
    CREATIVE.cta      = document.getElementById('editCta')?.value      || CREATIVE.cta;
    renderPreview();
}

function esc(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

function mediaEl(url, h='160px') {
    if (!url) return `<div style="width:100%;height:${h};background:#e4e6ea;display:flex;align-items:center;justify-content:center;color:#aaa;font-size:24px"><i class="bi bi-image"></i></div>`;
    return `<img src="${esc(url)}" style="width:100%;height:${h};object-fit:cover" alt="">`;
}

function renderPreview() {
    const d = CREATIVE;
    const area = document.getElementById('previewArea');
    let html = '';

    if (activeChannel === 'whatsapp') {
        html = `<div style="max-width:300px;margin:0 auto">
          <div style="background:#075e54;color:#fff;padding:10px 14px;border-radius:8px 8px 0 0;display:flex;align-items:center;gap:8px">
            <div style="width:32px;height:32px;border-radius:50%;background:#25d366;display:flex;align-items:center;justify-content:center;font-weight:700">B</div>
            <div><div style="font-size:13px;font-weight:600">Your Business</div><div style="font-size:10px;opacity:.8">Sponsored</div></div>
          </div>
          <div style="background:#e5ddd5;padding:12px;border-radius:0 0 8px 8px;min-height:200px">
            <div style="background:#fff;border-radius:8px;padding:10px 12px;max-width:88%;margin-left:auto;box-shadow:0 1px 2px rgba(0,0,0,.15)">
              ${d.media ? `<img src="${esc(d.media)}" style="width:100%;border-radius:6px;margin-bottom:6px">` : ''}
              ${d.headline ? `<div style="font-weight:600;font-size:13px;margin-bottom:4px">${esc(d.headline)}</div>` : ''}
              <div style="font-size:12px;color:#333;line-height:1.5">${esc(d.body)}</div>
              ${d.cta ? `<div style="background:#128c7e;color:#fff;border-radius:0 0 6px 6px;padding:7px;text-align:center;font-size:13px;margin:8px -12px -10px">${esc(d.cta)}</div>` : ''}
            </div>
          </div>
        </div>`;
    }
    else if (activeChannel === 'facebook') {
        html = `<div style="border:1px solid #ddd;border-radius:8px;overflow:hidden;background:#fff;font-family:sans-serif">
          <div style="display:flex;align-items:center;gap:8px;padding:10px 12px">
            <div style="width:36px;height:36px;border-radius:50%;background:#1877f2;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;flex-shrink:0">B</div>
            <div><div style="font-size:13px;font-weight:600">Your Page</div><div style="font-size:10px;color:#65676b">Sponsored</div></div>
          </div>
          ${d.body ? `<div style="padding:0 12px 8px;font-size:13px;color:#1c1e21;line-height:1.5">${esc(d.body)}</div>` : ''}
          ${mediaEl(d.media)}
          <div style="padding:8px 12px;background:#f0f2f5;border-top:1px solid #ddd">
            <div style="font-size:11px;color:#65676b;text-transform:uppercase;letter-spacing:.5px">yourwebsite.com</div>
            <div style="font-size:13px;font-weight:600">${esc(d.headline)}</div>
          </div>
          ${d.cta ? `<div style="margin:8px 12px 12px;background:#fff;border:1px solid #1877f2;color:#1877f2;border-radius:6px;padding:6px;text-align:center;font-size:13px;font-weight:600">${esc(d.cta)}</div>` : ''}
        </div>`;
    }
    else if (activeChannel === 'instagram') {
        if (d.format === 'story' || d.format === 'reel') {
            html = `<div style="background:#000;border-radius:20px;overflow:hidden;max-width:200px;margin:0 auto;position:relative;height:356px;display:flex;flex-direction:column">
              <div style="padding:8px 10px 0;z-index:2;position:relative">
                <div style="height:2px;background:rgba(255,255,255,.4);border-radius:2px;margin-bottom:8px"><div style="width:55%;height:100%;background:#fff;border-radius:2px"></div></div>
                <div style="display:flex;align-items:center;gap:6px">
                  <div style="width:22px;height:22px;border-radius:50%;background:#fff"></div>
                  <div style="color:#fff;font-size:11px;font-weight:600">yourbusiness</div>
                  <div style="color:rgba(255,255,255,.6);font-size:10px;margin-left:auto">Sponsored</div>
                </div>
              </div>
              <div style="flex:1;${d.media ? `background:url('${d.media}') center/cover` : 'background:linear-gradient(135deg,#833ab4,#fd1d1d,#fcb045)'};display:flex;align-items:center;justify-content:center">
                ${!d.media ? '<span style="color:rgba(255,255,255,.4);font-size:28px">▶</span>' : ''}
              </div>
              <div style="position:absolute;bottom:0;left:0;right:0;padding:12px;background:linear-gradient(transparent,rgba(0,0,0,.7));color:#fff">
                ${d.headline ? `<div style="font-size:12px;font-weight:600;margin-bottom:4px">${esc(d.headline)}</div>` : ''}
                ${d.cta ? `<div style="background:rgba(255,255,255,.2);border-radius:4px;padding:4px 8px;font-size:11px;text-align:center">↑ ${esc(d.cta)}</div>` : ''}
              </div>
            </div>`;
        } else {
            html = `<div style="border:1px solid #dbdbdb;border-radius:8px;overflow:hidden;background:#fff;font-family:sans-serif">
              <div style="display:flex;align-items:center;gap:8px;padding:8px 12px">
                <div style="width:28px;height:28px;border-radius:50%;background:linear-gradient(45deg,#f58529,#dd2a7b,#8134af);display:flex;align-items:center;justify-content:center;color:#fff;font-size:12px;font-weight:700">B</div>
                <div><div style="font-size:12px;font-weight:600">yourbusiness</div><div style="font-size:10px;color:#8e8e8e">Sponsored</div></div>
              </div>
              ${mediaEl(d.media, '220px')}
              <div style="padding:8px 12px">
                <div style="font-size:12px;line-height:1.5"><strong>yourbusiness</strong> ${esc(d.body)}</div>
                ${d.cta ? `<div style="color:#0095f6;font-size:12px;margin-top:4px">${esc(d.cta)} →</div>` : ''}
              </div>
            </div>`;
        }
    }
    else if (activeChannel === 'linkedin') {
        html = `<div style="border:1px solid #e0e0e0;border-radius:8px;overflow:hidden;background:#fff;font-family:sans-serif">
          <div style="display:flex;gap:8px;padding:12px;align-items:flex-start">
            <div style="width:40px;height:40px;border-radius:4px;background:#0a66c2;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:16px;flex-shrink:0">B</div>
            <div><div style="font-size:13px;font-weight:600">Your Company</div><div style="font-size:11px;color:#666">Promoted</div></div>
          </div>
          ${d.body ? `<div style="padding:0 12px 8px;font-size:13px;color:#333;line-height:1.5">${esc(d.body)}</div>` : ''}
          ${mediaEl(d.media, '150px')}
          ${d.headline ? `<div style="padding:8px 12px;font-size:13px;font-weight:600;background:#f3f2f0;border-top:1px solid #e0e0e0">${esc(d.headline)}</div>` : ''}
          ${d.cta ? `<div style="padding:8px 12px"><span style="background:#0a66c2;color:#fff;border-radius:16px;padding:5px 16px;font-size:13px;font-weight:600">${esc(d.cta)}</span></div>` : ''}
        </div>`;
    }
    else if (activeChannel === 'telegram') {
        html = `<div style="background:#f0f4f8;border-radius:8px;padding:12px">
          <div style="background:#effdde;border-radius:12px 12px 0 12px;padding:10px 14px;max-width:88%;margin-left:auto;box-shadow:0 1px 2px rgba(0,0,0,.1)">
            ${d.media ? `<img src="${esc(d.media)}" style="width:100%;border-radius:6px;margin-bottom:6px">` : ''}
            ${d.headline ? `<div style="font-weight:600;font-size:13px;margin-bottom:4px">${esc(d.headline)}</div>` : ''}
            <div style="font-size:12px;color:#333;line-height:1.5">${esc(d.body)}</div>
            ${d.cta ? `<div style="background:#5bb2f5;color:#fff;border-radius:8px;padding:6px 12px;text-align:center;font-size:13px;margin-top:6px">${esc(d.cta)}</div>` : ''}
            <div style="font-size:10px;color:#999;text-align:right;margin-top:4px">now · ✓✓</div>
          </div>
        </div>`;
    }
    else if (activeChannel === 'email') {
        html = `<div style="border:1px solid #ddd;border-radius:8px;overflow:hidden;background:#fff;font-family:sans-serif">
          <div style="background:#f5f5f5;padding:8px 12px;font-size:11px;color:#666;border-bottom:1px solid #ddd;line-height:1.6">
            <strong>From:</strong> Your Business &lt;hello@yourbusiness.com&gt;<br>
            <strong>Subject:</strong> ${esc(d.headline || '(no subject)')}
          </div>
          <div style="padding:16px">
            ${d.media ? `<img src="${esc(d.media)}" style="width:100%;border-radius:6px;margin-bottom:12px">` : ''}
            ${d.headline ? `<h6 style="font-size:15px;font-weight:700;margin-bottom:8px">${esc(d.headline)}</h6>` : ''}
            <p style="font-size:13px;color:#555;line-height:1.6;margin-bottom:12px">${esc(d.body)}</p>
            ${d.cta ? `<a style="background:#0d6efd;color:#fff;border-radius:6px;padding:8px 20px;font-size:13px;font-weight:600;display:inline-block;text-decoration:none">${esc(d.cta)}</a>` : ''}
          </div>
        </div>`;
    }

    area.innerHTML = html;
}

renderPreview();
</script>
