<?php
// includes/gamification.php

function add_points($user_id, $points) {
    global $conn;
    try {
        $stmt = $conn->prepare("UPDATE users SET points = points + :points WHERE id = :id");
        $stmt->execute([':points' => $points, ':id' => $user_id]);
    } catch (PDOException $e) {
        // Silently fail or log error
    } 
}

function award_badge($user_id, $badge_name) {
    global $conn;
    try {
        // Get badge ID
        $stmt = $conn->prepare("SELECT id FROM badges WHERE name = :name");
        $stmt->execute([':name' => $badge_name]);
        $badge = $stmt->fetch();

        if ($badge) {
            // Check if already awarded
            $check = $conn->prepare("SELECT id FROM user_badges WHERE user_id = :uid AND badge_id = :bid");
            $check->execute([':uid' => $user_id, ':bid' => $badge['id']]);
            
            if ($check->rowCount() == 0) {
                // Award Badge
                $insert = $conn->prepare("INSERT INTO user_badges (user_id, badge_id) VALUES (:uid, :bid)");
                $insert->execute([':uid' => $user_id, ':bid' => $badge['id']]);
                
                // Optional: Notification indicating new badge (store in session?)
                if (!isset($_SESSION['new_badges'])) $_SESSION['new_badges'] = [];
                $_SESSION['new_badges'][] = $badge_name;
            }
        }
    } catch (PDOException $e) {
        // Silently fail
    }
}

function check_event_badges($user_id) {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM event_participants WHERE user_id = :uid AND (status = 'Completed' OR status = 'Attended' OR status = 'Registered')");
        $stmt->execute([':uid' => $user_id]);
        $count = $stmt->fetchColumn();

        if ($count >= 1) {
            award_badge($user_id, 'First Clean-Up');
        }
        if ($count >= 5) {
            award_badge($user_id, '5 Events Joined');
        }
    } catch (PDOException $e) {
        
    }
}

function check_voting_badges($user_id) {
    global $conn;
    try {
        // Count votes
        $v_stmt = $conn->prepare("SELECT COUNT(*) FROM beach_votes WHERE user_id = :uid");
        $v_stmt->execute([':uid' => $user_id]);
        $votes = $v_stmt->fetchColumn();

        // Count reports
        $r_stmt = $conn->prepare("SELECT COUNT(*) FROM reports WHERE user_id = :uid");
        $r_stmt->execute([':uid' => $user_id]);
        $reports = $r_stmt->fetchColumn();

        $total_actions = $votes + $reports;

        // Threshold is now 10 (user requested)
        if ($total_actions >= 10) {
            award_badge($user_id, 'Top Voter');
        }
    } catch (PDOException $e) {
        // Silently fail
    }
}
?>
