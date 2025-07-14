<aside id="sidebarCollapse" class="col-md-2 col-lg-2 bg-primary sidebar border-end min-vh-100 mt-0 d-md-block">
    <div class="bg-white d-flex align-items-center justify-content-center py-3 border-bottom">
        <a href="https://depdev.gov.ph" target="_blank" rel="noopener" class="d-block">
            <img src="{{ asset('icons/login-logo-1.png') }}" alt="DEPDev Logo" style="height:8rem; width:auto; display:block;">
        </a>
    </div>
    <ul class="nav flex-column py-3" style="height: calc(100vh - 10rem); overflow:auto;">
        <li class="nav-item">
            <a class="nav-link d-flex align-items-center" href="/">
                <i class="bi bi-house-door text-white me-2"></i>Home
            </a>
        </li>
        @auth
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center" href="{{ route('chatbot') }}">
                    <i class="bi bi-robot text-white me-2"></i>Chatbot
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center" href="{{ route('chatbot.files') }}">
                    <i class="bi bi-folder2-open text-white me-2"></i>Gallery
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center" href="{{ route('dashboard') }}">
                    <i class="bi bi-speedometer2 text-white me-2"></i>Dashboard
                </a>
            </li>
        @else
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center" href="{{ route('login') }}">
                    <i class="bi bi-box-arrow-in-right text-white me-2"></i>Login
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center" href="{{ route('register') }}">
                    <i class="bi bi-person-plus text-white me-2"></i>Register
                </a>
            </li>
        @endauth
    </ul>
</aside>
