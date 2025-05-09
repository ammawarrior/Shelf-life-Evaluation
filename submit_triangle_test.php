<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'db.php'; // database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'];
    $request_no = $_POST['request_no'];
    $code_name = $_POST['code_name'];
    $product_code = $_POST['product_code'];
    $sample_code1 = $_POST['sample_code1'];
    $sample_code2 = $_POST['sample_code2'];
    $sample_code3 = $_POST['sample_code3'];
    $odd_sample = $_POST['odd_sample'];
    $comments = $_POST['comments'];
    $date = $_POST['date'];

    // Prevent double submission
    $check = $conn->prepare("SELECT * FROM triangle_results WHERE user_id = ? AND product_code = ?");
    $check->bind_param("is", $user_id, $product_code);
    $check->execute();
    $check_result = $check->get_result();
    if ($check_result->num_rows > 0) {
        echo "<script>alert('You have already submitted this evaluation.'); window.location.href='dashboard_p.php';</script>";
        exit();
    }

    // Get triad_no
    $triadStmt = $conn->prepare("SELECT triad_no FROM evaluation_assignments WHERE user_id = ? AND request_no = ?");
    $triadStmt->bind_param("is", $user_id, $request_no); // ✅
    $triadStmt->execute();
    $triadResult = $triadStmt->get_result();
    $triadRow = $triadResult->fetch_assoc();
    $triad_no = $triadRow ? $triadRow['triad_no'] : null;

    if (!$triad_no) {
        echo "<script>alert('Triad number not found.'); window.history.back();</script>";
        exit();
    }

    // ✅ Corrected: Bind types match the values — all strings except user_id (int) and triad_no (int)
    $stmt = $conn->prepare("INSERT INTO triangle_results 
        (user_id, code_name, product_code, request_no, triad_no, sample_code1, sample_code2, sample_code3, odd_sample, comments, date)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    // i = int, s = string
    $stmt->bind_param("isssissssss", 
        $user_id, 
        $code_name, 
        $product_code, 
        $request_no,
        $triad_no, 
        $sample_code1, 
        $sample_code2, 
        $sample_code3, 
        $odd_sample, 
        $comments, 
        $date
    );

    if ($stmt->execute()) {
        $update = $conn->prepare("UPDATE evaluation_assignments SET is_submitted = 1 WHERE user_id = ? AND request_no = ?");
        $update->bind_param("is", $user_id, $request_no);
        $update->execute();

        echo "<script>alert('Evaluation submitted successfully.'); window.location.href='dashboard_p.php';</script>";
    } else {
        // ✅ Show error info if insert fails
        echo "<script>alert('Submission failed: " . $stmt->error . "'); window.history.back();</script>";
    }
}
?>
