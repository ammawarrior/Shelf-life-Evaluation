<?php
require 'vendor/autoload.php'; // Include the Composer autoload file
use Dompdf\Dompdf;

// Include database connection
include('db.php');

// Get s_id from the query
$s_id = isset($_GET['s_id']) ? (int)$_GET['s_id'] : null;

if (!$s_id) {
    echo "Missing sample ID.";
    exit;
}

// Fetch all evaluation results for the specific sample ID with panelist details
$stmt = $conn->prepare("
    SELECT h.p_id, h.rating, h.remarks, h.name, h.institution_name, h.date_submitted, er.sample_code_no, h.date_checked
    FROM hedonic h
    JOIN evaluation_requests er ON h.s_id = er.s_id
    WHERE h.s_id = ?
");
$stmt->bind_param("i", $s_id);
$stmt->execute();
$result = $stmt->get_result();

$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}

if (empty($rows)) {
    echo "No evaluation data found for this sample ID.";
    exit;
}

// Initialize Dompdf
$dompdf = new Dompdf();
$dompdf->setPaper('A4', 'portrait'); // Set paper size and orientation

$html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { 
            font-family: Arial, sans-serif;
            line-height: 1.0;
            margin: 0;
            padding: 0;
        }
        .page { 
            page-break-after: always;
            margin: 5px;
            padding: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            border: none;
            margin: 0;
            padding: 0;
        }
        td {
            width: 50%;
            vertical-align: top;
            padding: 8px;
            border: 1px solid #ddd;
        }
        .form-group { 
            margin-bottom: 8px;
            line-height: 1.0;
        }
        .custom-radio { 
            margin: 3px 0;
            line-height: 1.0;
        }
        textarea { 
            width: 100%; 
            height: 80px;
            line-height: 1.0;
        }
        h1 { 
            font-size: 13px; 
            margin: 0 0 8px 0;
            line-height: 1.0;
        }
        h2 { 
            font-size: 13px; 
            margin: 0 0 8px 0;
            line-height: 1.0;
        }
        input[type="text"] { 
            width: 100%; 
            padding: 3px; 
            margin-bottom: 5px;
            line-height: 1.0;
            border: none;
        }
        p {
            line-height: 1.0;
            margin: 3px 0;
        }
    </style>
