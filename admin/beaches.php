<?php
include "admin_auth.php";
include "admin_header.php";

// --- Handle Form Actions (Traditional PHP) ---
$action_msg = '';
$action_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    try {
        if ($action == 'create' || $action == 'update') {
            $id = isset($_POST['beach_id']) ? (int)$_POST['beach_id'] : null;
            $name = trim($_POST['name'] ?? '');
            $location = trim($_POST['location'] ?? '');
            $state = trim($_POST['state'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $status = $_POST['status'] ?? 'Excellent';
            $image_path = $_POST['current_image'] ?? '';

            if (empty($name) || empty($location)) {
                throw new Exception("Beach name and location are required.");
            }

            // Handle Image Upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../uploads/beaches/';
                if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
                
                $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                if (in_array($file_ext, $allowed_ext)) {
                    $new_filename = uniqid('beach_') . '.' . $file_ext;
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $new_filename)) {
                        $image_path = 'uploads/beaches/' . $new_filename;
                    }
                }
            }

            if ($action == 'create') {
                $stmt = $conn->prepare("INSERT INTO beaches (name, location, state, description, status, image) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $location, $state, $description, $status, $image_path]);
                $_SESSION['success'] = "Beach created successfully!";
            } else {
                $stmt = $conn->prepare("UPDATE beaches SET name=?, location=?, state=?, description=?, status=?, image=? WHERE id=?");
                $stmt->execute([$name, $location, $state, $description, $status, $image_path, $id]);
                $_SESSION['success'] = "Beach updated successfully!";
            }
            header("Location: beaches.php");
            exit();
        }

        if ($action == 'delete' && isset($_POST['id'])) {
            $id = (int)$_POST['id'];
            $stmt = $conn->prepare("DELETE FROM beaches WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['success'] = "Beach deleted successfully!";
            header("Location: beaches.php");
            exit();
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
        header("Location: beaches.php");
        exit();
    }
}

// --- Fetch Data for Editing ---
$edit_data = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM beaches WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_data = $stmt->fetch(PDO::FETCH_ASSOC);
}

// --- Standard Page Logic (Filtering & Pagination) ---
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$state_filter = $_GET['state'] ?? '';
$sort = $_GET['sort'] ?? 'newest';

$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(name LIKE :search OR location LIKE :search OR state LIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($status_filter)) {
    $where[] = "status = :status";
    $params[':status'] = $status_filter;
}

if (!empty($state_filter)) {
    $where[] = "state = :state";
    $params[':state'] = $state_filter;
}

$where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Determine sort order
$order_by = "ORDER BY created_at DESC"; // default
switch ($sort) {
    case 'votes_high':
        $order_by = "ORDER BY clean_votes DESC, created_at DESC";
        break;
    case 'votes_low':
        $order_by = "ORDER BY clean_votes ASC, created_at DESC";
        break;
    case 'oldest':
        $order_by = "ORDER BY created_at ASC";
        break;
    case 'newest':
    default:
        $order_by = "ORDER BY created_at DESC";
        break;
}

try {
    // Fetch unique states for filter
    $states_stmt = $conn->query("SELECT DISTINCT state FROM beaches WHERE state IS NOT NULL AND state != '' ORDER BY state ASC");
    $states = $states_stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Get total count
    $count_query = "SELECT COUNT(*) FROM beaches $where_clause";
    $stmt = $conn->prepare($count_query);
    foreach ($params as $key => $val) $stmt->bindValue($key, $val);
    $stmt->execute();
    $total_beaches = $stmt->fetchColumn();
    $total_pages = ceil($total_beaches / $per_page);
    
    // Get beaches
    $query = "SELECT * FROM beaches $where_clause $order_by LIMIT $per_page OFFSET $offset";
    $stmt = $conn->prepare($query);
    foreach ($params as $key => $val) $stmt->bindValue($key, $val);
    $stmt->execute();
    $beaches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Database Error: " . $e->getMessage();
}
?>

