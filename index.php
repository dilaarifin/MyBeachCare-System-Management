<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include "includes/db_conn.php";
$isLoggedIn = isset($_SESSION['user_id']);

// Internal AJAX Handler for Load More Beaches
if (isset($_GET['load_more_beaches'])) {
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    $limit = 6;
    $state = $_GET['state'] ?? '';
    $status = $_GET['status'] ?? '';
    $search = $_GET['search'] ?? '';

    try {
        $where = [];
        $params = [];

        if (!empty($state)) {
            $where[] = "state = :state";
            $params[':state'] = $state;
        }

        if (!empty($status)) {
            $where[] = "status = :status";
            $params[':status'] = $status;
        }

        if (!empty($search)) {
            $where[] = "(name LIKE :search OR location LIKE :search OR state LIKE :search)";
            $params[':search'] = "%" . $search . "%";
        }

        $where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        
        $query = "SELECT * FROM beaches $where_clause ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $conn->prepare($query);
        foreach ($params as $key => $val) $stmt->bindValue($key, $val);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $beaches = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($beaches)) {
            exit('');
        }

        // Fetch user's votes if logged in
        $user_votes = [];
        if ($isLoggedIn) {
            $votes_stmt = $conn->prepare("SELECT beach_id FROM beach_votes WHERE user_id = ?");
            $votes_stmt->execute([(int)$_SESSION['user_id']]);
            $user_votes = $votes_stmt->fetchAll(PDO::FETCH_COLUMN);
        }

        foreach ($beaches as $beach): 
            $hasVoted = in_array($beach['id'], $user_votes);
        ?>
            <div class="bg-white rounded-lg overflow-hidden shadow-md hover:shadow-xl transition-all duration-300 group border border-gray-100 flex flex-col h-full">
                <!-- Image Container -->
                <div class="relative aspect-square overflow-hidden">
                    <img src="<?= htmlspecialchars($beach['image']) ?>" alt="<?= htmlspecialchars($beach['name']) ?>" 
                        class="w-full h-full object-cover transform group-hover:scale-110 transition-transform duration-700">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    
                    <!-- Status Badge (Top Right) -->
                     <div class="absolute top-4 right-4">
                        <?php
                            $statusColor = 'bg-emerald-500';
                            $statusIcon = 'fa-check-circle';
                            if ($beach['status'] == 'Needs Attention') {
                                $statusColor = 'bg-rose-500';
                                $statusIcon = 'fa-exclamation-circle';
                            } elseif ($beach['status'] == 'Good') {
                                $statusColor = 'bg-amber-500';
                                $statusIcon = 'fa-check';
                            }
                        ?>
                        <span class="<?= $statusColor ?> text-white text-xs font-bold px-3 py-1.5 rounded-full shadow-lg backdrop-blur-md flex items-center gap-1.5 uppercase tracking-wider">
                            <i class="fas <?= $statusIcon ?>"></i>
                            <?= htmlspecialchars($beach['status']) ?>
                        </span>
                    </div>
                </div>

                <!-- Content -->
                <div class="p-6 flex-1 flex flex-col">
                    <div class="mb-4">
                        <h3 class="text-xl font-bold text-gray-900 mb-1 group-hover:text-primary transition-colors"><?= htmlspecialchars($beach['name']) ?></h3>
                        <p class="text-gray-400 text-sm font-medium"><?= htmlspecialchars($beach['state']) ?></p>
                    </div>
                    
                    <!-- Mini Stats Grid -->
                    <div class="grid grid-cols-1 gap-3 mb-6">
                        <div class="bg-gray-50 p-3 rounded-xl border border-gray-100 flex items-center justify-between">
                            <span class="text-gray-400 text-[10px] font-bold uppercase tracking-wider">Votes</span>
                            <span class="text-gray-900 font-bold text-sm">
                                <i class="fas fa-vote-yea text-primary mr-1.5"></i><span class="vote-count-<?= $beach['id'] ?>"><?= number_format($beach['clean_votes']) ?></span>
                            </span>
                        </div>
                    </div>
                    
                     <div class="space-y-4 mb-6">
                         <?php if (!empty($beach['description'])): ?>
                            <p class="text-xs text-gray-500 leading-relaxed line-clamp-3">
                                <?= htmlspecialchars($beach['description']) ?>
                            </p>
                        <?php endif; ?>
                     </div>

                        <!-- Vote Button -->
                        <div class="mt-auto">
                            <?php if ($hasVoted): ?>
                                <button disabled 
                                   class="w-full px-6 py-3 bg-gray-400 text-white text-xs font-bold uppercase tracking-wider cursor-not-allowed shadow-lg">
                                    <i class="fas fa-check mr-2"></i>Voted
                                </button>
                            <?php else: ?>
                                <button onclick="handleVote(<?= $beach['id'] ?>, this)" 
                                   class="w-full bg-black text-white font-bold py-4 rounded-xl hover:bg-gray-800 active:scale-95 transition-all shadow-lg flex items-center justify-center group/btn vote-btn-<?= $beach['id'] ?>">
                                    <span>VOTE TO CLEAN</span>
                                    <i class="fas fa-arrow-right ml-2 opacity-0 -translate-x-2  group-hover/btn:opacity-100 group-hover/btn:translate-x-0 transition-all"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                </div>
            </div>
        <?php endforeach;
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
    exit;
}

