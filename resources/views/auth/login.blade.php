<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - IRIS</title>
    <link rel="stylesheet" href="{{ asset('assets/style.css') }}">
</head>
<body>
    <div class="auth-box">
        <h2>IRIS Login</h2>

        @if(session('error'))
            <p class="error">{{ session('error') }}</p>
        @endif

        @if($errors->any())
            <p class="error">{{ $errors->first() }}</p>
        @endif

        <form method="POST" action="{{ route('login.store') }}">
            @csrf
            <label>Username</label>
            <input type="text" name="username" value="{{ old('username') }}" required>

            <label>Password</label>
            <input type="password" name="password" required>

            <button type="submit" class="btn btn-primary btn-block">Log In</button>
        </form>

        <p>No account? <a href="{{ route('register') }}">Register here</a></p>
    </div>
</body>
</html>
