<?php
session_start();
require 'vendor/autoload.php';
use Dompdf\Dompdf;

// Debug logging
error_log("Request No from GET: " . (isset($_GET['request_no']) ? $_GET['request_no'] : 'not set'));

// Include database connection
include('db.php');

// Get request_no from query or use default
$request_no = isset($_GET['request_no']) ? $_GET['request_no'] : null;

// Debug logging
error_log("Request No after processing: " . ($request_no ?? 'null'));

// Check if request_no is valid before proceeding
if ($request_no === null) {
    die("Invalid request number.");
}

// Debug: Log the type of request_no
error_log("Request No type: " . gettype($request_no));

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

// Debug: Log the SQL query for sample details
error_log("Executing sample details query for request_no: " . $request_no);

// Query to get sample details
$stmt = $conn->prepare("SELECT sample_code_no, lab_code_no, date_of_computation, request_no FROM evaluation_requests WHERE request_no = ?");
$stmt->bind_param("s", $request_no);
$stmt->execute();
$sample_details = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Debug: Log the sample details result
error_log("Sample details result: " . print_r($sample_details, true));

// Check if sample details were found
if (!$sample_details) {
    die("Sample details not found.");
}

// Debug: Log the SQL query for hedonic data
error_log("Executing hedonic data query for request_no: " . $request_no);

// Query the data for hedonic table
$stmt = $conn->prepare("SELECT p_id, rating FROM hedonic WHERE request_no = ? AND rating IS NOT NULL ORDER BY p_id ASC");
$stmt->bind_param("s", $request_no);
$stmt->execute();
$result = $stmt->get_result();

// Debug: Log the number of rows found
$row_count = $result->num_rows;
error_log("Number of hedonic ratings found: " . $row_count);

// Debug: Log the first few rows of hedonic data
$first_rows = [];
$counter = 0;
while ($row = $result->fetch_assoc() && $counter < 5) {
    $first_rows[] = $row;
    $counter++;
}
error_log("First few rows of hedonic data: " . print_r($first_rows, true));

// Reset the result pointer
$result->data_seek(0);

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

// Debug: Log the total score and panelists
error_log("Total score: " . $total_score . ", Total panelists: " . $total_panelists);

// Calculate mean only if there are 50 panelists
$mean_score = ($total_panelists === 50) ? round($total_score / 50) : null;

// Split rows into two sets for side-by-side tables
$first_half = array_slice($rows, 0, 25);
$second_half = array_slice($rows, 25);

// Initialize Dompdf
$dompdf = new Dompdf();
$dompdf->setPaper('A4', 'portrait');

// Fetch analyst name from session
$analyst_name = '_______________________';
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT code_name FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($code_name);
    if ($stmt->fetch()) {
        $analyst_name = $code_name;
    }
    $stmt->close();
}

// Create the HTML content
$html = "<!DOCTYPE html>
<html lang=\"en\">
<head>
    <meta charset=\"UTF-8\">
    <title>Printable Hedonic Summary</title>
    <style>
        /* General print styling */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            font-size: 8pt;
        }

        .printable-summary {
            width: 100%;
            margin: 0 auto;
            padding: 4px;
            text-align: center;
        }

        .section-header h3 {
            margin: 3px 0 2px;
            font-size: 12pt;
        }

        .section-header h4 {
            margin: 2px 0;
            font-size: 8pt;
        }

        .table-container {
            width: 100%;
            border-collapse: collapse;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
            font-size: 8pt;
        }

        .table th, .table td {
            border: 1px solid #dee2e6;
            padding: 4px;
            text-align: center;
            word-wrap: break-word;
            font-size: 8pt;
        }

        .table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        .section-body {
            margin-top: 3px;
        }

        /* Legend Table Styling */
        .legend-table {
            width: 30%;
            float: right;
            border-collapse: collapse;
            margin-bottom: 3px;
            font-size: 6pt;
        }

        .legend-table th, .legend-table td {
            padding: 3px;
            text-align: left;
            border: 1px solid #dee2e6;
        }

        .legend-table th {
            background-color: #f2f2f2;
        }

        @media print {
            .printable-summary, .printable-summary * {
                visibility: visible;
            }

            .table-container {
                display: flex;
                gap: 3px;
                justify-content: center;
                flex-direction: row;
            }

            .table {
                width: 43%;
                border: 1px solid #dee2e6;
                padding: 3px;
            }

            .legend-table {
                width: 30%;
                float: right;
            }

            .printable-summary {
                width: 100%;
                margin: 0 auto;
                padding: 2px;
            }
        }
    </style>
</head>
<body>

<div class=\"printable-summary\">
    <div class=\"section-header\">
        <h3>Department of Science and Technology - X</h3>
        <h4>Regional Standards and Testing Laboratories</h4>
        <h4>Shelf Life Evaluation Laboratory</h4> <br>
        <h4>Sensory Evaluation - Acceptability Test Using 9-Point Hedonic Scale Summary</h4>
    </div>

    <div class=\"section-body\">
        <!-- Displaying the Sample Details -->
        <div style=\"text-align: left;\">
            <p><strong>Request No.:</strong> " . htmlspecialchars($sample_details['request_no']) . "</p>
            <p><strong>Sample Code:</strong> " . htmlspecialchars($sample_details['lab_code_no']) . "</p>
            <p><strong>Sample Description:</strong> " . htmlspecialchars($sample_details['sample_code_no']) . "</p>
            <p><strong>Date of Computation:</strong> " . htmlspecialchars(date("F j, Y", strtotime($sample_details['date_of_computation']))) . "</p>
        </div>
        <br>
        <table class=\"table-container\" style=\"width: 100%; border-collapse: collapse;\">
            <tr>
                <!-- First Table for the first 25 rows -->
                <td style=\"width: 47%; vertical-align: top; padding: 0;\">
                    <table class=\"table table-bordered\" style=\"width: 100%;\">
                        <thead>
                            <tr>
                                <th>Panelist No.</th>
                                <th>Descriptive Term</th>
                                <th>Numerical Score</th>
                            </tr>
                        </thead>
                        <tbody>";

