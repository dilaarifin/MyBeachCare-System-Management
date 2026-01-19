<?php
ob_start();
session_start();
include "includes/db_conn.php";

// Redirect if not logged in
$isLoggedIn = isset($_SESSION['user_id']);
if (!$isLoggedIn) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

// Handle Profile Update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $full_name = $_POST['full_name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Fetch current profile image for default
    $stmt = $conn->prepare("SELECT profile_image FROM users WHERE id = :id");
    $stmt->execute([':id' => $user_id]);
    $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
    $profile_image = $currentUser['profile_image'];

    // Handle Image Upload
    if (isset($_FILES['profile_image_file']) && $_FILES['profile_image_file']['error'] == 0) {
        $target_dir = "uploads/profiles/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        $file_extension = pathinfo($_FILES["profile_image_file"]["name"], PATHINFO_EXTENSION);
        $new_filename = "profile_" . $user_id . "_" . time() . "." . $file_extension;
        $target_file = $target_dir . $new_filename;

        if (getimagesize($_FILES["profile_image_file"]["tmp_name"]) !== false) {
            if (move_uploaded_file($_FILES["profile_image_file"]["tmp_name"], $target_file)) {
                $profile_image = $target_file;
                $_SESSION['profile_image'] = $profile_image;
            } else {
                $error = "Sorry, there was an error uploading your file.";
            }
        } else {
            $error = "File is not an image.";
        }
    }

    // Handle Password Change
    $password_sql = "";
    if (!empty($new_password)) {
        if ($new_password !== $confirm_password) {
            $error = "Passwords do not match.";
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $password_sql = ", password = :password";
        }
    }

    if (empty($error)) {
        try {
            $update_stmt = $conn->prepare("UPDATE users SET full_name = :full_name, phone = :phone, profile_image = :profile_image $password_sql WHERE id = :id");
            $params = [
                ':full_name' => $full_name,
                ':phone' => $phone,
                ':profile_image' => $profile_image,
                ':id' => $user_id
            ];
            if (!empty($password_sql)) {
                $params[':password'] = $hashed_password;
            }
            $update_stmt->execute($params);

            $_SESSION['full_name'] = $full_name;
            header("Location: profile.php?success=Profile updated successfully");
            exit();
        } catch (PDOException $e) {
            $error = "Update failed: " . $e->getMessage();
        }
    }
}

// Handle Event Cancellation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cancel_event'])) {
    $participant_id = $_POST['participant_id'] ?? '';
    try {
        // Delete the participation record so the user can register again if they want
        $cancel_stmt = $conn->prepare("DELETE FROM event_participants WHERE id = :pid AND user_id = :uid");
        $cancel_stmt->execute([':pid' => $participant_id, ':uid' => $user_id]);
        
        if ($cancel_stmt->rowCount() > 0) {
            // Revert 10 points for cancelling
            $deduct_stmt = $conn->prepare("UPDATE users SET points = GREATEST(0, points - 10) WHERE id = :uid");
            $deduct_stmt->execute([':uid' => $user_id]);
        }

        header("Location: profile.php?cancelled=1");
        exit();
    } catch (PDOException $e) {
        $error = "Cancellation failed: " . $e->getMessage();
    }
}

// Handle Voucher Claim
// Handle Reward Claim
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['claim_reward'])) {
    $reward_id = (int)$_POST['reward_id'];
    try {
        // Fetch reward details
        $reward_stmt = $conn->prepare("SELECT * FROM rewards WHERE id = ? AND is_active = 1");
        $reward_stmt->execute([$reward_id]);
        $reward = $reward_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$reward) {
            throw new Exception("Invalid or inactive reward.");
        }

        // Check user points
        $user_res = $conn->prepare("SELECT points FROM users WHERE id = ?");
        $user_res->execute([$user_id]);
        $current_points = $user_res->fetchColumn();

        if ($current_points < $reward['points_required']) {
            throw new Exception("Insufficient points to claim this reward.");
        }

        // Check if already claimed
        $check_claim = $conn->prepare("SELECT id FROM user_rewards WHERE user_id = ? AND reward_id = ?");
        $check_claim->execute([$user_id, $reward_id]);
        if ($check_claim->rowCount() > 0) {
            throw new Exception("You have already claimed this reward.");
        }

        // Process Claim (Deduct Points & Add Record)
        $conn->beginTransaction();

        $deduct = $conn->prepare("UPDATE users SET points = points - ? WHERE id = ?");
        $deduct->execute([$reward['points_required'], $user_id]);

        $insert = $conn->prepare("INSERT INTO user_rewards (user_id, reward_id) VALUES (?, ?)");
        $insert->execute([$user_id, $reward_id]);

        $conn->commit();

        header("Location: profile.php?success=Reward claimed successfully! Enjoy.");
        exit();

    } catch (Exception $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        $error = "Claim failed: " . $e->getMessage();
    }
}

