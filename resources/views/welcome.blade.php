@extends('layouts.app')

@section('title', 'Welcome')

@section('content')
<div class="container py-5">
    <div class="text-center mb-4">
        <h1 class="display-4">Welcome to BASA</h1>
        <p class="lead">Bot for Automated Semantic Assistance<br>Department of Economy, Planning, and Development</p>
    </div>
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">What is BASA?</h5>
                    <p class="card-text">BASA is an internal chatbot for DEPDev, designed to provide fast, reliable, and contextually relevant answers about the agency's functions, services, and structure. Use the sidebar to access the chatbot or return to this home page at any time.</p>
                    @auth
                        <a href="{{ route('chatbot') }}" class="btn btn-primary">Go to Chatbot</a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-primary">Login to Access Chatbot</a>
                    @endauth
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@if (Route::has('login'))
    <div class="h-14.5 hidden lg:block"></div>
@endif