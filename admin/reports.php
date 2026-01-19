<?php
include "admin_auth.php";
include "admin_header.php";

// --- Handle Form Actions (Traditional PHP) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    try {
        if ($action == 'update_status' && isset($_POST['id'])) {
            $id = (int)$_POST['id'];
            $status = $_POST['status'];
            $stmt = $conn->prepare("UPDATE reports SET status = ? WHERE id = ?");
            $stmt->execute([$status, $id]);
            $_SESSION['success'] = "Report status updated!";
            header("Location: reports.php");
            exit();
        }

        if ($action == 'update' && isset($_POST['report_id'])) {
            $id = (int)$_POST['report_id'];
            $description = trim($_POST['description'] ?? '');
            $image_path = $_POST['current_image'] ?? '';

            if (empty($description)) {
                throw new Exception("Description is required.");
            }

            // Handle Image Upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../uploads/reports/';
                if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
                
                $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                if (in_array($file_ext, $allowed_ext)) {
                    $new_filename = uniqid('report_') . '.' . $file_ext;
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $new_filename)) {
                        $image_path = 'uploads/reports/' . $new_filename;
                    }
                }
            }

            $stmt = $conn->prepare("UPDATE reports SET description = ?, image = ? WHERE id = ?");
            $stmt->execute([$description, $image_path, $id]);
            $_SESSION['success'] = "Report updated successfully!";
            header("Location: reports.php");
            exit();
        }

        if ($action == 'delete' && isset($_POST['id'])) {
            $id = (int)$_POST['id'];
            $stmt = $conn->prepare("DELETE FROM reports WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['success'] = "Report deleted successfully!";
            header("Location: reports.php");
            exit();
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
        header("Location: reports.php");
        exit();
    }
}