// Handle Mark Reward Used
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['mark_reward_used'])) {
    $claim_id = (int)$_POST['claim_id'];
    try {
        $update_claim = $conn->prepare("UPDATE user_rewards SET status = 'Used' WHERE id = ? AND user_id = ?");
        $update_claim->execute([$claim_id, $user_id]);
        
        header("Location: profile.php?success=Voucher marked as used/deleted.");
        exit();
    } catch (PDOException $e) {
        $error = "Operation failed: " . $e->getMessage();
    }
}

// Handle Delete Reward History
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_reward_history'])) {
    $claim_id = (int)$_POST['claim_id'];
    try {
        $delete_claim = $conn->prepare("DELETE FROM user_rewards WHERE id = ? AND user_id = ?");
        $delete_claim->execute([$claim_id, $user_id]);
        
        header("Location: profile.php?success=History item deleted successfully.");
        exit();
    } catch (PDOException $e) {
        $error = "Operation failed: " . $e->getMessage();
    }
}

// Fetch user data
try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->execute([':id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        session_destroy();
        header("Location: login.php");
        exit();
    }

    // Fetch Badges
    $badges_stmt = $conn->prepare("SELECT b.name, b.image, b.description FROM user_badges ub JOIN badges b ON ub.badge_id = b.id WHERE ub.user_id = :uid");
    $badges_stmt->execute([':uid' => (int)$user_id]);
    $badges = $badges_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch Volunteer Activities
    $activities_stmt = $conn->prepare("
        SELECT ep.id as participant_id, e.id as event_id, e.title, e.event_date, b.name as beach_name, ep.status, e.certificates_released, e.status as event_status
        FROM event_participants ep
        JOIN events e ON ep.event_id = e.id
        LEFT JOIN beaches b ON e.beach_id = b.id
        WHERE ep.user_id = :user_id
        ORDER BY e.event_date DESC
    ");
    $activities_stmt->execute([':user_id' => (int)$user_id]);
    $activities = $activities_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch Reports History
    $reports_stmt = $conn->prepare("
        SELECT r.*, b.name as beach_name 
        FROM reports r 
        LEFT JOIN beaches b ON r.beach_id = b.id 
        WHERE r.user_id = :user_id 
        ORDER BY r.created_at DESC
    ");
    $reports_stmt->execute([':user_id' => (int)$user_id]);
    $user_reports = $reports_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Count stats dynamically
    $stats_stmt = $conn->prepare("
        SELECT 
            (SELECT COUNT(*) FROM reports WHERE user_id = :uid1) as reports_count,
            (SELECT COUNT(*) FROM event_participants WHERE user_id = :uid2 AND status != 'Cancelled') as events_count
    ");
    $stats_stmt->execute([':uid1' => (int)$user_id, ':uid2' => (int)$user_id]);
    $user_stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user_stats) $user_stats = ['reports_count' => 0, 'events_count' => 0];

    // Fetch Active Rewards
    $rewards_stmt = $conn->query("SELECT * FROM rewards WHERE is_active = 1 ORDER BY points_required ASC");
    $available_rewards = $rewards_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch User's Claimed Rewards
    $claimed_stmt = $conn->prepare("
        SELECT ur.id as claim_id, ur.status as claim_status, ur.claimed_at, r.* 
        FROM user_rewards ur 
        JOIN rewards r ON ur.reward_id = r.id 
        WHERE ur.user_id = ?
        ORDER BY ur.claimed_at DESC
    ");
    $claimed_stmt->execute([$user_id]);
    $all_claimed_rewards = $claimed_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group into Active and History
    $my_vouchers = [];
    $history_rewards = [];
    $claimed_reward_ids = []; // For "already claimed" check in available list

    foreach ($all_claimed_rewards as $cr) {
        if ($cr['claim_status'] == 'Active') {
            $my_vouchers[] = $cr;
            $claimed_reward_ids[] = $cr['id']; // Only prevent re-claim if currently active
        } else {
            $history_rewards[] = $cr;
        }
    }

} catch (PDOException $e) {
    $error = "Database Error: " . $e->getMessage();
}

include "includes/header.php";
?>

<main class="min-h-screen bg-slate-50 py-12">
    <div class="container mx-auto px-6 max-w-full">
        
        <!-- Profile Header -->
        <div class="glass rounded-3xl p-8 mb-8 border border-white/40 shadow-xl overflow-hidden relative">
            <div class="absolute -top-24 -right-24 w-64 h-64 bg-primary/10 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-secondary/10 rounded-full blur-3xl"></div>
            
            <div class="relative flex flex-col md:flex-row items-center gap-8">
                <!-- Profile Image Preview -->
                <div class="relative group">
                    <div class="w-32 h-32 rounded-2xl overflow-hidden border-4 border-white shadow-lg bg-gray-100 flex items-center justify-center">
                        <?php if (!empty($user['profile_image'])): ?>
                            <img src="<?= htmlspecialchars($user['profile_image']) ?>" alt="Profile" class="w-full h-full object-cover">
                        <?php else: ?>
                            <i class="fa-solid fa-user text-4xl text-gray-400"></i>
                        <?php endif; ?>
                    </div>
                    <label for="image_upload" class="absolute -bottom-2 -right-2 bg-primary text-white w-10 h-10 rounded-full flex items-center justify-center cursor-pointer shadow-lg hover:scale-110 transition-transform">
                        <i class="fa-solid fa-camera"></i>
                    </label>
                </div>

                <div class="flex-1 flex flex-col md:flex-row justify-between items-center md:items-start gap-6">
                    <div class="text-center md:text-left">
                        <h1 class="text-3xl font-outfit font-bold text-gray-900"><?= htmlspecialchars($user['full_name'] ?? 'User Profile') ?></h1>
                        <p class="text-gray-500 font-medium mt-1">@<?= htmlspecialchars($user['username']) ?> <span class="mx-2">•</span> <span class="bg-primary/10 text-primary text-xs px-3 py-1 rounded-full uppercase tracking-wider font-bold"><?= htmlspecialchars($user['role']) ?></span></p>
                        <div class="flex flex-wrap justify-center md:justify-start gap-4 mt-4">
                            <div class="flex items-center text-sm text-gray-500">
                                <i class="fa-solid fa-calendar-alt mr-2"></i>
                                Joined <?= date('M Y', strtotime($user['created_at'])) ?>
                            </div>
                            <div class="flex items-center text-sm text-gray-500">
                                <i class="fa-solid fa-envelope mr-2"></i>
                                <?= htmlspecialchars($user['email']) ?>
                            </div>
                        </div>
                    </div>

                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <a href="admin/dashboard.php" class="px-6 py-3 text-sm font-bold text-white bg-primary hover:bg-secondary rounded-full transition-all shadow-md flex items-center gap-2 whitespace-nowrap">
                            <i class="fas fa-cog"></i>Manage System
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Side Info -->
            <div class="space-y-6">
                <!-- Activity Stats -->
                <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Contribution</h3>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-500 text-sm">Reports Made</span>
                            <span class="font-bold text-gray-900"><?= $user_stats['reports_count'] ?? 0 ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-500 text-sm">Events Joined</span>
                            <span class="font-bold text-gray-900"><?= number_format($user_stats['events_count']) ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-500 text-sm">Community Points</span>
                            <span class="font-bold text-primary"><?= number_format($user['points'] ?? 0) ?> PTS</span>
                        </div>
                        <div class="h-2 bg-gray-100 rounded-full overflow-hidden mt-6">
                            <?php 
                                $point_goal = 500;
                                $progress = min(100, (($user['points'] ?? 0) / $point_goal) * 100); 
                            ?>
                            <div class="h-full bg-primary" style="width: <?= $progress ?>%"></div>
                        </div>
                        <div class="flex justify-between mt-1">
                            <p class="text-[10px] text-gray-400 uppercase tracking-widest font-bold">Progress: <?= round($progress) ?>%</p>
                            <p class="text-[10px] text-gray-400 font-bold"><?= $user['points'] ?> / <?= $point_goal ?> pts</p>
                        </div>
                        

                    </div>
                </div>

                <!-- Earned Badges -->
                <?php if (!empty($badges)): ?>
                <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm mt-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Badges Earned</h3>
                    <div class="flex flex-wrap gap-3">
                        <?php foreach ($badges as $badge): ?>
                            <div onclick="showBadgeDetails('<?= htmlspecialchars($badge['name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($badge['description'], ENT_QUOTES) ?>', '<?= htmlspecialchars($badge['image'], ENT_QUOTES) ?>')" class="group relative cursor-pointer" title="<?= htmlspecialchars($badge['description']) ?>">
                                <img src="<?= htmlspecialchars($badge['image']) ?>" alt="<?= htmlspecialchars($badge['name']) ?>" class="w-12 h-12 rounded-xl border border-gray-100 p-1 bg-slate-50 hover:scale-110 transition-transform">
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>



                <!-- Rewards Section -->
                <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm mt-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Rewards Center</h3>
                    
                    <div class="space-y-4">
                        
                        <!-- Section: My Active Vouchers -->
                        <?php if (!empty($my_vouchers)): ?>
                            <h4 class="text-sm font-bold text-gray-400 uppercase tracking-widest mb-2 mt-2">My Active Vouchers</h4>
                            <?php foreach ($my_vouchers as $voucher): ?>
                                <?php 
                                    $expiry_date = $voucher['expiry_date'];
                                    $is_expired = !empty($expiry_date) && $expiry_date < date('Y-m-d');
                                ?>
                                <div class="flex items-center gap-4 p-3 rounded-2xl border border-emerald-200 bg-emerald-50/50">
                                    <div class="w-20 h-20 rounded-xl border border-gray-100 shadow-sm overflow-hidden flex-shrink-0 bg-white items-center justify-center flex relative">
                                        <?php if (!empty($voucher['image'])): ?>
                                            <img src="<?= htmlspecialchars($voucher['image']) ?>" alt="<?= htmlspecialchars($voucher['name']) ?>" class="w-full h-full object-cover">
                                        <?php else: ?>
                                            <i class="fas fa-gift text-2xl text-gray-300"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-1">
                                         <div class="flex justify-between items-start">
                                            <h4 class="font-bold text-gray-900 text-sm"><?= htmlspecialchars($voucher['name']) ?></h4>
                                            <span class="text-xs font-black text-emerald-600 bg-emerald-100 px-2 py-1 rounded-lg">CLAIMED</span>
                                         </div>
                                         <p class="text-[10px] text-gray-500 mb-1 line-clamp-1"><?= htmlspecialchars($voucher['description']) ?></p>
                                         
                                         <div class="bg-white border border-emerald-200 rounded-lg p-1.5 text-center mb-2">
                                            <p class="text-[10px] text-emerald-700 font-bold uppercase">Code: <span class="font-mono text-lg"><?= htmlspecialchars($voucher['voucher_code']) ?></span></p>
                                         </div>

                                         <?php if (!empty($expiry_date)): ?>
                                            <p class="text-[10px] <?= $is_expired ? 'text-rose-500 font-bold' : 'text-amber-600' ?> mb-2">
                                                <i class="fas fa-clock mr-1"></i>Valid until: <?= date('d M Y', strtotime($expiry_date)) ?>
                                            </p>
                                         <?php endif; ?>

                                         <form action="profile.php" method="POST" onsubmit="return confirm('Are you sure you want to delete/mark this voucher as used? This cannot be undone.');">
                                            <input type="hidden" name="mark_reward_used" value="1">
                                            <input type="hidden" name="claim_id" value="<?= $voucher['claim_id'] ?>">
                                            <button type="submit" class="w-full py-1.5 bg-gray-200 text-gray-600 text-[10px] font-bold rounded-lg hover:bg-rose-50 hover:text-rose-600 transition-all">
                                                <i class="fas fa-trash-alt mr-1"></i>Delete / Mark as Used
                                            </button>
                                         </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <div class="border-b border-gray-100 my-6"></div>
                        <?php endif; ?>

                        <!-- Section: Available Rewards -->
                         <h4 class="text-sm font-bold text-gray-400 uppercase tracking-widest mb-2">Available Rewards</h4>
                        <?php if (!empty($available_rewards)): ?>
                            <?php foreach ($available_rewards as $reward): ?>
                                <?php 
                                    // Skip if user already has an ACTIVE claim on this reward
                                    if (in_array($reward['id'], $claimed_reward_ids)) continue;

                                    $can_afford = ($user['points'] ?? 0) >= $reward['points_required'];
                                    $expiry_date = $reward['expiry_date'];
                                    $is_expired = !empty($expiry_date) && $expiry_date < date('Y-m-d');
                                ?>
                                <div class="flex items-center gap-4 p-3 rounded-2xl border <?= $is_expired ? 'border-gray-100 bg-gray-50 opacity-70' : 'border-gray-100 bg-white' ?>">
                                    <div class="w-20 h-20 rounded-xl border border-gray-100 shadow-sm overflow-hidden flex-shrink-0 bg-white items-center justify-center flex relative">
                                        <?php if (!empty($reward['image'])): ?>
                                            <img src="<?= htmlspecialchars($reward['image']) ?>" alt="<?= htmlspecialchars($reward['name']) ?>" class="w-full h-full object-cover <?= $is_expired ? 'grayscale' : '' ?>">
                                        <?php else: ?>
                                            <i class="fas fa-gift text-2xl text-gray-300"></i>
                                        <?php endif; ?>
                                        <?php if ($is_expired): ?>
                                            <div class="absolute inset-0 bg-gray-900/10 flex items-center justify-center">
                                                <span class="text-[10px] font-black uppercase text-white bg-gray-800 px-2 py-1 rounded">Expired</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-1">
                                         <div class="flex justify-between items-start">
                                            <h4 class="font-bold text-gray-900 text-sm"><?= htmlspecialchars($reward['name']) ?></h4>
                                            <?php if (!$is_expired): ?>
                                                <span class="text-xs font-black text-primary bg-primary/10 px-2 py-1 rounded-lg"><?= number_format($reward['points_required']) ?> PTS</span>
                                            <?php endif; ?>
                                         </div>
                                         <p class="text-[10px] text-gray-500 mb-1 line-clamp-1"><?= htmlspecialchars($reward['description']) ?></p>
                                         
                                         <?php if (!empty($expiry_date)): ?>
                                            <p class="text-[10px] <?= $is_expired ? 'text-rose-500 font-bold' : 'text-amber-600' ?> mb-2">
                                                <i class="fas fa-clock mr-1"></i>Valid until: <?= date('d M Y', strtotime($expiry_date)) ?>
                                            </p>
                                         <?php else: ?>
                                            <div class="mb-2"></div>
                                         <?php endif; ?>

                                         <?php if ($is_expired): ?>
                                            <button disabled class="w-full py-1.5 bg-gray-200 text-gray-500 text-[10px] font-bold rounded-lg cursor-not-allowed">
                                                Reward Expired
                                            </button>
                                         <?php else: ?>
                                            <?php if ($can_afford): ?>
                                                <form action="profile.php" method="POST" onsubmit="return confirm('Spend <?= $reward['points_required'] ?> points to claim this reward?');">
                                                    <input type="hidden" name="claim_reward" value="1">
                                                    <input type="hidden" name="reward_id" value="<?= $reward['id'] ?>">
                                                    <button type="submit" class="w-full py-1.5 bg-black text-white text-[10px] font-bold rounded-lg hover:bg-gray-800 transition-all shadow-md">
                                                        Claim Reward
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <button disabled class="w-full py-1.5 bg-gray-100 text-gray-400 text-[10px] font-bold rounded-lg cursor-not-allowed">
                                                    Locked (Need <?= number_format($reward['points_required']) ?> pts)
                                                </button>
                                            <?php endif; ?>
                                         <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-sm text-gray-400 italic text-center py-4">No rewards available at the moment.</p>
                        <?php endif; ?>
                        
                        <!-- Section: Rewards History -->
                        <?php if (!empty($history_rewards)): ?>
                            <div class="border-t border-gray-100 pt-6 mt-6">
                                <h4 class="text-sm font-bold text-gray-400 uppercase tracking-widest mb-4">Rewards History</h4>
                                <div class="space-y-3 opacity-60">
                                    <?php foreach ($history_rewards as $h_reward): ?>
                                        <div class="flex items-center justify-between gap-3 p-2 bg-gray-50 rounded-xl group hover:bg-red-50 transition-colors">
                                            <div class="flex items-center gap-3">
                                                <div class="w-12 h-12 rounded-lg bg-gray-200 flex items-center justify-center">
                                                    <?php if (!empty($h_reward['image'])): ?>
                                                        <img src="<?= htmlspecialchars($h_reward['image']) ?>" class="w-full h-full object-cover rounded-lg grayscale">
                                                    <?php else: ?>
                                                        <i class="fas fa-gift text-gray-400"></i>
                                                    <?php endif; ?>
                                                </div>
                                                <div>
                                                    <h5 class="text-sm font-bold text-gray-700 decoration-slice"><?= htmlspecialchars($h_reward['name']) ?></h5>
                                                    <p class="text-[10px] text-gray-500">Used on <?= date('d M Y', strtotime($h_reward['claimed_at'])) ?></p>
                                                </div>
                                            </div>
                                            
                                            <!-- Trash Bin -->
                                            <form action="profile.php" method="POST" onsubmit="return confirm('Permanently delete this item from history?');">
                                                <input type="hidden" name="delete_reward_history" value="1">
                                                <input type="hidden" name="claim_id" value="<?= $h_reward['claim_id'] ?>">
                                                <button type="submit" class="w-8 h-8 flex items-center justify-center rounded-full text-gray-400 hover:text-white hover:bg-rose-500 transition-all opacity-0 group-hover:opacity-100" title="Remove from history">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </button>
                                            </form>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>

            </div>

            <!-- Edit Profile Form -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-3xl p-8 border border-gray-100 shadow-sm">
                    <div class="flex items-center justify-between mb-8">
                        <h2 class="text-2xl font-bold text-gray-900">Edit Profile Information</h2>
                        <i class="fa-solid fa-user-pen text-primary text-xl"></i>
                    </div>

                    <form action="profile.php" method="POST" enctype="multipart/form-data" class="space-y-6">
                        <input type="hidden" name="update_profile" value="1">
                        <input type="file" id="image_upload" name="profile_image_file" class="hidden" onchange="previewImage(this)">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-1.5">
                                <label class="block text-sm font-bold text-gray-700 ml-1">Full Name</label>
                                <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" required 
                                    class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                            </div>

                            <div class="space-y-1.5">
                                <label class="block text-sm font-bold text-gray-700 ml-1">Phone Number</label>
                                <input type="text" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" 
                                    placeholder="+60 12-345 6789" maxlength="12"
                                    class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                            </div>

                            <div class="space-y-1.5 opacity-60">
                                <label class="block text-sm font-bold text-gray-700 ml-1">Username (Read-Only)</label>
                                <input type="text" value="<?= htmlspecialchars($user['username']) ?>" disabled 
                                    class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-100 cursor-not-allowed">
                            </div>

                            <div class="space-y-1.5 opacity-60">
                                <label class="block text-sm font-bold text-gray-700 ml-1">Email (Read-Only)</label>
                                <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled 
                                    class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-100 cursor-not-allowed">
                            </div>

                            <div class="space-y-1.5">
                                <label class="block text-sm font-bold text-gray-700 ml-1">New Password (Leave blank to keep current)</label>
                                <input type="password" name="new_password" placeholder="••••••••"
                                    class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                            </div>

                            <div class="space-y-1.5">
                                <label class="block text-sm font-bold text-gray-700 ml-1">Confirm New Password</label>
                                <input type="password" name="confirm_password" placeholder="••••••••"
                                    class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                            </div>
                        </div>

                        <div class="pt-4 flex items-center justify-end space-x-4">
                            <button type="reset" class="px-6 py-3 text-sm font-bold text-gray-500 hover:text-gray-900 transition-colors">Discard Changes</button>
                            <button type="submit" class="px-8 py-3 bg-primary text-white rounded-xl font-bold shadow-lg shadow-primary/20 hover:bg-black transition-all transform hover:scale-[1.02] active:scale-100">
                                Save Profile
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Volunteer Activities Section -->
                <div class="bg-white rounded-3xl p-8 border border-gray-100 shadow-sm mt-8">
                    <div class="flex items-center justify-between mb-8">
                        <h2 class="text-2xl font-bold text-gray-900">Event Activities</h2>
                        <i class="fa-solid fa-hands-helping text-primary text-xl"></i>
                    </div>

                    <?php if (!empty($activities)): ?>
                        <?php
                            $today = date('Y-m-d');
                            $upcoming = array_filter($activities, function($a) use ($today) { 
                                $event_date = date('Y-m-d', strtotime($a['event_date']));
                                // It is upcoming if: Status is Registered AND (Date is future OR Date is today) AND Event is NOT Completed
                                return $a['status'] == 'Registered' && $event_date >= $today && $a['event_status'] != 'Completed'; 
                            });
                            $completed = array_filter($activities, function($a) use ($today) { 
                                $event_date = date('Y-m-d', strtotime($a['event_date']));
                                // It is completed if: Status is Attended OR Event is Completed OR Date is past
                                return $a['status'] == 'Attended' || $a['event_status'] == 'Completed' || ($event_date < $today && $a['status'] != 'Cancelled'); 
                            });
                        ?>

                        <!-- Upcoming Events -->
                        <div class="mb-10">
                            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                                Upcoming Events
                            </h3>
                            <div class="space-y-4">
                                <?php if (empty($upcoming)): ?>
                                    <p class="text-sm text-gray-400 italic">No upcoming events scheduled.</p>
                                <?php else: ?>
                                    <?php foreach ($upcoming as $activity): ?>
                                        <div class="flex flex-col md:flex-row items-center justify-between p-5 rounded-2xl border border-gray-50 bg-slate-50/50 hover:bg-white hover:shadow-md transition-all group">
                                            <div class="flex items-center space-x-5">
                                                <div class="flex flex-col items-center justify-center w-16 h-16 rounded-xl bg-white border border-gray-100 shadow-sm text-blue-600">
                                                    <span class="text-xs font-bold uppercase"><?= date('M', strtotime($activity['event_date'])) ?></span>
                                                    <span class="text-xl font-black"><?= date('d', strtotime($activity['event_date'])) ?></span>
                                                </div>
                                                <div>
                                                    <h4 class="font-bold text-gray-900 group-hover:text-primary transition-colors"><?= htmlspecialchars($activity['title']) ?></h4>
                                                    <p class="text-xs text-gray-500"><i class="fa-solid fa-location-dot mr-1"></i><?= htmlspecialchars($activity['beach_name']) ?></p>
                                                </div>
                                            </div>
                                            <form action="profile.php" method="POST" onsubmit="return confirmCancel(event)" class="mt-4 md:mt-0">
                                                <input type="hidden" name="cancel_event" value="1">
                                                <input type="hidden" name="participant_id" value="<?= $activity['participant_id'] ?>">
                                                <button type="submit" class="px-5 py-2.5 bg-white border border-rose-200 text-rose-500 text-xs font-bold rounded-xl hover:bg-rose-50 transition-all flex items-center gap-2">
                                                    <i class="fa-solid fa-xmark"></i>
                                                    Cancel Registration
                                                </button>
                                            </form>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Completed Events -->
                        <div>
                            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                Completed Events
                            </h3>
                            <div class="space-y-4">
                                <?php if (empty($completed)): ?>
                                    <p class="text-sm text-gray-400 italic">You haven't completed any events yet.</p>
                                <?php else: ?>
                                    <?php foreach ($completed as $activity): ?>
                                        <div class="flex flex-col md:flex-row items-center justify-between p-5 rounded-2xl border border-gray-50 bg-white shadow-sm hover:shadow-md transition-all group">
                                            <div class="flex items-center space-x-5">
                                                <div class="flex flex-col items-center justify-center w-16 h-16 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-600">
                                                    <span class="text-xs font-bold uppercase"><?= date('M', strtotime($activity['event_date'])) ?></span>
                                                    <span class="text-xl font-black"><?= date('d', strtotime($activity['event_date'])) ?></span>
                                                </div>
                                                <div>
                                                    <h4 class="font-bold text-gray-900"><?= htmlspecialchars($activity['title']) ?></h4>
                                                    <?php if ($activity['status'] == 'Attended'): ?>
                                                        <p class="text-xs text-emerald-600 font-bold uppercase tracking-wider">Contribution Verified</p>
                                                    <?php else: ?>
                                                        <p class="text-xs text-amber-600 font-bold uppercase tracking-wider">Event Ended (Pending Verification)</p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            
                                            <div class="flex items-center gap-3 mt-4 md:mt-0">
                                                <?php if ($activity['certificates_released'] && $activity['status'] == 'Attended'): ?>
                                                    <a href="certificate.php?event_id=<?= $activity['event_id'] ?>&user_id=<?= $user_id ?>" target="_blank" class="px-5 py-2.5 bg-emerald-500 text-white text-xs font-bold rounded-xl hover:bg-black transition-all shadow-lg shadow-emerald-500/20 flex items-center gap-2">
                                                        <i class="fas fa-file-arrow-down"></i>
                                                        Download Certificate
                                                    </a>
                                                <?php elseif ($activity['certificates_released'] && $activity['status'] == 'Registered'): ?>
                                                    <span class="px-5 py-2.5 bg-rose-50 text-rose-500 text-[10px] font-bold rounded-xl flex items-center gap-2 cursor-not-allowed border border-rose-100">
                                                        <i class="fas fa-user-xmark"></i>
                                                        Absent (No Certificate)
                                                    </span>
                                                <?php else: ?>
                                                    <span class="px-5 py-2.5 bg-gray-100 text-gray-400 text-[10px] font-bold rounded-xl flex items-center gap-2 cursor-not-allowed">
                                                        <i class="fas fa-clock"></i>
                                                        Certificate Unavailable
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                    <?php else: ?>
                        <div class="py-12 text-center">
                            <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 text-gray-300">
                                <i class="fa-solid fa-calendar-xmark text-2xl"></i>
                            </div>
                            <p class="text-gray-500">No activities found yet. Join your first cleanup event today!</p>
                            <a href="events.php" class="inline-block mt-4 text-sm font-bold text-primary hover:underline">Browse Events</a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Report History Section -->
                <div class="bg-white rounded-3xl p-8 border border-gray-100 shadow-sm mt-8">
                    <div class="flex items-center justify-between mb-8">
                        <h2 class="text-2xl font-bold text-gray-900">Report History</h2>
                        <i class="fa-solid fa-flag text-rose-500 text-xl"></i>
                    </div>

                    <?php if (!empty($user_reports)): ?>
                        <div class="space-y-6">
                            <?php foreach ($user_reports as $report): ?>
                                <div class="p-6 rounded-2xl border border-gray-50 bg-slate-50/50 hover:bg-white hover:shadow-md transition-all group">
                                    <div class="flex flex-col md:flex-row gap-6">
                                        <!-- Report Image/Placeholder -->
                                        <div class="w-full md:w-32 h-32 rounded-xl overflow-hidden bg-gray-100 flex-shrink-0">
                                            <?php if (!empty($report['image'])): ?>
                                                <img src="<?= htmlspecialchars($report['image']) ?>" alt="Report" class="w-full h-full object-cover">
                                            <?php else: ?>
                                                <div class="w-full h-full flex flex-col items-center justify-center text-gray-300">
                                                    <i class="fa-solid fa-image text-2xl mb-1"></i>
                                                    <span class="text-[10px] uppercase font-bold">No Image</span>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="flex-1">
                                            <div class="flex items-start justify-between">
                                                <div>
                                                    <div class="flex items-center space-x-2 mb-1">
                                                        <span class="text-xs font-bold px-2 py-0.5 rounded bg-gray-100 text-gray-600 uppercase"><?= htmlspecialchars($report['type']) ?></span>
                                                        <span class="text-xs text-gray-400"><?= date('M d, Y', strtotime($report['created_at'])) ?></span>
                                                    </div>
                                                    <h4 class="font-bold text-gray-900"><?= htmlspecialchars($report['beach_name']) ?></h4>
                                                </div>
                                                
                                                <!-- Status Badge -->
                                                <?php 
                                                    $statusClass = "bg-amber-100 text-amber-600";
                                                    if ($report['status'] == 'Resolved') $statusClass = "bg-emerald-100 text-emerald-600";
                                                    if ($report['status'] == 'Dismissed') $statusClass = "bg-gray-100 text-gray-600";
                                                ?>
                                                <span class="px-3 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider <?= $statusClass ?>">
                                                    <?= $report['status'] ?>
                                                </span>
                                            </div>
                                            
                                            <p class="text-sm text-gray-500 mt-2 line-clamp-2 italic">
                                                "<?= htmlspecialchars($report['description']) ?>"
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="py-12 text-center">
                            <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 text-gray-300">
                                <i class="fa-solid fa-flag text-2xl"></i>
                            </div>
                            <p class="text-gray-500">No reports found. You haven't reported any issues yet.</p>
                            <a href="report.php" class="inline-block mt-4 text-sm font-bold text-primary hover:underline">Report an Issue</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            </div>
        </div>
    </div>
</main>

<?php include "includes/footer.php"; ?>

<script>
    <?php if (isset($_GET['cancelled'])): ?>
    Swal.fire({
        icon: 'success',
        title: 'Registration Cancelled',
        text: 'You have cancelled the registration for the event. Please feel free to re-join anytime before the date of the event.',
        confirmButtonColor: '#512DA8'
    });
    <?php endif; ?>

    function previewImage(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                const img = input.closest('.relative').querySelector('img');
                const icon = input.closest('.relative').querySelector('.fa-user');
                if (img) {
                    img.src = e.target.result;
                } else if (icon) {
                    // Replace icon with image if first upload
                    const container = icon.parentElement;
                    container.innerHTML = `<img src="${e.target.result}" alt="Profile" class="w-full h-full object-cover">`;
                }
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    function confirmCancel(event) {
        event.preventDefault();
        const form = event.target;
        
        Swal.fire({
            title: 'Are you sure?',
            text: "Do you really want to cancel your registration for this event?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#512DA8',
            confirmButtonText: 'Yes, cancel it!',
            cancelButtonText: 'No, keep it'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
        return false;
    }
        function showBadgeDetails(name, description, image) {
            Swal.fire({
                title: `<span class="font-outfit font-bold">${name}</span>`,
                text: description,
                imageUrl: image,
                imageWidth: 150,
                imageHeight: 150,
                imageAlt: name,
                confirmButtonText: 'Awesome!',
                confirmButtonColor: '#512DA8',
                customClass: {
                    popup: 'rounded-[2rem]',
                    confirmButton: 'rounded-full px-8 py-2'
                }
            });
        }
    </script>
</body>
</html>