<!-- Beaches Management Content -->
<div class="space-y-6">
    
    <!-- Header Actions -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h3 class="text-2xl font-bold text-gray-900">All Beaches</h3>
            <p class="text-sm text-gray-500">Total: <?= number_format($total_beaches) ?> beaches</p>
        </div>
        <button onclick="openCreateModal()" class="px-6 py-3 bg-primary text-white font-bold rounded-lg hover:bg-secondary transition-colors shadow-md">
            <i class="fas fa-plus mr-2"></i>Add New Beach
        </button>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
        <form method="GET" class="flex flex-col md:flex-row gap-4">
            <div class="flex-1">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                    placeholder="Search by name, location, or state..." 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>
            <select name="state" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                <option value="">All States</option>
                <?php foreach ($states as $state): ?>
                    <option value="<?= htmlspecialchars($state) ?>" <?= $state_filter == $state ? 'selected' : '' ?>><?= htmlspecialchars($state) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="status" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                <option value="">All Status</option>
                <option value="Excellent" <?= $status_filter == 'Excellent' ? 'selected' : '' ?>>Excellent</option>
                <option value="Good" <?= $status_filter == 'Good' ? 'selected' : '' ?>>Good</option>
                <option value="Needs Attention" <?= $status_filter == 'Needs Attention' ? 'selected' : '' ?>>Needs Attention</option>
            </select>
            <select name="sort" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                <option value="newest" <?= $sort == 'newest' ? 'selected' : '' ?>>Newest First</option>
                <option value="oldest" <?= $sort == 'oldest' ? 'selected' : '' ?>>Oldest First</option>
                <option value="votes_high" <?= $sort == 'votes_high' ? 'selected' : '' ?>>Highest Votes</option>
                <option value="votes_low" <?= $sort == 'votes_low' ? 'selected' : '' ?>>Lowest Votes</option>
            </select>
            <button type="submit" class="px-6 py-2 bg-gray-900 text-white font-semibold rounded-lg hover:bg-gray-800 transition-colors">
                <i class="fas fa-search mr-2"></i>Search
            </button>
            <?php if (!empty($search) || !empty($status_filter) || !empty($state_filter) || $sort != 'newest'): ?>
                <a href="beaches.php" class="px-6 py-2 bg-gray-200 text-gray-700 font-semibold rounded-lg hover:bg-gray-300 transition-colors text-center">
                    <i class="fas fa-times mr-2"></i>Clear
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Beaches Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (!empty($beaches)): ?>
            <?php foreach ($beaches as $beach): ?>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow">
                    <!-- Beach Image -->
                    <div class="relative h-48 bg-gray-200">
                        <?php if (!empty($beach['image'])): ?>
                            <img src="../<?= htmlspecialchars($beach['image']) ?>" alt="<?= htmlspecialchars($beach['name']) ?>" 
                                class="w-full h-full object-cover">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-cyan-100 to-blue-100">
                                <i class="fas fa-umbrella-beach text-4xl text-gray-400"></i>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Status Badge -->
                        <div class="absolute top-3 right-3">
                            <span class="px-3 py-1 rounded-lg text-xs font-bold uppercase shadow-lg
                                <?php
                                    if ($beach['status'] == 'Excellent') echo 'bg-emerald-500 text-white';
                                    elseif ($beach['status'] == 'Good') echo 'bg-amber-500 text-white';
                                    else echo 'bg-rose-500 text-white';
                                ?>">
                                <?= htmlspecialchars($beach['status']) ?>
                            </span>
                        </div>
                    </div>
                    
                    <!-- Beach Info -->
                    <div class="p-5">
                        <h4 class="text-lg font-bold text-gray-900 mb-2"><?= htmlspecialchars($beach['name']) ?></h4>
                        <div class="space-y-2 text-sm text-gray-600 mb-4">
                            <p><i class="fas fa-map-marker-alt text-primary mr-2"></i><?= htmlspecialchars($beach['location']) ?></p>
                            <?php if (!empty($beach['state'])): ?>
                                <p><i class="fas fa-map-pin text-primary mr-2"></i><?= htmlspecialchars($beach['state']) ?></p>
                            <?php endif; ?>
                            <?php if (!empty($beach['description'])): ?>
                                <p class="text-xs italic text-gray-500 line-clamp-2"><?= htmlspecialchars($beach['description']) ?></p>
                            <?php endif; ?>
                            <div class="flex items-center justify-between pt-2">
                                <p class="text-xs text-gray-400"><i class="far fa-clock mr-1"></i>Added <?= date('M j, Y', strtotime($beach['created_at'])) ?></p>
                                <div class="flex items-center space-x-3">
                                    <div class="flex items-center space-x-1 bg-blue-50 px-3 py-1 rounded-full" title="Votes to Clean">
                                        <i class="fas fa-thumbs-up text-blue-600 text-xs"></i>
                                        <span class="text-sm font-bold text-blue-600"><?= $beach['clean_votes'] ?? 0 ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Actions -->
                        <div class="flex items-center justify-end space-x-2 pt-3 border-t border-gray-100">
                            <a href="beaches.php?edit=<?= $beach['id'] ?>" class="px-4 py-2 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 transition-colors text-sm font-semibold">
                                <i class="fas fa-edit mr-1"></i>Edit
                            </a>
                            <form action="beaches.php" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this beach?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $beach['id'] ?>">
                                <button type="submit" class="px-4 py-2 bg-rose-50 text-rose-600 rounded-lg hover:bg-rose-100 transition-colors text-sm font-semibold">
                                    <i class="fas fa-trash mr-1"></i>Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-span-full py-20 text-center text-gray-500">
                <i class="fas fa-umbrella-beach text-6xl mb-4 text-gray-300"></i>
                <p class="text-xl font-semibold">No beaches found</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="flex items-center justify-center space-x-2">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($status_filter) ? '&status=' . urlencode($status_filter) : '' ?><?= !empty($state_filter) ? '&state=' . urlencode($state_filter) : '' ?><?= $sort != 'newest' ? '&sort=' . urlencode($sort) : '' ?>" 
                    class="px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="fas fa-chevron-left"></i>
                </a>
            <?php endif; ?>
            
            
            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                <a href="?page=<?= $i ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($status_filter) ? '&status=' . urlencode($status_filter) : '' ?><?= !empty($state_filter) ? '&state=' . urlencode($state_filter) : '' ?><?= $sort != 'newest' ? '&sort=' . urlencode($sort) : '' ?>" 
                    class="px-4 py-2 <?= $i == $page ? 'bg-primary text-white' : 'bg-white border border-gray-300 hover:bg-gray-50' ?> rounded-lg transition-colors">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
            
            
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?= $page + 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($status_filter) ? '&status=' . urlencode($status_filter) : '' ?><?= !empty($state_filter) ? '&state=' . urlencode($state_filter) : '' ?><?= $sort != 'newest' ? '&sort=' . urlencode($sort) : '' ?>" 
                    class="px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>

