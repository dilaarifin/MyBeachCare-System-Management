<?php
// Admin Header Component
// Include admin_auth.php before this file
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Admin Dashboard' ?> - MyBeachCare Admin</title>
    
    <!-- TailwindCSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- FontAwesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Outfit:wght@400;600;700;800&display=swap" rel="stylesheet">

    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        outfit: ['Outfit', 'sans-serif'],
                    },
                    colors: {
                        primary: '#512DA8',
                        secondary: '#5C6BC0',
                        accent: '#E0F2F1',
                    }
                }
            }
        }
    </script>
    <style>
        .sidebar-link {
            transition: all 0.3s ease;
        }
        .sidebar-link:hover {
            background: rgba(81, 45, 168, 0.1);
            border-left: 4px solid #512DA8;
        }
        .sidebar-link.active {
            background: rgba(81, 45, 168, 0.15);
            border-left: 4px solid #512DA8;
            font-weight: 600;
        }
        .glass {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-cyan-50 via-emerald-50 to-white min-h-screen font-sans">

    <!-- Mobile Menu Overlay -->
    <div id="mobile-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden lg:hidden"></div>

    <!-- Sidebar -->
    <aside id="sidebar" class="fixed left-0 top-0 h-full w-64 bg-white border-r border-gray-200 z-50 transform -translate-x-full lg:translate-x-0 transition-transform duration-300">
        <div class="flex flex-col h-full">
            <!-- Logo -->
            <div class="p-6 border-b border-gray-200">
                <a href="dashboard.php" class="flex items-center space-x-3">
                    <img src="../img/logo.png" alt="MyBeachCare Logo" class="w-10 h-10 object-contain">
                    <div>
                        <h1 class="font-outfit font-bold text-lg text-gray-900">MyBeachCare</h1>
                        <p class="text-xs text-gray-500 font-medium">Admin Panel</p>
                    </div>
                </a>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 overflow-y-auto py-6 px-3">
                <div class="space-y-1">
                    <a href="dashboard.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
                        <i class="fas fa-chart-line w-5 text-center"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="users.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 <?= basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : '' ?>">
                        <i class="fas fa-users w-5 text-center"></i>
                        <span>Users</span>
                    </a>
                    <a href="beaches.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 <?= basename($_SERVER['PHP_SELF']) == 'beaches.php' ? 'active' : '' ?>">
                        <i class="fas fa-umbrella-beach w-5 text-center"></i>
                        <span>Beaches</span>
                    </a>
                    <a href="events.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 <?= basename($_SERVER['PHP_SELF']) == 'events.php' ? 'active' : '' ?>">
                        <i class="fas fa-calendar-alt w-5 text-center"></i>
                        <span>Events</span>
                    </a>
                    <a href="reports.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 <?= basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : '' ?>">
                        <i class="fas fa-flag w-5 text-center"></i>
                        <span>Reports</span>
                    </a>
                    <a href="rewards.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 <?= basename($_SERVER['PHP_SELF']) == 'rewards.php' ? 'active' : '' ?>">
                        <i class="fas fa-gift w-5 text-center"></i>
                        <span>Rewards</span>
                    </a>
                </div>

                <div class="mt-8 pt-6 border-t border-gray-200">
                    <a href="../index.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
                        <i class="fas fa-globe w-5 text-center"></i>
                        <span>View Website</span>
                    </a>
                    <a href="../logout.php" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-lg text-rose-600 hover:bg-rose-50">
                        <i class="fas fa-sign-out-alt w-5 text-center"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </nav>

            <!-- Admin Info -->
            <div class="p-4 border-t border-gray-200 bg-gray-50">
                <div class="flex items-center space-x-3">
                    <?php if (!empty($admin_image)): ?>
                        <img src="../<?= htmlspecialchars($admin_image) ?>" alt="Profile" class="w-10 h-10 rounded-full object-cover border border-gray-200">
                    <?php else: ?>
                        <div class="w-10 h-10 bg-primary text-white rounded-full flex items-center justify-center font-bold">
                            <?= strtoupper(substr($admin_name, 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-900 truncate"><?= htmlspecialchars($admin_name) ?></p>
                        <p class="text-xs text-gray-500 truncate"><?= htmlspecialchars($admin_email) ?></p>
                    </div>
                </div>
            </div>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="lg:ml-64 min-h-screen">
        <!-- Top Header Bar -->
        <header class="bg-white border-b border-gray-200 sticky top-0 z-30">
            <div class="px-6 py-4 flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <!-- Mobile Menu Toggle -->
                    <button id="mobile-menu-btn" class="lg:hidden text-gray-600 hover:text-gray-900">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900"><?= $page_title ?? 'Dashboard' ?></h2>
                        <p class="text-sm text-gray-500"><?= $page_subtitle ?? 'Welcome to admin panel' ?></p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="hidden md:inline-block text-sm text-gray-600">
                        <i class="far fa-clock mr-2"></i>
                        <?= date('l, F j, Y') ?>
                    </span>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main class="p-6">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-lg flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <i class="fas fa-check-circle"></i>
                        <span><?= htmlspecialchars($_SESSION['success']) ?></span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-800">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="mb-6 bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-lg flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?= htmlspecialchars($_SESSION['error']) ?></span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-rose-600 hover:text-rose-800">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

    <script>
        // Mobile menu toggle
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const sidebar = document.getElementById('sidebar');
        const mobileOverlay = document.getElementById('mobile-overlay');

        mobileMenuBtn?.addEventListener('click', () => {
            sidebar.classList.toggle('-translate-x-full');
            mobileOverlay.classList.toggle('hidden');
        });

        mobileOverlay?.addEventListener('click', () => {
            sidebar.classList.add('-translate-x-full');
            mobileOverlay.classList.add('hidden');
        });
    </script>
