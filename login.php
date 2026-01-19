<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
include "includes/db_conn.php";

// Handle Login Logic
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'login') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        header("Location: login.php?error=All fields are required");
        exit();
    }

    try {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if ($stmt->rowCount() == 1) {
            $user = $stmt->fetch();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['profile_image'] = $user['profile_image'];
                
                // Redirect based on role
                if ($user['role'] === 'admin') {
                    header("Location: admin/dashboard.php");
                } else {
                    header("Location: index.php");
                }
                exit();

            } else {
                header("Location: login.php?error=Incorrect password");
                exit();
            }
        } else {
            header("Location: login.php?error=Email not found");
            exit();
        }
    } catch (PDOException $e) {
        header("Location: login.php?error=Database error: " . $e->getMessage());
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - MyBeachCare</title>
    
    <!-- TailwindCSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;700;900&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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
                    }
                }
            }
        }
    </script>
    <style>
        .glass-panel {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .glass-input {
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }
        .glass-input:focus {
            background: #ffffff;
            box-shadow: 0 0 0 2px rgba(81, 45, 168, 0.2);
        }
    </style>
</head>
<body class="h-screen w-full relative overflow-hidden font-sans">

    <!-- Video Background -->
    <div class="absolute inset-0 z-0">
        <video autoplay muted loop class="w-full h-full object-cover">
            <source src="vid/main_video.mp4" type="video/mp4">
        </video>
        <!-- Gradient Overlay -->
        <div class="absolute inset-0 bg-gradient-to-r from-black/60 to-black/30"></div>
    </div>

    <!-- Main Content -->
    <div class="relative z-10 container mx-auto px-6 h-full flex items-center justify-center lg:justify-center lg:gap-32">
        
        <!-- Left Side: Branding (Visible on Desktop) -->
        <div class="hidden lg:block w-full max-w-lg text-white">
            <div class="flex items-center gap-3 mb-8 opacity-90">
                <img src="img/logo.png" alt="MyBeachCare Logo" class="h-12 w-auto">
                <span class="text-2xl font-outfit font-bold tracking-widest uppercase">MyBeachCare</span>
            </div>
            
            <h1 class="text-7xl font-outfit font-black leading-tight tracking-tighter mb-6">
                Protect<br>
                our shores
            </h1>
            
            <div class="h-1 w-24 bg-white/50 mb-8 rounded-full"></div>
            
            <p class="text-xl font-light text-white/90 max-w-lg leading-relaxed">
                Join our community that cares for the ocean in Malaysia. Together, we safeguard our shores for generations ahead.
            </p>
        </div>

        <!-- Right Side: Glass Login Form -->
        <div class="w-full max-w-md">
            <div class="glass-panel p-8 md:p-10 rounded-3xl shadow-2xl relative overflow-hidden">
                <!-- Subtle decorative element -->
                <div class="absolute -top-10 -right-10 w-32 h-32 bg-primary/30 rounded-full blur-3xl"></div>

                <div class="relative">
                    <h2 class="text-2xl font-bold text-white mb-2">Welcome Back</h2>
                    <p class="text-white/70 text-sm mb-8">Enter your details to access your account</p>

                    <form action="login.php" method="POST" class="space-y-5">
                        <input type="hidden" name="action" value="login">
                        
                        <div class="space-y-1">
                            <label for="email" class="text-xs font-medium text-white/90 ml-1">Email Address</label>
                            <input id="email" name="email" type="email" autocomplete="email" required 
                                class="glass-input block w-full px-4 py-3.5 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none transition-all duration-200" 
                                placeholder="Enter your email">
                        </div>

                        <div class="space-y-1">
                            <label for="password" class="text-xs font-medium text-white/90 ml-1">Password</label>
                            <div class="relative">
                                <input id="password" name="password" type="password" autocomplete="current-password" required 
                                    class="glass-input block w-full px-4 py-3.5 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none transition-all duration-200" 
                                    placeholder="••••••••">
                                <button type="button" onclick="togglePassword()" class="absolute inset-y-0 right-0 px-4 flex items-center text-gray-500 hover:text-primary transition-colors focus:outline-none">
                                    <i id="eye-icon" class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="flex justify-end pt-1">
                                <a href="forgot_password.php" class="text-xs font-medium text-white/80 hover:text-white transition-colors">Forgot password?</a>
                            </div>
                        </div>

                        <button type="submit" 
                            class="w-full py-3.5 px-4 bg-primary hover:bg-indigo-700 text-white font-bold rounded-xl shadow-lg shadow-primary/30 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-transparent focus:ring-primary transition-all transform hover:scale-[1.02] mt-2">
                            SIGN IN
                        </button>
                    </form>

                    <div class="mt-8"></div>
                    <p class="text-center text-sm text-white/80">
                        Don't have an account? 
                        <a href="signup.php" class="font-bold text-white hover:underline decoration-2 underline-offset-4 transition-all">Sign Up</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eye-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        }

        // SweetAlert2 Toast
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            background: '#fff',
            color: '#333',
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        });

        const urlParams = new URLSearchParams(window.location.search);
        const errorMsg = urlParams.get('error');
        const successMsg = urlParams.get('success');

        if (errorMsg) {
            Swal.fire({
                icon: 'error',
                title: 'Authentication Failed',
                text: errorMsg,
                confirmButtonColor: '#512DA8',
                background: 'rgba(255, 255, 255, 0.9)',
                backdrop: `rgba(0,0,0,0.4)`
            });
        }

        if (successMsg) {
             Toast.fire({
                icon: 'success',
                title: successMsg
            });
        }
    </script>
</body>
</html>
