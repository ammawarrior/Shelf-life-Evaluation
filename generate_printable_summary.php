<?php
include('db.php'); // Include the database connection

// Get request_no from query or use default
$request_no = isset($_GET['request_no']) ? (int)$_GET['request_no'] : null;

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
    <meta charset="UTF-8">
    <title>Printable Hedonic Summary</title>
    <style>
        /* General print styling */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        .printable-summary {
            width: 100%;
            margin: 0 auto;
            padding: 20px;
            text-align: center;
        }

        .section-header h4, .section-header h5, .section-header h6 {
            margin: 5px 0;
        }

        .table-container {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            margin-top: 20px;
        }

        .table {
            width: 48%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .table th, .table td {
            border: 1px solid #dee2e6;
            padding: 12px;
            text-align: center;
            word-wrap: break-word;
        }

        .table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        .section-body {
            margin-top: 20px;
        }

        .page-break {
            page-break-before: always;
        }

        /* Legend Table Styling */
        .legend-table {
            width: 48%;
            float: right;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 12px;
        }

        .legend-table th, .legend-table td {
            padding: 12px;
            text-align: left;
            border: 1px solid #dee2e6;
        }

        .legend-table th {
            background-color: #f2f2f2;
        }

        @media print {
            /* Hide everything except the printable content */
            body * {
                visibility: hidden;
            }

            .printable-summary, .printable-summary * {
                visibility: visible;
            }

            .table-container {
                display: flex;
                gap: 20px;
                margin: 0 auto;
                justify-content: center;
                flex-direction: row;
            }

            .table {
                width: 48%;
                border: 1px solid #dee2e6;
                padding: 12px;
            }

            .legend-table {
                width: 40%; /* Adjust width for legend */
                float: right;
            }

            .printable-summary {
                width: 100%;
                margin: 0 auto;
                padding: 20px;
            }
        }
    </style>
</head>
<body>

<div class="printable-summary">
    <div class="section-header" style="margin: 0; padding: 0;">
        <h3 style="margin: 5px 0;">Department of Science and Technology - X</h3>
        <h4 style="margin: 5px 0;">Regional Standards and Testing Laboratories</h4>
        <h4 style="margin: 5px 0;">Shelf Life Evaluation Laboratory</h4> <br>
        <h4 style="margin: 5px 0;">Sensory Evaluation - Acceptability Test Using 9-Point Hedonic Scale Summary</h4>
    </div>

    <div class="section-body" style="margin: 20px;">
        <!-- Displaying the Sample Details -->
        <div style="text-align: left;">
            <p><strong>Request No.:</strong> <?php echo htmlspecialchars($sample_details['request_no']); ?></p>
            <p><strong>Sample Code:</strong> <?php echo htmlspecialchars($sample_details['lab_code_no']); ?></p>
            <p><strong>Sample Description:</strong> <?php echo htmlspecialchars($sample_details['sample_code_no']); ?></p>
            <p><strong>Date of Computation:</strong> <?php echo htmlspecialchars(date("F j, Y", strtotime($sample_details['date_of_computation']))); ?></p>
        </div>
        <br>
        <div class="table-container" style="margin-bottom: 0px;">

            <!-- First Table for the first 25 rows -->
            <table class="table table-bordered" style="height: 700px; width: 700px;">
                <thead>
                    <tr>
                        <th>Panelist No.</th>
                        <th style="width: 60%;">Descriptive Term</th>
                        <th style="width: 20%;">Numerical Score</th>
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
                        <th>Panelist No.</th>
                        <th style="width: 60%;">Descriptive Term</th>
                        <th style="width: 20%;">Numerical Score</th>
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
                        <th style="text-align: center; width: 60%;">Descriptive Term</th>
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
            <table class="table table-bordered" style="width: 78%; margin-top: 20px;">
            <tr>
                <td style="text-align: right; font-weight: bold;">Mean Numerical Score</td>
                <td style="text-align: center; font-weight: bold;"><?php echo $mean_score; ?></td>
            </tr>
            </table>
        <?php endif; ?>

        <div style="text-align: left; margin-top: 20px;">
            <p><strong>1.</strong> A mean numerical score of <strong>5</strong> or <strong>"Neither like nor dislike"</strong> is an indicative of unacceptable sensory evaluation of the sample.</p>
            <p><strong>Remarks:</strong> Mean numerical score value of <strong>"<?php echo $mean_score; ?>"</strong> showed that product was <strong>"<?php echo $descriptive_terms[$mean_score] ?? 'N/A'; ?>"</strong> by the general population who participated in the sensory evaluation conducted.</p>
            <br>
            <p><strong>Computed by:</strong> <u>_______________________</u> | <strong>Signature/Date:</strong> <u>_______________________</u></p> <br>

            <p><strong>Checked by:</strong> <u>_______________________</u> | <strong>Signature/Date:</strong> <u>_______________________</u></p>
        </div>
        <div style="display: flex; justify-content: space-evenly; margin-top: 20px;">
            <div>
                <label style="text-align: left;">Page 1 of 1</label>
            </div>
            <div>
                <label style="display: block; text-align: left;">STM -007-F2</label>
                <label style="display: block; text-align: left;">Revision 0</label>
                <label style="display: block; text-align: left;"><i>Effectivity Date: 24 June 2020</i></label>
            </div>
        </div>
    </div>
</div>

<!-- Print the page -->
<script>
    window.print();
</script>

</body>
</html>
