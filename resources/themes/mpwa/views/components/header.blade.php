 <header class="top-header">        
        <nav class="navbar navbar-expand gap-3 align-items-center">
          <div class="mobile-toggle-icon fs-3">
              <i class="bi bi-list"></i>
            </div>
            <form class="searchbar">
                <div class="position-absolute top-50 translate-middle-y search-icon ms-3"><i class="bi bi-search"></i></div>
                <input class="form-control" type="text" placeholder="{{__('Type here to search')}}">
                <div class="position-absolute top-50 translate-middle-y search-close-icon"><i class="bi bi-x-lg"></i></div>
            </form>
            <div class="top-navbar-right ms-auto">
              <ul class="navbar-nav align-items-center">
                <li class="nav-item search-toggle-icon">
                  <a class="nav-link" href="#">
                    <div class="">
                      <i class="bi bi-search"></i>
                    </div>
                  </a>
              </li>
			{{-- Template status notification bell (Phase A) --}}
			@php
				$unreadTemplateNotifs = \App\Models\TemplateStatusNotification::where('user_id', auth()->id())
					->whereNull('read_at')
					->latest()
					->limit(5)
					->get();
				$unreadCount = $unreadTemplateNotifs->count();
			@endphp
			<li class="nav-item dropdown">
				<a class="nav-link dnd-notif-bell" href="#" data-bs-toggle="dropdown" aria-expanded="false" title="{{ __('Template Notifications') }}">
					<i class="bi bi-bell fs-5"></i>
					@if($unreadCount > 0)
					<span class="badge rounded-pill bg-danger dnd-notif-badge">{{ $unreadCount }}</span>
					@endif
				</a>
				<ul class="dropdown-menu dropdown-menu-end dnd-notif-dropdown" style="min-width:320px; max-height:360px; overflow-y:auto;">
					<li><h6 class="dropdown-header">{{ __('Template Notifications') }}</h6></li>
					@forelse($unreadTemplateNotifs as $notif)
					<li>
						<a class="dropdown-item py-2 notif-item" href="{{ route('templates.index') }}" data-id="{{ $notif->id }}">
							<div class="d-flex align-items-start gap-2">
								@php
									$ic = match($notif->new_status) {
										'APPROVED' => ['text-success', 'bi-check-circle-fill'],
										'REJECTED' => ['text-danger',  'bi-x-circle-fill'],
										'PAUSED'   => ['text-warning', 'bi-pause-circle-fill'],
										default    => ['text-secondary', 'bi-info-circle-fill'],
									};
								@endphp
								@php
									$statusLabels = [
										'APPROVED'        => __('Approved'),
										'REJECTED'        => __('Rejected'),
										'PAUSED'          => __('Paused'),
										'PENDING'         => __('Pending review'),
										'PENDING_DELETION'=> __('Pending deletion'),
										'DELETED'         => __('Deleted'),
										'DISABLED'        => __('Disabled'),
										'IN_APPEAL'       => __('In appeal'),
										'LIMIT_EXCEEDED'  => __('Limit exceeded'),
									];
									$oldLabel  = $statusLabels[$notif->old_status]  ?? ucfirst(strtolower($notif->old_status ?? ''));
									$newLabel  = $statusLabels[$notif->new_status]  ?? ucfirst(strtolower($notif->new_status ?? ''));
									$rejection = $notif->rejection_reason && strtoupper($notif->rejection_reason) !== 'NONE'
										? $notif->rejection_reason : null;
									$statusMsg = match($notif->new_status) {
										'APPROVED' => __('Your template ":name" was approved.', ['name' => $notif->template_name]),
										'REJECTED' => __('Your template ":name" was rejected.', ['name' => $notif->template_name]),
										'PAUSED'   => __('Your template ":name" has been paused.', ['name' => $notif->template_name]),
										default    => __('Template ":name" status changed to :status.', ['name' => $notif->template_name, 'status' => $newLabel]),
									};
								@endphp
								<i class="bi {{ $ic[1] }} {{ $ic[0] }}" style="font-size:1.1rem;margin-top:2px;flex-shrink:0"></i>
								<div>
									<div class="fw-semibold" style="font-size:0.85rem">{{ $notif->template_name }}</div>
									<div class="{{ $ic[0] }}" style="font-size:0.8rem">{{ $statusMsg }}</div>
									@if($oldLabel)
									<div class="text-muted" style="font-size:0.75rem">{{ $oldLabel }} → {{ $newLabel }}</div>
									@endif
									@if($rejection)
									<div class="text-danger" style="font-size:0.75rem">{{ $rejection }}</div>
									@endif
									<div class="text-muted" style="font-size:0.75rem">{{ $notif->created_at->diffForHumans() }}</div>
								</div>
							</div>
						</a>
					</li>
					@empty
					<li><span class="dropdown-item text-muted">{{ __('No new notifications') }}</span></li>
					@endforelse
					@if($unreadCount > 0)
					<li><hr class="dropdown-divider"></li>
					<li>
						<a class="dropdown-item text-center text-primary small" href="#" id="mark-all-read">
							{{ __('Mark all as read') }}
						</a>
					</li>
					@endif
				</ul>
			</li>

			<li class="nav-item">
				<div class="dnd-theme-toggle" role="group" aria-label="{{ __('Theme') }}">
					<button type="button" class="dnd-theme-btn" id="dnd-theme-light" title="{{ __('Light mode') }}" aria-pressed="false">
						<i class="bi bi-sun"></i>
					</button>
					<button type="button" class="dnd-theme-btn" id="dnd-theme-dark" title="{{ __('Dark mode') }}" aria-pressed="false">
						<i class="bi bi-moon-stars"></i>
					</button>
				</div>
			</li>

			<li class="nav-item dropdown">
				<a class="nav-link dropdown-toggle" href="#" id="languageDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false" style="font-size: 15px;">
					<i class="bi bi-globe"></i>
					{{ __('Language') }}
				</a>
				<ul class="dropdown-menu" aria-labelledby="languageDropdown">
					@foreach(LaravelLocalization::getSupportedLocales() as $localeCode => $properties)
						<li>
							<a class="dropdown-item" rel="alternate" hreflang="{{ $localeCode }}" href="{{ LaravelLocalization::getLocalizedURL($localeCode, null, [], true) }}">
								<span class="flag-icon flag-icon-{{ strtolower($localeCode) }}"></span>
								{{ $properties['native'] }}
							</a>
						</li>
					@endforeach
				</ul>
			</li>
              <li class="nav-item dropdown dropdown-user-setting">
                <a class="nav-link dropdown-toggle dropdown-toggle-nocaret" href="#" data-bs-toggle="dropdown">
                  <div class="user-setting d-flex align-items-center">
                    <img src="{{asset('assets/images/avatars/avatar-1.png')}}" class="user-img" alt="">
                  </div>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                  <li>
                     <a class="dropdown-item" href="#">
                       <div class="d-flex align-items-center">
                          <img src="{{asset('assets/images/avatars/avatar-1.png')}}" alt="" class="rounded-circle" width="54" height="54">
                          <div class="ms-3">
                            <h6 class="mb-0 dropdown-user-name">{{Auth::user()->username}}</h6>
                            <small class="mb-0 dropdown-user-designation text-secondary">{{__(Auth::user()->level)}}</small>
                          </div>
                       </div>
                     </a>
                   </li>
				   @if(env("ENABLE_INDEX") == 'yes')
					<li><hr class="dropdown-divider"></li>
					<li>
                      <a class="dropdown-item" href="{{route('index')}}">
                         <div class="d-flex align-items-center">
                           <div class=""><i class="bi bi-house-fill"></i></div>
                           <div class="ms-3"><span>{{__('Home')}}</span></div>
                         </div>
                       </a>
                    </li>
					@endif
                   <li><hr class="dropdown-divider"></li>
                   
                    <li>
                      <a class="dropdown-item" href="{{route('user.settings')}}">
                         <div class="d-flex align-items-center">
                           <div class=""><i class="bi bi-gear-fill"></i></div>
                           <div class="ms-3"><span>{{__('Setting')}}</span></div>
                         </div>
                       </a>
                    </li>
                    
                    <li><hr class="dropdown-divider"></li>
                    <li>
                      <form action="{{route('logout')}}" method="post" >
                        @csrf
                      <button class="dropdown-item" type="submit">
                         <div class="d-flex align-items-center">
                           <div class=""><i class="bi bi-lock-fill"></i></div>
                           <div class="ms-3"><span>{{__('Logout')}}</span></div>
                         </div>
                       </button>
                      </form>
                    </li>
                </ul>
              </li>
              </ul>
              </div>
        </nav>
