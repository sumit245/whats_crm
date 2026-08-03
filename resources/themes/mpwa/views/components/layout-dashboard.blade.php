<!DOCTYPE html>
<html class="light-theme" lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ in_array(app()->getLocale(), ['ar', 'he', 'fa']) ? 'rtl' : 'ltr' }}">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script>
        (function () {
            var t = localStorage.getItem('dnd-theme');
            if (t !== 'dark' && t !== 'light') return;
            var root = document.documentElement;
            root.classList.remove('light-theme', 'dark-theme', 'semi-dark', 'minimal-theme');
            root.classList.add(t === 'dark' ? 'dark-theme' : 'light-theme');
        })();
    </script>
	<title>{{ html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8') }} | {{ config('config.site_name') }}</title>
    <link rel="icon" href="{{ asset('assets/images/favicon.png') }}" type="image/png" />
    <!--plugins-->
    <link href="{{ asset('assets/plugins/vectormap/jquery-jvectormap-2.0.2.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/plugins/simplebar/css/simplebar.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/plugins/perfect-scrollbar/css/perfect-scrollbar.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/plugins/metismenu/css/metisMenu.min.css') }}" rel="stylesheet" />
    <!-- Bootstrap CSS -->
    <link href="{{ asset('assets/css/bootstrap.' . (in_array(app()->getLocale(), ['ar', 'he', 'fa']) ? 'rtl' : 'ltr') . '.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/css/bootstrap-extended.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/css/style.' . (in_array(app()->getLocale(), ['ar', 'he', 'fa']) ? 'rtl' : 'ltr') . '.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/css/icons.css') }}" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
    <script src="{{ asset('assets/js/jquery.min.js') }}"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.min.css" />

    <!-- loader-->
    <link href="{{ asset('assets/css/pace.min.css') }}" rel="stylesheet" />

    <!--Theme Styles-->
    <link href="{{ asset('assets/css/dark-theme.' . (in_array(app()->getLocale(), ['ar', 'he', 'fa']) ? 'rtl' : 'ltr') . '.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/css/light-theme.' . (in_array(app()->getLocale(), ['ar', 'he', 'fa']) ? 'rtl' : 'ltr') . '.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/css/semi-dark.' . (in_array(app()->getLocale(), ['ar', 'he', 'fa']) ? 'rtl' : 'ltr') . '.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/css/header-colors.' . (in_array(app()->getLocale(), ['ar', 'he', 'fa']) ? 'rtl' : 'ltr') . '.css') }}" rel="stylesheet" />
    @php
        $dndTheme = env('THEME_NAME', 'mpwa');
        $dndCssVer = fn ($file) => @filemtime(public_path('themes/' . $dndTheme . '/css/' . $file)) ?: time();
    @endphp
    {{-- DnD design system v2 --}}
    <link href="{{ asset('css/dnd-tokens.css') }}?v={{ $dndCssVer('dnd-tokens.css') }}" rel="stylesheet" />
    <link href="{{ asset('css/dnd-components.css') }}?v={{ $dndCssVer('dnd-components.css') }}" rel="stylesheet" />
    <link href="{{ asset('css/dnd-forms.css') }}?v={{ $dndCssVer('dnd-forms.css') }}" rel="stylesheet" />
    <link href="{{ asset('css/dnd-buttons.css') }}?v={{ $dndCssVer('dnd-buttons.css') }}" rel="stylesheet" />
    <link href="{{ asset('css/dnd-pages.css') }}?v={{ $dndCssVer('dnd-pages.css') }}" rel="stylesheet" />
    <link href="{{ asset('css/dashboard.css') }}?v={{ $dndCssVer('dashboard.css') }}" rel="stylesheet" />
    <link href="{{ asset('css/dnd-chrome.css') }}?v={{ $dndCssVer('dnd-chrome.css') }}" rel="stylesheet" />
    {{-- csrf --}}
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <script>window.BASE_URL = '{{ request()->getBaseUrl() . '/' . app()->getLocale() }}';</script>
</head>

<body>
    {{-- <x-sidebar></x-sidebar> --}}
    <div class="wrapper">
        <x-header></x-header>
        <x-aside></x-aside>

        <!--start content-->
        <main class="page-content">
            <div class="dashboard-container">
                {{ $slot }}
            </div>
        </main>
        <!--end page main-->
		<footer class="footer">
        <div class="container-fluid"> 
            <div class="row">
                <div class="col-sm-6"> 
                    © {{config('config.footer_name')}}
                </div>
                <div class="col-sm-6">
                    <div class="text-sm-end d-none d-sm-block">
					    {!! config('config.footer_copyright') !!}
                    </div>
                </div>
            </div>
        </div>
		</footer>
    </div>


    <!-- Javascripts -->



    <!-- Bootstrap bundle JS -->
    <script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
    <!--plugins-->

    <script src="{{ asset('assets/plugins/simplebar/js/simplebar.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/metismenu/js/metisMenu.min.js') }}"></script>
    {{-- <script src="{{asset('assets/plugins/perfect-scrollbar/js/perfect-scrollbar.js')}}"></script> --}}
    <script src="{{ asset('assets/js/pace.min.js') }}"></script>
    <script src="{{ asset('assets/js/app.js') }}"></script>
    <script src="{{ asset('assets/plugins/smart-wizard/js/jquery.smartWizard.min.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
    <link href="{{ asset('assets/plugins/smart-wizard/css/smart_wizard_all.min.css') }}" rel="stylesheet"
        type="text/css" />
    {{-- <script>
    new PerfectScrollbar(".review-list")
    new PerfectScrollbar(".chat-talk")
 </script> --}}

    <script>
        toastr.options = {
            closeButton: false,
            debug: false,
            newestOnTop: false,
            progressBar: false,
            positionClass: "toast-top-right",
            preventDuplicates: false,
            onclick: null,
            showDuration: "300",
            hideDuration: "1000",
            timeOut: "5000",
            extendedTimeOut: "1000",
            showEasing: "swing",
            hideEasing: "linear",
            showMethod: "fadeIn",
            hideMethod: "fadeOut",
        };
    </script>
    {{-- Socket.io client (loaded globally; chat page uses it for real-time events) --}}
    <script src="https://cdn.socket.io/4.7.5/socket.io.min.js" crossorigin="anonymous"></script>

    {{-- Global flash messages via toastr --}}
    <script>
        @if(session('success'))  toastr.success('{{ addslashes(session('success')) }}'); @endif
        @if(session('error'))    toastr.error('{{ addslashes(session('error')) }}'); @endif
        @if(session('warning'))  toastr.warning('{{ addslashes(session('warning')) }}'); @endif
        @if(session('info'))     toastr.info('{{ addslashes(session('info')) }}'); @endif
        @if(session('status'))   toastr.success('{{ addslashes(session('status')) }}'); @endif
        @foreach($errors->all() as $e)
        toastr.error('{{ addslashes($e) }}');
        @endforeach
    </script>

    @stack('scripts')
</body>

</html>
