@php
    $authUser = request()->user();
    $isAgentUser = $authUser->isAgent();
    if ($isAgentUser) {
        $agentOwner = $authUser->agent()->with('user')->first()?->user ?? null;
        $numbers = $agentOwner ? $agentOwner->devices()->latest()->paginate(15) : collect();
    } else {
        $numbers = $authUser->devices()->latest()->paginate(15);
    }
@endphp
       <!--start sidebar -->
       <aside class="sidebar-wrapper" data-simplebar="true">
           <div class="sidebar-header">
               <div>
                   <img src="{{ asset('assets/images/logo-icon.png') }}" class="logo-icon" alt="logo icon">
               </div>
               <div>
                   <h4 class="logo-text">{{config('config.header_side')}}</h4>
               </div>
               <div class="toggle-icon ms-auto"> <i class="bi bi-list"></i>
               </div>
           </div>
           <!--navigation-->
           <ul class="metismenu" id="menu">
		    @if(env("ENABLE_INDEX") == 'yes')
               <li>
                   <a href="{{ route('index') }}">
                       <div class="parent-icon"><i class="bi bi-house-fill"></i>
                       </div>
                       <div class="menu-title">{{__('Home')}}</div>
                   </a>

               </li>
			@endif
               {{-- dashboard — owner only --}}
               @if(!$isAgentUser)
               <li class="{{ request()->is('home') ? 'active' : '' }}">
                   <a href="{{ route('home') }}">
                       <div class="parent-icon"><i class="bi bi-ui-radios-grid"></i></div>
                       <div class="menu-title">{{__('Dashboard')}}</div>
                   </a>
               </li>
               @endif
               {{-- chat (+ chat settings nested, owner only) --}}
               @if($isAgentUser)
               <li class="{{ request()->is('chat*') ? 'active' : '' }}">
                   <a href="{{ route('chat.index') }}">
                       <div class="parent-icon"><i class="bi bi-chat-dots-fill"></i></div>
                       <div class="menu-title">{{__('Chat')}}</div>
                   </a>
               </li>
               @else
               <li class="{{ request()->is('chat*') ? 'active' : '' }}">
                   <a href="javascript:;" class="has-arrow">
                       <div class="parent-icon"><i class="bi bi-chat-dots-fill"></i></div>
                       <div class="menu-title">{{__('Chat')}}</div>
                   </a>
                   <ul>
                       <li class="{{ request()->is('chat/settings*') ? '' : 'active' }}">
                           <a href="{{ route('chat.index') }}"><i class="bi bi-circle"></i>{{__('Inbox')}}</a>
                       </li>
                       <li class="{{ request()->is('chat/settings*') ? 'active' : '' }}">
                           <a href="{{ route('chat.settings') }}"><i class="bi bi-circle"></i>{{__('Chat Settings')}}</a>
                       </li>
                   </ul>
               </li>
               @endif
               @if(!$isAgentUser)
               {{-- chatbot flows --}}
               <li class="{{ request()->is('flows*') ? 'active' : '' }}">
                   <a href="{{ route('flows.index') }}">
                       <div class="parent-icon"><i class="bi bi-diagram-3-fill"></i></div>
                       <div class="menu-title">{{__('Chatbot Flows')}}</div>
                   </a>
               </li>
               {{-- agents & teams --}}
               <li class="{{ request()->is('agents*') ? 'active' : '' }}">
                   <a href="{{ route('agents.index') }}">
                       <div class="parent-icon"><i class="bi bi-people-fill"></i></div>
                       <div class="menu-title">{{__('Agents & Teams')}}</div>
                   </a>
               </li>
               {{-- file manager --}}
               <li class="{{ request()->is('file-manager') ? 'active' : '' }}">
                   <a href="{{ route('file-manager') }}">
                       <div class="parent-icon"><i class="bi bi-file-earmark-fill"></i>
                       </div>
                       <div class="menu-title">{{__('File Manager')}}</div>
                   </a>

               </li>
               {{-- contacts directory (unified hub — also covers phonebook/import deep links) --}}
               <li class="{{ request()->is('contacts') || request()->is('contacts/*') || request()->is('phonebook') ? 'active' : '' }}">
                   <a href="{{ route('contacts.directory') }}">
                       <div class="parent-icon"><i class="bi bi-people-fill"></i></div>
                       <div class="menu-title">{{__('Contacts')}}</div>
                   </a>
               </li>
               {{-- Templates --}}
               <li class="{{ request()->is('templates*') ? 'active' : '' }}">
                   <a href="{{ route('templates.index') }}">
                       <div class="parent-icon"><i class="bi bi-layout-text-sidebar-reverse"></i>
                       </div>
                       <div class="menu-title">{{__('Templates')}}</div>
                   </a>
               </li>
               {{-- reports --}}
               <li>
                   <a href="javascript:;" class="has-arrow">
                       <div class="parent-icon">
                           <i class="bi bi-file-earmark-bar-graph-fill"></i>
                       </div>
                       <div class="menu-title">{{__('Reports')}}</div>
                   </a>
                   <ul>
                       <li class="{{ request()->is('campaigns') ? 'active' : '' }}">
                           <a href="{{ route('campaigns') }}"><i class="bi bi-circle"></i>{{__('Campaign / Blast')}}</a>
                       </li>
                       <li class="{{ request()->is('messages.history') ? 'active' : '' }}">
                           <a href="{{ route('messages.history') }}"><i class="bi bi-circle"></i>{{__('Messages History')}}</a>
                       </li>
                       <li class="{{ request()->is('analytics*') ? 'active' : '' }}">
                           <a href="{{ route('analytics.index') }}"><i class="bi bi-circle"></i>{{__('Analytics')}}</a>
                       </li>
                       <li class="{{ request()->is('calendar*') ? 'active' : '' }}">
                           <a href="{{ route('calendar.index') }}"><i class="bi bi-circle"></i>{{__('Campaign Calendar')}}</a>
                       </li>
                       <li class="{{ request()->is('ab-tests*') ? 'active' : '' }}">
                           <a href="{{ route('ab.index') }}"><i class="bi bi-circle"></i>{{__('A/B Tests')}}</a>
                       </li>
                       <li class="{{ request()->is('campaigns/compare*') ? 'active' : '' }}">
                           <a href="{{ route('campaigns.compare') }}"><i class="bi bi-circle"></i>{{__('Campaign Comparison')}}</a>
                       </li>
                   </ul>
               </li>
               {{-- Ads Manager --}}
               <li class="{{ request()->is('*/ads*') ? 'active' : '' }}">
                   <a href="javascript:;" class="has-arrow">
                       <div class="parent-icon"><i class="bi bi-megaphone-fill"></i></div>
                       <div class="menu-title">{{__('Ads Manager')}}</div>
                   </a>
                   <ul>
                       <li class="{{ request()->is('*/ads') ? 'active' : '' }}">
                           <a href="{{ route('ads.dashboard') }}"><i class="bi bi-circle"></i>{{__('Dashboard')}}</a>
                       </li>
                       <li class="{{ request()->is('*/ads/campaigns*') ? 'active' : '' }}">
                           <a href="{{ route('ads.campaigns.index') }}"><i class="bi bi-circle"></i>{{__('Campaigns')}}</a>
                       </li>
                       <li class="{{ request()->is('*/ads/creatives*') ? 'active' : '' }}">
                           <a href="{{ route('ads.creatives.index') }}"><i class="bi bi-circle"></i>{{__('Creatives')}}</a>
                       </li>
                       <li class="{{ request()->is('*/ads/channels*') ? 'active' : '' }}">
                           <a href="{{ route('ads.channels.index') }}"><i class="bi bi-circle"></i>{{__('Channels')}}</a>
                       </li>
                       <li class="{{ request()->is('*/ads/analytics*') ? 'active' : '' }}">
                           <a href="{{ route('ads.analytics') }}"><i class="bi bi-circle"></i>{{__('Ad Analytics')}}</a>
                       </li>
                       <li class="{{ request()->is('*/ads/audiences*') ? 'active' : '' }}">
                           <a href="{{ route('ads.audiences.index') }}"><i class="bi bi-circle"></i>{{__('Audiences')}}</a>
                       </li>
                       <li class="{{ request()->is('*/ads/ab-tests*') ? 'active' : '' }}">
                           <a href="{{ route('ads.ab-tests.index') }}"><i class="bi bi-circle"></i>{{__('A/B Tests')}}</a>
                       </li>
                   </ul>
               </li>
               {{-- API Health --}}
               <li class="{{ request()->is('meta/health*') ? 'active' : '' }}">
                   <a href="{{ route('meta.health') }}">
                       <div class="parent-icon"><i class="bi bi-activity"></i>
                       </div>
                       <div class="menu-title">{{__('API Health')}}</div>
                   </a>
               </li>
               @endif {{-- end owner-only --}}
			@if (Auth::user()->level != 'admin' && !$isAgentUser)
			   <li class="{{ request()->is('user.tickets') ? 'active' : '' }}">
                   <a href="{{ route('user.tickets.index') }}">
					   <div class="parent-icon">
							<i class="bi bi-patch-question-fill"></i>
                       </div>
                       <div class="menu-title">{{__('Tickets')}}</div>
                   </a>
               </li>
			@endif
               @if(!$isAgentUser)
               <x-select-device :numbers="$numbers"></x-select-device>

               {{-- these menus only show if exists selected devices --}}
               @if (Session::has('selectedDevice'))
                   <li class="{{ request()->is('autoreply') ? 'active' : '' }}">
                       <a href="{{ route('autoreply') }}">
                           <div class="parent-icon"><i class="bi bi-chat-left-dots-fill"></i>
                           </div>
                           <div class="menu-title">{{__('Auto Reply')}}</div>
                       </a>
                   </li>
                   <li class="{{ request()->is('quick-replies') ? 'active' : '' }}">
                       <a href="{{ route('quick-replies.index') }}">
                           <div class="parent-icon"><i class="bi bi-lightning-fill"></i></div>
                           <div class="menu-title">{{__('Quick Replies')}}</div>
                       </a>
                   </li>
                   {{-- Create campaign --}}
                   <li class=" {{ url()->current() == route('campaign.create') ? 'mm-active' : '' }}">
                       <a class="" href="{{ route('campaign.create') }}">
                           <div class="parent-icon"><i class="bi bi-plus-circle-fill"></i>
                           </div>
                           <div class="menu-title">{{__('Create Campaign')}}</div>
                       </a>
                   </li>
                   {{-- Segments --}}
                   <li class="{{ request()->is('segments*') ? 'active' : '' }}">
                       <a href="{{ route('segments.index') }}">
                           <div class="parent-icon"><i class="bi bi-funnel-fill"></i></div>
                           <div class="menu-title">{{__('Segments')}}</div>
                       </a>
                   </li>
                   {{-- Suppression List --}}
                   <li class="{{ request()->is('suppression*') ? 'active' : '' }}">
                       <a href="{{ route('suppression.index') }}">
                           <div class="parent-icon"><i class="bi bi-slash-circle-fill"></i></div>
                           <div class="menu-title">{{__('Suppression List')}}</div>
                       </a>
                   </li>
                   {{-- Opt-in / Opt-out --}}
                   <li class="{{ request()->is('opt-in*') ? 'active' : '' }}">
                       <a href="{{ route('optin.show') }}">
                           <div class="parent-icon"><i class="bi bi-toggle-on"></i></div>
                           <div class="menu-title">{{__('Opt-in / Opt-out')}}</div>
                       </a>
                   </li>
                   {{-- Drip Sequences --}}
                   <li class="{{ request()->is('drip*') ? 'active' : '' }}">
                       <a href="{{ route('drip.index') }}">
                           <div class="parent-icon"><i class="bi bi-send-check-fill"></i></div>
                           <div class="menu-title">{{__('Drip Sequences')}}</div>
                       </a>
                   </li>
                   {{-- Message Test --}}
                   <li class=" {{ url()->current() == route('messagetest') ? 'mm-active' : '' }}">
                       <a class="" href="{{ route('messagetest') }}">
                           <div class="parent-icon"><i class="bi bi-chat-left-dots-fill"></i>
                           </div>
                           <div class="menu-title">{{__('Test Message')}}</div>
                       </a>
                   </li>
               @endif
               @endif {{-- end owner-only (device-dependent) --}}

               {{-- Api Documentation --}}
               @if(!$isAgentUser)
               <li class=" {{ url()->current() == route('rest-api') ? 'mm-active' : '' }}">
                   <a class="" href="{{ route('rest-api') }}">
                       <div class="parent-icon"><i class="bi bi-code-square"></i>
                       </div>
                       <div class="menu-title">{{__('API Docs')}}</div>
                   </a>
               </li>
               {{-- Integrations --}}
               <li class="{{ request()->is('integrations*') ? 'active' : '' }}">
                   <a href="{{ route('integrations.index') }}">
                       <div class="parent-icon"><i class="bi bi-plug-fill"></i></div>
                       <div class="menu-title">{{__('Integrations')}}</div>
                   </a>
               </li>
               {{-- Catalogue --}}
               <li class="{{ request()->is('catalogue*') ? 'active' : '' }}">
                   <a href="{{ route('catalogue.index') }}">
                       <div class="parent-icon"><i class="bi bi-shop"></i></div>
                       <div class="menu-title">{{__('Catalogue')}}</div>
                   </a>
               </li>
               {{-- Webhooks --}}
               <li class="{{ request()->is('*/webhooks*') ? 'active' : '' }}">
                   <a href="{{ route('webhooks.index') }}">
                       <div class="parent-icon"><i class="bi bi-arrow-down-square-fill"></i></div>
                       <div class="menu-title">{{__('Webhooks')}}</div>
                   </a>
               </li>
               {{-- Labels --}}
               <li class="{{ request()->is('chat/labels*') ? 'active' : '' }}">
                   <a href="{{ route('chat.labels.index') }}">
                       <div class="parent-icon"><i class="bi bi-tags"></i></div>
                       <div class="menu-title">{{__('Labels')}}</div>
                   </a>
               </li>
               {{-- WA Link Generator --}}
               <li class="{{ request()->is('wa-link*') ? 'active' : '' }}">
                   <a href="{{ route('wa-link.index') }}">
                       <div class="parent-icon"><i class="bi bi-qr-code"></i></div>
                       <div class="menu-title">{{__('Link Generator')}}</div>
                   </a>
               </li>
               @endif

               {{-- menus for admin --}}
               @if (Auth::user()->level == 'admin')
                   <li>
                       <a href="javascript:;" class="has-arrow">
                           <div class="parent-icon">
                               {{-- admin icon --}}
                               <i class="bi bi-person-lines-fill"></i>
                           </div>
                           <div class="menu-title">{{__('Admin')}}</div>
                       </a>
                       <ul>
                           <li class="{{ request()->is('admin.settings') ? 'active' : '' }}">
                               <a href="{{ route('admin.settings') }}"><i class="bi bi-circle"></i>{{__('Setting Server')}}</a>
                           </li>
                           <li class="{{ request()->is('admin.manage-users') ? 'active' : '' }}">
                               <a href="{{ route('admin.manage-users') }}">
                                   <i class="bi bi-circle"></i>
                                   {{__('Manage User')}}</a>
                           </li><li class="{{ request()->is('languages.index') ? 'active' : '' }}">
                               <a href="{{ route('languages.index') }}">
                                   <i class="bi bi-circle"></i>
                                   {{__('Manage Languages')}}</a>
                           </li>
						   <li class="{{ request()->is('admin.index.edit') ? 'active' : '' }}">
                               <a href="{{ route('admin.index.edit') }}">
                                   <i class="bi bi-circle"></i>
                                   {{__('Manage Homepage')}}</a>
                           </li>
						   <li class="{{ request()->is('admin.plans.index') ? 'active' : '' }}">
                               <a href="{{ route('admin.plans.index') }}">
                                   <i class="bi bi-circle"></i>
                                   {{__('Manage Plans')}}</a>
                           </li>
						   <li class="{{ request()->is('admin.payments.index') ? 'active' : '' }}">
                               <a href="{{ route('admin.payments.index') }}">
                                   <i class="bi bi-circle"></i>
                                   {{__('Manage Payments')}}</a>
                           </li>
						   <li class="{{ request()->is('admin.tickets') ? 'active' : '' }}">
                               <a href="{{ route('admin.tickets.index') }}">
                                   <i class="bi bi-circle"></i>
                                   {{__('Manage Tickets')}}</a>
                           </li>
						   <li class="{{ request()->is('admin.orders.index') ? 'active' : '' }}">
                               <a href="{{ route('admin.orders.index') }}">
                                   <i class="bi bi-circle"></i>
                                   {{__('Orders')}}</a>
                           </li>
						   <li class="{{ request()->is('cronjob') ? 'active' : '' }}">
                               <a href="{{ route('cronjob') }}">
                                   <i class="bi bi-circle"></i>
                                   {{__('Cronjob')}}</a>
                           </li>
	
                       </ul>
                   </li>
               @endif


               {{-- <li class="menu-label">UI Elements</li> --}}



           </ul>
           <!--end navigation-->
       </aside>
       <!--end sidebar -->
