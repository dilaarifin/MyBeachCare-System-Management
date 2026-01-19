<?php
include "admin_auth.php";
include "admin_header.php";

// --- Handle Form Actions (Traditional PHP) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    try {
        if ($action == 'create' || $action == 'update') {
            $id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : null;
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $full_name = trim($_POST['full_name'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $role = $_POST['role'] ?? 'user';
            
            if (empty($username) || empty($email)) {
                throw new Exception("Username and email are required.");
            }

            if ($action == 'create' && empty($password)) {
                throw new Exception("Password is required for new users.");
            }

            // Check uniqueness
            $stmt = $conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
            $stmt->execute([$username, $email, $id ?? 0]);
            if ($stmt->fetch()) {
                throw new Exception("Username or email already exists.");
            }

            // Handle Image Upload
            $profile_image = $_POST['current_image'] ?? null;
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../uploads/profiles/';
                if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
                
                $file_ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
                $new_filename = 'user_' . uniqid() . '.' . $file_ext;
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_dir . $new_filename)) {
                    $profile_image = 'uploads/profiles/' . $new_filename;
                }
            }

            if ($action == 'create') {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (username, email, password, full_name, phone, role, profile_image) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$username, $email, $hashed_password, $full_name, $phone, $role, $profile_image]);
                $_SESSION['success'] = "User created successfully!";
            } else {
                if (!empty($password)) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET username=?, email=?, password=?, full_name=?, phone=?, role=?, profile_image=? WHERE id=?");
                    $stmt->execute([$username, $email, $hashed_password, $full_name, $phone, $role, $profile_image, $id]);
                } else {
                    $stmt = $conn->prepare("UPDATE users SET username=?, email=?, full_name=?, phone=?, role=?, profile_image=? WHERE id=?");
                    $stmt->execute([$username, $email, $full_name, $phone, $role, $profile_image, $id]);
                }
                $_SESSION['success'] = "User updated successfully!";
            }
            header("Location: users.php");
            exit();
        }

        if ($action == 'delete' && isset($_POST['id'])) {
            $id = (int)$_POST['id'];
            if ($id == $_SESSION['user_id']) {
                throw new Exception("You cannot delete your own account.");
            }
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['success'] = "User deleted successfully!";
            header("Location: users.php");
            exit();
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
        header("Location: users.php");
        exit();
    }
}

// --- Fetch Data for Editing ---
$edit_data = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT id, username, email, full_name, phone, role, profile_image FROM users WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_data = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fetch users with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

$search = $_GET['search'] ?? '';
$role_filter = $_GET['role'] ?? '';

$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(username LIKE :search OR email LIKE :search OR full_name LIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($role_filter)) {
    $where[] = "role = :role";
    $params[':role'] = $role_filter;
}

$where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

try {
    // Get total count
    $count_query = "SELECT COUNT(*) FROM users $where_clause";
    $stmt = $conn->prepare($count_query);
    foreach ($params as $key => $val) $stmt->bindValue($key, $val);
    $stmt->execute();
    $total_users = $stmt->fetchColumn();
    $total_pages = ceil($total_users / $per_page);
    
    // Get users
    $query = "SELECT * FROM users $where_clause ORDER BY created_at DESC LIMIT $per_page OFFSET $offset";
    $stmt = $conn->prepare($query);
    foreach ($params as $key => $val) $stmt->bindValue($key, $val);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Database Error: " . $e->getMessage();
}
?>

