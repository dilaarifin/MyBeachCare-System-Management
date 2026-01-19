<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
include "includes/db_conn.php";

// Handle Signup Logic
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'signup') {
    $username = $_POST['username'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $security_question = $_POST['security_question'] ?? '';
    $security_answer = isset($_POST['security_answer']) ? trim($_POST['security_answer']) : '';

    if (empty($username) || empty($full_name) || empty($email) || empty($password) || empty($security_question) || empty($security_answer)) {
        header("Location: signup.php?error=All fields including security question are required");
        exit();
    }

    try {
        // Check if email or username already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email OR username = :username");
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            header("Location: signup.php?error=Email or Username already taken");
            exit();
        }

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        // Default role is 'user'
        $stmt = $conn->prepare("INSERT INTO users (username, full_name, email, password, role, security_question, security_answer) VALUES (:username, :full_name, :email, :password, 'user', :question, :answer)");
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':full_name', $full_name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':question', $security_question);
        $stmt->bindParam(':answer', $security_answer);
        $stmt->execute();

        header("Location: login.php?success=Account created successfully. Please login.");
        exit();
    } catch (PDOException $e) {
        header("Location: signup.php?error=Database error: " . $e->getMessage());
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - MyBeachCare</title>
    
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
                Join our community that cares for the ocean. Together, we safeguard our shores for generations ahead.
            </p>
        </div>

        <!-- Right Side: Glass Signup Form -->
        <div class="w-full max-w-md">
            <div class="glass-panel p-6 md:p-8 rounded-3xl shadow-2xl relative overflow-hidden">
                <!-- Subtle decorative element -->
                <div class="absolute -top-10 -right-10 w-32 h-32 bg-primary/30 rounded-full blur-3xl"></div>

                <div class="relative">
                    <h2 class="text-xl font-bold text-white mb-1">Create Account</h2>
                    <p class="text-white/70 text-xs mb-5">Join us to start making a difference</p>

                    <form action="signup.php" method="POST" class="space-y-3">
                        <input type="hidden" name="action" value="signup">
                        
                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-0.5">
                                <label for="username" class="text-[10px] font-medium text-white/90 ml-1 uppercase tracking-wider">Username</label>
                                <input id="username" name="username" type="text" required 
                                    class="glass-input block w-full px-4 py-2.5 rounded-lg text-sm text-gray-900 placeholder-gray-400 focus:outline-none transition-all duration-200" 
                                    placeholder="minahrambo">
                            </div>

                            <div class="space-y-0.5">
                                <label for="full_name" class="text-[10px] font-medium text-white/90 ml-1 uppercase tracking-wider">Full Name</label>
                                <input id="full_name" name="full_name" type="text" required 
                                    class="glass-input block w-full px-4 py-2.5 rounded-lg text-sm text-gray-900 placeholder-gray-400 focus:outline-none transition-all duration-200" 
                                    placeholder="Minah Rambo">
                            </div>
                        </div>

                        <div class="space-y-0.5">
                            <label for="email" class="text-[10px] font-medium text-white/90 ml-1 uppercase tracking-wider">Email Address</label>
                            <input id="email" name="email" type="email" autocomplete="email" required 
                                class="glass-input block w-full px-4 py-2.5 rounded-lg text-sm text-gray-900 placeholder-gray-400 focus:outline-none transition-all duration-200" 
                                placeholder="email@example.com">
                        </div>

                        <div class="space-y-0.5">
                            <label for="password" class="text-[10px] font-medium text-white/90 ml-1 uppercase tracking-wider">Password</label>
                            <div class="relative">
                                <input id="password" name="password" type="password" autocomplete="new-password" required 
                                    class="glass-input block w-full px-4 py-2.5 rounded-lg text-sm text-gray-900 placeholder-gray-400 focus:outline-none transition-all duration-200" 
                                    placeholder="••••••••">
                                <button type="button" onclick="togglePassword()" class="absolute inset-y-0 right-0 px-4 flex items-center text-gray-500 hover:text-primary transition-colors focus:outline-none">
                                    <i id="eye-icon" class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Security Questions -->
                        <div class="space-y-0.5">
                            <label for="security_question" class="text-[10px] font-medium text-white/90 ml-1 uppercase tracking-wider">Security Question</label>
                            <select id="security_question" name="security_question" required 
                                class="glass-input block w-full px-4 py-2.5 rounded-lg text-sm text-gray-900 placeholder-gray-500 focus:outline-none transition-all duration-200">
                                <option value="" disabled selected>Select a question...</option>
                                <option value="What is the name of your first pet?">What is the name of your first pet?</option>
                                <option value="What is your mother's maiden name?">What is your mother's maiden name?</option>
                                <option value="What was your first car?">What was your first car?</option>
                                <option value="What elementary school did you attend?">What elementary school did you attend?</option>
                                <option value="What is the name of the town where you were born?">What is the name of the town where you were born?</option>
                            </select>
                        </div>

                        <div class="space-y-0.5">
                            <label for="security_answer" class="text-[10px] font-medium text-white/90 ml-1 uppercase tracking-wider">Security Answer</label>
                            <input id="security_answer" name="security_answer" type="text" required 
                                class="glass-input block w-full px-4 py-2.5 rounded-lg text-sm text-gray-900 placeholder-gray-400 focus:outline-none transition-all duration-200" 
                                placeholder="Your answer">
                        </div>

                        <button type="submit" 
                            class="w-full py-3 px-4 bg-primary hover:bg-indigo-700 text-white font-bold text-sm rounded-xl shadow-lg shadow-primary/30 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-transparent focus:ring-primary transition-all transform hover:scale-[1.02] mt-2">
                            SIGN UP
                        </button>
                    </form>

                    <div class="mt-6"></div>
                    <p class="text-center text-xs text-white/80">
                        Already have an account? 
                        <a href="login.php" class="font-bold text-white hover:underline decoration-2 underline-offset-4 transition-all">Sign In</a>
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
                title: 'Registration Failed',
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