// --- Fetch Data for Editing/Viewing ---
$edit_data = null;
if (isset($_GET['edit']) || isset($_GET['view'])) {
    $id = isset($_GET['edit']) ? (int)$_GET['edit'] : (int)$_GET['view'];
    $stmt = $conn->prepare("SELECT r.*, b.name as beach_name, u.full_name as reporter_name, u.email as reporter_email FROM reports r JOIN beaches b ON r.beach_id = b.id JOIN users u ON r.user_id = u.id WHERE r.id = ?");
    $stmt->execute([$id]);
    $edit_data = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fetch reports with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$type_filter = $_GET['type'] ?? '';

$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(r.description LIKE :search OR u.full_name LIKE :search OR b.name LIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($status_filter)) {
    $where[] = "r.status = :status";
    $params[':status'] = $status_filter;
}

if (!empty($type_filter)) {
    $where[] = "r.type = :type";
    $params[':type'] = $type_filter;
}

$where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

try {
    // Get total count
    $count_query = "SELECT COUNT(*) FROM reports r JOIN users u ON r.user_id = u.id JOIN beaches b ON r.beach_id = b.id $where_clause";
    $stmt = $conn->prepare($count_query);
    foreach ($params as $key => $val) $stmt->bindValue($key, $val);
    $stmt->execute();
    $total_reports = $stmt->fetchColumn();
    $total_pages = ceil($total_reports / $per_page);
    
    // Get reports
    $query = "
        SELECT r.*, u.full_name as reporter_name, u.email as reporter_email, b.name as beach_name 
        FROM reports r 
        JOIN users u ON r.user_id = u.id 
        JOIN beaches b ON r.beach_id = b.id 
        $where_clause 
        ORDER BY r.created_at DESC 
        LIMIT $per_page OFFSET $offset
    ";
    $stmt = $conn->prepare($query);
    foreach ($params as $key => $val) $stmt->bindValue($key, $val);
    $stmt->execute();
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Database Error: " . $e->getMessage();
}
?>

<!-- Reports Management Content -->
<div class="space-y-6">
    
    <!-- Header Actions -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h3 class="text-2xl font-bold text-gray-900">All Reports</h3>
            <p class="text-sm text-gray-500">Total: <?= number_format($total_reports) ?> reports</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
        <form method="GET" class="flex flex-col md:flex-row gap-4">
            <div class="flex-1">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                    placeholder="Search by description, reporter, or beach..." 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>
            <select name="status" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                <option value="">All Status</option>
                <option value="Pending" <?= $status_filter == 'Pending' ? 'selected' : '' ?>>Pending</option>
                <option value="Resolved" <?= $status_filter == 'Resolved' ? 'selected' : '' ?>>Resolved</option>
                <option value="Dismissed" <?= $status_filter == 'Dismissed' ? 'selected' : '' ?>>Dismissed</option>
            </select>
            <select name="type" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                <option value="">All Types</option>
                <option value="Trash/Litter" <?= $type_filter == 'Trash/Litter' ? 'selected' : '' ?>>Trash/Litter</option>
                <option value="Trash Accumulation" <?= $type_filter == 'Trash Accumulation' ? 'selected' : '' ?>>Trash Accumulation</option>
                <option value="Safety Hazard" <?= $type_filter == 'Safety Hazard' ? 'selected' : '' ?>>Safety Hazard</option>
                <option value="Wildlife Concern" <?= $type_filter == 'Wildlife Concern' ? 'selected' : '' ?>>Wildlife Concern</option>
                <option value="Other" <?= $type_filter == 'Other' ? 'selected' : '' ?>>Other</option>
            </select>
            <button type="submit" class="px-6 py-2 bg-gray-900 text-white font-semibold rounded-lg hover:bg-gray-800 transition-colors">
                <i class="fas fa-search mr-2"></i>Search
            </button>
            <?php if (!empty($search) || !empty($status_filter) || !empty($type_filter)): ?>
                <a href="reports.php" class="px-6 py-2 bg-gray-200 text-gray-700 font-semibold rounded-lg hover:bg-gray-300 transition-colors text-center">
                    <i class="fas fa-times mr-2"></i>Clear
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Reports Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Report</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Beach</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Reporter</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-right text-xs font-bold text-gray-700 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php if (!empty($reports)): ?>
                        <?php foreach ($reports as $report): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4">
                                    <p class="text-sm text-gray-900 font-medium line-clamp-2"><?= htmlspecialchars($report['description']) ?></p>
                                    <?php if (!empty($report['image'])): ?>
                                        <span class="inline-block mt-1 text-xs text-blue-600"><i class="fas fa-image mr-1"></i>Has image</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900"><?= htmlspecialchars($report['beach_name']) ?></td>
                                <td class="px-6 py-4">
                                    <div class="text-sm">
                                        <p class="font-semibold text-gray-900"><?= htmlspecialchars($report['reporter_name']) ?></p>
                                        <p class="text-gray-500"><?= htmlspecialchars($report['reporter_email']) ?></p>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 rounded-full text-xs font-bold uppercase
                                        <?php
                                            if ($report['type'] == 'Trash/Litter' || $report['type'] == 'Trash Accumulation') echo 'bg-blue-100 text-blue-700';
                                            elseif ($report['type'] == 'Safety Hazard' || $report['type'] == 'Wildlife Concern') echo 'bg-rose-100 text-rose-700';
                                            else echo 'bg-gray-100 text-gray-700';
                                        ?>">
                                        <?= htmlspecialchars($report['type']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 rounded-full text-xs font-bold uppercase
                                        <?php
                                            if ($report['status'] == 'Pending') echo 'bg-amber-100 text-amber-700';
                                            elseif ($report['status'] == 'Resolved') echo 'bg-emerald-100 text-emerald-700';
                                            else echo 'bg-gray-100 text-gray-700';
                                        ?>">
                                        <?= htmlspecialchars($report['status']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500"><?= date('M j, Y', strtotime($report['created_at'])) ?></td>
                                <td class="px-6 py-4 text-right">
                                    <a href="reports.php?view=<?= $report['id'] ?>" class="text-blue-600 hover:text-blue-800 mr-3" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="reports.php?edit=<?= $report['id'] ?>" class="text-amber-600 hover:text-amber-800 mr-3" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($report['status'] == 'Pending'): ?>
                                        <form action="reports.php" method="POST" class="inline">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="id" value="<?= $report['id'] ?>">
                                            <input type="hidden" name="status" value="Resolved">
                                            <button type="submit" class="text-emerald-600 hover:text-emerald-800 mr-3" title="Mark as Resolved">
                                                <i class="fas fa-check-circle"></i>
                                            </button>
                                        </form>
                                        <form action="reports.php" method="POST" class="inline">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="id" value="<?= $report['id'] ?>">
                                            <input type="hidden" name="status" value="Dismissed">
                                            <button type="submit" class="text-gray-600 hover:text-gray-800 mr-3" title="Dismiss">
                                                <i class="fas fa-times-circle"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <form action="reports.php" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this report?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $report['id'] ?>">
                                        <button type="submit" class="text-rose-600 hover:text-rose-800" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                <i class="fas fa-flag text-4xl mb-3 text-gray-300"></i>
                                <p>No reports found</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="flex items-center justify-center space-x-2">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($status_filter) ? '&status=' . urlencode($status_filter) : '' ?><?= !empty($type_filter) ? '&type=' . urlencode($type_filter) : '' ?>" 
                    class="px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="fas fa-chevron-left"></i>
                </a>
            <?php endif; ?>
            
            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                <a href="?page=<?= $i ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($status_filter) ? '&status=' . urlencode($status_filter) : '' ?><?= !empty($type_filter) ? '&type=' . urlencode($type_filter) : '' ?>" 
                    class="px-4 py-2 <?= $i == $page ? 'bg-primary text-white' : 'bg-white border border-gray-300 hover:bg-gray-50' ?> rounded-lg transition-colors">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?= $page + 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($status_filter) ? '&status=' . urlencode($status_filter) : '' ?><?= !empty($type_filter) ? '&type=' . urlencode($type_filter) : '' ?>" 
                    class="px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>

<!-- View Report Modal -->
<div id="reportModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-xl font-bold text-gray-900">Report Details</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div id="reportDetails" class="p-6">
            <?php if ($edit_data && isset($_GET['view'])): ?>
            <div class="space-y-4">
                <div class="flex items-center space-x-3">
                    <span class="px-3 py-1 rounded-full text-xs font-bold uppercase 
                        <?php
                            if ($edit_data['type'] == 'Cleanup') echo 'bg-blue-100 text-blue-700';
                            elseif ($edit_data['type'] == 'Incident') echo 'bg-rose-100 text-rose-700';
                            else echo 'bg-gray-100 text-gray-700';
                        ?>"><?= $edit_data['type'] ?></span>
                    <span class="px-3 py-1 rounded-full text-xs font-bold uppercase 
                        <?php
                            if ($edit_data['status'] == 'Pending') echo 'bg-amber-100 text-amber-700';
                            elseif ($edit_data['status'] == 'Resolved') echo 'bg-emerald-100 text-emerald-700';
                            else echo 'bg-gray-100 text-gray-700';
                        ?>"><?= $edit_data['status'] ?></span>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Description</label>
                    <p class="text-gray-900"><?= htmlspecialchars($edit_data['description']) ?></p>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Beach</label>
                        <p class="text-gray-900"><?= htmlspecialchars($edit_data['beach_name']) ?></p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Reporter</label>
                        <p class="text-gray-900"><?= htmlspecialchars($edit_data['reporter_name']) ?></p>
                        <p class="text-sm text-gray-500"><?= htmlspecialchars($edit_data['reporter_email']) ?></p>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Reported On</label>
                    <p class="text-gray-900"><?= date('M j, Y g:i A', strtotime($edit_data['created_at'])) ?></p>
                </div>
                
                <?php if (!empty($edit_data['image'])): ?>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Attached Image</label>
                        <img src="../<?= htmlspecialchars($edit_data['image']) ?>" alt="Report image" class="w-full rounded-lg border border-gray-200">
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function closeModal() {
    window.location.href = 'reports.php';
}

// Auto-open modals
<?php if ($edit_data && isset($_GET['view'])): ?>
document.getElementById('reportModal').classList.remove('hidden');
<?php endif; ?>

<?php if ($edit_data && isset($_GET['edit'])): ?>
document.getElementById('editReportModal').classList.remove('hidden');
<?php endif; ?>
</script>
</script>

<!-- Edit Report Modal -->
<div id="editReportModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl max-w-lg w-full">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-xl font-bold text-gray-900">Edit Report</h3>
            <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form id="editReportForm" action="reports.php" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
            <input type="hidden" name="action" value="update">
            <input type="hidden" id="edit_report_id" name="report_id" value="<?= $edit_data['id'] ?? '' ?>">
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Description</label>
                <textarea id="edit_description" name="description" rows="4" required 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"><?= htmlspecialchars($edit_data['description'] ?? '') ?></textarea>
            </div>
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Image</label>
                <input type="file" name="image" accept="image/*"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                <input type="hidden" id="edit_current_image" name="current_image" value="<?= $edit_data['image'] ?? '' ?>">
                <?php if (!empty($edit_data['image'])): ?>
                <div id="edit_image_preview" class="mt-2">
                    <p class="text-xs text-gray-500 mb-1">Current Image:</p>
                    <img src="../<?= htmlspecialchars($edit_data['image']) ?>" alt="Preview" class="h-20 w-auto rounded border border-gray-200 object-cover">
                </div>
                <?php endif; ?>
            </div>
            
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeEditModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-black">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<?php include "admin_footer.php"; ?>
