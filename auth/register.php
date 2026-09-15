<?php
require_once __DIR__ . '/../includes/functions.php';

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = clean($_POST['username']);
    $email    = clean($_POST['email']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];

    if ($password !== $confirm) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } else {
        // Check for existing username/email
        $check = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username' OR email = '$email'");
        if (mysqli_num_rows($check) > 0) {
            $error = "Username or email is already registered.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (username, email, password, role) VALUES ('$username', '$email', '$hashed', 'user')";
            if (mysqli_query($conn, $sql)) {
                $success = "Account created successfully! You can now log in.";
            } else {
                $error = "Registration failed: " . mysqli_error($conn);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - IRIS Observatory</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#ecfdf5',
                            100: '#d1fae5',
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                            800: '#065f46',
                            900: '#064e3b',
                            gold: '#f59e0b'
                        }
                    }
                }
            }
        }
    </script>
    <!-- Flowbite CSS & JS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.js"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-md bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-xl p-8 space-y-6">
        
        <!-- Header -->
        <div class="text-center space-y-2">
            <div class="inline-flex w-14 h-14 rounded-2xl bg-gradient-to-tr from-emerald-600 to-teal-400 items-center justify-center text-white text-2xl shadow-lg shadow-emerald-500/20 ring-4 ring-emerald-400/20 mb-2">
                <i class="fa-solid fa-graduation-cap"></i>
            </div>
            <h1 class="text-2xl font-black tracking-tight text-gray-900 dark:text-white">Create IRIS Account</h1>
            <p class="text-xs text-gray-500 dark:text-gray-400">Join the Central Luzon State University Performance Observatory</p>
        </div>

        <?php if ($error): ?>
            <div class="flex items-center p-3.5 text-xs text-red-800 rounded-xl bg-red-50 dark:bg-gray-700 dark:text-red-400 border border-red-200 dark:border-red-800" role="alert">
                <i class="fa-solid fa-circle-exclamation text-base mr-2"></i>
                <div class="font-medium"><?= htmlspecialchars($error) ?></div>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="flex items-center p-3.5 text-xs text-emerald-800 rounded-xl bg-emerald-50 dark:bg-gray-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800" role="alert">
                <i class="fa-solid fa-circle-check text-base mr-2"></i>
                <div class="font-medium"><?= htmlspecialchars($success) ?></div>
            </div>
        <?php endif; ?>

        <!-- Form -->
        <form method="POST" class="space-y-4">
            <div>
                <label for="username" class="block mb-1.5 text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">Username</label>
                <input type="text" id="username" name="username" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-emerald-500 focus:border-emerald-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" placeholder="faculty_user" required autofocus>
            </div>

            <div>
                <label for="email" class="block mb-1.5 text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">Institutional Email</label>
                <input type="email" id="email" name="email" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-emerald-500 focus:border-emerald-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" placeholder="name@clsu.edu.ph" required>
            </div>

            <div>
                <label for="password" class="block mb-1.5 text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">Password</label>
                <input type="password" id="password" name="password" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-emerald-500 focus:border-emerald-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" placeholder="At least 8 characters" minlength="8" required>
            </div>

            <div>
                <label for="confirm_password" class="block mb-1.5 text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-emerald-500 focus:border-emerald-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" placeholder="Re-enter your password" required>
            </div>

            <button type="submit" class="w-full text-white bg-emerald-600 hover:bg-emerald-500 focus:ring-4 focus:ring-emerald-300 font-bold rounded-xl text-sm px-5 py-3 text-center dark:focus:ring-emerald-800 shadow-md transition-all mt-2">
                <i class="fa-solid fa-user-plus mr-2"></i> Register Account
            </button>
        </form>

        <!-- Footer link -->
        <p class="text-xs text-center text-gray-500 dark:text-gray-400">
            Already have an account? <a href="login.php" class="font-semibold text-emerald-600 dark:text-emerald-400 hover:underline">Log in</a>
        </p>
    </div>

</body>
</html>