// Handle Voting (AJAX Handler)
if (isset($_GET['ajax_vote_id'])) {
    if (!$isLoggedIn) {
        echo json_encode(['status' => 'error', 'message' => 'Please sign in to vote!']);
        exit;
    }
    
    $vote_id = (int)$_GET['ajax_vote_id'];
    $user_id = (int)$_SESSION['user_id'];
    
    try {
        // Check if user has already voted for this beach
        $check_stmt = $conn->prepare("SELECT id FROM beach_votes WHERE user_id = ? AND beach_id = ?");
        $check_stmt->execute([$user_id, $vote_id]);
        
        if ($check_stmt->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'You have already voted for this beach!']);
            exit;
        }
        
        // Record the vote
        $insert_stmt = $conn->prepare("INSERT INTO beach_votes (user_id, beach_id) VALUES (?, ?)");
        $insert_stmt->execute([$user_id, $vote_id]);
        
        // Update vote count
        $stmt = $conn->prepare("UPDATE beaches SET clean_votes = clean_votes + 1 WHERE id = ?");
        $stmt->execute([$vote_id]);
        
        // Fetch new count
        $stmt = $conn->prepare("SELECT clean_votes FROM beaches WHERE id = ?");
        $stmt->execute([$vote_id]);
        $new_votes = $stmt->fetchColumn();

        // Check for Badge
        include "includes/gamification.php";
        check_voting_badges($user_id);
        
        echo json_encode(['status' => 'success', 'message' => 'Vote cast!', 'new_votes' => $new_votes]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error.']);
    }
    exit;
}

// Handle Voting (Traditional PHP fallback if needed, but we prefer AJAX)
if (isset($_GET['vote_id'])) {
    if (!$isLoggedIn) {
        $_SESSION['error_msg'] = "Please sign in to vote for a beach cleanup!";
        header("Location: index.php#beaches");
        exit();
    }
    
    $vote_id = (int)$_GET['vote_id'];
    try {
        $stmt = $conn->prepare("UPDATE beaches SET clean_votes = clean_votes + 1 WHERE id = ?");
        $stmt->execute([$vote_id]);
        $_SESSION['success_msg'] = "Vote cast! Thank you for supporting this cleanup.";
    } catch (PDOException $e) {
        $_SESSION['error_msg'] = "Unable to register vote. Please try again.";
    }
    header("Location: index.php#beaches");
    exit();
}

include "includes/header.php";

// Fetch counts for stats
$stats = [
    'beaches' => 0,
    'volunteers' => 0,
    'clean' => 0,
    'events' => 0
];

