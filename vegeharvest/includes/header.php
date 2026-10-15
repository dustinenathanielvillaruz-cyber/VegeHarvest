<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentPage = basename($_SERVER['PHP_SELF']);

$navItems = [
    'Dashboard'     => ['url' => 'index.php',       'icon' => 'fa-gauge'],
    'Crops'         => ['url' => 'crop.php',        'icon' => 'fa-seedling'],
    'Harvests'      => ['url' => 'harvest.php',     'icon' => 'fa-basket-shopping'],
    'Distributions' => ['url' => 'distribute.php',  'icon' => 'fa-truck-ramp-box'],
    'Puroks'        => ['url' => 'purok.php',       'icon' => 'fa-location-dot'],
    'Members'       => ['url' => 'volunteer.php',   'icon' => 'fa-users'],
    'Analytics'     => ['url' => 'analytics.php',   'icon' => 'fa-chart-line'],
    // NOTE: Transactions removed as system feature per requirement.
    // DB transactions remain as TCL code technique (beginTransaction/commit/rollBack/SAVEPOINT)
    // inside Harvest, Distribution, Volunteer, Register — see classes/*.php.
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VegeHarvest Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        #nav-indicator {
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            will-change: transform, width;
        }
    </style>
</head>
<body class="bg-[#0b1329] text-slate-100 min-h-screen flex flex-col font-sans">

    <!-- Header / Navigation Bar -->
    <header class="bg-[#141e38] border-b border-slate-800/80 sticky top-0 z-50 shadow-lg">
        <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
            
            <!-- Logo & Title -->
            <a href="index.php" class="flex items-center gap-3">
                <div class="p-2 bg-emerald-500/10 rounded-lg border border-emerald-500/30">
                    <i class="fa-solid fa-leaf text-emerald-400 text-xl"></i>
                </div>
                <div>
                    <span class="text-xl font-extrabold text-white tracking-wide block leading-none">VegeHarvest</span>
                    <span class="text-[10px] text-slate-400 uppercase tracking-wider">Community Yield & Distribution</span>
                </div>
            </a>

            <!-- Navigation Links -->
            <?php if (isset($_SESSION['user_id'])): ?>
                <nav id="nav-menu" class="hidden md:flex items-center gap-6 text-sm font-semibold relative py-1">
                    <?php foreach ($navItems as $label => $item): ?>
                        <?php 
                            $isActive = ($currentPage === $item['url']);
                            $activeClasses = $isActive 
                                ? 'text-emerald-400 font-bold active-nav' 
                                : 'text-slate-300 hover:text-emerald-400 transition-colors duration-200';
                        ?>
                        <a href="<?= $item['url']; ?>" class="nav-item flex items-center gap-2 pb-1 relative z-10 <?= $activeClasses; ?>">
                            <i class="fa-solid <?= $item['icon']; ?> text-xs"></i> <?= $label; ?>
                        </a>
                    <?php endforeach; ?>

                    <!-- Sliding Underline Indicator -->
                    <span id="nav-indicator" class="absolute bottom-0 left-0 h-[2.5px] bg-emerald-400 rounded-full pointer-events-none opacity-0"></span>
                </nav>

                <!-- User Profile & Logout -->
                <div class="flex items-center gap-4">
                    <div class="text-right hidden sm:block">
                        <p class="text-xs font-bold text-white"><?= htmlspecialchars($_SESSION['username'] ?? 'User'); ?></p>
                        <p class="text-[10px] text-emerald-400">Active Coordinator</p>
                    </div>
                    <a href="logout.php" class="bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/30 text-xs font-bold px-3 py-2 rounded-lg transition-colors flex items-center gap-2">
                        <i class="fa-solid fa-right-from-bracket"></i> Logout
                    </a>
                </div>
            <?php else: ?>
                <div>
                    <a href="login.php" class="bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs px-4 py-2 rounded-lg transition-colors">
                        Login
                    </a>
                </div>
            <?php endif; ?>

        </div>
    </header>

    <script>
      document.addEventListener('DOMContentLoaded', () => {
        const navMenu = document.getElementById('nav-menu');
        const indicator = document.getElementById('nav-indicator');
        if (!navMenu || !indicator) return;

        const navItems = navMenu.querySelectorAll('.nav-item');
        const activeItem = navMenu.querySelector('.active-nav');

        function setIndicator(element) {
          if (!element) return;
          const rect = element.getBoundingClientRect();
          const parentRect = navMenu.getBoundingClientRect();

          indicator.style.transform = `translateX(${rect.left - parentRect.left}px)`;
          indicator.style.width = `${rect.width}px`;
          indicator.style.opacity = '1';
        }

        const prevPos = sessionStorage.getItem('nav_prev_pos');

        if (prevPos && activeItem) {
          const { left, width } = JSON.parse(prevPos);
          const parentRect = navMenu.getBoundingClientRect();
          
          // Place indicator at the old position instantly
          indicator.style.transition = 'none';
          indicator.style.transform = `translateX(${left - parentRect.left}px)`;
          indicator.style.width = `${width}px`;
          indicator.style.opacity = '1';

          // Trigger smooth slide to the active item on page load
          requestAnimationFrame(() => {
            requestAnimationFrame(() => {
              indicator.style.transition = '';
              setIndicator(activeItem);
            });
          });

          sessionStorage.removeItem('nav_prev_pos');
        } else if (activeItem) {
          indicator.style.transition = 'none';
          setIndicator(activeItem);
          indicator.offsetHeight;
          indicator.style.transition = '';
        }

        navItems.forEach(item => {
          item.addEventListener('click', (e) => {
            const href = item.getAttribute('href');
            if (!href || href === '#' || item.classList.contains('active-nav')) return;

            e.preventDefault();

            // Save current position for next page
            const rect = item.getBoundingClientRect();
            sessionStorage.setItem('nav_prev_pos', JSON.stringify({
              left: rect.left,
              width: rect.width
            }));

            // Slide indicator immediately to clicked tab
            setIndicator(item);

            // Navigate after transition completes
            setTimeout(() => {
              window.location.href = href;
            }, 250);
          });
        });
      });
    </script>