<?php
$page_title = "Dashboard";
$page_subtitle = "Overview of system statistics";
include "admin_auth.php";
include "admin_header.php";

// Fetch statistics
try {
    // Total Users by Role
    $stmt = $conn->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
    $user_stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $total_users = array_sum($user_stats);
    
    // Total Beaches by Status
    $stmt = $conn->query("SELECT status, COUNT(*) as count FROM beaches GROUP BY status");
    $beach_stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $total_beaches = array_sum($beach_stats);
    
    // Total Events by Status
    $stmt = $conn->query("SELECT status, COUNT(*) as count FROM events GROUP BY status");
    $event_stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $total_events = array_sum($event_stats);
    
    // Total Reports by Status
    $stmt = $conn->query("SELECT status, COUNT(*) as count FROM reports GROUP BY status");
    $report_stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $total_reports = array_sum($report_stats);
    
    // Recent Activity - Latest 10 reports
    $stmt = $conn->query("
        SELECT r.*, u.full_name as reporter_name, b.name as beach_name 
        FROM reports r 
        JOIN users u ON r.user_id = u.id 
        JOIN beaches b ON r.beach_id = b.id 
        ORDER BY r.created_at DESC 
        LIMIT 10
    ");
    $recent_reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Database Error: " . $e->getMessage();
}
?>

<!-- Dashboard Content -->
<div class="space-y-6">
    
    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        
        <!-- Users Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-users text-blue-600 text-xl"></i>
                </div>
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Users</span>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 mb-2"><?= number_format($total_users) ?></h3>
            <div class="flex items-center space-x-4 text-xs text-gray-600">
                <span><i class="fas fa-shield-halved text-purple-500 mr-1"></i><?= $user_stats['admin'] ?? 0 ?> Admin</span>
                <span><i class="fas fa-user text-blue-500 mr-1"></i><?= $user_stats['user'] ?? 0 ?> Users</span>
            </div>
            <a href="users.php" class="mt-4 inline-block text-sm font-semibold text-primary hover:text-secondary">
                Manage Users <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
        
        <!-- Beaches Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-emerald-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-umbrella-beach text-emerald-600 text-xl"></i>
                </div>
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Beaches</span>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 mb-2"><?= number_format($total_beaches) ?></h3>
            <div class="flex items-center space-x-3 text-xs text-gray-600">
                <span><i class="fas fa-circle text-emerald-500 mr-1"></i><?= $beach_stats['Excellent'] ?? 0 ?> Excellent</span>
                <span><i class="fas fa-circle text-amber-500 mr-1"></i><?= $beach_stats['Good'] ?? 0 ?> Good</span>
                <span><i class="fas fa-circle text-rose-500 mr-1"></i><?= $beach_stats['Needs Attention'] ?? 0 ?> Needs Attention</span>
            </div>
            <a href="beaches.php" class="mt-4 inline-block text-sm font-semibold text-primary hover:text-secondary">
                Manage Beaches <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
        
        <!-- Events Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-calendar-alt text-purple-600 text-xl"></i>
                </div>
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Events</span>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 mb-2"><?= number_format($total_events) ?></h3>
            <div class="flex items-center space-x-3 text-xs text-gray-600">
                <span><i class="fas fa-clock text-blue-500 mr-1"></i><?= $event_stats['Upcoming'] ?? 0 ?> Upcoming</span>
                <span><i class="fas fa-check-circle text-emerald-500 mr-1"></i><?= $event_stats['Completed'] ?? 0 ?> Completed</span>
                <span><i class="fas fa-ban text-rose-500 mr-1"></i><?= $event_stats['Cancelled'] ?? 0 ?> Cancelled</span>
            </div>
            <a href="events.php" class="mt-4 inline-block text-sm font-semibold text-primary hover:text-secondary">
                Manage Events <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
        
        <!-- Reports Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-flag text-orange-600 text-xl"></i>
                </div>
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Reports</span>
            </div>
            <h3 class="text-3xl font-bold text-gray-900 mb-2"><?= number_format($total_reports) ?></h3>
            <div class="flex items-center space-x-3 text-xs text-gray-600">
                <span><i class="fas fa-circle text-amber-500 mr-1"></i><?= $report_stats['Pending'] ?? 0 ?> Pending</span>
                <span><i class="fas fa-circle text-emerald-500 mr-1"></i><?= $report_stats['Resolved'] ?? 0 ?> Resolved</span>
            </div>
            <a href="reports.php" class="mt-4 inline-block text-sm font-semibold text-primary hover:text-secondary">
                Manage Reports <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
        
    </div>

    <!-- Recent Activity -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-lg font-bold text-gray-900">Recent Reports</h3>
            <a href="reports.php" class="text-sm font-semibold text-primary hover:text-secondary">View All</a>
        </div>
        <div class="divide-y divide-gray-100">
            <?php if (!empty($recent_reports)): ?>
                <?php foreach ($recent_reports as $report): ?>
                    <div class="px-6 py-4 hover:bg-gray-50 transition-colors">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center space-x-3 mb-2">
                                    <span class="px-2 py-1 rounded text-xs font-bold uppercase
                                        <?php
                                            if ($report['type'] == 'Cleanup') echo 'bg-blue-100 text-blue-700';
                                            elseif ($report['type'] == 'Incident') echo 'bg-rose-100 text-rose-700';
                                            else echo 'bg-gray-100 text-gray-700';
                                        ?>">
                                        <?= htmlspecialchars($report['type']) ?>
                                    </span>
                                    <span class="px-2 py-1 rounded text-xs font-bold uppercase
                                        <?php
                                            if ($report['status'] == 'Pending') echo 'bg-amber-100 text-amber-700';
                                            elseif ($report['status'] == 'Resolved') echo 'bg-emerald-100 text-emerald-700';
                                            else echo 'bg-gray-100 text-gray-700';
                                        ?>">
                                        <?= htmlspecialchars($report['status']) ?>
                                    </span>
                                </div>
                                <p class="text-sm text-gray-900 font-medium mb-1"><?= htmlspecialchars($report['description']) ?></p>
                                <div class="flex items-center space-x-4 text-xs text-gray-500">
                                    <span><i class="fas fa-user mr-1"></i><?= htmlspecialchars($report['reporter_name']) ?></span>
                                    <span><i class="fas fa-map-marker-alt mr-1"></i><?= htmlspecialchars($report['beach_name']) ?></span>
                                    <span><i class="far fa-clock mr-1"></i><?= date('M j, Y g:i A', strtotime($report['created_at'])) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="px-6 py-12 text-center text-gray-500">
                    <i class="fas fa-inbox text-4xl mb-3 text-gray-300"></i>
                    <p>No recent reports</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <a href="users.php" class="bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-xl p-6 hover:shadow-lg transition-shadow group">
            <i class="fas fa-user-plus text-3xl mb-3 group-hover:scale-110 transition-transform"></i>
            <h4 class="font-bold text-lg">Add New User</h4>
            <p class="text-sm text-blue-100">Create a new user account</p>
        </a>
        <a href="beaches.php" class="bg-gradient-to-br from-emerald-500 to-emerald-600 text-white rounded-xl p-6 hover:shadow-lg transition-shadow group">
            <i class="fas fa-plus-circle text-3xl mb-3 group-hover:scale-110 transition-transform"></i>
            <h4 class="font-bold text-lg">Add New Beach</h4>
            <p class="text-sm text-emerald-100">Register a new beach location</p>
        </a>
        <a href="events.php" class="bg-gradient-to-br from-purple-500 to-purple-600 text-white rounded-xl p-6 hover:shadow-lg transition-shadow group">
            <i class="fas fa-calendar-plus text-3xl mb-3 group-hover:scale-110 transition-transform"></i>
            <h4 class="font-bold text-lg">Create Event</h4>
            <p class="text-sm text-purple-100">Schedule a cleanup event</p>
        </a>
        <a href="reports.php" class="bg-gradient-to-br from-orange-500 to-orange-600 text-white rounded-xl p-6 hover:shadow-lg transition-shadow group">
            <i class="fas fa-tasks text-3xl mb-3 group-hover:scale-110 transition-transform"></i>
            <h4 class="font-bold text-lg">Review Reports</h4>
            <p class="text-sm text-orange-100">Manage pending reports</p>
        </a>
    </div>

</div>

<?php include "admin_footer.php"; ?>
