<?php
session_start();
require_once "../includes/db_conn.php";

// Check admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$event_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($event_id <= 0) {
    die("Invalid Event ID");
}

// Fetch event details
try {
    $query = "SELECT e.*, b.name as beach_name, b.state as beach_state, u.full_name as organizer_name, u.email as organizer_email, l.full_name as leader_name
              FROM events e 
              LEFT JOIN beaches b ON e.beach_id = b.id 
              LEFT JOIN users u ON e.organizer_id = u.id 
              LEFT JOIN users l ON e.leader_id = l.id 
              WHERE e.id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':id', $event_id);
    $stmt->execute();
    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        die("Event not found");
    }

    // Fetch participants
    $part_query = "SELECT u.full_name, u.email, u.phone, ep.status, ep.created_at as registered_at
                   FROM event_participants ep
                   JOIN users u ON ep.user_id = u.id
                   WHERE ep.event_id = :id
                   ORDER BY u.full_name ASC";
    $part_stmt = $conn->prepare($part_query);
    $part_stmt->bindValue(':id', $event_id);
    $part_stmt->execute();
    $participants = $part_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Details - <?= htmlspecialchars($event['title']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: white;
            }
            .shadow-sm, .shadow-md, .shadow-lg {
                box-shadow: none !important;
            }
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen p-8">

    <div class="max-w-4xl mx-auto bg-white p-8 shadow-lg rounded-xl print:shadow-none print:p-0">
        
        <!-- Header / Actions -->
        <div class="flex justify-between items-start mb-8 no-print">
            <a href="events.php" class="text-gray-600 hover:text-gray-900 flex items-center gap-2">
                <i class="fas fa-arrow-left"></i> Back to Events
            </a>
            <button onclick="window.print()" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 flex items-center gap-2">
                <i class="fas fa-print"></i> Print Details
            </button>
        </div>

        <!-- Event Letterhead -->
        <div class="border-b-2 border-gray-200 pb-6 mb-6 flex flex-col items-center text-center">
             <div class="flex items-center gap-3 mb-2">
                <img src="../img/logo.png" alt="Logo" class="h-12 w-auto object-contain">
                <h1 class="text-2xl font-bold text-gray-900">MyBeachCare Event Report</h1>
             </div>
             <p class="text-gray-500 text-sm">Generated on <?= date('F j, Y, g:i a') ?></p>
        </div>

        <!-- Event Details -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
            <div>
                <h2 class="text-xl font-bold text-gray-800 mb-4">Event Information</h2>
                <div class="space-y-3">
                    <div>
                        <span class="block text-sm font-semibold text-gray-500 uppercase tracking-wider">Title</span>
                        <p class="text-gray-900 font-medium text-lg"><?= htmlspecialchars($event['title']) ?></p>
                    </div>
                    <div>
                        <span class="block text-sm font-semibold text-gray-500 uppercase tracking-wider">Date & Time</span>
                        <p class="text-gray-900"><?= date('l, F j, Y', strtotime($event['event_date'])) ?> at <?= date('g:i A', strtotime($event['event_date'])) ?></p>
                    </div>
                    <div>
                        <span class="block text-sm font-semibold text-gray-500 uppercase tracking-wider">Location</span>
                        <p class="text-gray-900"><?= htmlspecialchars($event['beach_name']) ?> <?= !empty($event['beach_state']) ? '('.htmlspecialchars($event['beach_state']).')' : '' ?></p>
                    </div>
                    <div>
                        <span class="block text-sm font-semibold text-gray-500 uppercase tracking-wider">Status</span>
                        <span class="inline-block px-2 py-1 text-xs font-bold uppercase rounded 
                            <?php
                                if ($event['status'] == 'Upcoming') echo 'bg-blue-100 text-blue-800';
                                elseif ($event['status'] == 'Completed') echo 'bg-green-100 text-green-800';
                                else echo 'bg-red-100 text-red-800';
                            ?>">
                            <?= $event['status'] ?>
                        </span>
                    </div>
                </div>
            </div>

            <div>
                 <h2 class="text-xl font-bold text-gray-800 mb-4">Organizer Details</h2>
                 <div class="space-y-3">
                    <div>
                        <span class="block text-sm font-semibold text-gray-500 uppercase tracking-wider">Organizer</span>
                        <p class="text-gray-900"><?= htmlspecialchars($event['organizer_name']) ?></p>
                        <p class="text-gray-500 text-sm"><?= htmlspecialchars($event['organizer_email']) ?></p>
                    </div>
                    <?php if (!empty($event['leader_name'])): ?>
                    <div>
                        <span class="block text-sm font-semibold text-gray-500 uppercase tracking-wider">Team Leader</span>
                        <p class="text-gray-900"><?= htmlspecialchars($event['leader_name']) ?></p>
                    </div>
                    <?php endif; ?>
                    <div>
                        <span class="block text-sm font-semibold text-gray-500 uppercase tracking-wider">Description</span>
                        <p class="text-gray-700 text-sm leading-relaxed"><?= nl2br(htmlspecialchars($event['description'])) ?></p>
                    </div>
                 </div>
            </div>
        </div>

        <!-- Participants List -->
        <div>
            <div class="flex justify-between items-center mb-4 border-b border-gray-200 pb-2">
                <h2 class="text-xl font-bold text-gray-800">Participants List</h2>
                <span class="bg-gray-100 text-gray-800 text-sm font-semibold px-3 py-1 rounded-full">
                    Total: <?= count($participants) ?>
                </span>
            </div>
            
            <?php if (count($participants) > 0): ?>
                <table class="min-w-full text-left text-sm">
                    <thead>
                        <tr class="text-gray-500 border-b border-gray-200">
                            <th class="py-2 font-semibold">#</th>
                            <th class="py-2 font-semibold">Name</th>
                            <th class="py-2 font-semibold">Email</th>
                            <th class="py-2 font-semibold">Phone</th>
                            <th class="py-2 font-semibold">Status</th>
                            <th class="py-2 font-semibold">Registered</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($participants as $index => $p): ?>
                            <tr>
                                <td class="py-3 text-gray-400"><?= $index + 1 ?></td>
                                <td class="py-3 font-medium text-gray-900"><?= htmlspecialchars($p['full_name']) ?></td>
                                <td class="py-3 text-gray-600"><?= htmlspecialchars($p['email']) ?></td>
                                <td class="py-3 text-gray-600"><?= htmlspecialchars($p['phone'] ?? '-') ?></td>
                                <td class="py-3">
                                    <span class="px-2 py-1 text-xs rounded-full 
                                        <?php 
                                            echo match($p['status']) {
                                                'Registered' => 'bg-blue-50 text-blue-600',
                                                'Attended' => 'bg-green-50 text-green-600',
                                                'Cancelled' => 'bg-gray-100 text-gray-500',
                                                default => 'bg-gray-100',
                                            };
                                        ?>">
                                        <?= $p['status'] ?>
                                    </span>
                                </td>
                                <td class="py-3 text-gray-500"><?= date('M j, Y', strtotime($p['registered_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="text-center py-8 text-gray-500 bg-gray-50 rounded-lg border border-dashed border-gray-300">
                    No participants registered for this event yet.
                </div>
            <?php endif; ?>
        </div>

        <div class="mt-12 pt-6 border-t border-gray-200 text-center text-xs text-gray-400">
            <p>Printed by <?= htmlspecialchars($_SESSION['full_name'] ?? 'Admin') ?> on <?= date('Y-m-d H:i:s') ?></p>
        </div>

    </div>

</body>
</html>