<!-- Create/Edit Beach Modal -->
<div id="beachModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 id="modalTitle" class="text-xl font-bold text-gray-900">Add New Beach</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form id="beachForm" action="beaches.php" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
            <input type="hidden" name="action" value="<?= $edit_data ? 'update' : 'create' ?>">
            <input type="hidden" id="beach_id" name="beach_id" value="<?= $edit_data['id'] ?? '' ?>">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Beach Name *</label>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($edit_data['name'] ?? '') ?>" required 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>
                
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">State</label>
                    <select id="state" name="state" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        <option value="">Select State</option>
                        <?php 
                        $all_states = ["Johor", "Kedah", "Kelantan", "Melaka", "Negeri Sembilan", "Pahang", "Perak", "Perlis", "Penang", "Sabah", "Sarawak", "Selangor", "Terengganu", "Kuala Lumpur", "Labuan", "Putrajaya"];
                        foreach ($all_states as $s): ?>
                            <option value="<?= $s ?>" <?= ($edit_data['state'] ?? '') == $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Location *</label>
                <input type="text" id="location" name="location" value="<?= htmlspecialchars($edit_data['location'] ?? '') ?>" required 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Description</label>
                <textarea id="description" name="description" rows="3" 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"><?= htmlspecialchars($edit_data['description'] ?? '') ?></textarea>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Status *</label>
                    <select id="status" name="status" required 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        <option value="Excellent" <?= ($edit_data['status'] ?? '') == 'Excellent' ? 'selected' : '' ?>>Excellent</option>
                        <option value="Good" <?= ($edit_data['status'] ?? '') == 'Good' ? 'selected' : '' ?>>Good</option>
                        <option value="Needs Attention" <?= ($edit_data['status'] ?? '') == 'Needs Attention' ? 'selected' : '' ?>>Needs Attention</option>
                    </select>
                </div>
                
            </div>
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Beach Image</label>
                <input type="file" id="image_file" name="image" accept="image/*"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                <input type="hidden" id="current_image" name="current_image" value="<?= $edit_data['image'] ?? '' ?>">
                <?php if (!empty($edit_data['image'])): ?>
                <div id="image_preview" class="mt-2">
                    <p class="text-xs text-gray-500 mb-1">Current Image:</p>
                    <img src="../<?= htmlspecialchars($edit_data['image']) ?>" alt="Preview" class="h-20 w-auto rounded border border-gray-200 object-cover">
                </div>
                <?php endif; ?>
            </div>
            
            <div class="flex items-center justify-end space-x-3 pt-4">
                <button type="button" onclick="closeModal()" class="px-6 py-2 bg-gray-200 text-gray-700 font-semibold rounded-lg hover:bg-gray-300 transition-colors">
                    Cancel
                </button>
                <button type="submit" class="px-6 py-2 bg-primary text-white font-semibold rounded-lg hover:bg-secondary transition-colors">
                    <i class="fas fa-save mr-2"></i>Save Beach
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openCreateModal() {
    window.location.href = 'beaches.php?create=1';
}

function closeModal() {
    window.location.href = 'beaches.php';
}

// Auto-open modal if editing or creating
<?php if ($edit_data || isset($_GET['create'])): ?>
document.getElementById('beachModal').classList.remove('hidden');
<?php endif; ?>

<?php if (isset($_GET['create'])): ?>
document.getElementById('modalTitle').textContent = 'Add New Beach';
<?php endif; ?>

<?php if ($edit_data): ?>
document.getElementById('modalTitle').textContent = 'Edit Beach';
<?php endif; ?>
</script>
</script>

<?php include "admin_footer.php"; ?>
