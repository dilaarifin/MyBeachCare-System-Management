<?php
include "admin_auth.php";

// --- Handle Form Actions (Traditional PHP) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    try {
        if ($action == 'create' || $action == 'update') {
            $id = isset($_POST['event_id']) ? (int)$_POST['event_id'] : null;
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $beach_id = (int)($_POST['beach_id'] ?? 0);
            $event_date = $_POST['event_date'] ?? '';
            $status = $_POST['status'] ?? 'Upcoming';
            $organizer_id = !empty($_POST['organizer_id']) ? $_POST['organizer_id'] : $_SESSION['user_id'];
            $leader_id = !empty($_POST['leader_id']) ? $_POST['leader_id'] : null;
            $image_path = $_POST['current_image'] ?? '';
            
            $provision_details = trim($_POST['provision_details'] ?? '');
            $provision_image = $_POST['current_provision_image'] ?? '';
            $kit_details = trim($_POST['kit_details'] ?? '');
            $kit_image = $_POST['current_kit_image'] ?? '';

            if (empty($title) || empty($beach_id) || empty($event_date)) {
                throw new Exception("Title, Beach, and Date are required.");
            }

            // Function to handle uploads
            function handleEventUpload($fileKey, $prefix) {
                if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = '../uploads/events/';
                    if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
                    
                    $file_ext = strtolower(pathinfo($_FILES[$fileKey]['name'], PATHINFO_EXTENSION));
                    $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    
                    if (in_array($file_ext, $allowed_ext)) {
                        $new_filename = uniqid($prefix) . '.' . $file_ext;
                        if (move_uploaded_file($_FILES[$fileKey]['tmp_name'], $upload_dir . $new_filename)) {
                            return 'uploads/events/' . $new_filename;
                        }
                    }
                }
                return null;
            }

            // Upload Main Image
            if ($new_path = handleEventUpload('image', 'event_')) $image_path = $new_path;
            
            // Upload Provision Image
            if ($new_path = handleEventUpload('provision_image_file', 'prov_')) $provision_image = $new_path;

            // Upload Kit Image
            if ($new_path = handleEventUpload('kit_image_file', 'kit_')) $kit_image = $new_path;

            $price = $_POST['price'] ?? 25.00; // Default to 25 if not set

            if ($action == 'create') {
                $stmt = $conn->prepare("INSERT INTO events (title, description, beach_id, organizer_id, leader_id, event_date, status, image, provision_details, provision_image, kit_details, kit_image, price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$title, $description, $beach_id, $organizer_id, $leader_id, $event_date, $status, $image_path, $provision_details, $provision_image, $kit_details, $kit_image, $price]);
                $_SESSION['success'] = "Event created successfully!";
            } else {
                $stmt = $conn->prepare("UPDATE events SET title=?, description=?, beach_id=?, organizer_id=?, leader_id=?, event_date=?, status=?, image=?, provision_details=?, provision_image=?, kit_details=?, kit_image=?, price=? WHERE id=?");
                $stmt->execute([$title, $description, $beach_id, $organizer_id, $leader_id, $event_date, $status, $image_path, $provision_details, $provision_image, $kit_details, $kit_image, $price, $id]);
                $_SESSION['success'] = "Event updated successfully!";
            }
            header("Location: events.php");
            exit();
        }

        if ($action == 'blast_certificate' && isset($_POST['id'])) {
            $id = (int)$_POST['id'];
            $participated_ids = isset($_POST['participated']) ? $_POST['participated'] : []; // Array of user IDs or participant IDs depending on form value. 
                                                                                             // Form sends participant ID (table id), not user_id.
            
            // 1. Release Certificates
            $stmt = $conn->prepare("UPDATE events SET certificates_released = 1 WHERE id = ?");
            $stmt->execute([$id]);
            
            // 2. Process Participants
            if (!empty($participated_ids)) {
                // Mark selected as Attended
                // Create placeholders for prepared statement
                $placeholders = implode(',', array_fill(0, count($participated_ids), '?'));
                $stmt_attend = $conn->prepare("UPDATE event_participants SET status = 'Attended' WHERE id IN ($placeholders)");
                $stmt_attend->execute($participated_ids);

                // Mark unselected as Registered (only if they were Registered or Attended, don't touch Cancelled)
                // We need to exclude the ones we just marked Attended
                $stmt_revert = $conn->prepare("UPDATE event_participants SET status = 'Registered' 
                                               WHERE event_id = ? 
                                               AND id NOT IN ($placeholders) 
                                               AND status IN ('Registered', 'Attended')");
                // Add event_id to the beginning of the params array
                $revert_params = array_merge([$id], $participated_ids);
                $stmt_revert->execute($revert_params);
            } else {
                // If NO checkboxes selected, revert everyone (except Cancelled) to Registered
                $stmt_reset = $conn->prepare("UPDATE event_participants SET status = 'Registered' 
                                              WHERE event_id = ? AND status IN ('Registered', 'Attended')");
                $stmt_reset->execute([$id]);
            }
            
            $_SESSION['success'] = "Certificates released and participants updated!";
            header("Location: events.php");
            exit();
        }

        if ($action == 'delete' && isset($_POST['id'])) {
            $id = (int)$_POST['id'];
            $stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['success'] = "Event deleted successfully!";
            header("Location: events.php");
            exit();
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
        header("Location: events.php");
        exit();
    }
}

include "admin_header.php";

// --- Fetch Data for Editing ---
$edit_data = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_data = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($edit_data) {
        $edit_data['event_date'] = date('Y-m-d\TH:i', strtotime($edit_data['event_date']));
    }
}

