<?php
include "admin_auth.php";

// --- Handle Form Actions ---
$action_msg = '';
$action_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    try {
        if ($action == 'create' || $action == 'update') {
            $id = isset($_POST['reward_id']) ? (int)$_POST['reward_id'] : null;
            $name = trim($_POST['name'] ?? '');
            $points = (int)($_POST['points_required'] ?? 0);
            $code = trim($_POST['voucher_code'] ?? '');
            $expiry_date = $_POST['expiry_date'] ?? null;
            if (empty($expiry_date)) $expiry_date = null; // Handle empty date string

            $description = trim($_POST['description'] ?? '');
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            $image_path = $_POST['current_image'] ?? '';

            if (empty($name) || empty($code)) {
                throw new Exception("Reward name and voucher code are required.");
            }

            // Handle Image Upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../uploads/rewards/';
                if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
                
                $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                if (in_array($file_ext, $allowed_ext)) {
                    $new_filename = uniqid('reward_') . '.' . $file_ext;
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $new_filename)) {
                        $image_path = 'uploads/rewards/' . $new_filename;
                    }
                }
            }

            if ($action == 'create') {
                $stmt = $conn->prepare("INSERT INTO rewards (name, description, points_required, voucher_code, expiry_date, image, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $description, $points, $code, $expiry_date, $image_path, $is_active]);
                $_SESSION['success'] = "Reward created successfully!";
            } else {
                $stmt = $conn->prepare("UPDATE rewards SET name=?, description=?, points_required=?, voucher_code=?, expiry_date=?, image=?, is_active=? WHERE id=?");
                $stmt->execute([$name, $description, $points, $code, $expiry_date, $image_path, $is_active, $id]);
                $_SESSION['success'] = "Reward updated successfully!";
            }
            header("Location: rewards.php");
            exit();
        }

        if ($action == 'delete' && isset($_POST['id'])) {
            $id = (int)$_POST['id'];
            $stmt = $conn->prepare("DELETE FROM rewards WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['success'] = "Reward deleted successfully!";
            header("Location: rewards.php");
            exit();
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
        header("Location: rewards.php");
        exit();
    }
}

// --- Fetch Data for Editing ---
$edit_data = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM rewards WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_data = $stmt->fetch(PDO::FETCH_ASSOC);
}

// --- Fetch All Rewards ---
$stmt = $conn->query("SELECT * FROM rewards ORDER BY created_at DESC");
$rewards = $stmt->fetchAll(PDO::FETCH_ASSOC);

