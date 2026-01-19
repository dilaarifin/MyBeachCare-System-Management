<?php
session_start();
include "includes/db_conn.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;

if ($event_id === 0) {
    echo "Invalid Event.";
    exit();
}

// Fetch Event and User details, checking participation and certificate release status
$stmt = $conn->prepare("
    SELECT u.full_name, e.title as event_title, e.event_date, e.certificates_released
    FROM event_participants ep
    JOIN events e ON ep.event_id = e.id
    JOIN users u ON ep.user_id = u.id
    WHERE ep.user_id = :user_id AND ep.event_id = :event_id AND ep.status = 'Attended'
");
$stmt->bindParam(':user_id', $user_id);
$stmt->bindParam(':event_id', $event_id);
$stmt->execute();
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    echo "Certificate not available. You must have attended this event, or the certificate has not been generated yet.";
    exit();
}

if (!$data['certificates_released']) {
    echo "Certificates for this event have not been released by the organizer yet.";
    exit();
}

$user_name = $data['full_name'];
$event_title = $data['event_title'];
$event_date_str = date('F d, Y', strtotime($data['event_date']));
$issue_date = date('F d, Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate - <?= htmlspecialchars($user_name) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Pinyon+Script&display=swap" rel="stylesheet">
    <style>
        .font-cinzel { font-family: 'Cinzel', serif; }
        .font-pinyon { font-family: 'Pinyon Script', cursive; }
        @media print {
            @page {
                size: A4 landscape;
                margin: 0;
            }
            html, body {
                width: 297mm;
                height: 210mm;
                margin: 0 !important;
                padding: 0 !important;
                overflow: hidden !important;
            }
            .no-print {
                display: none !important;
            }
            .print-container {
                position: relative !important;
                width: 297mm !important;
                height: 210mm !important;
                margin: 0 !important; /* Reset margin */
                padding: 3rem !important; /* Adjust padding if needed, or keep px */
                box-shadow: none !important;
                border: 20px double #d97706 !important;
                box-sizing: border-box;
                left: auto !important;
                top: auto !important;
                overflow: hidden !important;
                page-break-after: avoid;
                page-break-inside: avoid;
            }
            /* Universal print fix */
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col items-center py-10">

    <!-- Actions -->
    <div class="no-print mb-8 space-x-4">
        <button onclick="window.print()" class="px-6 py-3 bg-blue-600 text-white font-bold rounded-lg shadow-lg hover:bg-blue-700 transition flex items-center gap-2">
            <i class="fas fa-print"></i> Print Certificate
        </button>
        <a href="profile.php" class="px-6 py-3 bg-gray-500 text-white font-bold rounded-lg shadow-lg hover:bg-gray-600 transition flex items-center gap-2">
            <i class="fas fa-arrow-left"></i> Back to Profile
        </a>
    </div>

    <!-- Certificate Container -->
    <div class="print-container w-[1123px] h-[794px] bg-white relative p-12 shadow-2xl text-center border-[20px] border-double border-yellow-600 flex flex-col items-center justify-between mx-auto" style="width: 297mm; height: 210mm;">
        
        <!-- Background Pattern -->
        <div class="absolute inset-0 opacity-5 pointer-events-none" style="background-image: url('img/logo.png'); background-repeat: no-repeat; background-position: center; background-size: 50%;"></div>

        <!-- Certificate Header -->
        <div class="mt-8 relative z-10">
            <img src="img/logo.png" alt="Logo" class="h-24 mx-auto mb-4 opacity-80">
            <h1 class="text-6xl font-cinzel font-bold text-gray-900 tracking-widest uppercase">Certificate</h1>
            <h2 class="text-3xl font-cinzel text-gray-500 tracking-widest uppercase mt-2">of Appreciation</h2>
        </div>

        <!-- Content -->
        <div class="flex-1 flex flex-col justify-center w-full relative z-10">
            <p class="text-xl text-gray-500 italic mb-4">This certificate is proudly presented to</p>
            <h3 class="text-6xl font-pinyon text-yellow-600 mb-6"><?= htmlspecialchars(ucwords($user_name)) ?></h3>
            <p class="text-lg text-gray-600 max-w-3xl mx-auto leading-relaxed px-8">
                For their dedicated service and participation in the <br>
                <span class="font-bold text-gray-800 text-2xl">"<?= htmlspecialchars($event_title) ?>"</span> <br>
                held on <?= $event_date_str ?>.
                <br><br>
                Your commitment to preserving our coastlines makes a difference.
            </p>
        </div>

        <!-- Footer -->
        <div class="w-full flex justify-between items-end px-16 pb-8 relative z-10">
            <div class="text-center">
                <div class="w-48 border-b-2 border-gray-400 mb-2"></div>
                <p class="font-cinzel font-bold text-gray-600">Dila Arifin</p>
                <p class="text-xs text-gray-400 uppercase tracking-widest">Founder, MyBeachCare</p>
            </div>

            <div class="text-center">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=VerifiedCertificate-<?= $user_id ?>" alt="QR" class="w-16 h-16 mx-auto mb-2 opacity-80">
                <p class="text-xs text-gray-400"><?= $issue_date ?></p>
            </div>
        </div>
    </div>

</body>
</html>
