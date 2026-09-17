<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - IRIS</title>
    <link rel="stylesheet" href="{{ asset('assets/style.css') }}">
</head>
<body>
    <div class="auth-box">
        <h2>Create an IRIS Account</h2>

        @if(session('error'))
            <p class="error">{{ session('error') }}</p>
        @endif

        @if(session('success'))
            <p class="success">{{ session('success') }}</p>
        @endif

        @if($errors->any())
            <p class="error">{{ $errors->first() }}</p>
        @endif

        <form method="POST" action="{{ route('register.store') }}">
            @csrf
            <label>Username</label>
            <input type="text" name="username" value="{{ old('username') }}" required>

            <label>Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required>

            <label>Password</label>
            <input type="password" name="password" required>

            <label>Confirm Password</label>
            <input type="password" name="confirm_password" required>

            <button type="submit" class="btn btn-primary btn-block">Register</button>
        </form>

        <p>Already have an account? <a href="{{ route('login') }}">Log in</a></p>
    </div>
</body>
</html>
