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
				<a class="nav-link position-relative" href="#" data-bs-toggle="dropdown" aria-expanded="false" title="{{ __('Template Notifications') }}">
					<i class="bi bi-bell fs-5"></i>
					@if($unreadCount > 0)
					<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:0.6rem">
						{{ $unreadCount }}
					</span>
					@endif
				</a>
				<ul class="dropdown-menu dropdown-menu-end" style="min-width:320px; max-height:360px; overflow-y:auto;">
					<li><h6 class="dropdown-header">{{ __('Template Notifications') }}</h6></li>
					@forelse($unreadTemplateNotifs as $notif)
					<li>
						<a class="dropdown-item py-2 notif-item" href="{{ route('templates.index') }}" data-id="{{ $notif->id }}">
							<div class="d-flex align-items-start gap-2">
								@php
									$ic = match($notif->new_status) {
										'APPROVED' => ['text-success', 'check_circle'],
										'REJECTED' => ['text-danger',  'cancel'],
										'PAUSED'   => ['text-warning', 'pause_circle'],
										default    => ['text-secondary','info'],
									};
								@endphp
								<i class="material-icons {{ $ic[0] }}" style="font-size:18px;margin-top:2px">{{ $ic[1] }}</i>
								<div>
									<div class="fw-semibold" style="font-size:0.85rem">{{ $notif->template_name }}</div>
									<div class="text-muted" style="font-size:0.8rem">
										{{ $notif->old_status ?? '—' }} → {{ $notif->new_status }}
										@if($notif->rejection_reason)
											&nbsp;· {{ $notif->rejection_reason }}
										@endif
									</div>
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
    // Mark individual notifications read on click
    document.querySelectorAll('.notif-item').forEach(function (el) {
        el.addEventListener('click', function () {
            fetch('/notifications/template/' + this.dataset.id + '/read', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
            });
        });
    });

    // Mark all read
    const markAllBtn = document.getElementById('mark-all-read');
    if (markAllBtn) {
        markAllBtn.addEventListener('click', function (e) {
            e.preventDefault();
            fetch('/notifications/template/read-all', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
            }).then(() => location.reload());
        });
    }
});
</script>