</header>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Theme toggle (light / dark)
    function dndSetTheme(theme) {
        if (typeof window.dndApplyTheme === 'function') {
            window.dndApplyTheme(theme);
        } else {
            var isDark = theme === 'dark';
            var root = document.documentElement;
            root.classList.remove('light-theme', 'dark-theme', 'semi-dark', 'minimal-theme');
            root.classList.add(isDark ? 'dark-theme' : 'light-theme');
            localStorage.setItem('dnd-theme', theme);
        }
        var isDark = theme === 'dark';
        var lightBtn = document.getElementById('dnd-theme-light');
        var darkBtn = document.getElementById('dnd-theme-dark');
        if (lightBtn && darkBtn) {
            lightBtn.classList.toggle('is-active', !isDark);
            darkBtn.classList.toggle('is-active', isDark);
            lightBtn.setAttribute('aria-pressed', String(!isDark));
            darkBtn.setAttribute('aria-pressed', String(isDark));
        }
    }
    var savedTheme = localStorage.getItem('dnd-theme');
    if (savedTheme !== 'dark' && savedTheme !== 'light') {
        savedTheme = document.documentElement.classList.contains('dark-theme') ? 'dark' : 'light';
    }
    dndSetTheme(savedTheme);
    document.getElementById('dnd-theme-light')?.addEventListener('click', function () { dndSetTheme('light'); });
    document.getElementById('dnd-theme-dark')?.addEventListener('click', function () { dndSetTheme('dark'); });

    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content;

    function postJson(url) {
        return fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' } });
    }

    function updateBadge(count) {
        const badge = document.querySelector('.dnd-notif-badge');
        if (!badge) return;
        if (count <= 0) { badge.remove(); } else { badge.textContent = count; }
    }

    // Mark individual notifications read on click
    document.querySelectorAll('.notif-item').forEach(function (el) {
        el.addEventListener('click', function () {
            const url = '{{ rtrim(route("notifications.template.read", "__ID__"), "/") }}'.replace('__ID__', this.dataset.id);
            postJson(url).then(() => {
                const li = this.closest('li');
                if (li) li.remove();
                const remaining = document.querySelectorAll('.notif-item').length;
                updateBadge(remaining);
                if (remaining === 0) {
                    const markAll = document.getElementById('mark-all-read')?.closest('li');
                    if (markAll) markAll.previousElementSibling?.remove(), markAll.remove();
                    const list = document.querySelector('.dnd-notif-dropdown');
                    if (list) {
                        const empty = document.createElement('li');
                        empty.innerHTML = '<span class="dropdown-item text-muted">{{ __("No new notifications") }}</span>';
                        list.querySelector('li:last-child')?.after(empty);
                    }
                }
            });
        });
    });

    // Mark all read
    const markAllBtn = document.getElementById('mark-all-read');
    if (markAllBtn) {
        markAllBtn.addEventListener('click', function (e) {
            e.preventDefault();
            postJson('{{ route("notifications.template.read.all") }}').then(() => {
                document.querySelectorAll('.notif-item').forEach(el => el.closest('li')?.remove());
                updateBadge(0);
                const hr = markAllBtn.closest('li')?.previousElementSibling;
                if (hr) hr.remove();
                markAllBtn.closest('li')?.remove();
                const list = document.querySelector('.dnd-notif-dropdown');
                if (list) {
                    const empty = document.createElement('li');
                    empty.innerHTML = '<span class="dropdown-item text-muted">{{ __("No new notifications") }}</span>';
                    const header = list.querySelector('.dropdown-header')?.closest('li');
                    if (header) header.after(empty);
                }
            });
        });
    }
});
</script>