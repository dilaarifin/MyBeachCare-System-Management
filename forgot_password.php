<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - MyBeachCare</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: { primary: '#512DA8', secondary: '#5C6BC0' }
                }
            }
        }
    </script>
</head>
<body class="h-screen w-full flex bg-gray-50 font-sans">
    <div class="w-full h-full flex items-center justify-center p-4">
        <div class="w-full max-w-md bg-white rounded-2xl shadow-xl overflow-hidden p-8 text-center">
            <div class="mb-6">
                <img src="img/logo.png" alt="Logo" class="w-20 h-auto mx-auto mb-4">
                <h1 class="text-2xl font-bold text-gray-900">Forgot Password</h1>
            </div>
            
            <div class="bg-blue-50 text-blue-800 p-4 rounded-xl mb-6 text-sm">
                <i class="fas fa-info-circle mr-2"></i>
                Please contact an administrator to reset your password.
            </div>

            <a href="login.php" class="w-full block py-3 bg-primary text-white font-bold rounded-xl hover:bg-secondary transition-colors">
                Back to Login
            </a>
        </div>
    </div>
</body>
</html>
