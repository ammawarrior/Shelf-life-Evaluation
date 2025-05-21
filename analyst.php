<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'includes/header.php';
include 'db.php';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['request_no']) && isset($_POST['lab_code_no'])) {
        // Add Evaluation
        $request_no = $_POST['request_no'];
        $lab_code_no = $_POST['lab_code_no'];
        $sample_code_no = $_POST['sample_code_no'];
        $date_of_computation = date('Y-m-d H:i:s');
        $sensory_type = $_POST['sensory_type'];

        $stmt = $conn->prepare("INSERT INTO evaluation_requests (request_no, lab_code_no, sample_code_no, date_of_computation, sensory_type) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $request_no, $lab_code_no, $sample_code_no, $date_of_computation, $sensory_type);

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Evaluation request added successfully!";
        } else {
            $_SESSION['error_message'] = "Error: " . $stmt->error;
        }

        $stmt->close();
        header("Location: analyst.php");
        exit();

    } elseif (isset($_POST['delete_request_no'])) {
        // Delete Evaluation
        $delete_no = $_POST['delete_request_no'];
        $stmt = $conn->prepare("DELETE FROM evaluation_requests WHERE request_no = ?");
        $stmt->bind_param("s", $delete_no);

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Evaluation request deleted successfully.";
        } else {
            $_SESSION['error_message'] = "Error deleting request.";
        }

        $stmt->close();
        header("Location: analyst.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="assets/modules/datatables/datatables.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="assets/modules/datatables/DataTables-1.10.16/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="assets/modules/datatables/Select-1.2.4/css/select.bootstrap4.min.css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:400,600,700" rel="stylesheet">
    <style>
        .glowing {
            animation: glow 1s infinite alternate;
            color: #00ffea;
        }
        @keyframes glow {
            from { text-shadow: 0 0 5px #00ffea, 0 0 10px #00ffea; }
            to { text-shadow: 0 0 20px #00ffea, 0 0 30px #00ffea; }
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
                        <h1>Sensory</h1>
                    </div>
                    <div class="section-body">
                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h4>Open for Evaluation</h4>
                                        <button type="button" class="btn btn-sm" style="background-color: #2A5298; color: white;" data-toggle="modal" data-target="#evaluationModal">
    Create new Test
</button>

                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-striped v_center" id="table-1">
                                                <thead>
                                                    <tr>
                                                        <th>Sample Name</th>
                                                        <th>Type of Sensory</th>
                                                        <th>Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
<?php
$query = "SELECT request_no, lab_code_no, sample_code_no, sensory_type, date_of_computation, status FROM evaluation_requests ORDER BY date_of_computation DESC";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $type = $row['sensory_type'];
        $typeBadge = '<div class="badge badge-secondary">Unknown</div>';
        if ($type === 'Triangle Test') {
            $typeBadge = '<div class="badge badge-primary">Triangle Test</div>';
        } elseif ($type === 'Hedonic Scale') {
            $typeBadge = '<div class="badge badge-secondary">Hedonic Scale</div>';
        }

        $iconClass = ($row['status'] === 'active') ? 'fa-eye glowing' : 'fa-eye';
        $btnClass = ($row['status'] === 'active') ? 'btn-info' : 'btn-secondary';
        $tooltip = ($row['status'] === 'active') ? 'Deactivate Evaluation' : 'Activate Evaluation';

        echo "<tr>
            <td>{$row['sample_code_no']}</td>
            <td>{$typeBadge}</td>
            <td>
                    <a href='" . 
                        ($row['sensory_type'] === 'Hedonic Scale' 
                            ? "hedonic_evaluation_form.php?request_no=" 
                            : "monitoring.php?request_no=") 
                        . urlencode($row['request_no']) . "' 
                        class='btn btn-sm btn-warning' title='Edit'>
                        <i class='fas fa-pen'></i>
                    </a>


                <a href='toggle_evaluation.php?request_no=" . urlencode($row['request_no']) . "' class='btn btn-sm $btnClass' title='$tooltip'>
                    <i class='fas $iconClass'></i>
                </a>
                <a href='" . 
                        ($row['sensory_type'] === 'Hedonic Scale' 
                            ? "hedonic_summary.php?request_no=" 
                            : "summary_triangle.php?request_no=") 
                        . urlencode($row['request_no']) . "' 
                        class='btn btn-sm btn-success' title='Summary'>
                        <i class='fas fa-chart-pie'></i>
                    </a>

                <a href='" . 
                        ($row['sensory_type'] === 'Hedonic Scale' 
                            ? "generate_all_results_pdf.php?request_no=" 
                            : "print_panelist.php?request_no=") 
                        . urlencode($row['request_no']) . "' 
                        class='btn btn-sm' style='background-color: #6f42c1; color: white;' title='Download Panelist Answers' target='_blank'>
                        <i class='fas fa-file-download'></i>
                    </a>


                <button type='button' class='btn btn-sm btn-danger btn-delete' data-request-no='{$row['request_no']}' title='Delete'>
                    <i class='fas fa-trash-alt'></i>
                </button>
            </td>
        </tr>";
    }
} else {
    echo "<tr><td colspan='3' class='text-center'>No evaluation requests found</td></tr>";
}
?>
                                                </tbody>
                                            </table>
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

     <!-- Modal -->
<div class="modal fade" id="evaluationModal" tabindex="-1" role="dialog" aria-labelledby="evaluationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document"> <!-- modal-lg for wider layout -->
        <form method="POST">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">New Evaluation Request</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="container-fluid">
                        <div class="row">
                            <!-- Left Column -->
                            <div class="col-md-6">
   
                                <div class="form-group">
                                    <label>Request Code</label>
                                    <input type="text" class="form-control" name="request_no" required>
                                </div>
                                <div class="form-group">
                                    <label>Sample Code</label>
                                    <input type="text" class="form-control" name="lab_code_no" required>
                                </div>

                                <div class="form-group">
                                    <label>Sample Description</label>
                                    <input type="text" class="form-control" name="sample_code_no" required>
                                </div>

                                <div class="form-group">
                                    <label>Date of Entry</label>
                                    <input type="date" name="date_of_computation" class="form-control" required value="">
                                </div>   

                            </div>

                            <!-- Right Column -->
                            <div class="col-md-6">
                            <div class="form-group">
                                    <label>Select Analyst</label>
                                    <select name="analyst" class="form-control" required>
                                        <option value="">Select Analyst</option>
                                        <option value="Gia Marie Cagubcub">Gia Marie B. Cagubcub</option>
                                        <option value="Marl Andrian Patrick H. Dalinao">Marl Andrian Patrick H. Dalinao</option>
                                        <option value="Shenna Grace Eran">Shenna Grace P. Eran</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Sensory Type</label>
                                    <select name="sensory_type" class="form-control" id="sensoryTypeSelect" required>
                                        <option value="">Select Type</option>
                                        <option value="Triangle Test">Triangle Test</option>
                                        <option value="Hedonic Scale">Hedonic Scale</option>
                                    </select>
                                </div>

                                <div class="form-group" id="triangleItemCountWrapper" style="display: none;">
                                    <label>Triangle Test Count</label>
                                    <select name="triangle_item_count" class="form-control">
                                        <option value="">Select Count</option>
                                        <option value="12" selected>12</option>
                                        <!-- <option value="15">15</option> -->
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn" style="background-color: #3b4c7d; color: white;">Submit</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                </div>
            </div>
        </form>
    </div>
</div>

    <!-- JS Scripts -->
    <script src="assets/bundles/lib.vendor.bundle.js"></script>
    <script src="js/CodiePie.js"></script>
    <script src="assets/modules/datatables/datatables.min.js"></script>
    <script src="assets/modules/datatables/DataTables-1.10.16/js/dataTables.bootstrap4.min.js"></script>
    <script src="assets/modules/datatables/Select-1.2.4/js/dataTables.select.min.js"></script>
    <script src="assets/modules/jquery-ui/jquery-ui.min.js"></script>
    <script src="js/page/modules-datatables.js"></script>
    <script src="js/scripts.js"></script>
    <script src="js/custom.js"></script>

    <!-- SweetAlert for messages -->
    <?php if (isset($_SESSION['success_message'])) : ?>
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Success',
            text: '<?php echo $_SESSION['success_message']; ?>',
            showConfirmButton: false,
            timer: 2000
        });
    </script>
    <?php unset($_SESSION['success_message']); endif; ?>

    <?php if (isset($_SESSION['error_message'])) : ?>
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: '<?php echo $_SESSION['error_message']; ?>',
        });
    </script>
    <?php unset($_SESSION['error_message']); endif; ?>

    <!-- SweetAlert delete confirmation -->
    <script>
    document.querySelectorAll('.btn-delete').forEach(button => {
        button.addEventListener('click', function () {
            const requestNo = this.getAttribute('data-request-no');
            Swal.fire({
                title: 'Are you sure?',
                text: "This will permanently delete the evaluation request.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#aaa',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'delete_request_no';
                    input.value = requestNo;
                    form.appendChild(input);
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        });
    });
    </script>
    


</body>
</html>