include "admin_header.php";
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h3 class="text-2xl font-bold text-gray-900">Manage Rewards</h3>
            <p class="text-sm text-gray-500">Total: <?= count($rewards) ?> rewards</p>
        </div>
        <button onclick="openCreateModal()" class="px-6 py-3 bg-primary text-white font-bold rounded-lg hover:bg-secondary transition-colors shadow-md">
            <i class="fas fa-plus mr-2"></i>Add New Reward
        </button>
    </div>

    <!-- Rewards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($rewards as $reward): ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow relative group">
                <!-- Active Badge -->
                <div class="absolute top-3 right-3 z-10">
                    <span class="px-3 py-1 rounded-full text-xs font-bold uppercase shadow-sm <?= $reward['is_active'] ? 'bg-emerald-500 text-white' : 'bg-gray-400 text-white' ?>">
                        <?= $reward['is_active'] ? 'Active' : 'Inactive' ?>
                    </span>
                </div>

                <!-- Image -->
                <div class="h-48 bg-gray-100 flex items-center justify-center overflow-hidden">
                    <?php if (!empty($reward['image'])): ?>
                        <img src="../<?= htmlspecialchars($reward['image']) ?>" alt="<?= htmlspecialchars($reward['name']) ?>" class="w-full h-full object-cover">
                    <?php else: ?>
                        <i class="fas fa-gift text-4xl text-gray-300"></i>
                    <?php endif; ?>
                </div>

                <!-- Content -->
                <div class="p-5">
                    <h4 class="text-lg font-bold text-gray-900 mb-1"><?= htmlspecialchars($reward['name']) ?></h4>
                    <p class="text-primary font-bold text-sm mb-3"><?= number_format($reward['points_required']) ?> PTS</p>
                    
                    <div class="space-y-2 mb-4">
                        <div class="bg-gray-50 p-2 rounded-lg border border-gray-100">
                            <p class="text-xs text-gray-400 uppercase tracking-widest font-bold">Voucher Code</p>
                            <p class="text-sm font-mono text-gray-700"><?= htmlspecialchars($reward['voucher_code']) ?></p>
                        </div>
                        <?php if (!empty($reward['expiry_date'])): ?>
                        <div class="bg-amber-50 p-2 rounded-lg border border-amber-100 flex items-center justify-between">
                            <span class="text-xs text-amber-700 font-bold uppercase">Expires</span>
                            <span class="text-sm font-medium text-amber-900"><?= date('M d, Y', strtotime($reward['expiry_date'])) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <p class="text-sm text-gray-500 line-clamp-2 mb-4"><?= htmlspecialchars($reward['description']) ?></p>

                    <!-- Actions -->
                    <div class="flex items-center justify-end space-x-2 pt-3 border-t border-gray-100">
                        <a href="rewards.php?edit=<?= $reward['id'] ?>" class="px-4 py-2 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 transition-colors text-sm font-semibold">
                            <i class="fas fa-edit mr-1"></i>Edit
                        </a>
                        <form action="rewards.php" method="POST" class="inline" onsubmit="return confirm('Are you sure?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $reward['id'] ?>">
                            <button type="submit" class="px-4 py-2 bg-rose-50 text-rose-600 rounded-lg hover:bg-rose-100 transition-colors text-sm font-semibold">
                                <i class="fas fa-trash mr-1"></i>Delete
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Modal -->
<div id="rewardModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 id="modalTitle" class="text-xl font-bold text-gray-900">Add New Reward</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form action="rewards.php" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
            <input type="hidden" name="action" value="<?= $edit_data ? 'update' : 'create' ?>">
            <input type="hidden" name="reward_id" value="<?= $edit_data['id'] ?? '' ?>">
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Reward Name *</label>
                <input type="text" name="name" value="<?= htmlspecialchars($edit_data['name'] ?? '') ?>" required 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Points Required *</label>
                    <input type="number" name="points_required" value="<?= htmlspecialchars($edit_data['points_required'] ?? 500) ?>" required 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Voucher Code *</label>
                    <input type="text" name="voucher_code" value="<?= htmlspecialchars($edit_data['voucher_code'] ?? '') ?>" required 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Expiry Date (Optional)</label>
                <input type="date" name="expiry_date" value="<?= $edit_data['expiry_date'] ?? '' ?>" 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                <p class="text-xs text-gray-500 mt-1">Leave blank for no expiry</p>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Description</label>
                <textarea name="description" rows="3" 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"><?= htmlspecialchars($edit_data['description'] ?? '') ?></textarea>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Image</label>
                <input type="file" name="image" accept="image/*" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                <input type="hidden" name="current_image" value="<?= $edit_data['image'] ?? '' ?>">
                <?php if (!empty($edit_data['image'])): ?>
                    <img src="../<?= htmlspecialchars($edit_data['image']) ?>" class="mt-2 h-16 w-auto rounded border">
                <?php endif; ?>
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" id="is_active" name="is_active" value="1" <?= ($edit_data['is_active'] ?? 1) ? 'checked' : '' ?> class="w-5 h-5 text-primary rounded focus:ring-primary">
                <label for="is_active" class="text-sm font-semibold text-gray-700">Active (Visible to users)</label>
            </div>

            <div class="flex items-center justify-end space-x-3 pt-4">
                <button type="button" onclick="closeModal()" class="px-6 py-2 bg-gray-200 text-gray-700 font-semibold rounded-lg hover:bg-gray-300 transition-colors">Cancel</button>
                <button type="submit" class="px-6 py-2 bg-primary text-white font-semibold rounded-lg hover:bg-secondary transition-colors">Save Reward</button>
            </div>
        </form>
    </div>
</div>

<script>
function openCreateModal() {
    window.location.href = 'rewards.php?create=1';
}
function closeModal() {
    window.location.href = 'rewards.php';
}
<?php if ($edit_data || isset($_GET['create'])): ?>
document.getElementById('rewardModal').classList.remove('hidden');
<?php endif; ?>
<?php if ($edit_data): ?>
document.getElementById('modalTitle').textContent = 'Edit Reward';
<?php endif; ?>
</script>

<?php include "admin_footer.php"; ?>
