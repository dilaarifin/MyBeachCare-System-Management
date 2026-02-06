<?php
session_start();
include "includes/db_conn.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id']) && isset($_POST['event_id'])) {
    $user_id = $_SESSION['user_id'];
    $event_id = $_POST['event_id'];

    try {
        // Check if already registered
        $check_stmt = $conn->prepare("SELECT id FROM event_participants WHERE user_id = :user_id AND event_id = :event_id");
        $check_stmt->execute([':user_id' => $user_id, ':event_id' => $event_id]);
        
        if ($check_stmt->rowCount() > 0) {
            header("Location: events.php?error=" . urlencode("You are already registered for this event."));
            exit();
        }

        // Register user
        $stmt = $conn->prepare("INSERT INTO event_participants (event_id, user_id, status) VALUES (:event_id, :user_id, 'Registered')");
        if ($stmt->execute([':event_id' => $event_id, ':user_id' => $user_id])) {
            
            // Gamification: Award points and check badges
            include_once "includes/gamification.php";
            add_points($user_id, 10); // Award 10 points for joining an event
            check_event_badges($user_id);

            header("Location: events.php?success=" . urlencode("Successfully registered! You earned 10 points."));
        } else {
            header("Location: events.php?error=" . urlencode("Something went wrong. Please try again later."));
        }
    } catch (PDOException $e) {
        header("Location: events.php?error=" . urlencode("Database Error: " . $e->getMessage()));
    }
} else {
    header("Location: events.php");
}
exit();
?>
