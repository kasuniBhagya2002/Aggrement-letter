<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

require_once 'db.php';

$agreement_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($agreement_id > 0) {
    $stmt = $conn->prepare("DELETE FROM agreements WHERE id = ?");
    $stmt->bind_param("i", $agreement_id);
    
    if ($stmt->execute()) {
        $_SESSION['delete_success'] = "ගිවිසුම සාර්ථකව මකා දමන ලදී!";
    } else {
        $_SESSION['delete_error'] = "මකාදැමීමේ දෝෂයක්: " . $stmt->error;
    }
    
    $stmt->close();
}

header("Location: view_agreements.php");
exit;
?>