// Add first table rows
foreach ($first_half as $row) {
    $html .= "
                        <tr>
                            <td style=\"padding-bottom: 0px; padding-top: 0px;\">" . htmlspecialchars($row['p_id']) . "</td>
                            <td style=\"text-align: left; padding-bottom: 3px; padding-top: 3px;\">" . htmlspecialchars($row['term']) . "</td>
                            <td style=\"padding-bottom: 3px; padding-top: 3px;\">" . htmlspecialchars($row['rating']) . "</td>
                        </tr>";
}

$html .= "
                </tbody>
                </td>
                <!-- Second Table for the remaining rows -->
                <td style=\"width: 47%; vertical-align: top; padding: 0;\">
                    <table class=\"table table-bordered\" style=\"width: 100%;\">
                        <thead>
                            <tr>
                                <th>Panelist No.</th>
                                <th>Descriptive Term</th>
                                <th>Numerical Score</th>
                            </tr>
                        </thead>
                        <tbody>";

// Add second table rows
foreach ($second_half as $row) {
    $html .= "
                        <tr>
                            <td style=\"padding-bottom: 3px; padding-top: 3px;\">" . htmlspecialchars($row['p_id']) . "</td>
                            <td style=\"text-align: left; padding-bottom: 3px; padding-top: 3px;\">" . htmlspecialchars($row['term']) . "</td>
                            <td style=\"padding-bottom: 3px; padding-top: 3px;\">" . htmlspecialchars($row['rating']) . "</td>
                        </tr>";
}

$html .= "
                </tbody>
            </table>
                </td>
                <!-- Legend Table -->
                <td style=\"width: 30%; vertical-align: top; padding: 0;\">
                    <table class=\"legend-table table-bordered\" style=\"width: 100%;\">
                        <thead>
                            <tr>
                                <th style=\"text-align: center; width: 60%;\">Descriptive Term</th>
                                <th style=\"text-align: center; width: 20%;\">Numerical Score</th>
                            </tr>
                        </thead>
                        <tbody>";

// Add legend table rows
foreach ($descriptive_terms as $score => $term) {
    $html .= "
                        <tr>
                            <td style=\"text-align: left; padding-bottom: 3px; padding-top: 3px;\">" . htmlspecialchars($term) . "</td>
                            <td style=\"text-align: center; padding-bottom: 3px; padding-top: 3px;\">" . htmlspecialchars($score) . "</td>
                        </tr>";
}

$html .= "
                </tbody>
            </table>
        </div>

        <!-- Mean Numerical Score -->
        <table class=\"table table-bordered\" style=\"width: 75.8%; margin-top: 0px;\">
            <tr>
                <td style=\"text-align: right; font-weight: bold;\">Mean Numerical Score:</td>
                <td style=\"text-align: center; font-weight: bold; width: 18%;\">" . ($mean_score !== null ? htmlspecialchars($mean_score) : '') . "</td>
            </tr>
        </table>

        <div style=\"text-align: left; margin-top: 20px;\">
            <p><strong>1.</strong> A mean numerical score of <strong>5</strong> or <strong>\"Neither like nor dislike\"</strong> is an indicative of unacceptable sensory evaluation of the sample.</p>
            <p><strong>Remarks:</strong> Mean numerical score value of <strong>\"" . ($mean_score !== null ? htmlspecialchars($mean_score) : '') . "\"</strong> showed that product was <strong>\"" . ($mean_score !== null ? htmlspecialchars($descriptive_terms[$mean_score] ?? 'N/A') : '') . "\"</strong> by the general population who participated in the sensory evaluation conducted.</p>
            <br>
            <p><strong>Computed by:</strong> <u>" . htmlspecialchars($analyst_name) . "</u> | <strong>Signature/Date:</strong> <u>_______________________</u></p> <br>

            <p><strong>Checked by:</strong> <u>_______________________</u> | <strong>Signature/Date:</strong> <u>_______________________</u></p>
        </div>
        <div style=\"display: flex; justify-content: space-between; margin-top: 20px;\">
            <div style=\"flex: 1; text-align: left;\">
                <label>Page 1 of 1</label>
            </div>
            <div style=\"flex: 1; text-align: left; margin-left: 550px;\">
                <label style=\"display: block;\">STM -007-F2</label>
                <label style=\"display: block;\">Revision 0</label>
                <label style=\"display: block;\"><i>Effectivity Date: 24 June 2020</i></label>
            </div>
        </div>
    </div>
</div>
</body>
</html>";

// Load the HTML content into Dompdf
$dompdf->loadHtml($html);

// Render the PDF
$dompdf->render();

// Output the generated PDF to the browser
$dompdf->stream("printable_summary_request_{$request_no}.pdf", ["Attachment" => false]);

exit;
?>
