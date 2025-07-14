<nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom position-relative">
    <div class="d-flex align-items-center ps-3 flex-grow-1" style="height: 100%;">
        <button class="btn btn-primary d-md-none" type="button" id="sidebarToggleBtn" aria-label="Toggle sidebar">
            <i class="bi bi-list"></i>
        </button>
        <a class="navbar-brand fw-bold text-primary d-flex align-items-center gap-2" href="/">
            <img src="{{ asset('icons/chatbot-logo-icon.png') }}" alt="BASA Icon" style="height: 28px; width: 28px;">
            BASA
        </a>
        <span class="mx-2 text-secondary">|</span>
        <span class="navbar-text">Bot for Automated Semantic Assistance</span>
    </div>
    
    <!-- Authentication Section + Bagong Pilipinas Logo -->
    <div class="d-flex align-items-center gap-3 me-3">
        <img src="{{ asset('icons/Bagong-Pilipinas-Logo.png') }}" alt="Bagong Pilipinas Logo" style="height:2.5rem; width:auto; display:block;">
        @auth
            <div class="dropdown">
                <button class="btn btn-outline-primary dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person-circle me-1"></i>
                    {{ Auth::user()->name }}
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                    <li><a class="dropdown-item" href="{{ route('profile.edit') }}">
                        <i class="bi bi-person me-2"></i>Profile
                    </a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}" class="d-inline">
                            @csrf
                            <button type="submit" class="dropdown-item">
                                <i class="bi bi-box-arrow-right me-2"></i>Log Out
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        @else
            <div class="d-flex gap-2">
                <a href="{{ route('login') }}" class="btn btn-outline-primary btn-sm">Log in</a>
                <a href="{{ route('register') }}" class="btn btn-primary btn-sm">Register</a>
            </div>
        @endauth
    </div>
</nav>