</head>
<body>';
// Process rows in pairs
for ($i = 0; $i < count($rows); $i += 2) {
        $html .= '<div style="border: none;" class="page">';
        $html .= '<table style="border: none;"><tr>';
        
        // First column
        $html .= '<td>';
        $html .= '<h1 style="text-align: center;">DEPARTMENT OF SCIENCE AND TECHNOLOGY - X</h1>
                    <h2 style="text-align: center;">Regional Standards and Testing Laboratories</h2>
                    <h2 style="text-align: center;">Shelf life Evaluation Laboratory</h2>
                    <br>
                    <h2 style="text-align: center;">Score Sheet - Acceptability Test Using 9-Point Hedonic Scale</h2>';

        $html .= '<div class="form-group">
                    <input type="text" class="form-control" value="Date: ' . htmlspecialchars($rows[$i]['date_submitted']) . '" readonly><br>
                    <input type="text" class="form-control" value="Name: ' . htmlspecialchars($rows[$i]['name']) . '" readonly><br>
                    <input type="text" class="form-control" value="Agency/School: ' . htmlspecialchars($rows[$i]['institution_name']) . '" readonly><br>
                    <input type="text" class="form-control" value="Panelist No.:' . htmlspecialchars($rows[$i]['p_id']) . '" readonly><br>
                    <input type="text" class="form-control" value="Product Code: ' . htmlspecialchars($rows[$i]['sample_code_no']) . '" readonly><br>
                </div>';
        $html .= '<p style="margin-top: 20px; margin-bottom: 20px;"><strong>Instruction: </strong>Please evaluate the sample and select the option that best reflects how much you like or accept the product.</p>';
        $html .= '<div class="form-group">';
        
        // Rating options for first column
        $ratings = [
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

        foreach ($ratings as $value => $label) {
            $checked = ($rows[$i]['rating'] == $value) ? 'checked' : '';
            $html .= '<div class="custom-radio">
                        <input type="radio" class="custom-control-input" id="customCheck' . $i . '_' . $value . '" name="rating' . $i . '" value="' . $value . '" ' . $checked . ' disabled>
                        <label class="custom-control-label" for="customCheck' . $i . '_' . $value . '">' . $label . '</label>
                    </div>';
        }
        
    // Always display the remarks section, even if it is empty or null
    $html .= '<div class="form-group">
    <label>Remarks (Optional):</label>
    <textarea readonly>' . htmlspecialchars($rows[$i]['remarks'] ?? '') . '</textarea>
    </div>';


    // SHL Analyst Only section
    $html .= '<div class="form-group" style="text-align: center;">
                <label style="display: block; margin-bottom: 25px; margin-top: 25px;">---------------SHL Analyst Only---------------</label>
                <input type="text" style="margin-bottom: 30px; text-align: left;" class="form-control" readonly value="Numerical Score: ' . htmlspecialchars($rows[$i]['rating']) . '"><br>
                <input type="text" style="margin-bottom: 30px; text-align: left;" class="form-control" readonly value="Checked by/Date: ' . htmlspecialchars($rows[$i]['date_checked']) . '">
            </div>';

    // Control Number and Page Number (structured as requested)
    $html .= '<table style="width: 100%; margin-top: 20px; font-size: 12px; border-collapse: collapse;">
                <tr>
                    <td style="text-align: left; width: 40%; border: none;">Page 1 of 1</td>
                    <td style="text-align: left; width: 60%; border: none;">
                        STM-007-F1<br>
                        Revision 0<br>
                        <i>Effectivity Date: 24 June 2020</i>
                    </td>
                </tr>
            </table>';

        $html .= '</td>'; // End first column
    
    // Second column (if there is a second result)
    if (isset($rows[$i + 1])) {
        $html .= '<td>';
        $html .= '<h1 style="text-align: center;">DEPARTMENT OF SCIENCE AND TECHNOLOGY - X</h1>
                    <h2 style="text-align: center;">Regional Standards and Testing Laboratories</h2>
                    <h2 style="text-align: center;">Shelf life Evaluation Laboratory</h2>
                    <br>
                    <h2 style="text-align: center;">Score Sheet - Acceptability Test Using 9-Point Hedonic Scale</h2>';

        $html .= '<div class="form-group">
                    <input type="text" class="form-control" value="Date: ' . htmlspecialchars($rows[$i + 1]['date_submitted']) . '" readonly><br>
                    <input type="text" class="form-control" value="Name: ' . htmlspecialchars($rows[$i + 1]['name']) . '" readonly><br>
                    <input type="text" class="form-control" value="Agency/School: ' . htmlspecialchars($rows[$i + 1]['institution_name']) . '" readonly><br>
                    <input type="text" class="form-control" value="Panelist No.: ' . htmlspecialchars($rows[$i + 1]['p_id']) . '" readonly><br>
                    <input type="text" class="form-control" value="Product Code: ' . htmlspecialchars($rows[$i + 1]['sample_code_no']) . '" readonly><br>
                  </div>';
        $html .= '<p style="margin-top: 20px; margin-bottom: 20px;"><strong>Instruction: </strong>Please evaluate the sample and select the option that best reflects how much you like or accept the product.</p>';
        $html .= '<div class="form-group">';
        
        // Rating options for second column
        foreach ($ratings as $value => $label) {
            $checked = ($rows[$i + 1]['rating'] == $value) ? 'checked' : '';
            $html .= '<div class="custom-radio">
                        <input type="radio" class="custom-control-input" id="customCheck' . ($i + 1) . '_' . $value . '" name="rating' . ($i + 1) . '" value="' . $value . '" ' . $checked . ' disabled>
                        <label class="custom-control-label" for="customCheck' . ($i + 1) . '_' . $value . '">' . $label . '</label>
                      </div>';
        }
        
        // Always display the remarks section, even if it is empty or null
        $html .= '<div class="form-group">
                    <label>Remarks (Optional):</label>
                    <textarea readonly>' . htmlspecialchars($rows[$i + 1]['remarks'] ?? '') . '</textarea>
                  </div>';

        // SHL Analyst Only section
        $html .= '<div class="form-group" style="text-align: center;">
                    <label style="display: block; margin-bottom: 25px; margin-top: 25px;">---------------SHL Analyst Only---------------</label>
                    <input type="text" style="margin-bottom: 30px; text-align: left;" class="form-control" readonly value="Numerical Score: ' . htmlspecialchars($rows[$i + 1]['rating']) . '"><br>
                    <input type="text" style="margin-bottom: 30px; text-align: left;" class="form-control" readonly value="Checked by/Date: ' . htmlspecialchars($rows[$i + 1]['date_checked']) . '">
                  </div>';

        // Control Number and Page Number (structured as requested)
        $html .= '<table style="width: 100%; margin-top: 20px; font-size: 12px; border-collapse: collapse;">
                    <tr>
                        <td style="text-align: left; width: 40%; border: none;">Page 1 of 1</td>
                        <td style="text-align: left; width: 60%; border: none;">
                            STM-007-F1<br>
                            Revision 0<br>
                            <i>Effectivity Date: 24 June 2020</i>
                        </td>
                    </tr>
                  </table>';

        $html .= '</td>'; // End second column
    }
    
    $html .= '</tr></table>'; // End table
    $html .= '</div>'; // End page
}

$html .= '</body></html>';

// Load the HTML content into Dompdf
$dompdf->loadHtml($html);

// Render the PDF
$dompdf->render();

// Output the generated PDF to the browser
$dompdf->stream("evaluation_results_sample_{$s_id}.pdf", ["Attachment" => true]);
?>
