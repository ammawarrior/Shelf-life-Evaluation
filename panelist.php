<?php
session_start();
$request_no = isset($_GET['request_no']) ? $_GET['request_no'] : null;

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include('includes/header.php');
include 'db.php';

// Fetch panelist info
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT code_name FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$code_name = isset($user['code_name']) ? $user['code_name'] : 'Unknown';

// Fetch lab_code_no using request_no
$lab_code_no = '';
if ($request_no) {
    $stmt2 = $conn->prepare("SELECT lab_code_no, user_input_codes FROM evaluation_requests WHERE request_no = ?");
    $stmt2->bind_param("s", $request_no);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    if ($result2->num_rows > 0) {
        $data = $result2->fetch_assoc();
        $lab_code_no = $data['lab_code_no'];
        $user_input_codes = json_decode($data['user_input_codes'], true);
    }
}

// Handle triad assignment
$triad_data = null;
if ($request_no) {
    $stmt = $conn->prepare("SELECT * FROM evaluation_assignments WHERE request_no = ? AND user_id = ?");
    $stmt->bind_param("si", $request_no, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $triad_data = $result->fetch_assoc();
    } else {
        $stmt3 = $conn->prepare("SELECT COUNT(*) as count FROM evaluation_assignments WHERE request_no = ?");
        $stmt3->bind_param("s", $request_no);
        $stmt3->execute();
        $result3 = $stmt3->get_result();
        $count_row = $result3->fetch_assoc();
        $assigned_count = $count_row['count'];

        if ($assigned_count < 12) {
            $triad_to_assign = $user_input_codes[$assigned_count];
            $stmt4 = $conn->prepare("INSERT INTO evaluation_assignments (request_no, user_id, triad_no, triad_type, code1, code2, code3) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt4->bind_param("siissss", $request_no, $user_id, $triad_to_assign['triad_no'], $triad_to_assign['triad_type'], $triad_to_assign['code1'], $triad_to_assign['code2'], $triad_to_assign['code3']);
            $stmt4->execute();
            $triad_data = $triad_to_assign;
        } else {
            echo "<script>alert('Maximum panelists reached for this evaluation.'); window.location='dashboard.php';</script>";
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="assets/modules/datatables/datatables.min.css">
    <link rel="stylesheet" href="assets/modules/datatables/DataTables-1.10.16/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="assets/modules/datatables/Select-1.2.4/css/select.bootstrap4.min.css">
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
                        <h1>Score Sheet - Triangle Test</h1>
                    </div>

                    <div class="section-body">
                        <<form id="triangleForm" action="submit_triangle_test.php" method="post">

                            <div class="row">
                            <input type="hidden" name="request_no" value="<?= htmlspecialchars($request_no) ?>">

                                <div class="col-md-6 form-group">
                                    <label>Product Code:</label>
                                    <input type="text" class="form-control uniform-input" value="<?= htmlspecialchars($lab_code_no) ?>" readonly>
                                    <input type="hidden" name="product_code" value="<?= htmlspecialchars($lab_code_no) ?>">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Date:</label>
                                    <input type="text" class="form-control uniform-input" value="<?= date('M d, Y') ?>" readonly>
                                    <input type="hidden" name="date" value="<?= date('Y-m-d') ?>">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label>Name:</label>
                                    <input type="text" class="form-control uniform-input" value="<?= htmlspecialchars($code_name) ?>" readonly>
                                    <input type="hidden" name="code_name" value="<?= htmlspecialchars($code_name) ?>">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Panelist No.:</label>
                                    <input type="text" class="form-control uniform-input" value="<?= htmlspecialchars($triad_data['triad_no']) ?>" readonly>
                                    <input type="hidden" name="triad_no" value="<?= htmlspecialchars($triad_data['triad_no']) ?>">
                                    <input type="hidden" name="user_id" value="<?= $user_id ?>">
                                </div>

                            </div>

                            <p><strong>Instruction:</strong> Taste the samples from left to right. Two samples are alike; one is different. Select or guess the odd/different sample and indicate by placing a mark “⦿” next to the code of the odd sample.</p>

                            <?php if ($triad_data): ?>
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Sample Code</th>
                                            <th>Odd Sample</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><input type="text" class="form-control uniform-input" value="<?= $triad_data['code1'] ?>" readonly>
                                                <input type="hidden" name="sample_code1" value="<?= $triad_data['code1'] ?>">
                                            </td>
                                            <td><input type="radio" name="odd_sample" value="1" required></td>
                                        </tr>
                                        <tr>
                                            <td><input type="text" class="form-control uniform-input" value="<?= $triad_data['code2'] ?>" readonly>
                                                <input type="hidden" name="sample_code2" value="<?= $triad_data['code2'] ?>">
                                            </td>
                                            <td><input type="radio" name="odd_sample" value="2"></td>
                                        </tr>
                                        <tr>
                                            <td><input type="text" class="form-control uniform-input" value="<?= $triad_data['code3'] ?>" readonly>
                                                <input type="hidden" name="sample_code3" value="<?= $triad_data['code3'] ?>">
                                            </td>
                                            <td><input type="radio" name="odd_sample" value="3"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            <?php endif; ?>

                            <div class="form-group">
                                <label>Panelist's Comments/Remarks:</label>
                                <textarea name="comments" class="form-control uniform-input" rows="3" required></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary">Submit</button>
                        </form>

                    </div>

                </section>
            </div>
             <!-- Modal for Correct/Incorrect Answer -->
<div class="modal fade" id="resultModal" tabindex="-1" role="dialog" aria-labelledby="resultModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="resultModalLabel">Result</h5>
      </div>
      <div class="modal-body" id="modalBodyText">
        <!-- Will be filled by JS -->
      </div>
      <div class="modal-footer">
        <button type="button" id="modalOkButton" class="btn btn-primary">OK</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal for Success Confirmation -->
<div class="modal fade" id="successModal" tabindex="-1" role="dialog" aria-labelledby="successModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="successModalLabel">Submission Success</h5>
      </div>
      <div class="modal-body">
        <strong>Your answer has been successfully submitted.</strong>
      </div>
      <div class="modal-footer">
        <button type="button" id="successOkButton" class="btn btn-success">OK</button>
      </div>
    </div>
  </div>
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
const correctAnswers = {
    1: '1', 2: '3', 3: '2', 4: '1', 5: '3', 6: '2',
    7: '1', 8: '3', 9: '2', 10: '1', 11: '3', 12: '2'
};

document.getElementById('triangleForm').addEventListener('submit', function(e) {
    e.preventDefault(); // Stop normal submit

    const oddSample = document.querySelector('input[name="odd_sample"]:checked').value;
    const triadNo = document.querySelector('input[name="triad_no"]').value;
    const correctAnswer = correctAnswers[triadNo];
    const isCorrect = (oddSample === correctAnswer);

    const modalBodyText = document.getElementById('modalBodyText');
    modalBodyText.innerHTML = isCorrect ? 
        "<strong>Correct!</strong> You selected the right odd sample." :
        "<strong>Incorrect!</strong> You selected the wrong odd sample.";

    const modalContent = document.querySelector('#resultModal .modal-content');
    modalContent.style.backgroundColor = isCorrect ? '#d4edda' : '#f8d7da';

    $('#resultModal').modal('show');
});

// When OK is clicked on the result modal, hide it and show success
document.getElementById('modalOkButton').addEventListener('click', function() {
    $('#resultModal').modal('hide');
    $('.modal-backdrop').remove(); // move this here

    setTimeout(() => {
        $('#successModal').modal('show');
    }, 500);
});


// When OK is clicked on the success modal, submit via AJAX
document.getElementById('successOkButton').addEventListener('click', function() {
    const form = document.getElementById('triangleForm');
    const formData = new FormData(form);

    fetch('submit_triangle_test.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        // Optional: show some success message
        console.log('Submitted:', data);

        // Hide modal properly
        $('#successModal').modal('hide');
        $('.modal-backdrop').remove(); // VERY IMPORTANT: remove backdrop manually

        // Redirect after small delay
        setTimeout(function() {
            window.location.href = "dashboard_p.php";
        }, 500);
    })
    .catch(error => {
        console.error('Error submitting:', error);
        alert('There was a problem submitting your form.');
    });
});
</script>



</body>
</html>