// Fetch events with pagination and beach details
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';

$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(e.title LIKE :search OR b.name LIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($status_filter)) {
    $where[] = "e.status = :status";
    $params[':status'] = $status_filter;
}

$where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

try {
    // Get total count
    $count_query = "SELECT COUNT(*) FROM events e LEFT JOIN beaches b ON e.beach_id = b.id $where_clause";
    $stmt = $conn->prepare($count_query);
    foreach ($params as $key => $val) $stmt->bindValue($key, $val);
    $stmt->execute();
    $total_events = $stmt->fetchColumn();
    $total_pages = ceil($total_events / $per_page);
    
    // Get events
    $query = "SELECT e.*, b.name as beach_name, b.state as beach_state, u.full_name as organizer_name, l.full_name as leader_name,
              (SELECT COUNT(*) FROM event_participants WHERE event_id = e.id AND status = 'Registered') as participant_count 
              FROM events e 
              LEFT JOIN beaches b ON e.beach_id = b.id 
              LEFT JOIN users u ON e.organizer_id = u.id 
              LEFT JOIN users l ON e.leader_id = l.id 
              $where_clause 
              ORDER BY e.event_date DESC 
              LIMIT $per_page OFFSET $offset";
    $stmt = $conn->prepare($query);
    foreach ($params as $key => $val) $stmt->bindValue($key, $val);
    $stmt->execute();
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get beaches and states for dropdowns
    $beaches_stmt = $conn->query("SELECT id, name, state FROM beaches ORDER BY name ASC");
    $all_beaches = $beaches_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $states_stmt = $conn->query("SELECT DISTINCT state FROM beaches WHERE state IS NOT NULL AND state != '' ORDER BY state ASC");
    $all_states = $states_stmt->fetchAll(PDO::FETCH_COLUMN);

    // Get users for organizer/leader selection
    $users_stmt = $conn->query("SELECT id, full_name, email FROM users ORDER BY full_name ASC");
    $all_users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Database Error: " . $e->getMessage();
}
?>

