<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') — DEPDev BASA — Department of Economy, Planning, and Development</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <link rel="icon" type="image/png" href="{{ asset('icons/chatbot-logo-icon.png') }}">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#0e4384">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    @yield('head')
</head>
<body>
    <div class="container-fluid p-0">
        <div class="row g-0">
            @include('partials.sidebar')
            <div class="col p-0 d-flex flex-column min-vh-100">
                <header>
                    @include('partials.header')
                </header>
                <main class="flex-grow-1" style="background: transparent;">
                    @yield('content')
                </main>
            </div>
        </div>
    </div>
    @yield('scripts')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var sidebar = document.getElementById('sidebarCollapse');
        if (!sidebar || typeof bootstrap === 'undefined') return;
        var bsCollapse = bootstrap.Collapse.getOrCreateInstance(sidebar, {toggle: false});
        function isPortrait() {
            return window.innerHeight > window.innerWidth;
        }
        function setSidebarState() {
            if (window.innerWidth < 768 || isPortrait()) {
                bsCollapse.hide();
            } else {
                bsCollapse.show();
            }
        }
        setSidebarState();
        window.addEventListener('resize', setSidebarState);
    });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/pusher-js@7.2/dist/web/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo/dist/echo.iife.js"></script>
    <script>
        const Echo = window.Echo.default;

        window.Pusher = Pusher;

        window.Echo = new Echo({
            broadcaster: 'pusher',
            key: 'local',
            wsHost: window.location.hostname,
            wsPort: 6001,
            forceTLS: false,
            disableStats: true,
            enabledTransports: ['ws'],
        });

        Echo.connector.ws.onopen = () => {
            console.log('✔️ Echo connected to Reverb WebSocket server');
        };
    </script>
</body>
</html>
