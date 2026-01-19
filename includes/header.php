<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include __DIR__ . "/db_conn.php";

// Common data for header
$isLoggedIn = isset($_SESSION['user_id']);
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MyBeachCare</title>
    
    <!-- TailwindCSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- FontAwesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">

    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Custom JS -->
    <script src="js/main.js" defer></script>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="style/style.css">

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
                    },
                    backgroundImage: {
                        'hero-pattern': "url('https://www.transparenttextures.com/patterns/cubes.png')",
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gradient-to-br from-cyan-50 via-emerald-50 to-white min-h-screen font-sans text-gray-900 selection:bg-primary selection:text-white">

    <!-- Global Navigation -->
    <!-- Global Navigation -->
    <nav class="sticky top-0 z-50 glass border-b border-gray-100 py-4">
        <div class="container mx-auto px-6 flex justify-between items-center">
            <a href="index.php" class="flex items-center space-x-2 group">
                <img src="img/logo.png" alt="My Beach Care Logo" class="h-8 w-auto group-hover:scale-110 transition-transform">
                <span class="font-outfit font-bold text-xl tracking-tight text-gray-900 border-l-2 border-primary/20 pl-3">MyBeachCare</span>
            </a>

            <!-- Desktop Navigation -->
            <div class="hidden md:flex items-center space-x-4">
                <a href="index.php" class="px-4 py-2 rounded-xl text-sm font-bold transition-all flex items-center gap-2 <?= ($currentPage == 'index.php') ? 'bg-blue-50 text-blue-600 border border-blue-200 shadow-sm' : 'text-gray-500 hover:text-blue-600 hover:bg-blue-50' ?>">
                    <i class="fa-solid fa-house"></i>Home
                </a>
                <a href="events.php" class="px-4 py-2 rounded-xl text-sm font-bold transition-all flex items-center gap-2 <?= ($currentPage == 'events.php') ? 'bg-blue-50 text-blue-600 border border-blue-200 shadow-sm' : 'text-gray-500 hover:text-blue-600 hover:bg-blue-50' ?>">
                    <i class="fa-solid fa-calendar-days"></i>Events
                </a>
                <?php if ($isLoggedIn): ?>
                    <a href="report.php" class="px-4 py-2 rounded-xl text-sm font-bold transition-all flex items-center gap-2 <?= ($currentPage == 'report.php') ? 'bg-blue-50 text-blue-600 border border-blue-200 shadow-sm' : 'text-gray-500 hover:text-blue-600 hover:bg-blue-50' ?>">
                        <i class="fa-solid fa-triangle-exclamation"></i>Reports
                    </a>
                <?php else: ?>
                    <button onclick="Swal.fire({icon: 'info', title: 'Login Required', text: 'Please log in to report an issue.', confirmButtonColor: '#3B82F6'})" class="px-4 py-2 rounded-xl text-sm font-bold text-gray-500 hover:text-blue-600 hover:bg-blue-50 transition-all flex items-center gap-2">
                        <i class="fa-solid fa-triangle-exclamation"></i>Reports
                    </button>
                <?php endif; ?>
                <a href="about.php" class="px-4 py-2 rounded-xl text-sm font-bold transition-all flex items-center gap-2 <?= ($currentPage == 'about.php') ? 'bg-blue-50 text-blue-600 border border-blue-200 shadow-sm' : 'text-gray-500 hover:text-blue-600 hover:bg-blue-50' ?>">
                    <i class="fa-solid fa-info-circle"></i>About
                </a>
                <a href="contact.php" class="px-4 py-2 rounded-xl text-sm font-bold transition-all flex items-center gap-2 <?= ($currentPage == 'contact.php') ? 'bg-blue-50 text-blue-600 border border-blue-200 shadow-sm' : 'text-gray-500 hover:text-blue-600 hover:bg-blue-50' ?>">
                    <i class="fa-solid fa-envelope"></i>Contact
                </a>
            </div>

            <div class="flex items-center space-x-6">
                <?php if ($isLoggedIn): ?>
                    <div class="flex items-center gap-6 ml-4">
                        <!-- User Profile Link -->
                        <a href="profile.php" class="flex items-center gap-3 group no-underline">
                            <?php if (!empty($_SESSION['profile_image'])): ?>
                                <img src="<?= htmlspecialchars($_SESSION['profile_image']) ?>" alt="Avatar" class="h-10 w-10 rounded-full object-cover border-2 border-white shadow-sm ring-2 ring-primary/10 transition-transform group-hover:scale-105">
                            <?php else: ?>
                                <div class="h-10 w-10 rounded-full bg-primary/10 flex items-center justify-center text-primary text-lg border-2 border-white shadow-sm ring-2 ring-primary/10 transition-transform group-hover:scale-105">
                                    <i class="fa-solid fa-user"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="hidden md:flex flex-col">
                                <span class="text-[10px] text-gray-400 font-medium uppercase tracking-wider leading-none mb-1">Hi,</span>
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-bold text-gray-900 leading-none group-hover:text-primary transition-colors">
                                        <?= htmlspecialchars($_SESSION['username'] ?? 'User') ?>
                                    </span>
                                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                                        <span class="text-[10px] font-bold text-purple-600 bg-purple-100 px-1.5 rounded uppercase tracking-wider">Admin</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>

                        <!-- Logout Button -->
                        <a href="logout.php" class="hidden md:flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 text-rose-600 rounded-full text-sm font-bold hover:bg-rose-50 hover:border-rose-200 transition-all shadow-sm">
                            <i class="fa-solid fa-arrow-right-from-bracket"></i>
                            <span>Logout</span>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="hidden md:flex items-center space-x-4">
                        <a href="login.php" class="text-sm font-semibold text-gray-600 hover:text-primary transition-colors">Sign In</a>
                        <a href="signup.php" class="px-5 py-2 text-sm font-bold text-white bg-primary hover:bg-black rounded-full transition-all shadow-md shadow-primary/20">Join Now</a>
                    </div>
                <?php endif; ?>
                
                <!-- Mobile Hamburger -->
                <button id="mobile-menu-button" class="md:hidden text-gray-600 focus:outline-none p-2 rounded-lg hover:bg-gray-100 transition-colors ml-2">
                    <i class="fas fa-bars text-xl" id="mobile-menu-icon"></i>
                </button>
            </div>
        </div>

        <!-- Mobile Menu Container -->
        <div id="mobile-menu" class="hidden md:hidden glass border-t border-gray-100 overflow-hidden transition-all duration-300 ease-in-out">
            <div class="container mx-auto px-6 py-6 space-y-4">
                <a href="index.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-base font-bold transition-all <?= ($currentPage == 'index.php') ? 'bg-blue-50 text-blue-600' : 'text-gray-700 hover:text-blue-600 hover:bg-blue-50' ?>">
                    <i class="fa-solid fa-house w-5"></i> Home
                </a>
                <a href="events.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-base font-bold transition-all <?= ($currentPage == 'events.php') ? 'bg-blue-50 text-blue-600' : 'text-gray-700 hover:text-blue-600 hover:bg-blue-50' ?>">
                    <i class="fa-solid fa-calendar-days w-5"></i> Join Events
                </a>
                <a href="about.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-base font-bold transition-all <?= ($currentPage == 'about.php') ? 'bg-blue-50 text-blue-600' : 'text-gray-700 hover:text-blue-600 hover:bg-blue-50' ?>">
                    <i class="fa-solid fa-info-circle w-5"></i> About Us
                </a>
                <a href="contact.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-base font-bold transition-all <?= ($currentPage == 'contact.php') ? 'bg-blue-50 text-blue-600' : 'text-gray-700 hover:text-blue-600 hover:bg-blue-50' ?>">
                    <i class="fa-solid fa-envelope w-5"></i> Contact Us
                </a>
                <?php if ($isLoggedIn): ?>
                    <a href="report.php" class="px-4 py-3 rounded-xl text-base font-bold transition-all flex items-center gap-3 <?= ($currentPage == 'report.php') ? 'bg-blue-50 text-blue-600' : 'text-gray-700 hover:text-blue-600 hover:bg-blue-50' ?>">
                        <i class="fa-solid fa-triangle-exclamation w-5"></i> Report Issues
                    </a>
                <?php else: ?>
                    <button onclick="Swal.fire({icon: 'info', title: 'Login Required', text: 'Please log in to report an issue.', confirmButtonColor: '#3B82F6'})" class="px-4 py-3 rounded-xl text-base font-bold text-gray-700 hover:text-blue-600 hover:bg-blue-50 transition-all w-full text-left flex items-center gap-3">
                        <i class="fa-solid fa-triangle-exclamation w-5"></i> Report Issues
                    </button>
                <?php endif; ?>

                <?php if ($isLoggedIn): ?>
                    <a href="profile.php" class="flex items-center gap-3 text-base font-semibold text-gray-700 hover:text-primary transition-colors">
                        <i class="fa-solid fa-id-card text-gray-400 w-5"></i> My Profile
                    </a>

                    <div class="h-px bg-gray-100 mx-0 my-2"></div>
                    <a href="logout.php" class="flex items-center gap-3 text-base font-semibold text-rose-600 hover:text-rose-700 transition-colors">
                        <i class="fa-solid fa-arrow-right-from-bracket w-5"></i> Sign Out
                    </a>
                <?php else: ?>
                    <div class="pt-4 flex flex-col gap-3">
                        <a href="login.php" class="w-full py-3 text-center text-sm font-semibold text-gray-700 bg-gray-100 rounded-xl hover:bg-gray-200 transition-all">Sign In</a>
                        <a href="signup.php" class="w-full py-3 text-center text-sm font-bold text-white bg-primary rounded-xl shadow-md shadow-primary/20 hover:bg-black transition-all">Join Now</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>
