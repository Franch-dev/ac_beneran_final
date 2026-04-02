<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>
        try {
            document.documentElement.setAttribute('data-theme', localStorage.getItem('theme') || 'light');
        } catch (e) {
            document.documentElement.setAttribute('data-theme', 'light');
        }
    </script>
    <title>Login - Forkis Platform</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="login-body">
    <nav class="navbar glass-navbar" role="navigation" aria-label="Login navigation">
        <div class="navbar-brand">
            <div class="brand-icon"><i class="fas fa-snowflake"></i></div>
            <span class="brand-text">Forkis Platform</span>
        </div>
        <a href="{{ route('home') }}" class="btn btn-outline btn-sm">
            <i class="fas fa-arrow-left"></i> Beranda
        </a>
    </nav>
    <div class="login-container">
        <div class="login-card glass-card">
            <div class="login-header">
                <div class="login-logo">
                    <i class="fas fa-snowflake"></i>
                </div>
                <h1>AC Servis Masjid</h1>
                <p>Masuk ke sistem manajemen</p>
            </div>

            @if($errors->any())
                <div class="alert alert-danger">
                    {{ $errors->first() }}
                </div>
            @endif

            {{-- Path-only URL keeps POST on the current browser host (avoids 419 when APP_URL is another host). --}}
            <form action="{{ route('login.post', [], false) }}" method="POST" class="login-form">
                @csrf
                @if(!empty($redirectTo))
                    <input type="hidden" name="redirect" value="{{ $redirectTo }}">
                @endif
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-envelope"></i> Email
                    </label>
                    <input type="email" name="email" class="form-input"
                           value="{{ old('email') }}" placeholder="email@example.com" required autofocus>
                </div>
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <div class="input-group">
                        <input type="password" name="password" id="password" class="form-input"
                               placeholder="••••••••" required>
                        <button type="button" class="input-addon" onclick="togglePassword()">
                            <i class="fas fa-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember">
                        <span>Ingat saya</span>
                    </label>
                </div>
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-sign-in-alt"></i> Masuk
                </button>
            </form>

            <div class="login-footer">
                <a href="{{ route('home') }}"><i class="fas fa-arrow-left"></i> Kembali ke Beranda</a>
            </div>
        </div>
    </div>
    <script src="{{ asset('js/app.js') }}"></script>
    <script>
    function togglePassword() {
        const pwd = document.getElementById('password');
        const icon = document.getElementById('eyeIcon');
        if (pwd.type === 'password') {
            pwd.type = 'text';
            icon.className = 'fas fa-eye-slash';
        } else {
            pwd.type = 'password';
            icon.className = 'fas fa-eye';
        }
    }
    </script>
</body>
</html>