<!-- Users Management Content -->
<div class="space-y-6">
    
    <!-- Header Actions -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h3 class="text-2xl font-bold text-gray-900">All Users</h3>
            <p class="text-sm text-gray-500">Total: <?= number_format($total_users) ?> users</p>
        </div>
        <a href="users.php?create=1" class="px-6 py-3 bg-primary text-white font-bold rounded-lg hover:bg-secondary transition-colors shadow-md">
            <i class="fas fa-plus mr-2"></i>Add New User
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
        <form method="GET" class="flex flex-col md:flex-row gap-4">
            <div class="flex-1">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                    placeholder="Search by username, email, or name..." 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>
            <select name="role" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                <option value="">All Roles</option>
                <option value="admin" <?= $role_filter == 'admin' ? 'selected' : '' ?>>Admin</option>
                <option value="user" <?= $role_filter == 'user' ? 'selected' : '' ?>>User</option>
            </select>
            <button type="submit" class="px-6 py-2 bg-gray-900 text-white font-semibold rounded-lg hover:bg-gray-800 transition-colors">
                <i class="fas fa-search mr-2"></i>Search
            </button>
            <?php if (!empty($search) || !empty($role_filter)): ?>
                <a href="users.php" class="px-6 py-2 bg-gray-200 text-gray-700 font-semibold rounded-lg hover:bg-gray-300 transition-colors text-center">
                    <i class="fas fa-times mr-2"></i>Clear
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Users Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">User</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Phone</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Joined</th>
                        <th class="px-6 py-3 text-right text-xs font-bold text-gray-700 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php if (!empty($users)): ?>
                        <?php foreach ($users as $user): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-3">
                                        <?php if (!empty($user['profile_image'])): ?>
                                            <img src="../<?= htmlspecialchars($user['profile_image']) ?>" alt="Profile" class="w-10 h-10 rounded-full object-cover border-2 border-gray-200">
                                        <?php else: ?>
                                            <div class="w-10 h-10 bg-gradient-to-br from-primary to-secondary text-white rounded-full flex items-center justify-center font-bold">
                                                <?= strtoupper(substr($user['full_name'] ?? $user['username'], 0, 1)) ?>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <p class="font-semibold text-gray-900"><?= htmlspecialchars($user['full_name'] ?? 'N/A') ?></p>
                                            <p class="text-sm text-gray-500">@<?= htmlspecialchars($user['username']) ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900"><?= htmlspecialchars($user['email']) ?></td>
                                <td class="px-6 py-4 text-sm text-gray-900"><?= htmlspecialchars($user['phone'] ?? 'N/A') ?></td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 rounded-full text-xs font-bold uppercase
                                        <?php
                                            if ($user['role'] == 'admin') echo 'bg-purple-100 text-purple-700';
                                            else echo 'bg-gray-100 text-gray-700';
                                        ?>">
                                        <?= htmlspecialchars($user['role']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500"><?= date('M j, Y', strtotime($user['created_at'])) ?></td>
                                <td class="px-6 py-4 text-right">
                                    <a href="users.php?edit=<?= $user['id'] ?>" class="text-blue-600 hover:text-blue-800 mr-3" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="users.php" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                        <button type="submit" class="text-rose-600 hover:text-rose-800" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                <i class="fas fa-users text-4xl mb-3 text-gray-300"></i>
                                <p>No users found</p>
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
                <a href="?page=<?= $page - 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($role_filter) ? '&role=' . urlencode($role_filter) : '' ?>" 
                    class="px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="fas fa-chevron-left"></i>
                </a>
            <?php endif; ?>
            
            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                <a href="?page=<?= $i ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($role_filter) ? '&role=' . urlencode($role_filter) : '' ?>" 
                    class="px-4 py-2 <?= $i == $page ? 'bg-primary text-white' : 'bg-white border border-gray-300 hover:bg-gray-50' ?> rounded-lg transition-colors">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?= $page + 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($role_filter) ? '&role=' . urlencode($role_filter) : '' ?>" 
                    class="px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>

<!-- Create/Edit User Modal -->
<div id="userModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl max-w-md w-full max-h-[90vh] overflow-y-auto">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 id="modalTitle" class="text-xl font-bold text-gray-900"><?= $edit_data ? 'Edit User' : 'Add New User' ?></h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form id="userForm" action="users.php" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
            <input type="hidden" name="action" value="<?= $edit_data ? 'update' : 'create' ?>">
            <input type="hidden" id="user_id" name="user_id" value="<?= $edit_data['id'] ?? '' ?>">
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Username *</label>
                <input type="text" id="username" name="username" value="<?= htmlspecialchars($edit_data['username'] ?? '') ?>" required 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Email *</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($edit_data['email'] ?? '') ?>" required 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Full Name</label>
                <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($edit_data['full_name'] ?? '') ?>"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Phone</label>
                <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($edit_data['phone'] ?? '') ?>" maxlength="12" 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Role *</label>
                <select id="role" name="role" required 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="user" <?= (isset($edit_data['role']) && $edit_data['role'] == 'user') ? 'selected' : '' ?>>User</option>
                    <option value="admin" <?= (isset($edit_data['role']) && $edit_data['role'] == 'admin') ? 'selected' : '' ?>>Admin</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Profile Image</label>
                <div class="flex items-center space-x-4">
                    <div id="imagePreview" class="w-20 h-20 rounded-full overflow-hidden bg-gray-100 border-2 border-gray-200 flex items-center justify-center">
                        <?php if (!empty($edit_data['profile_image'])): ?>
                            <img src="../<?= htmlspecialchars($edit_data['profile_image']) ?>" alt="Profile" class="w-full h-full object-cover">
                        <?php else: ?>
                            <i class="fas fa-user text-3xl text-gray-400"></i>
                        <?php endif; ?>
                    </div>
                    <div class="flex-1">
                        <input type="file" id="profile_image" name="profile_image" accept="image/*" class="hidden" onchange="previewProfileImage(this)">
                        <input type="hidden" name="current_image" value="<?= $edit_data['profile_image'] ?? '' ?>">
                        <label for="profile_image" class="cursor-pointer inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 font-semibold rounded-lg hover:bg-gray-200 transition-colors">
                            <i class="fas fa-upload mr-2"></i>Choose Image
                        </label>
                        <p class="text-xs text-gray-500 mt-1">JPG, PNG or GIF (Max 2MB)</p>
                    </div>
                </div>
            </div>
            
            <div id="passwordField">
                <label class="block text-sm font-semibold text-gray-700 mb-2"><?= $edit_data ? 'Change Password' : 'Password *' ?></label>
                <input type="password" id="password" name="password" <?= $edit_data ? '' : 'required' ?>
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                <?php if ($edit_data): ?>
                <p class="text-xs text-gray-500 mt-1">Leave blank to keep current password</p>
                <?php endif; ?>
            </div>
            
            <div class="flex items-center justify-end space-x-3 pt-4">
                <button type="button" onclick="closeModal()" class="px-6 py-2 bg-gray-200 text-gray-700 font-semibold rounded-lg hover:bg-gray-300 transition-colors">
                    Cancel
                </button>
                <button type="submit" class="px-6 py-2 bg-primary text-white font-semibold rounded-lg hover:bg-secondary transition-colors">
                    <i class="fas fa-save mr-2"></i>Save User
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function closeModal() {
    window.location.href = 'users.php';
}

function previewProfileImage(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        if (file.size > 2 * 1024 * 1024) {
            alert('Image size must be less than 2MB');
            input.value = '';
            return;
        }
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('imagePreview').innerHTML = 
                `<img src="${e.target.result}" alt="Preview" class="w-full h-full object-cover">`;
        };
        reader.readAsDataURL(file);
    }
}

// Auto-open modals
<?php if (isset($_GET['create']) || $edit_data): ?>
document.getElementById('userModal').classList.remove('hidden');
<?php endif; ?>
</script>
</script>

<?php include "admin_footer.php"; ?>
