<?php
session_start();
require_once 'config/connection.php';

$error = '';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        try {
            // Selecting password_hash AS password to match database structure
            $stmt = $pdo->prepare("SELECT user_id AS id, username, password_hash AS password FROM users WHERE username = :username");
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                if (password_verify($password, $user['password']) || $password === $user['password']) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    
                    header("Location: index.php"); 
                    exit();
                } else {
                    $error = "Invalid password. Please try again.";
                }
            } else {
                $error = "Username not found. Please register first.";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    } else {
        $error = "Please fill in all required fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VegeHarvest - Login</title>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- FontAwesome CDN for Eye Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        .auth-card {
            transition: opacity 0.1s ease, transform 0.1s ease;
            opacity: 0;
            transform: scale(0.95) translateY(10px);
            animation: cardFadeIn 0.2s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        .auth-card.fade-out {
            opacity: 0 !important;
            transform: scale(0.95) translateY(-10px) !important;
        }

        @keyframes cardFadeIn {
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center bg-cover bg-center bg-no-repeat relative p-4" 
      style="background-image: url('bg.jpg');">

    <!-- Lighter Dark Overlay -->
    <div class="absolute inset-0 bg-slate-950/30"></div>

    <!-- Glassmorphism Login Card -->
    <div class="auth-card relative z-10 w-full max-w-md bg-[#141d2b]/60 backdrop-blur-md border border-white/10 rounded-2xl p-8 shadow-2xl" id="authCard">
        
        <!-- Brand Header -->
        <div class="text-center mb-6">
            <div class="flex items-center justify-center gap-2 mb-1">
                <!-- Sprout Icon -->
                <svg class="w-8 h-8 text-emerald-400" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2a10 10 0 0 0-10 10c0 5.52 4.48 10 10 10s10-4.48 10-10A10 10 0 0 0 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79 2.89 2.87 6.2 4.7 9.79 5.37-.8 2.37-1.8 3.51-3 4.35zm6.79-4.21c-3.11-.63-6.01-2.22-8.58-4.63 2.41-2.57 4-5.47 4.63-8.58 3.16 1.45 5.37 4.63 5.37 8.21 0 1.83-.55 3.53-1.42 5zm-3.21-9.21c-.49 2.5-1.78 4.86-3.71 6.91-2.05-1.93-4.41-3.22-6.91-3.71 1.25-2.68 3.86-4.57 6.91-4.71.49 0 .97.05 1.44.14z"/>
                </svg>
                <span class="text-2xl font-extrabold text-emerald-400 tracking-tight">VegeHarvest</span>
            </div>
            <p class="text-xs font-semibold tracking-wider text-slate-300 uppercase">Community Garden System</p>
        </div>

        <!-- Error Alert -->
        <?php if (!empty($error)): ?>
            <div class="bg-red-500/20 border border-red-500/40 text-red-300 text-xs rounded-lg p-3 mb-5 text-center font-medium backdrop-blur-sm">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Form -->
        <form action="login.php" method="POST" class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-slate-200 uppercase tracking-wider mb-2">Username</label>
                <input type="text" name="username" placeholder="Enter username or email" 
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" 
                       class="w-full bg-slate-900/60 border border-white/10 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-400 focus:outline-none focus:border-emerald-400 focus:ring-1 focus:ring-emerald-400 transition" required>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-200 uppercase tracking-wider mb-2">Password</label>
                <div class="relative">
                    <input type="password" id="passwordInput" name="password" placeholder="Enter password" 
                           class="w-full bg-slate-900/60 border border-white/10 rounded-xl pl-4 pr-11 py-3 text-sm text-white placeholder-slate-400 focus:outline-none focus:border-emerald-400 focus:ring-1 focus:ring-emerald-400 transition" required>
                    <button type="button" id="togglePassword" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-emerald-400 focus:outline-none transition-colors">
                        <i class="fa-solid fa-eye" id="toggleIcon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" 
                    class="w-full bg-emerald-500 hover:bg-emerald-600 active:bg-emerald-700 text-white font-semibold py-3 px-4 rounded-xl shadow-lg shadow-emerald-500/20 transition-all duration-150 mt-2">
                Log In
            </button>

            <p class="text-center text-xs text-slate-300 mt-4">
                Don't have an account? 
                <a href="register.php" class="text-emerald-400 hover:underline font-semibold transition-link">Register here</a>
            </p>
        </form>
    </div>

    <script>
        const authCard = document.getElementById('authCard');
        const passwordInput = document.getElementById('passwordInput');
        const togglePassword = document.getElementById('togglePassword');
        const toggleIcon = document.getElementById('toggleIcon');

        // Toggle Password Visibility
        togglePassword.addEventListener('click', function () {
            const isPassword = passwordInput.getAttribute('type') === 'password';
            passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
            
            // Toggle eye icon classes
            toggleIcon.classList.toggle('fa-eye', !isPassword);
            toggleIcon.classList.toggle('fa-eye-slash', isPassword);
        });

        // Fast 100ms fade transition to register.php
        document.querySelectorAll('.transition-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const targetUrl = this.href;
                authCard.classList.add('fade-out');
                setTimeout(() => {
                    window.location.href = targetUrl;
                }, 100);
            });
        });
    </script>
</body>
</html>