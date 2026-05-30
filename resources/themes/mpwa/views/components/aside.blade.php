@php
    $numbers = request()->user()->devices()->latest()->paginate(15);
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
               {{-- dashboard --}}
               <li class="{{ request()->is('home') ? 'active' : '' }}">
                   <a href="{{ route('home') }}">
                       <div class="parent-icon"><i class="bi bi-ui-radios-grid"></i></div>
                       <div class="menu-title">{{__('Dashboard')}}</div>
                   </a>
               </li>
               {{-- chat --}}
               <li class="{{ request()->is('chat*') ? 'active' : '' }}">
                   <a href="{{ route('chat.index') }}">
                       <div class="parent-icon"><i class="bi bi-chat-dots-fill"></i></div>
                       <div class="menu-title">{{__('Chat')}}</div>
                   </a>
               </li>
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
               {{-- phone book --}}
               <li class="{{ request()->is('phonebook') || request()->is('contacts/import') ? 'active' : '' }}">
                   <a href="{{ route('phonebook') }}">
                       <div class="parent-icon"><i class="bi bi-telephone-fill"></i>
                       </div>
                       <div class="menu-title">{{__('Phone Book')}}</div>
                   </a>
               </li>
               {{-- Contact Import --}}
               <li class="{{ request()->is('contacts/import*') ? 'active' : '' }}">
                   <a href="{{ route('contacts.import') }}">
                       <div class="parent-icon"><i class="bi bi-file-earmark-spreadsheet-fill"></i>
                       </div>
                       <div class="menu-title">{{__('Import Contacts')}}</div>
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
                           {{-- histories icon --}}
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
			@if (Auth::user()->level != 'admin')
			   <li class="{{ request()->is('user.tickets') ? 'active' : '' }}">
                   <a href="{{ route('user.tickets.index') }}">
					   <div class="parent-icon">
							<i class="bi bi-patch-question-fill"></i>
                       </div>
                       <div class="menu-title">{{__('Tickets')}}</div>
                   </a>
               </li>
			@endif
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
                   {{-- Create campaign --}}
                   <li class=" {{ url()->current() == route('campaign.create') ? 'mm-active' : '' }}">
                       <a class="" href="{{ route('campaign.create') }}">
                           <div class="parent-icon"><i class="bi bi-plus-circle-fill"></i>
                           </div>
                           <div class="menu-title">{{__('Create Campaign')}}</div>
                       </a>
                   </li>
                   {{-- end create campaign --}}
                   {{-- Message Test --}}
                   <li class=" {{ url()->current() == route('messagetest') ? 'mm-active' : '' }}">
                       <a class="" href="{{ route('messagetest') }}">
                           <div class="parent-icon"><i class="bi bi-chat-left-dots-fill"></i>
                           </div>
                           <div class="menu-title">{{__('Test Message')}}</div>
                       </a>
                   </li>
                   {{-- Message Test --}}
               @endif

               {{-- Api Documentation --}}

               <li class=" {{ url()->current() == route('rest-api') ? 'mm-active' : '' }}">
                   <a class="" href="{{ route('rest-api') }}">
                       <div class="parent-icon"><i class="bi bi-code-square"></i>
                       </div>
                       <div class="menu-title">{{__('API Docs')}}</div>
                   </a>
               </li>
               {{-- end api documentation --}}

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
