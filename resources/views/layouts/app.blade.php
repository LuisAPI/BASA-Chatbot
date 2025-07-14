<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', config('app.name', 'Laravel'))</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Bootstrap Icons -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        
        @yield('head')
    </head>
    <body>
        <!-- Sidebar backdrop overlay for mobile -->
        <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
        
        <div class="container-fluid">
            <div class="row">
                <!-- Sidebar -->
                @include('partials.sidebar')
                
                <!-- Main Content Area -->
                <div class="col-12 col-md-10 col-lg-10 px-0">
                    <!-- Header -->
                    @include('partials.header')
                    
                    <!-- Page Content -->
                    <main class="px-4 py-3">
                        @yield('content')
                    </main>
                </div>
            </div>
        </div>
        
        @yield('scripts')
    </body>
</html>