<!-- Events Management Content -->
<div class="space-y-6">
    
    <!-- Header Actions -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h3 class="text-2xl font-bold text-gray-900">All Events</h3>
            <p class="text-sm text-gray-500">Total: <?= number_format($total_events) ?> events</p>
        </div>
        <button onclick="openCreateModal()" class="px-6 py-3 bg-primary text-white font-bold rounded-lg hover:bg-secondary transition-colors shadow-md">
            <i class="fas fa-plus mr-2"></i>Create New Event
        </button>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
        <form method="GET" class="flex flex-col md:flex-row gap-4">
            <div class="flex-1">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                    placeholder="Search by title or beach name..." 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>
            <select name="status" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                <option value="">All Status</option>
                <option value="Upcoming" <?= $status_filter == 'Upcoming' ? 'selected' : '' ?>>Upcoming</option>
                <option value="Completed" <?= $status_filter == 'Completed' ? 'selected' : '' ?>>Completed</option>
                <option value="Cancelled" <?= $status_filter == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
            </select>
            <button type="submit" class="px-6 py-2 bg-gray-900 text-white font-semibold rounded-lg hover:bg-gray-800 transition-colors">
                <i class="fas fa-search mr-2"></i>Search
            </button>
            <?php if (!empty($search) || !empty($status_filter)): ?>
                <a href="events.php" class="px-6 py-2 bg-gray-200 text-gray-700 font-semibold rounded-lg hover:bg-gray-300 transition-colors text-center">
                    <i class="fas fa-times mr-2"></i>Clear
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Events Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (!empty($events)): ?>
            <?php foreach ($events as $event): ?>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow flex flex-col">
                    
                    <!-- Event Image -->
                    <div class="relative h-48 bg-gray-200">
                         <?php if (!empty($event['image'])): ?>
                            <img src="../<?= htmlspecialchars($event['image']) ?>" alt="<?= htmlspecialchars($event['title']) ?>" 
                                class="w-full h-full object-cover">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-indigo-50 to-purple-50">
                                <i class="fas fa-calendar-alt text-4xl text-gray-300"></i>
                            </div>
                        <?php endif; ?>
                        
                         <!-- State Badge -->
                         <?php if (!empty($event['beach_state'])): ?>
                            <div class="absolute top-3 right-3">
                                <span class="px-3 py-1 bg-black/50 backdrop-blur-md text-white rounded-lg text-xs font-bold uppercase shadow-lg">
                                    <?= htmlspecialchars($event['beach_state']) ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="p-5 flex-1">
                        <div class="flex justify-between items-start mb-3">
                            <span class="px-3 py-1 rounded-lg text-xs font-bold uppercase shadow-sm
                                <?php
                                    if ($event['status'] == 'Upcoming') echo 'bg-blue-100 text-blue-700';
                                    elseif ($event['status'] == 'Completed') echo 'bg-emerald-100 text-emerald-700';
                                    else echo 'bg-rose-100 text-rose-700';
                                ?>">
                                <?= $event['status'] ?>
                            </span>
                            <span class="text-xs text-gray-400 font-semibold">
                                <i class="far fa-calendar-alt mr-1"></i><?= date('M j, Y', strtotime($event['event_date'])) ?>
                            </span>
                        </div>
                        
                        <h4 class="text-lg font-bold text-gray-900 mb-2 line-clamp-2"><?= htmlspecialchars($event['title']) ?></h4>
                        
                        <div class="space-y-2 text-sm text-gray-600 mb-4">
                            <p><i class="fas fa-map-marker-alt text-primary mr-2"></i><?= htmlspecialchars($event['beach_name']) ?></p>
                            <p><i class="far fa-clock text-primary mr-2"></i><?= date('g:i A', strtotime($event['event_date'])) ?></p>
                            <p><i class="fas fa-users text-primary mr-2"></i><?= number_format($event['participant_count']) ?> joined</p>
                            <p class="text-xs italic text-gray-500">Org: <?= htmlspecialchars($event['organizer_name'] ?? 'N/A') ?></p>
                            <?php if (!empty($event['leader_name'])): ?>
                                <p class="text-xs italic text-gray-500">Lead: <?= htmlspecialchars($event['leader_name']) ?></p>
                            <?php endif; ?>
                            
                            <?php if ($event['status'] == 'Completed'): ?>
                                <div class="mt-2 text-xs font-bold">
                                    <?php if (!empty($event['certificates_released'])): ?>
                                        <span class="text-emerald-600"><i class="fas fa-check-circle mr-1"></i>Certificates Released</span>
                                    <?php else: ?>
                                        <span class="text-amber-600"><i class="fas fa-clock mr-1"></i>Certificates Not Released</span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <p class="text-gray-500 text-sm line-clamp-3 mb-4">
                            <?= htmlspecialchars($event['description']) ?>
                        </p>
                    </div>
                    
                    <!-- Actions -->
                    <div class="p-5 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
                         <div class="text-xs text-gray-400">
                             ID: #<?= $event['id'] ?>
                         </div>
                        <div class="flex space-x-2">
                            <?php if ($event['status'] == 'Completed' && empty($event['certificates_released'])): ?>
                                <a href="event_blast_review.php?id=<?= $event['id'] ?>" class="p-2 text-amber-600 hover:bg-amber-100 rounded-lg transition-colors" title="Blast Certificates">
                                    <i class="fas fa-paper-plane"></i>
                                </a>
                            <?php endif; ?>

                            <a href="print_event.php?id=<?= $event['id'] ?>" target="_blank" class="p-2 text-gray-600 hover:bg-gray-100 rounded-lg transition-colors" title="Print Details">
                                <i class="fas fa-print"></i>
                            </a>
                            <a href="events.php?edit=<?= $event['id'] ?>" class="p-2 text-blue-600 hover:bg-blue-100 rounded-lg transition-colors" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="events.php" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this event?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $event['id'] ?>">
                                <button type="submit" class="p-2 text-rose-600 hover:bg-rose-100 rounded-lg transition-colors" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-span-full py-20 text-center text-gray-500">
                <i class="fas fa-calendar-times text-6xl mb-4 text-gray-300"></i>
                <p class="text-xl font-semibold">No events found</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="flex items-center justify-center space-x-2">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($status_filter) ? '&status=' . urlencode($status_filter) : '' ?>" 
                    class="px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="fas fa-chevron-left"></i>
                </a>
            <?php endif; ?>
            
            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                <a href="?page=<?= $i ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($status_filter) ? '&status=' . urlencode($status_filter) : '' ?>" 
                    class="px-4 py-2 <?= $i == $page ? 'bg-primary text-white' : 'bg-white border border-gray-300 hover:bg-gray-50' ?> rounded-lg transition-colors">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?= $page + 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($status_filter) ? '&status=' . urlencode($status_filter) : '' ?>" 
                    class="px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>

