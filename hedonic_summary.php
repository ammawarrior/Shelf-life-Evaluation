<!-- Sample Code, Request No., Sample Description, and Date of Computation -->
<!-- retrieved tanan sa evaluation_requests table -->
<!-- JOINT fields sa hedonic table and evaluation_requests is s_id -->
<!-- I count niya ang Panelists (p_id) 1-50 based on s_id -->
<!-- I display niya ang mga ratings sa table -->
<!--If over 50 na ang panelists then dili na ma access ang form  -->


<?php
include('db.php'); // Include the database connection

// Get s_id from query or use default
$s_id = isset($_GET['s_id']) ? (int)$_GET['s_id'] : null;

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
$stmt = $conn->prepare("SELECT sample_code_no, sample_description, date_of_computation, request_no FROM evaluation_requests WHERE s_id = ?");
$stmt->bind_param("i", $s_id);
$stmt->execute();
$sample_details = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Query the data for hedonic table
$stmt = $conn->prepare("SELECT p_id, rating FROM hedonic WHERE s_id = ? AND rating IS NOT NULL ORDER BY p_id ASC");
$stmt->bind_param("i", $s_id);
$stmt->execute();
$result = $stmt->get_result();

$total_score = 0;
$total_panelists = 0;
$rows = [];

while ($row = $result->fetch_assoc()) {
    $term = $descriptive_terms[$row['rating']] ?? 'N/A';
    $rows[] = [
        'p_id' => $row['p_id'],
        'term' => $term,
        'rating' => $row['rating']
    ];
    $total_score += $row['rating'];
    $total_panelists++;
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
    <?php include('header.php'); ?>
    <title>Hedonic Summary</title>

    <style>
        /* General table styling */
        .table {
            width: 100%; /* Full width for the table */
            table-layout: fixed; /* Fixed layout for equal column widths */
            word-wrap: break-word;
        }

        /* Container to hold both tables side by side */
        .table-container {
            display: flex;
            justify-content: space-between; /* Space between the tables */
            gap: 20px; /* Space between tables */
            margin: 20px 0;
        }

        .table th, .table td {
            padding: 8px;
            text-align: center;
            word-wrap: break-word;
        }

        /* Print-specific styling */
        @media print {
            body * {
                visibility: hidden;
            }

            .main-content, .main-content * {
                visibility: visible;
            }

            /* Ensure the print content takes up the full width */
            .main-content {
                margin: 0;
                padding: 0;
            }

            /* Table container for print */
            .table-container {
                flex-direction: row; /* Tables remain side by side */
                gap: 10px; /* Adjust space between the tables in print */
            }

            .table {
                width: 48%; /* Ensure both tables take up 48% of the page */
                margin-bottom: 20px;
            }

            .table th, .table td {
                padding: 12px;
                font-size: 14px; /* Adjust font size for print */
            }
        }
    </style>
</head>
<body class="layout-4">
    <div id="app">
        <div class="main-wrapper main-wrapper-1">
            <?php include('topnav.php'); include('sidebar.php'); ?>

            <div class="main-content">
                <div class="section-header text-center">
                    <h4>Department of Science and Technology - X</h4>
                    <h5>Regional Standards and Testing Laboratories</h5>
                    <h5>Shelf Life Evaluation Laboratory</h5> <br>
                    <h6>Sensory Evaluation - Acceptability Test Using 9-Point Hedonic Scale Summary</h6>
                </div>

                <div class="section-body">
                    <!-- Displaying the Sample Details -->
                    <p><strong>Sample Code:</strong> <?php echo htmlspecialchars($sample_details['sample_code_no']); ?></p>
                    <p><strong>Request No.:</strong> <?php echo htmlspecialchars($sample_details['request_no']); ?></p>
                    <p><strong>Sample Description:</strong> <?php echo htmlspecialchars($sample_details['sample_description']); ?></p>
                    <p><strong>Date of Computation:</strong> <?php echo htmlspecialchars(date("F j, Y", strtotime($sample_details['date_of_computation']))); ?></p>

            <div class="table-container" style="margin-bottom: 0px;">
            
            <!-- First Table for the first 25 rows -->
            <table class="table table-bordered" style="height: 700px; width: 700px;">
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
                            <td style="padding-bottom: 0px; padding-top: 0px;"><?php echo htmlspecialchars($row['p_id']); ?></td>
                            <td style="text-align: left; padding-bottom: 3px; padding-top: 3px;"><?php echo $row['term']; ?></td>
                            <td style="padding-bottom: 3px; padding-top: 3px;"><?php echo $row['rating']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Second Table for the remaining rows -->
            <table class="table table-bordered" style="height: 700px; width: 700px;">
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
                            <td style="padding-bottom: 3px; padding-top: 3px;"><?php echo htmlspecialchars($row['p_id']); ?></td>
                            <td style="text-align: left; padding-bottom: 3px; padding-top: 3px;"><?php echo $row['term']; ?></td>
                            <td style="padding-bottom: 3px; padding-top: 3px;"><?php echo $row['rating']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Legend Table -->
            <table class="legend-table table-bordered" style="height: 10px; width: 370px;">
                <thead>
                    <tr>
                        <th style="text-align: center; width: 55%;">Descriptive Term</th>
                        <th style="text-align: center; width: 20%;">Numerical Score</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($descriptive_terms as $score => $term): ?>
                        <tr>
                            <td style="text-align: left; padding-bottom: 3px; padding-top: 3px;"><?php echo $term; ?></td>
                            <td style="text-align: center; padding-bottom: 3px; padding-top: 3px;"><?php echo $score; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Mean Numerical Score -->
        <?php if ($mean_score !== null): ?>
            <table class="table table-bordered" style="width: 78%; margin-top: 5px; font-size: 25px;">
            <tr>
                <td style="text-align: right; font-weight: bold; width: 70%;">Mean Numerical Score</td>
                <td style="text-align: center; font-weight: bold; width: 20%;"><?php echo $mean_score; ?></td>
            </tr>
            </table>
        <?php endif; ?>

        <div style="text-align: left; margin-top: 20px;">
            <p><strong>1.</strong> A mean numerical score of <strong>5</strong> or <strong>"Neither like nor dislike"</strong> is an indicative of unacceptable sensory evaluation of the sample.</p>
            <p><strong>Remarks:</strong> Mean numerical score value of <strong>"<?php echo $mean_score; ?>"</strong> showed that product was <strong>"<?php echo $descriptive_terms[$mean_score] ?? 'N/A'; ?>"</strong> by the general population who participated in the sensory evaluation conducted.</p>
            <br>
            <p><strong>Computed by:</strong> <u>_______________________</u> || <strong>Signature/Date:</strong> <u>_______________________</u></p> <br>

            <p><strong>Checked by:</strong><u>_______________________</u> || <strong>Signature/Date:</strong><u>_______________________</u></p>
        </div>
                <div class="text-center mt-4">
                    <a href="generate_printable_summary.php?s_id=<?php echo $s_id; ?>" class="btn btn-secondary" target="_blank">Print</a>
                </div>
            </div>
        </div>

            <?php include('footer.php'); ?>
        </div>
    </div>

    <!-- Scripts -->
    <script src="assets/bundles/lib.vendor.bundle.js"></script>
    <script src="js/CodiePie.js"></script>
    <script src="js/scripts.js"></script>
    <script src="js/custom.js"></script>
</body>
</html>
