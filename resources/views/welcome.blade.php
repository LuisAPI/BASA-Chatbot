@extends('layouts.app')

@section('title', 'Welcome')

@section('content')
<div class="container py-5">
    <div class="text-center mb-4">
        <h1 class="display-4">Welcome to {{ config('app.name', 'DEPDex') }}</h1>
        <p class="lead">{{ config('app.description', 'An AI-powered chatbot for DEPDev document assistance') }}</p>
    </div>
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">What is {{ config('app.name', 'DEPDex') }}?</h5>
                    <p class="card-text">{{ config('app.name', 'DEPDex') }} is an internal chatbot for {{ config('app.operator', 'DEPDev') }}, designed to provide fast, reliable, and contextually relevant answers about the agency's functions, services, and structure. Use the sidebar to access the chatbot or return to this home page at any time.</p>
                    <a href="/chatbot" class="btn btn-primary">Go to Chatbot</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@if (Route::has('login'))
    <div class="h-14.5 hidden lg:block"></div>
@endif