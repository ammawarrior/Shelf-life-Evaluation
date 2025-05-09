<?php
require 'vendor/autoload.php'; // Dompdf autoload

use Dompdf\Dompdf;
use Dompdf\Options;

session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$request_no = $_GET['request_no'] ?? '';

$stmt = $conn->prepare("SELECT * FROM evaluation_requests WHERE request_no = ?");
$stmt->bind_param("s", $request_no);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

$lab_code_no = $data['lab_code_no'] ?? '';
$sample_code_no = $data['sample_code_no'] ?? '';
$date_of_computation = $data['date_of_computation'] ?? date('Y-m-d');

// Format date nicely
$timestamp = strtotime($date_of_computation);
$formatted_date_of_computation = date('F j, Y', $timestamp);

$sample_a_label = $data['sample_a_label'] ?? '';
$sample_b_label = $data['sample_b_label'] ?? '';

// Fetch code_name from users table
$user_id = $_SESSION['user_id'] ?? 0;
$prepared_by = 'Unknown User';

if ($user_id) {
    $user_stmt = $conn->prepare("SELECT code_name FROM users WHERE user_id = ?");
    $user_stmt->bind_param("i", $user_id);
    $user_stmt->execute();
    $user_stmt->bind_result($code_name);
    if ($user_stmt->fetch()) {
        $prepared_by = $code_name;
    }
    $user_stmt->close();
}

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

ob_start();
?>

<style>
    .tables-container {
        width: 100%;
    }
    body {
        font-family: "Calibri", sans-serif;
        font-size: 10px;
        margin: 20px;
    }
    .header {
        text-align: center;
        line-height: 1.2;
        margin-bottom: 10px;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }
    th, td {
        border: 1px solid black;
        padding: 2px;
        text-align: center;
        word-wrap: break-word;
        font-size: 10px;
    }
    .prepared-checked {
        margin-top: 40px;
    }
    .footer {
        font-size: 9px;
        width: 100%;
        position: fixed;
        bottom: 10px;
    }
    .footer-left {
        float: left;
    }
    .footer-right {
        float: right;
        text-align: left;
    }
    .gap-cell {
        background-color: white;
        border: none;
    }
    .signature-section {
        margin-top: 50px;
        width: 100%;
        font-size: 10px;
    }
</style>

<div class="header">
    <h3>DEPARTMENT OF SCIENCE AND TECHNOLOGY – X</h3>
    <h4>Regional Standards and Testing Laboratories</h4>
    <h4>Shelf life Evaluation Laboratory</h4>
    <br>
    <h4><b>Sensory Evaluation - Triangle Test Result Summary</b></h4>
</div>
<br><br><br>

<strong>
    <p>Request No: &nbsp;&nbsp;<?= htmlspecialchars($request_no) ?></p>
    <p>Sample Code:&nbsp;&nbsp;<?= htmlspecialchars($lab_code_no) ?></p>
    <p>Sample Description:&nbsp;&nbsp; <?= htmlspecialchars($sample_code_no) ?></p>
    <p>Date of Computation: &nbsp;&nbsp;<?= htmlspecialchars($formatted_date_of_computation) ?></p>
</strong>

<br>

<div class="tables-container">
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Panelist No.</th>
                <th>Correct</th>
                <th>Incorrect</th>
                <th class="gap-cell"></th>
                <th>n</th>
                <th>α = 0.05</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $assignment_stmt = $conn->prepare("SELECT triad_no, user_id FROM evaluation_assignments WHERE request_no = ?");
            $assignment_stmt->bind_param("s", $request_no);
            $assignment_stmt->execute();
            $assignment_result = $assignment_stmt->get_result();

            $triad_data = [];
            while ($row = $assignment_result->fetch_assoc()) {
                $triad_data[(int)$row['triad_no']] = $row;
            }
            $assignment_stmt->close();

            $correct_answers = [
                1 => '1', 2 => '3', 3 => '2', 4 => '1', 5 => '3', 6 => '2',
                7 => '1', 8 => '3', 9 => '2', 10 => '1', 11 => '3', 12 => '2'
            ];

            $n_alpha_values = [
                [6, 5], [7, 5], [8, 6], [9, 6], [10, 7], [11, 7],
                [12, 8], [13, 8], [14, 9], [15, 9], [16, 9], [17, 10],
                [18, 10], [19, 11], [20, 11],
            ];

            $correct_count = 0;
            $wrong_count = 0;

            for ($i = 1; $i <= 12; $i++) {
                $row = $triad_data[$i] ?? null;
                echo "<tr>";
                echo "<td>{$i}</td>";

                if ($row) {
                    $triad_no = (int)$row['triad_no'];
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

                    echo "<td>{$triad_no}</td>";

                    if ($odd_sample === $correct) {
                        $correct_count++;
                        echo "<td style='color:green;font-weight:bold;'><span style='font-family: \"DejaVu Sans\", sans-serif;'>&#x2713;</span></td><td></td>";
                    } else {
                        $wrong_count++;
                        echo "<td></td><td style='color:red;font-weight:bold;'><span style='font-family: \"DejaVu Sans\", sans-serif;'>&#x2713;</span></td>";
                    } 
                    
                    } else {
                        echo "<td>---</td><td></td><td></td>";
                    }
                    

                echo "<td class='gap-cell'></td>";

                if (isset($n_alpha_values[$i - 1])) {
                    [$n_val, $alpha_val] = $n_alpha_values[$i - 1];
                    echo "<td>{$n_val}</td><td>{$alpha_val}</td>";
                } else {
                    echo "<td></td><td></td>";
                }

                echo "</tr>";
            }

            for ($j = 12; $j < count($n_alpha_values); $j++) {
                [$n_val, $alpha_val] = $n_alpha_values[$j];
                echo "<tr><td></td><td></td><td></td><td></td><td class='gap-cell'></td><td>{$n_val}</td><td>{$alpha_val}</td></tr>";
            }
            ?>
            <tr>
                <td colspan="2" style="text-align:right;font-weight:bold;">Total</td>
                <td style="color:green;font-weight:bold;"><?= $correct_count ?></td>
                <td style="color:red;font-weight:bold;"><?= $wrong_count ?></td>
                <td class="gap-cell"></td>
                <td></td><td></td>
            </tr>
        </tbody>
    </table>
</div>

<br>
<h5>
    <p>1. If the number of correct responses is greater than or equal to the number given in Table 1 (corresponding to the number of panelists and 0.05 α-risk level), conclude that a perceptible difference exists between samples A and B.</p>
    <p>2. Otherwise, test sample (A) and controlled sample (B) has no perceptible difference at all.</p>
    <br>
    <p><strong>Remarks: There is PERCEPTIBLE DIFFERENCE between samples A & B.</strong></p>
</h5>

<div class="signature-section">
    <table style="width: 100%; font-size: 10px; border: none; border-collapse: collapse;">
        <tr>
            <td style="text-align: left; padding: 5px; border: none;"><strong>Computed by:&nbsp;<?= htmlspecialchars($prepared_by) ?></strong></td>
            <td style="text-align: right; padding: 5px; border: none;"><strong>Signature/Date:&nbsp;<?= date('F d, Y') ?></strong></td>
        </tr>
    </table>
</div>

<div class="footer">
    <div class="footer-left">Page 1 of 1</div>
    <div class="footer-right">STM - 023 F3<br>Revision 0<br>Effectivity Date: 16 March 2020</div>
</div>



<?php
$html = ob_get_clean();

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("Triangle_Test_Summary_$request_no.pdf", ["Attachment" => false]);
exit;
?>
