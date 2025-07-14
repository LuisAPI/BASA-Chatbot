@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-speedometer2 me-2"></i>Dashboard</h2>
            </div>
            
            <div class="row">
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="bi bi-robot text-primary" style="font-size: 2rem;"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h5 class="card-title mb-1">Chatbot</h5>
                                    <p class="card-text text-muted">Access the BASA chatbot</p>
                                    <a href="{{ route('chatbot') }}" class="btn btn-primary btn-sm">Go to Chatbot</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="bi bi-folder2-open text-primary" style="font-size: 2rem;"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h5 class="card-title mb-1">File Gallery</h5>
                                    <p class="card-text text-muted">Browse uploaded files</p>
                                    <a href="{{ route('chatbot.files') }}" class="btn btn-primary btn-sm">View Files</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                

            </div>
            
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Welcome, {{ Auth::user()->name }}!</h5>
                        </div>
                        <div class="card-body">
                            <p class="card-text">You're successfully logged into BASA (Bot for Automated Semantic Assistance). Use the sidebar navigation to access different features:</p>
                            <ul class="list-unstyled">
                                <li><i class="bi bi-robot text-primary me-2"></i><strong>Chatbot:</strong> Ask questions and get AI-powered responses</li>
                                <li><i class="bi bi-folder2-open text-primary me-2"></i><strong>File Gallery:</strong> Browse, search, and manage uploaded documents</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
