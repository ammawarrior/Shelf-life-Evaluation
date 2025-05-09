<?php
require 'vendor/autoload.php'; // dompdf autoload

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

$user_input_codes = json_decode($data['user_input_codes'], true);
$samples_container = json_decode($data['samples_container'], true);
$date_of_computation = $data['date_of_computation'] ?? date('Y-m-d');

// Format date nicely
$timestamp = strtotime($date_of_computation);
$formatted_date_of_computation = date('F j, Y', $timestamp);
$sample_a_label = $data['sample_a_label'] ?? '';
$sample_b_label = $data['sample_b_label'] ?? '';
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
    body {
    font-family: "Calibri", sans-serif;
    font-size: 12px;
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
        font-size: 12px;
    }
    .gap-cell {
        background-color: #fff;
        border: none;
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
</style>


<div class="header">
<h3>DEPARTMENT OF SCIENCE AND TECHNOLOGY – X</h3>
    <h4>Regional Standards and Testing Laboratories</h4>
    <h4>Shelf life Evaluation Laboratory</h4>
    <br>
    <h4><b>Sensory Evaluation - Triangle Test Sample Monitoring</b></h4>
</div>
<br><br><br>
<strong><p>Date of Evaluation: <?= htmlspecialchars($formatted_date_of_computation) ?></p></strong>
<br>

<table>
    <thead>
    <tr>
            <th colspan="4"><p>Code A: <?= htmlspecialchars($sample_a_label) ?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Code B: <?= htmlspecialchars($sample_b_label) ?></p></th>
            <td class="gap-cell"></td>
            <th colspan="4">Codes Monitoring</th>
        </tr>
        <tr>
            <th style="width:14%;">Code (A)</th>
            <th style="width:10%;">Triad No.</th>
            <th style="width:14%;">Code (B)</th>
            <th style="width:10%;">Triad No.</th>
            <td class="gap-cell"></td>
            <th style="width:13%;">Triad No.</th>
            <th style="width:10%;">Code 1</th>
            <th style="width:10%;">Code 2</th>
            <th style="width:10%;">Code 3</th>
        </tr>
    </thead>
    <tbody>
    <?php
    $maxRows = max(
        count($samples_container['sample_a'] ?? []),
        count($samples_container['sample_b'] ?? []),
        count($user_input_codes ?? [])
    );

    // List of triad_no values that require an asterisk
    $specialTriads = ['A2','B2','C2','D2','E2','F2','G2','H2','I2','J2','K2','L2'];

    for ($i = 0; $i < $maxRows; $i++) {
        echo "<tr>";

        // Sample A
        echo "<td>" . htmlspecialchars($samples_container['sample_a'][$i]['code'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($samples_container['sample_a'][$i]['triad_no'] ?? '') . "</td>";

        // Sample B
        echo "<td>" . htmlspecialchars($samples_container['sample_b'][$i]['code'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($samples_container['sample_b'][$i]['triad_no'] ?? '') . "</td>";

        // Gap
        echo "<td class='gap-cell'></td>";

        // User Inputs
        if (isset($user_input_codes[$i])) {
            $triad_no = $user_input_codes[$i]['triad_no'];
            $triad_type = $user_input_codes[$i]['triad_type'];

            // Add asterisk if triad_no is in the special list
            if (in_array($triad_no, $specialTriads)) {
                $triad_no = '*' . $triad_no;
            }

            echo "<td>" . htmlspecialchars($triad_no) . " (" . htmlspecialchars($triad_type) . ")</td>";
            echo "<td>" . htmlspecialchars($user_input_codes[$i]['code1']) . "</td>";
            echo "<td>" . htmlspecialchars($user_input_codes[$i]['code2']) . "</td>";
            echo "<td>" . htmlspecialchars($user_input_codes[$i]['code3']) . "</td>";
        } else {
            echo "<td></td><td></td><td></td><td></td>";
        }

        echo "</tr>";
    }
    ?>
</tbody>

</table>
<h5>The middle sample is marked with an asterisk (*).</h5>
<div class="prepared-checked">
   <strong><p>Prepared by:&nbsp; <?= htmlspecialchars($prepared_by) ?></p>
    <p>Date Prepared: &nbsp;<?= date('F d, Y') ?></p> </strong> 
</div>

<div class="footer">
    <div class="footer-left">
        Page 1 of 1
    </div>
    <div class="footer-right">
        STM - 023 F1<br>
        Revision 0<br>
        Effectivity Date: 16 March 2020
    </div>
</div>

<?php
$html = ob_get_clean();

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("Monitoring_$request_no.pdf", ["Attachment" => false]);
exit;
?>
