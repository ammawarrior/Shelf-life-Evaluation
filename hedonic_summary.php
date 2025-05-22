<!-- Sample Code, Request No., Sample Description, and Date of Computation -->
<!-- retrieved tanan sa evaluation_requests table -->
<!-- JOINT fields sa hedonic table and evaluation_requests is s_id -->
<!-- I count niya ang Panelists (p_id) 1-50 based on s_id -->
<!-- I display niya ang mga ratings sa table -->
<!--If over 50 na ang panelists then dili na ma access ang form  -->


<?php
include('db.php'); // Include the database connection

// Get request_no from query or use default
$request_no = isset($_GET['request_no']) ? $_GET['request_no'] : null;

// Check if request_no is valid before proceeding
if ($request_no === null) {
    die("Invalid request number."); // Handle the case where request_no is not provided
}

// Descriptive terms mapping
$descriptive_terms = [
    9 => "Like Extremely",
    8 => "Like Very Much",
    7 => "Like Moderately",
    6 => "Like Slightly",
    5 => "Neither Like nor Dislike",
    4 => "Dislike Slightly",
    3 => "Dislike Moderately",
    2 => "Dislike Very Much",
    1 => "Dislike Extremely"
];

// Query to get sample details
$stmt = $conn->prepare("SELECT sample_code_no, lab_code_no, date_of_computation, request_no FROM evaluation_requests WHERE request_no = ?");
$stmt->bind_param("i", $request_no);
$stmt->execute();
$sample_details = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Check if sample details were found
if (!$sample_details) {
    die("Sample details not found."); // Handle the case where no details are found
}

// Query the data for hedonic table
$stmt = $conn->prepare("SELECT p_id, rating FROM hedonic WHERE request_no = ? AND rating IS NOT NULL ORDER BY p_id ASC");
$stmt->bind_param("i", $request_no);
$stmt->execute();
$result = $stmt->get_result();

$total_score = 0;
$total_panelists = 0;

// Initialize array with 50 empty positions
$rows = array_fill(0, 50, [
    'p_id' => 0,
    'term' => '',
    'rating' => ''
]);

// Fill in the actual submissions in their correct positions
while ($row = $result->fetch_assoc()) {
    $p_id = $row['p_id'] - 1; // Convert to 0-based index
    $term = $descriptive_terms[$row['rating']] ?? 'N/A';
    $rows[$p_id] = [
        'p_id' => $row['p_id'],
        'term' => $term,
        'rating' => $row['rating']
    ];
    $total_score += $row['rating'];
    $total_panelists++;
}

// Fill in the p_id for empty positions
for ($i = 0; $i < 50; $i++) {
    if ($rows[$i]['p_id'] === 0) {
        $rows[$i]['p_id'] = $i + 1;
    }
}

// Calculate mean only if there are 50 panelists
$mean_score = ($total_panelists === 50) ? round($total_score / 50) : null;

// Split rows into two sets for side-by-side tables
$first_half = array_slice($rows, 0, 25);
$second_half = array_slice($rows, 25);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include('includes/header.php'); ?>
    <title>Hedonic Summary</title>

    <style>
        /* General table styling */
        .table {
            width: 100%;
            table-layout: fixed;
            word-wrap: break-word;
            border-collapse: collapse;
        }

        /* Container to hold both tables side by side */
        .table-container {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            margin: 20px 0;
        }

        .table th, .table td {
            padding: 8px;
            text-align: center;
            border: 1px solid #dee2e6;
        }

        /* Print-specific styling */
        @media print {
            body * {
                visibility: hidden;
            }

            .main-content, .main-content * {
                visibility: visible;
            }

            .main-content {
                margin: 0;
                padding: 0;
            }

            .table-container {
                flex-direction: row;
                gap: 10px;
            }

            .table {
                width: 48%;
                margin-bottom: 20px;
            }

            .table th, .table td {
                padding: 12px;
                font-size: 14px;
            }
        }

        /* Custom styles for specific tables */
        .data-table {
            height: 700px;
            width: 700px;
        }

        .legend-table {
            height: 10px;
            width: 370px;
        }

        .mean-score-table {
            margin-top: 5px;
            font-size: 25px;
        }
    </style>
</head>
<body class="layout-4">
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
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4>Hedonic Scale Result Summary</h4>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 form-group">
                                        <label>Request Code:</label>
                                        <input type="text" class="form-control uniform-input" value="<?= htmlspecialchars($request_no) ?>" readonly>
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label>Sample Code:</label>
                                        <input type="text" class="form-control uniform-input" value="<?= htmlspecialchars($sample_details['lab_code_no']) ?>" readonly>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 form-group">
                                        <label>Sample Description:</label>
                                        <input type="text" class="form-control uniform-input" value="<?= htmlspecialchars($sample_details['sample_code_no']) ?>" readonly>
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label>Date of Computation:</label>
                                        <input type="text" class="form-control uniform-input" value="<?= htmlspecialchars($sample_details['date_of_computation']) ?>" readonly>
                                    </div>
                                </div>

                                <div class="table-container">
                                    <!-- First Table for the first 25 rows -->
                                    <table class="table table-bordered data-table">
                                        <thead>
                                            <tr>
                                                <th style="width: 25%;">Panelist No.</th>
                                                <th style="width: 45%;">Descriptive Term</th>
                                                <th style="width: 30%;">Numerical Score</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($first_half as $row): ?>
                                                <tr>
                                                    <td style="padding: 3px; text-align: center;">
                                                        <?= htmlspecialchars($row['p_id']) ?>
                                                    </td>
                                                    <td style="padding: 3px; text-align: left;">
                                                        <?= htmlspecialchars($row['term']) ?>
                                                    </td>
                                                    <td style="padding: 3px; text-align: center;">
                                                        <?= htmlspecialchars($row['rating']) ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>

                                    <!-- Second Table for the remaining rows -->
                                    <table class="table table-bordered data-table">
                                        <thead>
                                            <tr>
                                                <th style="width: 25%;">Panelist No.</th>
                                                <th style="width: 45%;">Descriptive Term</th>
                                                <th style="width: 30%;">Numerical Score</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($second_half as $row): ?>
                                                <tr>
                                                    <td style="padding: 3px; text-align: center;">
                                                        <?= htmlspecialchars($row['p_id']) ?>
                                                    </td>
                                                    <td style="padding: 3px; text-align: left;">
                                                        <?= htmlspecialchars($row['term']) ?>
                                                    </td>
                                                    <td style="padding: 3px; text-align: center;">
                                                        <?= htmlspecialchars($row['rating']) ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Mean Numerical Score -->
                                <?php if ($mean_score !== null): ?>
                                    <table class="table table-bordered mean-score-table">
                                        <tr>
                                            <td style="text-align: right; font-weight: bold;">
                                                Mean Numerical Score
                                            </td>
                                            <td style="text-align: center; font-weight: bold; width: 20%;">
                                                <?= htmlspecialchars($mean_score) ?>
                                            </td>
                                        </tr>
                                    </table>
                                <?php endif; ?>

                                <div class="text-right mt-4">
                                    <a href="generate_printable_summary_pdf.php?request_no=<?= htmlspecialchars($request_no) ?>" class="btn btn-success btn-sm" target="_blank">Print Hedonic Summary</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <?php include('includes/footer.php'); ?>
        </div>
    </div>

    <!-- Scripts -->
    <script src="assets/bundles/lib.vendor.bundle.js"></script>
    <script src="js/CodiePie.js"></script>
    <script src="js/scripts.js"></script>
    <script src="js/custom.js"></script>
</body>
</html>
