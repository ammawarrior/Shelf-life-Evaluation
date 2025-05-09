<?php
session_start();
require 'db.php'; // your DB connection
require_once 'vendor/autoload.php'; // dompdf

use Dompdf\Dompdf;
use Dompdf\Options;

$request_no = isset($_GET['request_no']) ? $_GET['request_no'] : '';

if (empty($request_no)) {
    die("Request number is missing.");
}

// Get prepared_by name from users table
$user_id = $_SESSION['user_id'] ?? 0;
$prepared_by = 'Unknown User';

if ($user_id) {
    $user_stmt = $conn->prepare("SELECT code_name FROM users WHERE user_id = ?");
    $user_stmt->bind_param("i", $user_id);
    $user_stmt->execute();
    $user_stmt->bind_result($code_name_from_db);
    if ($user_stmt->fetch()) {
        $prepared_by = $code_name_from_db;
    }
    $user_stmt->close();
}

// Fetch all 12 triangle_results rows for the given request_no
$stmt = $conn->prepare("SELECT * FROM triangle_results WHERE request_no = ? ORDER BY triad_no ASC");
$stmt->bind_param("s", $request_no);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("No data found for this request.");
}

// Map correct answers
$correct_answers = [
    1 => '1', 2 => '3', 3 => '2', 4 => '1', 5 => '3', 6 => '2',
    7 => '1', 8 => '3', 9 => '2', 10 => '1', 11 => '3', 12 => '2'
];

ob_start();
$panel_count = 0;

echo '<style>
    body {
        font-family: "Calibri", sans-serif;
        font-size: 10px;
        margin: 20px;
    }
    .panel-wrapper {
        page-break-after: always;
    }
    .panel-block {
        width: 100%;
        padding: 10px 0;
        page-break-inside: avoid;
        border-bottom: 1px dashed #000;
    }
    .panel-block:last-child {
        border-bottom: none;
    }
    .header {
        text-align: center;
        line-height: 1.2;
        margin-bottom: 10px;
    }
    .info-section table {
        width: 100%;
        font-size: 10px;
        border-collapse: collapse;
        border: none;
    }
    .info-section td {
        padding: 5px;
        border: none;
    }
    .sample-table {
        margin-top: 10px;
        font-size: 10px;
    }
    .sample-table table {
        width: 100%;
        border-collapse: collapse;
    }
    .sample-table th,
    .sample-table td {
        border: 1px solid black;
        padding: 5px;
        text-align: center;
    }
    .signature-section {
        margin-top: 10px;
        width: 100%;
        font-size: 10px;
    }
    .footer {
        font-size: 9px;
        width: 100%;
        display: flex;
        justify-content: space-between;
        margin-top: 10px;
    }
</style>';

while ($data = $result->fetch_assoc()) {
    $panel_count++;

    $code_name = $data['code_name'];
    $triad_no = $data['triad_no'];
    $date = $data['date'];
    $sample_code1 = $data['sample_code1'];
    $sample_code2 = $data['sample_code2'];
    $sample_code3 = $data['sample_code3'];
    $odd_sample = $data['odd_sample'];

    $correct_answer = $correct_answers[$triad_no] ?? null;
    $answer_result = ($odd_sample == $correct_answer) ? 'Correct' : 'Incorrect';
    $formatted_date = date("F j, Y", strtotime($date));

    if ($panel_count % 2 == 1) {
        echo '<div class="panel-wrapper">';
    }
    ?>

    <div class="panel-block">
        <div class="header">
            <h3>DEPARTMENT OF SCIENCE AND TECHNOLOGY – X</h3>
            <h4>Regional Standards and Testing Laboratories</h4>
            <h4>Shelf life Evaluation Laboratory</h4>
            <br>
            <h4><b>Score Sheet – Triangle Test</b></h4>
        </div>

        <div class="info-section">
            <table>
                <tr>
                    <td style="text-align: left;"><strong>Product Code: <?= htmlspecialchars($request_no) ?></strong></td>
                    <td style="text-align: right;"><strong>Date: <?= htmlspecialchars($formatted_date) ?></strong></td>
                </tr>
                <tr>
                    <td style="text-align: left;"><strong>Name: <?= htmlspecialchars($code_name) ?></strong></td>
                    <td style="text-align: right;"><strong>Panelist Number: <?= htmlspecialchars($triad_no) ?></strong></td>
                </tr>
            </table>
        </div>

        <p>
            Instruction: Taste the samples from left to right. Two samples are alike; one is different.
            Select or guess the odd/different sample and indicate by placing a check mark “✔” next to the code of the odd sample.
        </p>

        <div class="sample-table">
            <table>
                <thead>
                    <tr>
                        <th>Sample Code</th>
                        <th>Odd Sample</th>
                    </tr>
                </thead>
                <tbody>
    <tr>
        <td><?= htmlspecialchars($sample_code1) ?></td>
        <td><?= ($odd_sample == 1) ? '<span style="font-family: \'DejaVu Sans\', sans-serif;">✔</span>' : '' ?></td>
    </tr>
    <tr>
        <td><?= htmlspecialchars($sample_code2) ?></td>
        <td><?= ($odd_sample == 2) ? '<span style="font-family: \'DejaVu Sans\', sans-serif;">✔</span>' : '' ?></td>
    </tr>
    <tr>
        <td><?= htmlspecialchars($sample_code3) ?></td>
        <td><?= ($odd_sample == 3) ? '<span style="font-family: \'DejaVu Sans\', sans-serif;">✔</span>' : '' ?></td>
    </tr>
</tbody>

            </table>
        </div>

        <p>
            Panelist’s Comments/Remarks: _____________________________________________________________________________________________
        </p>
        <p>
            - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -For SHL analyst only- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
        </p>

        <div class="signature-section">
    <table style="width: 100%; font-size: 10px; border: none; border-collapse: collapse;">
        <tr>
            <td style="text-align: left; padding: 5px; border: none; width: 50%;">
                Panelist’s Answer: <strong><?= $answer_result ?></strong>
            </td>
            <td style="text-align: right; padding: 5px; border: none; width: 50%;">
                Checked by/Date: <?= htmlspecialchars($prepared_by) ?> | <?= date('F d, Y') ?>
            </td>
        </tr>
    </table>
</div>


        <div class="footer">
            <div>
                Page <?= $triad_no ?> of 12
            </div>
            <div>
                STM - 023 F1<br>
                Revision 0<br>
                Effectivity Date: 16 March 2020
            </div>
        </div>
    </div>

    <?php
    if ($panel_count % 2 == 0) {
        echo '</div>'; // Close wrapper after 2 panelists
    }
}

// Close the last wrapper if odd number
if ($panel_count % 2 != 0) {
    echo '</div>';
}

$html = ob_get_clean();

// Render using Dompdf
$options = new Options();
$options->set('defaultFont', 'DejaVu Sans');
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Output PDF to browser
$dompdf->stream("print_panelists_$request_no.pdf", ["Attachment" => false]);
exit;
?>