<!-- Create/Edit Event Modal -->
<div id="eventModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 id="modalTitle" class="text-xl font-bold text-gray-900">Create New Event</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form id="eventForm" action="events.php" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
            <input type="hidden" name="action" value="<?= $edit_data ? 'update' : 'create' ?>">
            <input type="hidden" id="event_id" name="event_id" value="<?= $edit_data['id'] ?? '' ?>">
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Event Title *</label>
                <input type="text" id="title" name="title" value="<?= htmlspecialchars($edit_data['title'] ?? '') ?>" required 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Filter State</label>
                    <select id="state_filter" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" onchange="filterBeaches()">
                        <option value="">All States</option>
                        <?php foreach ($all_states as $state): ?>
                            <option value="<?= htmlspecialchars($state) ?>"><?= htmlspecialchars($state) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Location (Beach) *</label>
                    <select id="beach_id" name="beach_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        <option value="">Select Beach</option>
                        <?php foreach ($all_beaches as $beach): ?>
                            <option value="<?= $beach['id'] ?>" <?= ($edit_data['beach_id'] ?? '') == $beach['id'] ? 'selected' : '' ?> data-state="<?= htmlspecialchars($beach['state'] ?? '') ?>"><?= htmlspecialchars($beach['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Date & Time *</label>
                <input type="datetime-local" id="event_date" name="event_date" value="<?= $edit_data['event_date'] ?? '' ?>" required 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Organizer</label>
                    <select id="organizer_id" name="organizer_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        <option value="">Select Organizer</option>
                        <?php foreach ($all_users as $user): ?>
                            <option value="<?= $user['id'] ?>" <?= ($edit_data['organizer_id'] ?? $_SESSION['user_id']) == $user['id'] ? 'selected' : '' ?>><?= htmlspecialchars($user['full_name']) ?> (<?= htmlspecialchars($user['email']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Leader</label>
                    <select id="leader_id" name="leader_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        <option value="">Select Leader</option>
                        <?php foreach ($all_users as $user): ?>
                            <option value="<?= $user['id'] ?>" <?= ($edit_data['leader_id'] ?? '') == $user['id'] ? 'selected' : '' ?>><?= htmlspecialchars($user['full_name']) ?> (<?= htmlspecialchars($user['email']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <!-- Image Upload -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Event Image</label>
                <input type="file" id="image_file" name="image" accept="image/*"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                <input type="hidden" id="current_image" name="current_image" value="<?= $edit_data['image'] ?? '' ?>">
                <?php if (!empty($edit_data['image'])): ?>
                <div id="image_preview" class="mt-2">
                    <p class="text-xs text-gray-500 mb-1">Current Image:</p>
                    <img src="../<?= htmlspecialchars($edit_data['image']) ?>" alt="Preview" class="h-32 w-full rounded border border-gray-200 object-cover">
                </div>
                <?php endif; ?>
            </div>
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Status</label>
                <select id="status" name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="Upcoming" <?= ($edit_data['status'] ?? '') == 'Upcoming' ? 'selected' : '' ?>>Upcoming</option>
                    <option value="Completed" <?= ($edit_data['status'] ?? '') == 'Completed' ? 'selected' : '' ?>>Completed</option>
                    <option value="Cancelled" <?= ($edit_data['status'] ?? '') == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Registration Fee (RM)</label>
                <input type="number" step="0.01" name="price" value="<?= $edit_data['price'] ?? 25.00 ?>" required 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <!-- Provisions -->
                <div class="space-y-2 border border-blue-50 bg-blue-50/30 p-3 rounded-lg">
                    <label class="block text-sm font-bold text-gray-700">Provisions (Food/Drinks)</label>
                    <input type="text" name="provision_details" placeholder="e.g. Lunch & Drinks" value="<?= htmlspecialchars($edit_data['provision_details'] ?? '') ?>"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    
                    <label class="block text-xs font-semibold text-gray-500">Custom Icon (Optional)</label>
                    <input type="file" name="provision_image_file" accept="image/*" class="w-full text-xs">
                    <input type="hidden" name="current_provision_image" value="<?= $edit_data['provision_image'] ?? '' ?>">
                    <?php if (!empty($edit_data['provision_image'])): ?>
                        <div class="mt-1"><img src="../<?= htmlspecialchars($edit_data['provision_image']) ?>" class="h-10 w-10 object-cover rounded"></div>
                    <?php endif; ?>
                </div>

                <!-- Kit -->
                <div class="space-y-2 border border-purple-50 bg-purple-50/30 p-3 rounded-lg">
                    <label class="block text-sm font-bold text-gray-700">Kit (T-Shirt/Tools)</label>
                    <input type="text" name="kit_details" placeholder="e.g. T-Shirt & Gloves" value="<?= htmlspecialchars($edit_data['kit_details'] ?? '') ?>"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    
                    <label class="block text-xs font-semibold text-gray-500">Custom Icon (Optional)</label>
                    <input type="file" name="kit_image_file" accept="image/*" class="w-full text-xs">
                    <input type="hidden" name="current_kit_image" value="<?= $edit_data['kit_image'] ?? '' ?>">
                    <?php if (!empty($edit_data['kit_image'])): ?>
                        <div class="mt-1"><img src="../<?= htmlspecialchars($edit_data['kit_image']) ?>" class="h-10 w-10 object-cover rounded"></div>
                    <?php endif; ?>
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Description</label>
                <textarea id="description" name="description" rows="4" 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"><?= htmlspecialchars($edit_data['description'] ?? '') ?></textarea>
            </div>
            
            <div class="flex items-center justify-end space-x-3 pt-4">
                <button type="button" onclick="closeModal()" class="px-6 py-2 bg-gray-200 text-gray-700 font-semibold rounded-lg hover:bg-gray-300 transition-colors">
                    Cancel
                </button>
                <button type="submit" class="px-6 py-2 bg-primary text-white font-semibold rounded-lg hover:bg-secondary transition-colors">
                    <i class="fas fa-save mr-2"></i>Save Event
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Store all beaches for filtering
const allBeaches = <?= json_encode($all_beaches) ?>;

function filterBeaches() {
    const selectedState = document.getElementById('state_filter').value;
    const beachSelect = document.getElementById('beach_id');
    const currentBeachId = beachSelect.value;
    
    // Clear current options
    beachSelect.innerHTML = '<option value="">Select Beach</option>';
    
    allBeaches.forEach(beach => {
        if (!selectedState || beach.state === selectedState || beach.id == currentBeachId) {
            const option = document.createElement('option');
            option.value = beach.id;
            option.textContent = beach.name;
            option.dataset.state = beach.state;
            if (beach.id == currentBeachId) option.selected = true;
            beachSelect.appendChild(option);
        }
    });
}

function openCreateModal() {
    window.location.href = 'events.php?create=1';
}

function closeModal() {
    window.location.href = 'events.php';
}

// Auto-open modal if editing or creating
<?php if ($edit_data || isset($_GET['create'])): ?>
document.getElementById('eventModal').classList.remove('hidden');
<?php endif; ?>

<?php if (isset($_GET['create'])): ?>
document.getElementById('modalTitle').textContent = 'Create New Event';
<?php endif; ?>

<?php if ($edit_data): ?>
document.getElementById('modalTitle').textContent = 'Edit Event';
<?php endif; ?>
</script>
</script>

<?php include "admin_footer.php"; ?>