try {
// Beaches Monitored
    $stmt = $conn->prepare("SELECT COUNT(*) FROM beaches");
    $stmt->execute();
    $stats['beaches'] = $stmt->fetchColumn();

    // Active Participants (Unique participants)
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT user_id) FROM event_participants");
    $stmt->execute();
    $stats['volunteers'] = $stmt->fetchColumn();

    // Clean Beaches (Excellent only)
    $stmt = $conn->prepare("SELECT COUNT(*) FROM beaches WHERE status = 'Excellent'");
    $stmt->execute();
    $stats['clean'] = $stmt->fetchColumn();

    // Events
    $stmt = $conn->prepare("SELECT COUNT(*) FROM events WHERE event_date >= NOW()");
    $stmt->execute();
    $stats['events'] = $stmt->fetchColumn();

    // Fetch Unique States
    $states_stmt = $conn->query("SELECT DISTINCT state FROM beaches WHERE state IS NOT NULL AND state != '' ORDER BY state ASC");
    $states = $states_stmt->fetchAll(PDO::FETCH_COLUMN);

    // Initial Filter Setup
    $where = [];
    $params = [];

    if (!empty($_GET['state'])) {
        $where[] = "state = :state";
        $params[':state'] = $_GET['state'];
    }

    if (!empty($_GET['status'])) {
        $where[] = "status = :status";
        $params[':status'] = $_GET['status'];
    }

    if (!empty($_GET['search'])) {
        $where[] = "(name LIKE :search OR location LIKE :search OR state LIKE :search)";
        $params[':search'] = "%" . $_GET['search'] . "%";
    }

    $where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
    
    // Fetch user's votes if logged in
    $user_votes = [];
    if ($isLoggedIn) {
        $votes_stmt = $conn->prepare("SELECT beach_id FROM beach_votes WHERE user_id = ?");
        $votes_stmt->execute([(int)$_SESSION['user_id']]);
        $user_votes = $votes_stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    // Get total count of beaches for "Show More" button logic
    $count_query = "SELECT COUNT(*) FROM beaches $where_clause";
    $count_stmt = $conn->prepare($count_query);
    foreach ($params as $key => $val) $count_stmt->bindValue($key, $val);
    $count_stmt->execute();
    $total_beaches = $count_stmt->fetchColumn();
    
    // Fetch Beaches for display (Initial limit 6)
    $query = "SELECT * FROM beaches $where_clause ORDER BY created_at DESC LIMIT 6";
    $stmt = $conn->prepare($query);
    foreach ($params as $key => $val) $stmt->bindValue($key, $val);
    $stmt->execute();
    $beaches = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Database Error: " . $e->getMessage();
}
?>

    <!-- Hero Section & Header Area -->
    <section class="relative min-h-[80vh] flex items-center pt-24 pb-20 overflow-hidden">
        <!-- Video Background -->
        <div class="absolute inset-0 z-0">
            <video autoplay muted loop playsinline class="w-full h-full object-cover">
                <source src="vid/main_video.mp4" type="video/mp4">
            </video>
            <!-- Gradient Overlay -->
            <div class="absolute inset-0 bg-gradient-to-b from-black/70 via-black/40 to-white"></div>
        </div>
        
        <div class="container mx-auto px-6 relative z-10">
            <!-- Hero Text Content -->
            <div class="flex flex-col items-center justify-center text-center space-y-8 mb-16">
                <div class="space-y-4">
                    <h2 class="text-6xl md:text-7xl font-outfit font-black tracking-tight text-white reveal">
                        Preserving Malaysia's <br><span class="text-emerald-400 italic">Coastal Treasures</span>
                    </h2>
                </div>
                <p class="max-w-2xl text-xl text-white/80 leading-relaxed font-light reveal" style="transition-delay: 200ms">
                    Join Malaysia's most dedicated community of environmental stewards. Real-time monitoring, cleanup events, and impact tracking across our beautiful coastlines.
                </p>
                
                <div class="flex items-center gap-4 pt-4 reveal" style="transition-delay: 400ms">
                    <a href="report.php" class="px-8 py-4 bg-emerald-500 hover:bg-emerald-600 text-white font-black rounded-2xl shadow-xl shadow-emerald-500/20 transition-all hover:scale-105">
                        REPORT ISSUES
                    </a>
                    <a href="events.php" class="px-8 py-4 bg-white/10 hover:bg-white/20 text-white font-black rounded-2xl backdrop-blur-md border border-white/20 transition-all hover:scale-105">
                        JOIN EVENTS
                    </a>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mt-12">
                <!-- Beaches Monitored -->
                <div class="glass p-6 rounded-2xl flex items-center space-x-4 border border-gray-100 shadow-sm transition-all duration-300 hover:bg-white">
                    <div class="w-12 h-12 flex items-center justify-center bg-blue-100 text-blue-600 rounded-xl">
                        <i class="fas fa-map-marker-alt text-xl"></i>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Beaches Monitored</p>
                        <p class="text-2xl font-bold text-gray-900 leading-tight"><?= $stats['beaches'] ?></p>
                    </div>
                </div>

                <!-- Active Volunteers -->
                <div class="glass p-6 rounded-2xl flex items-center space-x-4 border border-gray-100 shadow-sm transition-all duration-300 hover:bg-white">
                    <div class="w-12 h-12 flex items-center justify-center bg-emerald-100 text-emerald-600 rounded-xl">
                        <i class="fas fa-users text-xl"></i>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Active Participants</p>
                        <p class="text-2xl font-bold text-gray-900 leading-tight"><?= $stats['volunteers'] ?></p>
                    </div>
                </div>

                <!-- Clean Beaches -->
                <div class="glass p-6 rounded-2xl flex items-center space-x-4 border border-gray-100 shadow-sm transition-all duration-300 hover:bg-white">
                    <div class="w-12 h-12 flex items-center justify-center bg-purple-100 text-purple-600 rounded-xl">
                        <i class="fas fa-broom text-xl"></i>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Clean Beaches</p>
                        <p class="text-2xl font-bold text-gray-900 leading-tight"><?= $stats['clean'] ?></p>
                    </div>
                </div>

                <!-- Upcoming Events -->
                <div class="glass p-6 rounded-2xl flex items-center space-x-4 border border-gray-100 shadow-sm transition-all duration-300 hover:bg-white">
                    <div class="w-12 h-12 flex items-center justify-center bg-orange-100 text-orange-600 rounded-xl">
                        <i class="fas fa-calendar-alt text-xl"></i>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Upcoming Events</p>
                        <p class="text-2xl font-bold text-gray-900 leading-tight"><?= $stats['events'] ?></p>
                    </div>
                </div>
            </div>
            <!-- Success/Error Messages -->
            <?php if (isset($_SESSION['success_msg'])): ?>
                <div class="mt-8 bg-emerald-50 border border-emerald-200 text-emerald-700 px-6 py-4 rounded-2xl flex items-center shadow-sm reveal">
                    <i class="fas fa-check-circle mr-3 text-xl"></i>
                    <span class="font-medium"><?= $_SESSION['success_msg']; unset($_SESSION['success_msg']); ?></span>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error_msg'])): ?>
                <div class="mt-8 bg-rose-50 border border-rose-200 text-rose-700 px-6 py-4 rounded-2xl flex items-center shadow-sm reveal">
                    <i class="fas fa-exclamation-circle mr-3 text-xl"></i>
                    <span class="font-medium"><?= $_SESSION['error_msg']; unset($_SESSION['error_msg']); ?></span>
                </div>
            <?php endif; ?>
        </div>
    </section>
    


    <!-- Content Section -->
    <main class="container mx-auto px-6 py-12">
        <!-- Section Header -->
        <div class="flex flex-col md:flex-row md:items-end justify-between mb-10 gap-6">
            <div class="space-y-2">
                <h2 class="text-2xl font-bold text-gray-900">Beach Cleanliness Status</h2>
                <p class="text-gray-500 text-sm">Real time updates on <?= $stats['beaches'] ?> monitored beaches across Malaysia</p>
                
                <!-- Filters -->
                <div class="flex flex-wrap gap-2 mt-4">
                    <a href="index.php#beaches" class="px-4 py-1.5 rounded-full text-sm font-medium <?= empty($_GET['status']) ? 'bg-gray-900 text-white' : 'bg-white text-gray-600 border border-gray-200' ?> transition-all shadow-md">All Status</a>
                    <a href="?status=Excellent#beaches" class="px-4 py-1.5 rounded-full text-sm font-medium <?= ($_GET['status']??'') == 'Excellent' ? 'bg-emerald-500 text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-emerald-50' ?> transition-all">Excellent</a>
                    <a href="?status=Good#beaches" class="px-4 py-1.5 rounded-full text-sm font-medium <?= ($_GET['status']??'') == 'Good' ? 'bg-amber-500 text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-amber-50' ?> transition-all">Good</a>
                    <a href="?status=Needs Attention#beaches" class="px-4 py-1.5 rounded-full text-sm font-medium <?= ($_GET['status']??'') == 'Needs Attention' ? 'bg-rose-500 text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-rose-50' ?> transition-all">Needs Attention</a>
                </div>
            </div>

            <!-- Search and State Filter -->
            <div class="flex flex-col md:flex-row gap-4 w-full md:w-auto">
                <select onchange="location.href='?state=' + this.value + '#beaches'" 
                    class="px-4 py-2.5 rounded-xl border border-gray-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all shadow-sm text-sm font-medium">
                    <option value="">All States</option>
                    <?php foreach ($states as $state): ?>
                        <option value="<?= htmlspecialchars($state) ?>" <?= ($_GET['state']??'') == $state ? 'selected' : '' ?>><?= htmlspecialchars($state) ?></option>
                    <?php endforeach; ?>
                </select>

                <form action="index.php#beaches" method="GET" class="relative w-full md:w-80">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="text" name="search" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" placeholder="Search beaches..." 
                        class="w-full pl-11 pr-4 py-2.5 rounded-xl border border-gray-200 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all shadow-sm">
                </form>
            </div>
        </div>

        <!-- Beach Grid -->
        <div id="beach-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php if (!empty($beaches)): ?>
                <?php foreach ($beaches as $beach): ?>
                <div class="bg-white rounded-lg overflow-hidden shadow-md hover:shadow-xl transition-all duration-300 group border border-gray-100 flex flex-col h-full">
                    <!-- Image Container -->
                    <div class="relative aspect-square overflow-hidden">
                        <img src="<?= htmlspecialchars($beach['image']) ?>" alt="<?= htmlspecialchars($beach['name']) ?>" 
                            class="w-full h-full object-cover transform group-hover:scale-110 transition-transform duration-700">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                        
                        <!-- Status Badge (Top Right) -->
                         <div class="absolute top-4 right-4">
                            <?php
                                $statusColor = 'bg-emerald-500';
                                $statusIcon = 'fa-check-circle';
                                if ($beach['status'] == 'Needs Attention') {
                                    $statusColor = 'bg-rose-500';
                                    $statusIcon = 'fa-exclamation-circle';
                                } elseif ($beach['status'] == 'Good') {
                                    $statusColor = 'bg-amber-500';
                                    $statusIcon = 'fa-check';
                                }
                            ?>
                            <span class="<?= $statusColor ?> text-white text-xs font-bold px-3 py-1.5 rounded-full shadow-lg backdrop-blur-md flex items-center gap-1.5 uppercase tracking-wider">
                                <i class="fas <?= $statusIcon ?>"></i>
                                <?= htmlspecialchars($beach['status']) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="p-6 flex-1 flex flex-col">
                        <div class="mb-4">
                            <h3 class="text-xl font-bold text-gray-900 mb-1 group-hover:text-primary transition-colors"><?= htmlspecialchars($beach['name']) ?></h3>
                            <p class="text-gray-400 text-sm font-medium"><?= htmlspecialchars($beach['state']) ?></p>
                        </div>
                        
                        <!-- Mini Stats Grid -->
                        <div class="grid grid-cols-1 gap-3 mb-6">
                            <div class="bg-gray-50 p-3 rounded-xl border border-gray-100 flex items-center justify-between">
                                <span class="text-gray-400 text-[10px] font-bold uppercase tracking-wider">Votes</span>
                                <span class="text-gray-900 font-bold text-sm">
                                    <i class="fas fa-vote-yea text-primary mr-1.5"></i><span class="vote-count-<?= $beach['id'] ?>"><?= number_format($beach['clean_votes']) ?></span>
                                </span>
                            </div>
                        </div>
                        
                         <div class="space-y-4 mb-6">
                             <?php if (!empty($beach['description'])): ?>
                                <p class="text-xs text-gray-500 leading-relaxed line-clamp-3">
                                    <?= htmlspecialchars($beach['description']) ?>
                                </p>
                            <?php endif; ?>
                         </div>

                        <!-- Vote Button -->
                        <div class="mt-auto">
                            <?php 
                                $hasVoted = in_array($beach['id'], $user_votes);
                            ?>
                            <?php if ($hasVoted): ?>
                                <button disabled 
                                   class="w-full px-6 py-3 bg-gray-400 text-white text-xs font-bold uppercase tracking-wider cursor-not-allowed shadow-lg">
                                    <i class="fas fa-check mr-2"></i>Voted
                                </button>
                            <?php else: ?>
                                <button onclick="handleVote(<?= $beach['id'] ?>, this)" 
                                   class="w-full bg-black text-white font-bold py-4 rounded-xl hover:bg-gray-800 active:scale-95 transition-all shadow-lg flex items-center justify-center group/btn vote-btn-<?= $beach['id'] ?>">
                                    <span>VOTE TO CLEAN</span>
                                    <i class="fas fa-arrow-right ml-2 opacity-0 -translate-x-2  group-hover/btn:opacity-100 group-hover/btn:translate-x-0 transition-all"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>    
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-span-full py-20 text-center">
                    <div class="text-gray-300 text-6xl mb-4">
                        <i class="fas fa-umbrella-beach"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-600">No beaches found</h3>
                    <p class="text-gray-400">Try adjusting your filters or search keywords.</p>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($total_beaches > 6): ?>
            <div class="mt-16 text-center">
                <button id="load-more" data-offset="6" 
                    class="px-10 py-4 bg-white border-2 border-gray-100 text-gray-600 font-bold rounded-full hover:bg-gray-50 hover:border-gray-200 transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                    Show More Beaches
                </button>
            </div>
        <?php endif; ?>
    </main>


