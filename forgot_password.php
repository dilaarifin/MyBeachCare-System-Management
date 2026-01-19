<?php
session_start();
include "includes/db_conn.php";

$step = 1;
$error = '';
$success = '';
$username_val = '';
$question_val = '';

// Clear reset session data if opening fresh (GET request without query params)
if ($_SERVER["REQUEST_METHOD"] == "GET") {
    unset($_SESSION['reset_user_id']);
    unset($_SESSION['reset_verified']);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $step = isset($_POST['step']) ? (int)$_POST['step'] : 1;

    try {
        if ($step == 1 && isset($_POST['check_username'])) {
            // Step 1: Verify Username
            $username = $_POST['username'] ?? '';
            $username_val = $username; // For repopulating form
            
            $stmt = $conn->prepare("SELECT id, security_question FROM users WHERE username = :username");
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if (!empty($row['security_question'])) {
                    // Store ID in session - SAFER than hidden field
                    $_SESSION['reset_user_id'] = $row['id'];
                    $_SESSION['security_question'] = $row['security_question'];
                    $step = 2;
                } else {
                    $error = "This account has not set up a security question. Please contact support.";
                }
            } else {
                $error = "Username not found.";
            }
        } elseif ($step == 2 && isset($_POST['verify_answer'])) {
            // Step 2: Verify Answer
            if (!isset($_SESSION['reset_user_id'])) {
                header("Location: forgot_password.php"); 
                exit();
            }

            $answer = trim($_POST['answer']);
            $user_id = $_SESSION['reset_user_id'];
            
            $stmt = $conn->prepare("SELECT security_answer FROM users WHERE id = :id");
            $stmt->bindParam(':id', $user_id);
            $stmt->execute();
            
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if (strcasecmp($row['security_answer'], $answer) == 0) {
                    // Mark as verified
                    $_SESSION['reset_verified'] = true;
                    $step = 3;
                } else {
                    $error = "Incorrect answer.";
                    $step = 2;
                }
            } else {
                $error = "User not found."; // Should not happen
                $step = 1;
            }
        } elseif ($step == 3 && isset($_POST['reset_password'])) {
            // Step 3: Reset Password
            if (!isset($_SESSION['reset_user_id']) || !isset($_SESSION['reset_verified'])) {
                die("Unauthorized access.");
            }

            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];
            
            if ($password !== $confirm_password) {
                $error = "Passwords do not match.";
                $step = 3;
            } elseif (strlen($password) < 6) {
                $error = "Password must be at least 6 characters.";
                $step = 3;
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $user_id = $_SESSION['reset_user_id'];

                $stmt = $conn->prepare("UPDATE users SET password = :password WHERE id = :id");
                $stmt->bindParam(':password', $hashed_password);
                $stmt->bindParam(':id', $user_id);
                
                if ($stmt->execute()) {
                    $success = "Password has been successfully reset. You can now login.";
                    // Cleanup session
                    unset($_SESSION['reset_user_id']);
                    unset($_SESSION['reset_verified']);
                    unset($_SESSION['security_question']);
                    $step = 4;
                } else {
                    $error = "Update failed. Please try again.";
                    $step = 3;
                }
            }
        }
    } catch (PDOException $e) {
        $error = "Database Error: " . $e->getMessage();
    }
} else {
    // If returning to page (GET) or first load
    if (isset($_SESSION['reset_user_id']) && isset($_SESSION['security_question'])) {
        // Resume session if exists (optional, maybe reset?)
        // actually better to just let them start over if they refresh to avoid confusion
    }
}
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="style/style.css">
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
        <div class="w-full max-w-md bg-white rounded-2xl shadow-xl overflow-hidden">
            <div class="p-8">
                <div class="text-center mb-8">
                    <img src="img/logo.png" alt="Logo" class="w-20 h-auto mx-auto mb-4">
                    <h1 class="text-2xl font-bold text-gray-900">Forgot Password</h1>
                    <p class="text-gray-600 mt-2">Reset your account access</p>
                </div>

                <!-- Step 1: Enter Username -->
                <?php if ($step == 1): ?>
                    <form method="POST" class="space-y-6">
                        <input type="hidden" name="step" value="1">
                        <input type="hidden" name="check_username" value="1">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Username</label>
                            <input type="text" name="username" required value="<?= htmlspecialchars($username_val) ?>"
                                class="mt-1 block w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                        <button type="submit" class="w-full py-3 bg-primary text-white font-bold rounded-xl hover:bg-secondary transition-colors">
                            Next
                        </button>
                         <div class="text-center">
                            <a href="login.php" class="text-sm text-gray-500 hover:text-gray-900">Back to Login</a>
                        </div>
                    </form>

                <!-- Step 2: Answer Security Question -->
                <?php elseif ($step == 2): ?>
                    <form method="POST" class="space-y-6">
                        <input type="hidden" name="step" value="2">
                        <input type="hidden" name="verify_answer" value="1">
                        
                        <div class="bg-indigo-50 p-4 rounded-lg text-indigo-900 mb-4">
                            <p class="text-xs font-bold uppercase tracking-wide text-indigo-500 mb-1">Security Question</p>
                            <p class="font-medium"><?= htmlspecialchars($_SESSION['security_question']) ?></p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Your Answer</label>
                            <input type="text" name="answer" required autocomplete="off"
                                class="mt-1 block w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                        <button type="submit" class="w-full py-3 bg-primary text-white font-bold rounded-xl hover:bg-secondary transition-colors">
                            Verify Answer
                        </button>
                         <div class="text-center">
                            <a href="forgot_password.php" class="text-sm text-gray-500 hover:text-gray-900">Start Over</a>
                        </div>
                    </form>

                <!-- Step 3: New Password -->
                <?php elseif ($step == 3): ?>
                    <form method="POST" class="space-y-6">
                        <input type="hidden" name="step" value="3">
                        <input type="hidden" name="reset_password" value="1">

                        <div>
                            <label class="block text-sm font-medium text-gray-700">New Password</label>
                            <input type="password" name="password" required 
                                class="mt-1 block w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Confirm Password</label>
                            <input type="password" name="confirm_password" required 
                                class="mt-1 block w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                        <button type="submit" class="w-full py-3 bg-primary text-white font-bold rounded-xl hover:bg-secondary transition-colors">
                            Reset Password
                        </button>
                    </form>

                <!-- Step 4: Success -->
                <?php elseif ($step == 4): ?>
                    <div class="text-center">
                        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-6">
                            <i class="fas fa-check text-2xl text-green-600"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900">Password Reset!</h3>
                        <p class="text-gray-500 mt-2 mb-6"><?= $success ?></p>
                        <a href="login.php" class="w-full block py-3 bg-primary text-white font-bold rounded-xl hover:bg-secondary transition-colors">
                            Back to Login
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        <?php if ($error): ?>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: "<?= addslashes($error) ?>",
                confirmButtonColor: '#512DA8'
            });
        <?php endif; ?>
    </script>
</body>
</html>
