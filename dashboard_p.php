<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include('includes/header.php');
include 'db.php';

$user_id = $_SESSION['user_id'];

if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    ob_start();
    ?>
    <div id="mobile-card-content">
        <?php
        $query = "SELECT request_no, lab_code_no, sample_code_no, sensory_type, date_of_computation, status 
                FROM evaluation_requests 
                WHERE status = 'active' 
                ORDER BY date_of_computation DESC";
        $result = $conn->query($query);

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $type = $row['sensory_type'];
                $typeBadge = '<div class="badge badge-secondary">Unknown</div>';
                if ($type === 'Triangle Test') {
                    $typeBadge = '<div class="badge badge-info">Triangle Test</div>';
                } elseif ($type === 'Hedonic Scale') {
                    $typeBadge = '<div class="badge badge-primary">Hedonic Scale</div>';
                }

                $request_no = $row['request_no'];

                if ($type === 'Triangle Test') {
                    $countQuery = $conn->prepare("SELECT COUNT(*) as completed FROM evaluation_assignments WHERE request_no = ? AND is_submitted = 1");
                    $countQuery->bind_param("s", $request_no);
                    $countQuery->execute();
                    $countResult = $countQuery->get_result();
                    $countRow = $countResult->fetch_assoc();

                    if ($countRow['completed'] >= 12) {
                        $actionButton = "<button class='btn btn-sm btn-warning' disabled>Evaluation Full</button>";
                    } else {
                        $check = $conn->prepare("SELECT * FROM evaluation_assignments WHERE request_no = ? AND user_id = ? AND is_submitted = 1");
                        $check->bind_param("si", $request_no, $user_id);
                        $check->execute();
                        $checkResult = $check->get_result();

                        if ($checkResult->num_rows > 0) {
                            $actionButton = "<button class='btn btn-sm btn-secondary' disabled>Already Submitted</button>";
                        } else {
                            $link = ($type === 'Hedonic Scale') 
                            ? 'hedonic_evaluation_form.php?request_no=' . urlencode($row['request_no']) 
                            : 'panelist.php?request_no=' . urlencode($row['request_no']);
                        
                        $actionButton = "<a href='" . $link . "' class='btn btn-sm btn-success'>Evaluate Now</a>";
                        
                        }
                    }
                } else {
                    $check = $conn->prepare("SELECT * FROM evaluation_assignments WHERE request_no = ? AND user_id = ? AND is_submitted = 1");
                    $check->bind_param("si", $request_no, $user_id);
                    $check->execute();
                    $checkResult = $check->get_result();

                    if ($checkResult->num_rows > 0) {
                        $actionButton = "<button class='btn btn-sm btn-secondary' disabled>Already Submitted</button>";
                    } else {
                        $link = ($type === 'Hedonic Scale') 
                        ? 'hedonic_evaluation_form.php?request_no=' . urlencode($row['request_no']) 
                        : 'panelist.php?request_no=' . urlencode($row['request_no']);
                    
                    $actionButton = "<a href='" . $link . "' class='btn btn-sm btn-success'>Evaluate Now</a>";
                    
                    }
                }

                echo "<div class='card'>
                    <div class='card-content'>
                        <h5><strong>Sample:</strong> {$row['sample_code_no']}</h5>
                        <div class='line'></div>
                        {$typeBadge}
                        {$actionButton}
                    </div>
                </div>";
            }
        } else {
            echo "<div class='card'><div class='card-content text-center'>No evaluation requests found</div></div>";
        }
        ?>
    </div>
    <?php
    exit(); // Stop page load — this is for AJAX only
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="assets/modules/datatables/datatables.min.css">
    <link rel="stylesheet" href="assets/modules/datatables/DataTables-1.10.16/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="assets/modules/datatables/Select-1.2.4/css/select.bootstrap4.min.css">
    <style>
        @media (max-width: 768px) {
            .desktop-table { display: none; }
            .mobile-card { display: block; }
            .mobile-card .card {
                margin-bottom: 1rem;
                border: 1px solid #ddd;
                border-radius: 10px;
                padding: 1rem;
                background-color: #fff;
                box-shadow: 0 2px 4px rgba(0,0,0,0.05);
                text-align: center;
            }
            .mobile-card .card-content {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 8px;
            }
            .mobile-card h5 {
                font-size: 16px;
                margin: 0;
            }
            .mobile-card .badge {
                display: inline-block;
                margin-top: 2px;
                width: 100%;
                max-width: 80%;
                text-align: center;
            }
            .mobile-card .btn {
                margin-top: 6px;
                width: 100%;
                max-width: 150px;
            }
            .mobile-card .line {
                width: 100%;
                height: 1px;
                background-color: #ddd;
                margin: 10px 0;
            }
        }
        @media (min-width: 769px) {
            .mobile-card { display: none; }
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
                    <h1>Open for Sensory Evaluation</h1>
                </div>
                <div class="section-body">
                    <div class="row">
                        <div class="col-12">

                            <!-- Desktop Table View -->
                            <div class="card desktop-table">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h4>Open for Evaluation</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped v_center text-center" id="table-1">
                                            <thead>
                                                <tr>
                                                    <th>Sample Name</th>
                                                    <th>Type of Sensory</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
<?php
$query = "SELECT request_no, lab_code_no, sample_code_no, sensory_type, date_of_computation, status 
          FROM evaluation_requests 
          WHERE status = 'active' 
          ORDER BY date_of_computation DESC";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $type = $row['sensory_type'];
        $typeBadge = '<div class="badge badge-secondary">Unknown</div>';
        if ($type === 'Triangle Test') {
            $typeBadge = '<div class="badge badge-info">Triangle Test</div>';
        } elseif ($type === 'Hedonic Scale') {
            $typeBadge = '<div class="badge badge-primary">Hedonic Scale</div>';
        }

        $request_no = $row['request_no'];

        if ($type === 'Triangle Test') {
            $countQuery = $conn->prepare("SELECT COUNT(*) as completed FROM evaluation_assignments WHERE request_no = ? AND is_submitted = 1");
            $countQuery->bind_param("s", $request_no);
            $countQuery->execute();
            $countResult = $countQuery->get_result();
            $countRow = $countResult->fetch_assoc();

            if ($countRow['completed'] >= 12) {
                $actionButton = "<button class='btn btn-sm btn-warning' disabled>Evaluation Full</button>";
            } else {
                $check = $conn->prepare("SELECT * FROM evaluation_assignments WHERE request_no = ? AND user_id = ? AND is_submitted = 1");
                $check->bind_param("si", $request_no, $user_id);
                $check->execute();
                $checkResult = $check->get_result();

                if ($checkResult->num_rows > 0) {
                    $actionButton = "<button class='btn btn-sm btn-secondary' disabled>Already Submitted</button>";
                } else {
                    $link = ($type === 'Hedonic Scale') 
                    ? 'hedonic_evaluation_form.php?request_no=' . urlencode($row['request_no']) 
                    : 'panelist.php?request_no=' . urlencode($row['request_no']);
                
                $actionButton = "<a href='" . $link . "' class='btn btn-sm btn-success'>Evaluate Now</a>";
                
                }
            }
        } else {
            $check = $conn->prepare("SELECT * FROM evaluation_assignments WHERE request_no = ? AND user_id = ? AND is_submitted = 1");
            $check->bind_param("si", $request_no, $user_id);
            $check->execute();
            $checkResult = $check->get_result();

            if ($checkResult->num_rows > 0) {
                $actionButton = "<button class='btn btn-sm btn-secondary' disabled>Already Submitted</button>";
            } else {
                $link = ($type === 'Hedonic Scale') 
    ? 'hedonic_evaluation_form.php?request_no=' . urlencode($row['request_no']) 
    : 'panelist.php?request_no=' . urlencode($row['request_no']);

$actionButton = "<a href='" . $link . "' class='btn btn-sm btn-success'>Evaluate Now</a>";

            }
        }

        echo "<tr>
                <td>{$row['sample_code_no']}</td>
                <td>{$typeBadge}</td>
                <td>{$actionButton}</td>
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

                            <!-- Mobile Card View -->
                            <div class="mobile-card" id="mobile-card-wrapper">
                                <div id="mobile-card-content">
                                    <!-- Initial content rendered from PHP above -->
                                    <!-- This will be auto-reloaded via JS -->
                                    <!-- No need to duplicate the card PHP block here -->
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

<!-- JS Scripts -->
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

<!-- Auto-refresh mobile card every 1s -->
<script>
function reloadMobileCards() {
    const xhr = new XMLHttpRequest();
    xhr.open("GET", window.location.href, true);
    xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");
    xhr.onload = function () {
        if (xhr.status === 200) {
            const temp = document.createElement('div');
            temp.innerHTML = xhr.responseText;
            const newContent = temp.querySelector("#mobile-card-content");
            const current = document.querySelector("#mobile-card-content");
            if (newContent && current) {
                current.innerHTML = newContent.innerHTML;
            }
        }
    };
    xhr.send();
}

setInterval(reloadMobileCards, 1000);
</script>

</body>
</html>