<script>
        async function handleVote(beachId, btn) {
            try {
                const response = await fetch(`index.php?ajax_vote_id=${beachId}`);
                const data = await response.json();
                
                if (data.status === 'success') {
                    // Update vote count display ONLY
                    const voteCountElements = document.querySelectorAll(`.vote-count-${beachId}`);
                    voteCountElements.forEach(el => {
                        el.textContent = data.new_votes;
                    });

                    // Change button to "Voted" state
                    btn.disabled = true;
                    btn.className = 'w-full px-6 py-3 bg-gray-400 text-white text-xs font-bold uppercase tracking-wider cursor-not-allowed shadow-lg';
                    btn.innerHTML = '<i class="fas fa-check mr-2"></i>Voted';

                    // Success message
                    const Toast = Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true,
                        didOpen: (toast) => {
                            toast.addEventListener('mouseenter', Swal.stopTimer)
                            toast.addEventListener('mouseleave', Swal.resumeTimer)
                        }
                    });

                    Toast.fire({
                        icon: 'success',
                        title: 'Vote cast successfully!'
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: data.message || 'Something went wrong!',
                        confirmButtonColor: '#512DA8',
                        customClass: { popup: 'rounded-3xl' }
                    });
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }
</script>
<?php include "includes/footer.php"; ?>
```
