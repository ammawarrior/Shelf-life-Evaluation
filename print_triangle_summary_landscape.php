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
    .header {
        text-align: center;
        margin-bottom: 10px;
    }
    .signature-section {
        margin-top: 20px;
        width: 100%;
        font-size: 10px;
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
    <h4><b>Sensory Evaluation - Summary of Triangle Test Feedback Results</b></h4>
</div>
<br>
<strong>
<p>Sample Description:&nbsp;&nbsp; <?= htmlspecialchars($sample_code_no) ?></p>
</strong>
<table>
    <thead>
        <tr>
        <th style="width: 10%;">Panelist No.</th>
      <th style="width: 10%;">Code</th>
      <th style="width: 15%;">Identification</th>
      <th style="width: 12%;">Answer</th>
      <th style="width: 53%;">Comments</th> 
        </tr>
    </thead>
    <tbody>
        <?php
        $assignment_stmt = $conn->prepare("SELECT triad_no, user_id FROM evaluation_assignments WHERE request_no = ?");
        $assignment_stmt->bind_param("s", $request_no);
        $assignment_stmt->execute();
        $assignment_result = $assignment_stmt->get_result();

        $correct_answers = [
            1 => '1', 2 => '3', 3 => '2', 4 => '1', 5 => '3', 6 => '2',
            7 => '1', 8 => '3', 9 => '2', 10 => '1', 11 => '3', 12 => '2'
        ];

        while ($row = $assignment_result->fetch_assoc()) {
            $triad_no = (int)$row['triad_no'];
            $user_id = (int)$row['user_id'];

            $stmt = $conn->prepare("SELECT odd_sample, comments, sample_code1, sample_code2, sample_code3 FROM triangle_results WHERE user_id = ? AND product_code = ? LIMIT 1");
            $stmt->bind_param("is", $user_id, $lab_code_no);
            $stmt->execute();
            $stmt->bind_result($odd_sample, $comment, $sample1, $sample2, $sample3);
            $stmt->fetch();
            $stmt->close();

            $odd_sample = trim((string)$odd_sample);
            $comment = htmlspecialchars($comment ?? '');
            $correct = $correct_answers[$triad_no] ?? '';
            $is_correct = ($odd_sample === $correct) ? 'Correct' : 'Incorrect';

            $identification = '';
            if ($odd_sample == '1') $identification = $sample_a_label;
            elseif (in_array($odd_sample, ['2', '3'])) $identification = $sample_b_label;

            $sample_code = '';
            if ($odd_sample == '1') $sample_code = $sample1;
            elseif ($odd_sample == '2') $sample_code = $sample2;
            elseif ($odd_sample == '3') $sample_code = $sample3;

            echo "<tr>";
            echo "<td>{$triad_no}</td>";
            echo "<td>{$sample_code}</td>";
            echo "<td>{$identification}</td>";
            echo "<td>{$is_correct}</td>";
            echo "<td>{$comment}</td>";
            echo "</tr>";
        }
        ?>
    </tbody>
</table>
<br>
<p><strong>Remarks: __________________________________________________________________________________________________________________________________________</strong></p>

<div class="signature-section">
    <table style="width: 100%; font-size: 10px; border: none; border-collapse: collapse;">
        <tr>
            <td style="text-align: left; padding: 5px; border: none;"><strong>Computed by:&nbsp;<?= htmlspecialchars($prepared_by) ?></strong></td>
            <td style="text-align: right; padding: 5px; border: none;"><strong>Date:&nbsp;<?= date('F d, Y') ?></strong></td>
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
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
$dompdf->stream("Triangle_Test_Feedback_Summary_$request_no.pdf", ["Attachment" => false]);
exit;
?>
