<?php
require_once __DIR__.'/../includes/functions.php';

if (!empty($_SESSION['user_id'])) {
    redirect_to((strtolower((string)($_SESSION['role'] ?? 'user')) === 'admin') ? 'admin/dashboard.php' : 'user/dashboard.php');
}

$error = flash('error');
$success = flash('success');
clear_old();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $login = trim($_POST['username'] ?? '');
    $pw = (string)($_POST['password'] ?? '');

    if ($login === '' || $pw === '') {
        set_old(['username' => $login]);
        flash_redirect('auth/login.php', 'error', 'Please enter your username/email and password.');
    }

    if (str_contains($login, '@') && !preg_match('/^[A-Za-z0-9._%+-]+@clsu2\.edu\.ph$/i', $login)) {
        set_old(['username' => $login]);
        flash_redirect('auth/login.php', 'error', 'Use your username or a valid CLSU2 email like name@clsu2.edu.ph.');
    }

    try {
        // Allow administrators and users to sign in with either username or CLSU email.
        $q = db()->prepare('SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1');
        $q->execute([$login, $login]);
        $user = $q->fetch();
    } catch (Throwable $e) {
        set_old(['username' => $login]);
        flash_redirect('auth/login.php', 'error', 'Unable to connect to the IRIS database. Check XAMPP MySQL and config/db.php.');
    }

    $validPassword = false;
    $needsRehash = false;

    if ($user) {
        $stored = (string)($user->password ?? '');
        $validPassword = password_verify($pw, $stored);

        // Compatibility with older local IRIS databases that stored passwords
        // before the current password_hash() implementation was introduced.
        if (!$validPassword && $stored !== '' && hash_equals($stored, $pw)) {
            $validPassword = true;
            $needsRehash = true;
        }
    }

    if (!$user || !$validPassword) {
        set_old(['username' => $login]);
        flash_redirect('auth/login.php', 'error', 'Invalid username/email or password.');
    }

    // Upgrade a legacy password to a secure password_hash() value after login.
    if ($needsRehash) {
        try {
            $update = db()->prepare('UPDATE users SET password = ? WHERE id = ?');
            $update->execute([password_hash($pw, PASSWORD_DEFAULT), $user->id]);
        } catch (Throwable $e) {
            // Login can still continue if only the password upgrade fails.
        }
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = $user->id;
    $_SESSION['username'] = $user->username;
    $_SESSION['role'] = strtolower(trim((string)$user->role));

    $destination = $_SESSION['role'] === 'admin' ? 'admin/dashboard.php' : 'user/dashboard.php';
    flash_redirect($destination, 'success', 'Welcome back, '.$user->username.'!');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - IRIS Institutional Observatory</title>
    <script>
        if (localStorage.getItem('iris-theme') === 'dark' ||
            (!localStorage.getItem('iris-theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
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
                            900: '#053a2c',
                            950: '#04261d',
                            gold: '#f59e0b'
                        }
                    }
                }
            }
        }
    </script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .bg-dots {
            background-image: radial-gradient(rgba(255,255,255,0.08) 1px, transparent 1px);
            background-size: 18px 18px;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 bg-gradient-to-br from-brand-950 via-brand-900 to-brand-800 bg-dots">

    <div class="w-full max-w-4xl grid md:grid-cols-2 rounded-3xl overflow-hidden shadow-2xl border border-white/10">

        <!-- Left brand panel -->
        <div class="hidden md:flex flex-col justify-between bg-gradient-to-br from-brand-600 to-brand-800 dark:from-brand-900 dark:to-brand-950 p-10 text-white relative overflow-hidden transition-colors">
            <div class="absolute inset-0 bg-dots opacity-40 pointer-events-none"></div>
            <div class="relative">
               <div class="inline-flex w-40 h-40 rounded-2xl items-center justify-center p-1 mb-8 shadow-lg bg-white">
               <img src="<?= e(base_url('images/iris-logo.png')) ?>" alt="IRIS Logo" class="w-full h-full object-contain">
            </div>
            </div>
            <div class="relative space-y-3">
                <h2 class="text-3xl font-extrabold leading-tight">A clearer view of<br>institutional performance.</h2>
                <p class="text-sm text-emerald-50/90 max-w-xs">CLSU Performance Observatory for the Office of International Affairs.</p>
            </div>
            <div class="relative text-xs text-emerald-100/70 pt-8">
                International Affairs Office &middot; Central Luzon State University
            </div>
        </div>

        <!-- Right form panel -->
        <div class="bg-white dark:bg-slate-900 p-8 sm:p-10 flex flex-col justify-center relative transition-colors">
            <button id="themeToggle" type="button" class="absolute top-6 right-6 w-9 h-9 rounded-full flex items-center justify-center bg-gray-100 dark:bg-slate-800 text-gray-600 dark:text-amber-300 hover:bg-gray-200 dark:hover:bg-slate-700 transition-colors">
                <i class="fa-solid fa-sun text-sm hidden dark:inline"></i>
                <i class="fa-solid fa-moon text-sm dark:hidden"></i>
            </button>

            <p class="text-xs font-bold uppercase tracking-widest text-emerald-600 dark:text-emerald-400 mb-2">IRIS Login</p>
            <h1 class="text-2xl font-black text-gray-900 dark:text-white mb-1">Welcome back</h1>
            <p class="text-sm text-gray-500 dark:text-slate-400 mb-6">Sign in to continue to your observatory.</p>

            <?php if ($error): ?>
                <div class="flex items-center p-3.5 mb-4 text-xs text-red-800 rounded-xl bg-red-50 dark:bg-red-500/10 dark:text-red-400 border border-red-200 dark:border-red-500/30" role="alert">
                    <i class="fa-solid fa-circle-exclamation text-base mr-2"></i>
                    <div class="font-medium"><?= htmlspecialchars($error) ?></div>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="flex items-center p-3.5 mb-4 text-xs text-emerald-800 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/30" role="alert">
                    <i class="fa-solid fa-circle-check text-base mr-2"></i>
                    <div class="font-medium"><?= htmlspecialchars($success) ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <?= csrf_field() ?>
                <div>
                    <label for="username" class="block mb-2 text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-slate-300">Username</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-gray-400 dark:text-slate-500">
                            <i class="fa-solid fa-user text-xs"></i>
                        </div>
                        <input type="text" id="username" name="username" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-emerald-500 focus:border-emerald-500 block w-full pl-10 p-2.5 dark:bg-slate-800 dark:border-slate-700 dark:placeholder-slate-500 dark:text-white transition-colors" placeholder="faculty_user" required autofocus>
                    </div>
                </div>

                <div>
                    <div class="flex justify-between items-center mb-2">
                        <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-slate-300">Password</label>
                        <button type="button" id="togglePassword" class="text-xs text-emerald-600 dark:text-emerald-400 hover:underline">Show</button>
                    </div>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-gray-400 dark:text-slate-500">
                            <i class="fa-solid fa-lock text-xs"></i>
                        </div>
                        <input type="password" id="password" name="password" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-emerald-500 focus:border-emerald-500 block w-full pl-10 p-2.5 dark:bg-slate-800 dark:border-slate-700 dark:placeholder-slate-500 dark:text-white transition-colors" placeholder="••••••••" required>
                    </div>
                </div>

                <button type="submit" class="w-full text-white bg-emerald-600 hover:bg-emerald-500 focus:ring-4 focus:ring-emerald-300 dark:focus:ring-emerald-800 font-bold rounded-xl text-sm px-5 py-3 text-center shadow-md shadow-emerald-600/20 transition-all">
                    <i class="fa-solid fa-right-to-bracket mr-2"></i> Log In
                </button>
            </form>

            <p class="text-xs text-center text-gray-500 dark:text-slate-400 mt-6">
                No account? <a href="<?= e(base_url('auth/register.php')) ?>" class="font-semibold text-emerald-600 dark:text-emerald-400 hover:underline">Register here</a>
            </p>
        </div>
    </div>

    <script>
        const themeToggle = document.getElementById('themeToggle');
        themeToggle.addEventListener('click', () => {
            document.documentElement.classList.toggle('dark');
            localStorage.setItem('iris-theme', document.documentElement.classList.contains('dark') ? 'dark' : 'light');
        });

        const toggleBtn = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        toggleBtn.addEventListener('click', () => {
            const isPassword = passwordInput.type === 'password';
            passwordInput.type = isPassword ? 'text' : 'password';
            toggleBtn.textContent = isPassword ? 'Hide' : 'Show';
        });
    </script>
</body>
</html>