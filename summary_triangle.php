<?php 
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include('includes/header.php');
include 'db.php';

if (!isset($_GET['request_no'])) {
    echo "No request specified.";
    exit();
}

$request_no = $_GET['request_no'];

$stmt = $conn->prepare("SELECT lab_code_no, sample_code_no, date_of_computation FROM evaluation_requests WHERE request_no = ?");
$stmt->bind_param("s", $request_no);
$stmt->execute();
$stmt->bind_result($lab_code_no, $sample_code_no, $date_of_computation);
$stmt->fetch();
$stmt->close();

// Correct odd_sample mapping by triad_no
$correct_answers = [
    1 => '1', 2 => '3', 3 => '2', 4 => '1', 5 => '3', 6 => '2',
    7 => '1', 8 => '3', 9 => '2', 10 => '1', 11 => '3', 12 => '2'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="assets/modules/datatables/datatables.min.css">
    <link rel="stylesheet" href="assets/modules/datatables/DataTables-1.10.16/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="assets/modules/datatables/Select-1.2.4/css/select.bootstrap4.min.css">
    <style>
    .button-group-gap button + button {
        margin-left: 10px;
    }
</style>

</head>
<body class="layout-4">
<div class="page-loader-wrapper">
        <span class="loader"><span class="loader-inner"></span></span>
    </div>
    <div id="app">
        <div class="main-wrapper main-wrapper-1">
            <?php include('includes/topnav.php'); ?>
            <?php include('includes/sidebar.php'); ?>
            <div class="main-content">
                <section class="section">
                    <div class="section-header">
                        <h1>Sensory Evaluation</h1>
                    </div>
                    <div class="section-body">
                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h4>Triangle Test Result Summary</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6 form-group">
                                                <label>Request Code:</label>
                                                <input type="text" class="form-control uniform-input" value="<?= htmlspecialchars($request_no) ?>" readonly>
                                            </div>

                                            <div class="col-md-6 form-group">
                                                <label>Sample Code:</label>
                                                <input type="text" class="form-control uniform-input" value="<?= htmlspecialchars($lab_code_no) ?>" readonly>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 form-group">
                                                <label>Sample Description:</label>
                                                <input type="text" class="form-control uniform-input" value="<?= htmlspecialchars($sample_code_no) ?>" readonly>
                                            </div>
                                            <div class="col-md-6 form-group">
                                                <label>Date of Computation:</label>
                                                <input type="text" class="form-control uniform-input" value="<?= htmlspecialchars($date_of_computation) ?>" readonly>
                                            </div>
                                        </div>

                                        <div class="card mt-4">
                                            <div class="card-body">
                                                <div class="table-responsive">
                                                    <table class="table table-bordered table-striped">
                                                        <thead class="text-center">
                                                            <tr>
                                                                <th>#</th>
                                                                <!-- Combination column removed -->
                                                                <th>Panelist No.</th>
                                                                <th>Correct</th>
                                                                <th>Incorrect</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                        <?php
                                                        $assignment_stmt = $conn->prepare("SELECT triad_no, user_id, triad_type FROM evaluation_assignments WHERE request_no = ?");
                                                        $assignment_stmt->bind_param("s", $request_no);
                                                        $assignment_stmt->execute();
                                                        $assignment_result = $assignment_stmt->get_result();

                                                        $triad_data = [];
                                                        while ($row = $assignment_result->fetch_assoc()) {
                                                            $triad_no = (int)$row['triad_no'];
                                                            $triad_data[$triad_no] = $row;
                                                        }
                                                        $assignment_stmt->close();

                                                        $correct_count = 0;
                                                        $wrong_count = 0;

                                                        for ($i = 1; $i <= 12; $i++) {
                                                            $row = $triad_data[$i] ?? null;
                                                            if (!$row) {
                                                                echo "<tr><td class='text-center'>{$i}.</td><td class='text-center'>---</td><td></td><td></td></tr>";
                                                                continue;
                                                            }
                                                        
                                                            $triad_no = (int)$row['triad_no']; // this line is important
                                                            $user_id = (int)$row['user_id'];
                                                        
                                                            $result_stmt = $conn->prepare("SELECT odd_sample FROM triangle_results WHERE user_id = ? AND product_code = ? LIMIT 1");
                                                            $product_code = $lab_code_no;
                                                            $result_stmt->bind_param("is", $user_id, $product_code);
                                                            $result_stmt->execute();
                                                            $result_stmt->bind_result($odd_sample);
                                                            $result_stmt->fetch();
                                                            $result_stmt->close();
                                                        
                                                            $odd_sample = trim((string)$odd_sample);
                                                            $correct = $correct_answers[$i] ?? null;
                                                        
                                                            echo "<tr><td class='text-center'>{$i}.</td>
                                                                <td class='text-center'>{$triad_no}</td>"; // <-- CHANGED THIS LINE
                                                        
                                                            if ($odd_sample === $correct) {
                                                                $correct_count++;
                                                                echo "<td class='text-success font-weight-bold text-center' style='font-size: 24px;'>✓</td><td></td>";
                                                            } else {
                                                                $wrong_count++;
                                                                echo "<td></td><td class='text-danger font-weight-bold text-center' style='font-size: 24px;'>✓</td>";
                                                            }
                                                        
                                                            echo "</tr>";
                                                        }
                                                        
                                                        
                                                        ?>
                                                        <tr>
                                                            
                                                            <td colspan="2" class="text-right font-weight-bold">Total</td>
                                                            <td class="text-success font-weight-bold text-center"><?= $correct_count ?></td>
                                                            <td class="text-danger font-weight-bold text-center"><?= $wrong_count ?></td>
                                                        </tr>
                                                        </tbody>
                                                    </table>
                                                    <div class="d-flex justify-content-end button-group-gap mb-3">
    <button type="button" class="btn btn-success btn-sm" onclick="printPage()">Print Triangle Test Result Summary</button>
    <button type="button" class="btn btn-info btn-sm" onclick="printPageCom()">Print Triangle Test Result Comments</button>
</div>

                                                    
                                                </div>
                                                
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
            <?php include('includes/footer.php'); ?>
        </div>
    </div>

    <script src="assets/bundles/lib.vendor.bundle.js"></script>
    <script src="js/CodiePie.js"></script>
    <script src="assets/modules/datatables/datatables.min.js"></script>
    <script src="assets/modules/datatables/DataTables-1.10.16/js/dataTables.bootstrap4.min.js"></script>
    <script src="assets/modules/datatables/Select-1.2.4/js/dataTables.select.min.js"></script>
    <script src="assets/modules/jquery-ui/jquery-ui.min.js"></script>
    <script src="assets/modules/sweetalert/sweetalert.min.js"></script>
    <script src="js/page/modules-datatables.js"></script>
    <script src="js/page/modules-sweetalert.js"></script>
    <script src="js/scripts.js"></script>
    <script src="js/custom.js"></script>

    <script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('userInputForm');
    const inputs = Array.from(form.querySelectorAll('.code-input'));

    // Listen for PASTE event on the FIRST input
    inputs[0].addEventListener('paste', function(event) {
        event.preventDefault();

        let paste = (event.clipboardData || window.clipboardData).getData('text');
        paste = paste.trim();

        let values = paste.split(/[\s,]+/); // Split by commas, spaces, tabs, etc.

        // Fill the inputs one by one
        for (let i = 0; i < values.length && i < inputs.length; i++) {
            inputs[i].value = values[i];
            syncCode(inputs[i].id); // Update the Samples Container also
        }
    });
});

function shuffleInputs() {
    const inputs = Array.from(document.querySelectorAll('.code-input'));
    const values = inputs.map(input => input.value.trim()).filter(v => v !== '');

    // Fisher-Yates Shuffle Algorithm
    for (let i = values.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [values[i], values[j]] = [values[j], values[i]];
    }

    // Put shuffled values back into inputs
    inputs.forEach((input, index) => {
        input.value = values[index] || '';
        syncCode(input.id); // update sample container too
    });
}

function printPage() {
    const requestNo = "<?= htmlspecialchars($request_no) ?>";
    window.open('print_triangle_summary.php?request_no=' + requestNo, '_blank');
}
function printPageCom() {
    const requestNo = "<?= htmlspecialchars($request_no) ?>";
    window.open('print_triangle_summary_landscape.php?request_no=' + requestNo, '_blank');
}
</script>

</body>
</html>
