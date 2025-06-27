<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') — BASA by DEPDev — Department of Economy, Planning, and Development</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    @yield('head')
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <header class="col-12 p-0">
                @include('partials.header')
            </header>
        </div>
        <div class="row">
            @include('partials.sidebar')
            <main class="col p-0 min-vh-100" style="background: transparent;">
                @yield('content')
            </main>
        </div>
    </div>
    @yield('scripts')
</body>
</html>
