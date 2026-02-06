<?php
session_start();
include "includes/db_conn.php";

// Internal AJAX Handler for Beaches by State
if (isset($_GET['get_beaches_for_state'])) {
    $state = $_GET['get_beaches_for_state'];
    try {
        $stmt = $conn->prepare("SELECT id, name FROM beaches WHERE state = :state ORDER BY name ASC");
        $stmt->execute([':state' => $state]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

ob_start();

$isLoggedIn = isset($_SESSION['user_id']);

// Redirect if not logged in
if (!$isLoggedIn) {
    header("Location: index.php?error=" . urlencode("Please log in to report an issue."));
    exit();
}

// Fetch states for the dropdown
try {
    $states_stmt = $conn->query("SELECT DISTINCT state FROM beaches WHERE state IS NOT NULL AND state != '' ORDER BY state ASC");
    $states = $states_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $error = "Database Error: " . $e->getMessage();
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $beach_id = $_POST['beach_id'];
    $type = $_POST['type'];
    $description = $_POST['description'];
    $user_id = $_SESSION['user_id'];
    $image_path = null;

    // Handle Image Upload
    if (!empty($_FILES['image']['name'])) {
        $target_dir = "uploads/reports/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
        $new_filename = uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $new_filename;
        
        // Check if image file is a actual image
        $check = getimagesize($_FILES["image"]["tmp_name"]);
        if($check !== false) {
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                $image_path = $target_file;
            } else {
                $form_error = "Sorry, there was an error uploading your file.";
            }
        } else {
            $form_error = "File is not an image.";
        }
    }

    if (!isset($form_error)) {
        try {
            $stmt = $conn->prepare("INSERT INTO reports (user_id, beach_id, type, description, image, status) VALUES (:user_id, :beach_id, :type, :description, :image, 'Pending')");
            $stmt->execute([
                ':user_id' => $user_id,
                ':beach_id' => $beach_id,
                ':type' => $type,
                ':description' => $description,
                ':image' => $image_path
            ]);

            // Add Points and Check Badge
            include_once "includes/gamification.php";
            add_points($user_id, 10); // 10 points for reporting
            check_voting_badges($user_id);
            
            header("Location: profile.php?success=" . urlencode("Report submitted successfully! Thank you for your contribution."));
            exit();
        } catch (PDOException $e) {
            $form_error = "Database Error: " . $e->getMessage();
        }
    }
}
include "includes/header.php";
?>

    <!-- Hero Section -->
    <section class="relative bg-gradient-to-b from-rose-50 via-white to-white pb-12 pt-16 overflow-hidden">
        <div class="absolute inset-0 bg-hero-pattern opacity-5"></div>
        
        <div class="container mx-auto px-6 relative z-10 pt-8">
            <div class="flex flex-col items-center justify-center text-center space-y-6 mb-12">
                <div class="space-y-2">
                    <h2 class="text-5xl md:text-6xl font-outfit font-extrabold tracking-tight text-gray-900">
                        Report an <span class="text-rose-600 italic">Issue</span>
                    </h2>
                </div>
                <p class="max-w-xl text-lg text-gray-600 leading-relaxed font-light">
                    Help us keep Malaysia's beaches pristine. Found trash, damage, or other issues? Let us know so our community can take action.
                </p>
            </div>
        </div>
    </section>

    <main class="container mx-auto px-6 py-12 max-w-3xl">
        <!-- View All Reports Button -->
        <div class="flex justify-end mb-6">
            <button onclick="toggleReports()" class="px-6 py-3 bg-white border border-gray-200 text-gray-600 font-bold rounded-2xl hover:bg-gray-50 transition-all flex items-center gap-2 shadow-sm">
                <i class="fas fa-list-ul"></i>
                <span id="toggle-text">View All Reports</span>
            </button>
        </div>

        <!-- All Reports Section (Hidden initially) -->
        <div id="all-reports-section" class="hidden mb-12 space-y-6">
            <h3 class="text-2xl font-bold text-gray-900 border-b pb-4">Community Reports</h3>
            <?php
            try {
                $reports_stmt = $conn->query("SELECT r.*, b.name as beach_name, u.username FROM reports r JOIN beaches b ON r.beach_id = b.id JOIN users u ON r.user_id = u.id ORDER BY r.created_at DESC");
                $all_reports = $reports_stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (empty($all_reports)): ?>
                    <p class="text-gray-500 text-center py-8">No reports found yet.</p>
                <?php else: ?>
                    <div class="grid grid-cols-1 gap-4">
                        <?php foreach ($all_reports as $rep): ?>
                            <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                                <div class="flex justify-between items-start mb-4">
                                    <div>
                                        <span class="text-xs font-bold text-primary uppercase tracking-wider"><?= htmlspecialchars($rep['type']) ?></span>
                                        <h4 class="font-bold text-gray-900"><?= htmlspecialchars($rep['beach_name']) ?></h4>
                                    </div>
                                    <span class="text-xs px-3 py-1 rounded-full font-bold uppercase 
                                        <?= $rep['status'] == 'Pending' ? 'bg-amber-100 text-amber-600' : ($rep['status'] == 'Resolved' ? 'bg-emerald-100 text-emerald-600' : 'bg-gray-100 text-gray-400') ?>">
                                        <?= $rep['status'] ?>
                                    </span>
                                </div>
                                <p class="text-sm text-gray-600 mb-4"><?= htmlspecialchars($rep['description']) ?></p>
                                <div class="flex items-center justify-between text-[10px] text-gray-400 font-bold uppercase">
                                    <span>By @<?= htmlspecialchars($rep['username']) ?></span>
                                    <span><?= date('M d, Y', strtotime($rep['created_at'])) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif;
            } catch (PDOException $e) {}
            ?>
        </div>

        <div id="report-form-container" class="glass p-8 md:p-12 rounded-[2rem] border border-gray-100 shadow-2xl relative overflow-hidden">
            <!-- Form Header Decor -->
            <div class="absolute top-0 right-0 w-32 h-32 bg-primary/5 rounded-bl-full -mr-16 -mt-16"></div>
            
            <?php if (isset($form_error)): ?>
                <div class="mb-8 p-4 bg-rose-50 border border-rose-100 text-rose-600 rounded-2xl flex items-center space-x-3 text-sm font-medium">
                    <i class="fas fa-circle-exclamation text-lg"></i>
                    <span><?= $form_error ?></span>
                </div>
            <?php endif; ?>

            <form action="report.php" method="POST" enctype="multipart/form-data" class="space-y-8 relative z-10">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- State Selection -->
                    <div class="space-y-2">
                        <label class="block text-sm font-bold text-gray-700 ml-1">Select State</label>
                        <div class="relative group">
                            <i class="fas fa-map absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-primary transition-colors"></i>
                            <select name="state" id="state-select" required 
                                onchange="fetchBeachesByState(this.value)"
                                class="w-full pl-11 pr-4 py-4 rounded-2xl border border-gray-200 bg-white focus:outline-none focus:ring-4 focus:ring-primary/10 focus:border-primary transition-all appearance-none">
                                <option value="" disabled selected>Choose a state...</option>
                                <?php foreach ($states as $state): ?>
                                    <option value="<?= htmlspecialchars($state) ?>"><?= htmlspecialchars($state) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                        </div>
                    </div>

                    <!-- Beach Selection -->
                    <div class="space-y-2">
                        <label class="block text-sm font-bold text-gray-700 ml-1">Select Beach</label>
                        <div class="relative group">
                            <i class="fas fa-map-marker-alt absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-primary transition-colors"></i>
                            <select name="beach_id" id="beach-select" required disabled class="w-full pl-11 pr-4 py-4 rounded-2xl border border-gray-200 bg-white focus:outline-none focus:ring-4 focus:ring-primary/10 focus:border-primary transition-all appearance-none disabled:bg-gray-50 disabled:text-gray-400">
                                <option value="" disabled selected>First, choose a state...</option>
                            </select>
                            <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                        </div>
                    </div>
                </div>

                <!-- Report Type -->
                <div class="space-y-2">
                    <label class="block text-sm font-bold text-gray-700 ml-1">Issue Type</label>
                    <div class="relative group">
                        <i class="fas fa-tag absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-primary transition-colors"></i>
                        <select name="type" required class="w-full pl-11 pr-4 py-4 rounded-2xl border border-gray-200 bg-white focus:outline-none focus:ring-4 focus:ring-primary/10 focus:border-primary transition-all appearance-none">
                            <option value="Trash/Litter">Trash/Litter</option>
                            <option value="Trash Accumulation">Trash Accumulation</option>
                            <option value="Safety Hazard">Safety Hazard</option>
                            <option value="Wildlife Concern">Wildlife Concern</option>
                            <option value="Other">Other</option>
                        </select>
                        <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                    </div>
                </div>

                <!-- Description -->
                <div class="space-y-2">
                    <label class="block text-sm font-bold text-gray-700 ml-1">Details</label>
                    <div class="relative group">
                        <i class="fas fa-message absolute left-4 top-5 text-gray-400 group-focus-within:text-primary transition-colors"></i>
                        <textarea name="description" required rows="5" placeholder="Describe the issue in detail..." 
                            class="w-full pl-11 pr-4 py-4 rounded-2xl border border-gray-200 bg-white focus:outline-none focus:ring-4 focus:ring-primary/10 focus:border-primary transition-all"></textarea>
                    </div>
                </div>

                <!-- Image Upload -->
                <div class="space-y-2">
                    <label class="block text-sm font-bold text-gray-700 ml-1">Upload Evidence (Optional)</label>
                    <div class="flex items-center justify-center w-full">
                        <label class="flex flex-col items-center justify-center w-full h-48 border-2 border-dashed border-gray-200 rounded-3xl cursor-pointer bg-gray-50/50 hover:bg-gray-50 transition-all hover:border-primary/40 group">
                            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                <div id="upload-icon-container" class="w-12 h-12 bg-white rounded-xl shadow-sm flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                                    <i class="fas fa-cloud-upload-alt text-xl text-gray-400 group-hover:text-primary transition-colors"></i>
                                </div>
                                <p class="mb-2 text-sm text-gray-600 font-medium">Click to upload or drag and drop</p>
                                <p class="text-xs text-gray-400">PNG, JPG or JPEG (Max. 5MB)</p>
                            </div>
                            <input type="file" name="image" id="issue-image" class="hidden" accept="image/*" onchange="previewIssueImage(this)" />
                        </label>
                    </div>
                    <!-- Image Preview Container -->
                    <div id="image-preview" class="hidden mt-4 relative w-full h-64 rounded-2xl overflow-hidden shadow-md">
                        <img src="" class="w-full h-full object-cover" />
                        <button type="button" onclick="clearPreview()" class="absolute top-4 right-4 w-10 h-10 bg-black/50 backdrop-blur-md text-white rounded-full flex items-center justify-center hover:bg-rose-500 transition-colors">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="pt-4">
                    <button type="submit" class="w-full py-5 bg-primary text-white font-black text-lg rounded-2xl hover:bg-black transition-all duration-300 shadow-xl hover:shadow-primary/30 flex items-center justify-center space-x-3 group active:scale-[0.98]">
                        <span>Submit Report</span>
                        <i class="fas fa-paper-plane text-sm group-hover:translate-x-1 group-hover:-translate-y-1 transition-transform"></i>
                    </button>
                </div>
            </form>
        </div>
    </main>

    <script>
        async function fetchBeachesByState(state) {
            const beachSelect = document.getElementById('beach-select');
            beachSelect.disabled = true;
            beachSelect.innerHTML = '<option value="" disabled selected>Loading beaches...</option>';

            try {
                const response = await fetch('report.php?get_beaches_for_state=' + encodeURIComponent(state));
                const beaches = await response.json();

                beachSelect.innerHTML = '<option value="" disabled selected>Choose a beach...</option>';
                if (beaches.length > 0) {
                    beaches.forEach(beach => {
                        const option = document.createElement('option');
                        option.value = beach.id;
                        option.textContent = beach.name;
                        beachSelect.appendChild(option);
                    });
                    beachSelect.disabled = false;
                } else {
                    beachSelect.innerHTML = '<option value="" disabled selected>No beaches found in this state</option>';
                }
            } catch (error) {
                console.error('Error fetching beaches:', error);
                beachSelect.innerHTML = '<option value="" disabled selected>Error loading beaches</option>';
            }
        }

        function toggleReports() {
            const section = document.getElementById('all-reports-section');
            const text = document.getElementById('toggle-text');
            const formContainer = document.getElementById('report-form-container');
            
            if (section.classList.contains('hidden')) {
                section.classList.remove('hidden');
                text.textContent = 'Hide All Reports';
                formContainer.classList.add('opacity-50', 'pointer-events-none');
            } else {
                section.classList.add('hidden');
                text.textContent = 'View All Reports';
                formContainer.classList.remove('opacity-50', 'pointer-events-none');
            }
        }

        function previewIssueImage(input) {
            const preview = document.getElementById('image-preview');
            const img = preview.querySelector('img');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    img.src = e.target.result;
                    preview.classList.remove('hidden');
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        function clearPreview() {
            const preview = document.getElementById('image-preview');
            const input = document.getElementById('issue-image');
            input.value = '';
            preview.classList.add('hidden');
        }

    </script>

<?php include "includes/footer.php"; ?>
