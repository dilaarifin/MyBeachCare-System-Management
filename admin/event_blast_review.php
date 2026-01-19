<?php
include "admin_auth.php";
include "admin_header.php";

$event_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($event_id === 0) {
    echo "<div class='p-6 text-red-600'>Invalid Event ID. <a href='events.php' class='underline'>Back to Events</a></div>";
    include "admin_footer.php";
    exit();
}

// Fetch event details
$stmt = $conn->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    echo "<div class='p-6 text-red-600'>Event not found. <a href='events.php' class='underline'>Back to Events</a></div>";
    include "admin_footer.php";
    exit();
}

// Fetch participants who are 'Registered' (and will be marked Attended) OR 'Attended'
$stmt = $conn->prepare("
    SELECT p.*, u.full_name, u.email 
    FROM event_participants p 
    JOIN users u ON p.user_id = u.id 
    WHERE p.event_id = ? AND (p.status = 'Registered' OR p.status = 'Attended')
    ORDER BY u.full_name ASC
");
$stmt->execute([$event_id]);
$participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count stats
$registered_count = 0;
foreach ($participants as $p) {
    if ($p['status'] == 'Registered') $registered_count++;
}
?>

<div class="max-w-4xl mx-auto py-10 px-4">
    
    <div class="mb-8">
        <a href="events.php" class="text-gray-500 hover:text-gray-900 flex items-center gap-2 mb-4">
            <i class="fas fa-arrow-left"></i> Back to Events
        </a>
        <h1 class="text-3xl font-bold text-gray-900">Review Certificate Release</h1>
        <p class="text-gray-500 mt-2">You are about to release certificates for <span class="font-bold text-gray-800">"<?= htmlspecialchars($event['title']) ?>"</span>.</p>
    </div>

    <div class="bg-amber-50 border border-amber-200 rounded-xl p-6 mb-8 flex items-start gap-4 shadow-sm">
        <div class="bg-amber-100 p-3 rounded-full text-amber-600">
            <i class="fas fa-exclamation-triangle text-xl"></i>
        </div>
        <div>
            <h3 class="text-lg font-bold text-amber-800">Action Required</h3>
            <p class="text-amber-700 mt-1">
                This action will:
                <ul class="list-disc list-inside ml-4 mt-2 space-y-1">
                    <li>Mark <strong><?= $registered_count ?></strong> 'Registered' participants as 'Attended'.</li>
                    <li>Make certificates available for download to all Attended participants.</li>
                    <li>This action cannot be easily undone.</li>
                </ul>
            </p>
        </div>
    </div>

    <form action="events.php" method="POST">
        <input type="hidden" name="action" value="blast_certificate">
        <input type="hidden" name="id" value="<?= $event['id'] ?>">

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-8">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                <h3 class="font-bold text-gray-700">Recipient List</h3>
                <span class="text-sm bg-blue-100 text-blue-700 px-3 py-1 rounded-full font-bold"><?= count($participants) ?> Participants</span>
            </div>
            
            <div class="max-h-96 overflow-y-auto">
                <?php if (empty($participants)): ?>
                    <div class="p-8 text-center text-gray-400 italic">No participants found.</div>
                <?php else: ?>
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 text-gray-500 font-semibold sticky top-0">
                            <tr>
                                <th class="px-6 py-3 w-10">
                                    <input type="checkbox" id="selectAll" class="rounded border-gray-300 text-amber-600 focus:ring-amber-500" onclick="toggleAll(this)">
                                </th>
                                <th class="px-6 py-3">Name</th>
                                <th class="px-6 py-3">Email</th>
                                <th class="px-6 py-3">Current Status</th>
                                <th class="px-6 py-3">Effect</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach ($participants as $p): ?>
                                <tr class="hover:bg-gray-50 transition-colors cursor-pointer" onclick="document.getElementById('cb_<?= $p['id'] ?>').click()">
                                    <td class="px-6 py-3" onclick="event.stopPropagation()">
                                        <input type="checkbox" name="participated[]" value="<?= $p['id'] ?>" id="cb_<?= $p['id'] ?>"
                                            class="rounded border-gray-300 text-amber-600 focus:ring-amber-500 participant-cb"
                                            <?= $p['status'] == 'Attended' ? 'checked' : '' ?>>
                                    </td>
                                    <td class="px-6 py-3 font-medium text-gray-900"><?= htmlspecialchars($p['full_name']) ?></td>
                                    <td class="px-6 py-3 text-gray-500"><?= htmlspecialchars($p['email']) ?></td>
                                    <td class="px-6 py-3">
                                        <span class="px-2 py-1 rounded-lg text-xs font-bold uppercase 
                                            <?= $p['status'] == 'Attended' ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-blue-700' ?>">
                                            <?= $p['status'] ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-3 text-xs font-bold">
                                        <?php if ($p['status'] == 'Registered'): ?>
                                            <span class="text-emerald-600 flex items-center gap-1">
                                                <i class="fas fa-check-circle"></i> Will Upgrade if Checked
                                            </span>
                                        <?php else: ?>
                                            <span class="text-gray-400">Already Attended</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <div class="flex justify-end gap-4">
            <a href="events.php" class="px-6 py-3 rounded-xl border border-gray-300 text-gray-600 font-bold hover:bg-gray-50 transition-colors">
                Cancel
            </a>
            <button type="submit" class="px-8 py-3 bg-amber-600 text-white rounded-xl font-bold shadow-lg shadow-amber-600/20 hover:bg-amber-700 transition-all transform hover:scale-105"
                <?= empty($participants) ? 'disabled style="opacity:0.5; cursor:not-allowed;"' : '' ?>>
                <i class="fas fa-paper-plane mr-2"></i> Confirm & Release Certificates
            </button>
        </div>
    </form>

    <script>
        function toggleAll(source) {
            checkboxes = document.querySelectorAll('.participant-cb');
            for(var i=0, n=checkboxes.length;i<n;i++) {
                checkboxes[i].checked = source.checked;
            }
        }
    </script>

</div>

<?php include "admin_footer.php"; ?>
