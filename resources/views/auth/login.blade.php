<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    
    <link rel="stylesheet" href="{{ asset('assets/css/sidebar-menu.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/simplebar.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/google-icon.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/remixicon.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    
    <link rel="icon" type="image/png" href="{{ app_favicon() }}">
    <title>Login - {{ app_name() }}</title>
    
    <style>
        .authentication-card {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .login-form-card {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 480px;
            width: 100%;
        }
        .login-logo {
            font-size: 48px;
            color: #667eea;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="authentication-card">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-5 col-md-6">
                    <div class="login-form-card">
                        <div class="text-center mb-4">
                            @if(app_logo())
                                <img src="{{ app_logo() }}" alt="{{ app_name() }}" class="mb-3" style="max-height: 80px; max-width: 200px;">
                            @else
                                <span class="material-symbols-outlined login-logo">directions_car</span>
                            @endif
                            <h3 class="fw-bold mb-2">{{ app_name() }}</h3>
                            <p class="text-muted mb-0">Sign in to continue to dashboard</p>
                        </div>

                        @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show mb-4">
                            <span class="material-symbols-outlined me-2" style="vertical-align: middle;">error</span>
                            {{ $errors->first() }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        @endif

                        <form method="POST" action="{{ route('login') }}">
                            @csrf
                            
                            <div class="mb-3">
                                <label for="email" class="form-label fw-semibold">Email Address</label>
                                <div class="position-relative">
                                    <input type="email" 
                                           class="form-control ps-5 @error('email') is-invalid @enderror" 
                                           id="email" 
                                           name="email" 
                                           value="{{ old('email') }}" 
                                           placeholder="Enter your email"
                                           required 
                                           autofocus>
                                    <span class="material-symbols-outlined position-absolute top-50 start-0 translate-middle-y ms-3" style="color: #A9A9C8;">mail</span>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label fw-semibold">Password</label>
                                <div class="position-relative">
                                    <input type="password" 
                                           class="form-control ps-5 @error('password') is-invalid @enderror" 
                                           id="password" 
                                           name="password" 
                                           placeholder="Enter your password"
                                           required>
                                    <span class="material-symbols-outlined position-absolute top-50 start-0 translate-middle-y ms-3" style="color: #A9A9C8;">lock</span>
                                </div>
                            </div>

                            <div class="mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="remember" name="remember">
                                    <label class="form-check-label" for="remember">
                                        Remember me
                                    </label>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 py-3 fw-semibold">
                                <span class="material-symbols-outlined me-2" style="vertical-align: middle;">login</span>
                                Sign In
                            </button>
                        </form>

                        <div class="text-center mt-4">
                            <p class="text-muted mb-0">
                                <small>&copy; {{ date('Y') }} {{ app_name() }}</small>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
</body>
</html>
