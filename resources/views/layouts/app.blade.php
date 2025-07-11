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
    <div id="sidebarOverlay" class="sidebar-overlay"></div>
    <div class="container-fluid p-0">
        <div class="row g-0">
            @include('partials.sidebar')
            <div class="col-md-10 col p-0 d-flex flex-column min-vh-100">
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
        var toggleBtn = document.getElementById('sidebarToggleBtn');
        var overlay = document.getElementById('sidebarOverlay');
        if (!sidebar || typeof bootstrap === 'undefined') return;
        
        var bsCollapse = bootstrap.Collapse.getOrCreateInstance(sidebar, {toggle: false});
        
        function getBootstrapBreakpoint() {
            // Bootstrap 5 breakpoints
            if (window.innerWidth >= 1400) return 'xxl';
            if (window.innerWidth >= 1200) return 'xl';
            if (window.innerWidth >= 992) return 'lg';
            if (window.innerWidth >= 768) return 'md';
            if (window.innerWidth >= 576) return 'sm';
            return 'xs';
        }
        
        function updateOverlay(visible) {
            if (!overlay) return;
            if (visible) {
                overlay.classList.add('active');
            } else {
                overlay.classList.remove('active');
            }
        }
        
        function setSidebarState() {
            var breakpoint = getBootstrapBreakpoint();
            var shouldShowSidebar = false;
            var shouldShowToggle = false;
            
            // Define behavior for each breakpoint
            switch(breakpoint) {
                case 'xxl':
                case 'xl':
                case 'lg':
                    shouldShowSidebar = true;
                    shouldShowToggle = false;
                    break;
                case 'md':
                case 'sm':
                case 'xs':
                    shouldShowSidebar = false;
                    shouldShowToggle = true;
                    break;
            }
            
            // Apply sidebar state
            if (shouldShowSidebar) {
                bsCollapse.show();
            } else {
                bsCollapse.hide();
            }
            
            // Ensure toggle button visibility matches sidebar state
            if (toggleBtn) {
                if (shouldShowToggle) {
                    toggleBtn.classList.remove('d-none');
                    toggleBtn.classList.add('d-lg-none');
                } else {
                    toggleBtn.classList.add('d-none');
                    toggleBtn.classList.remove('d-lg-none');
                }
            }
        }
        
        // Listen for sidebar show/hide events to control overlay
        sidebar.addEventListener('show.bs.collapse', function() {
            var breakpoint = getBootstrapBreakpoint();
            // Only show overlay on small/medium screens
            if (breakpoint === 'md' || breakpoint === 'sm' || breakpoint === 'xs') {
                updateOverlay(true);
            }
        });
        
        sidebar.addEventListener('hide.bs.collapse', function() {
            updateOverlay(false);
        });
        
        // Initial setup
        setSidebarState();
        
        // Handle window resize with debouncing
        var resizeTimeout;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(function() {
                setSidebarState();
                // Also update overlay state based on current sidebar visibility
                var breakpoint = getBootstrapBreakpoint();
                if (breakpoint === 'md' || breakpoint === 'sm' || breakpoint === 'xs') {
                    if (sidebar.classList.contains('show')) {
                        updateOverlay(true);
                    } else {
                        updateOverlay(false);
                    }
                } else {
                    updateOverlay(false);
                }
            }, 100);
        });
        
        // Handle orientation changes
        window.addEventListener('orientationchange', function() {
            setTimeout(function() {
                setSidebarState();
                var breakpoint = getBootstrapBreakpoint();
                if (breakpoint === 'md' || breakpoint === 'sm' || breakpoint === 'xs') {
                    if (sidebar.classList.contains('show')) {
                        updateOverlay(true);
                    } else {
                        updateOverlay(false);
                    }
                } else {
                    updateOverlay(false);
                }
            }, 100);
        });
        
        // Handle manual toggle button clicks
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function() {
                // Let Bootstrap handle the toggle, overlay will be managed by show/hide events
            });
        }
        
        // Hide sidebar if overlay is clicked
        if (overlay) {
            overlay.addEventListener('click', function() {
                bsCollapse.hide();
            });
        }
    });
    </script>

</body>
</html>
