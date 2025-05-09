<?php
include('db.php'); // Include the database connection

$s_id = 11;

if (isset($_GET['start'])) {
    // Check current number of panelists for this s_id
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM hedonic WHERE s_id = ?");
    $stmt->bind_param("i", $s_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $current_count = $row['total'];
    $stmt->close();

    // If limit reached
    if ($current_count >= 50) {
        echo "<h3 style='color: red; text-align: center;'>Maximum of 50 panelists reached for this sample.</h3>";
        exit;
    }

    // Generate p_id
    $p_id = $current_count + 1;

    // Insert new p_id and s_id into hedonic table
    $stmt = $conn->prepare("INSERT INTO hedonic (p_id, s_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $p_id, $s_id);
    $stmt->execute();
    $stmt->close();

    // Redirect to form
    header("Location: hedonic_evaluation_form.php?p_id={$p_id}&s_id={$s_id}");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include('header.php'); ?>
    <title>Panelist Access</title>
</head>
<body class="layout-4">
    <div id="app">
        <div class="main-wrapper main-wrapper-1">
            <?php include('topnav.php'); include('sidebar.php'); ?>

            <div class="main-content text-center">
                <h2>Access the Evaluation Form</h2>
                <!-- This triggers the logic above by adding ?start=1 -->
                <a href="?start=1" class="btn btn-primary">Start Evaluation</a>
                <!-- Summary button: redirect to hedonic_summary.php with s_id -->
                <a href="hedonic_summary.php?s_id=<?php echo $s_id; ?>" class="btn btn-primary">Summary</a>
                <a href="generate_all_results_pdf.php?s_id=<?php echo $s_id; ?>" class="btn btn-primary">Individual Results</a>
            </div>

            <?php include('footer.php'); ?>
        </div>
    </div>

    <script src="assets/bundles/lib.vendor.bundle.js"></script>
    <script src="js/CodiePie.js"></script>
    <script src="js/scripts.js"></script>
    <script src="js/custom.js"></script>
</body>
</html>
