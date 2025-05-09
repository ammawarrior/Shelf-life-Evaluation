<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include('includes/header.php');
include 'db.php';

$access_granted = true; // Set access to true by default
$request_no = $_GET['request_no'] ?? ''; // Get request_no from the URL or set it from another logic
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_data'])) {
        $request_no = $_POST['request_no_hidden'];
        $structured_codes = [];
    
        $triads = [
            'ABB', 'AAB', 'ABA', 'BAA', 'BBA', 'BAB',
            'ABB', 'AAB', 'ABA', 'BAA', 'BBA', 'BAB',
        ];
    
        $codeLabels = range('A', 'O');
    
        for ($i = 0; $i < count($triads); $i++) {
            $triad_type = $triads[$i];
            $triad_no = $i + 1;
            $code_prefix = $codeLabels[$i];
            $structured_codes[] = [
                'triad_no' => $triad_no,
                'triad_type' => $triad_type,
                'code1' => $_POST[$code_prefix . '1'] ?? '',
                'code2' => $_POST[$code_prefix . '2'] ?? '',
                'code3' => $_POST[$code_prefix . '3'] ?? ''
            ];
        }
    
        $user_input_json = json_encode($structured_codes);
    
        $sample_a_label = $_POST['sample_a_label'] ?? '';
        $sample_b_label = $_POST['sample_b_label'] ?? '';
    
        // Sample mappings
        $sample_a_codes = [
            ['A1', 1], ['B1', 2], ['B2', 2], ['C1', 3], ['C3', 3], ['D2', 4], ['D3', 4], ['E3', 5], ['F2', 6], ['G1', 7],
            ['H1', 8], ['H2', 8], ['I1', 9], ['I3', 9], ['J2', 10], ['J3', 10], ['K3', 11], ['L2', 12]
        ];
        $sample_b_codes = [
            ['A2', 1], ['A3', 1], ['B3', 2], ['C2', 3], ['D1', 4], ['E1', 5], ['E2', 5], ['F1', 6], ['F3', 6], ['G2', 7], ['G3', 7],
            ['H3', 8], ['I2', 9], ['J1', 10], ['K1', 11], ['K2', 11], ['L1', 12], ['L3', 12]
        ];
    
        $samples_container = [
            'sample_a' => [],
            'sample_b' => []
        ];
    
        $add_asterisk = ['A2', 'B2', 'C2', 'D2', 'E2', 'F2', 'G2', 'H2', 'I2', 'J2', 'K2', 'L2'];

        foreach ($sample_a_codes as [$code, $triad_no]) {
            $samples_container['sample_a'][] = [
                'code' => $_POST[$code] ?? '',
                'code_id' => $code,
                'triad_no' => in_array($code, $add_asterisk) ? $triad_no . '*' : $triad_no
            ];
        }
        
        foreach ($sample_b_codes as [$code, $triad_no]) {
            $samples_container['sample_b'][] = [
                'code' => $_POST[$code] ?? '',
                'code_id' => $code,
                'triad_no' => in_array($code, $add_asterisk) ? $triad_no . '*' : $triad_no
            ];
        }
        
    
        $samples_container_json = json_encode($samples_container);
    
        // Save to DB — add samples_container field
        $stmt = $conn->prepare(
            "UPDATE evaluation_requests
            SET user_input_codes = ?, sample_a_label = ?, sample_b_label = ?, samples_container = ?, date_of_computation = NOW()
            WHERE request_no = ?"
        );
        $stmt->bind_param("sssss", $user_input_json, $sample_a_label, $sample_b_label, $samples_container_json, $request_no);
        $stmt->execute();
    
        $_SESSION['success_message'] = "Data successfully saved for request #$request_no.";
header("Location: analyst.php");
exit();

    }
}

// Fetch data from evaluation_requests where request_no = $request_no
$stmt = $conn->prepare("SELECT user_input_codes, sample_a_label, sample_b_label FROM evaluation_requests WHERE request_no = ?");
$stmt->bind_param("s", $request_no);
$stmt->execute();
$result = $stmt->get_result();
$request_data = $result->fetch_assoc();

$user_input_data = [];
$sample_a_label = '';
$sample_b_label = '';

if ($request_data) {
    $user_input_data = json_decode($request_data['user_input_codes'], true);
    $sample_a_label = $request_data['sample_a_label'];
    $sample_b_label = $request_data['sample_b_label'];
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="assets/modules/datatables/datatables.min.css">
    <link rel="stylesheet" href="assets/modules/datatables/DataTables-1.10.16/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="assets/modules/datatables/Select-1.2.4/css/select.bootstrap4.min.css">
    <style>
        .table-sm th,
        .table-sm td {
            padding: 0.25rem !important;
            font-size: 0.95rem;
        }
        .form-control-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.85rem;
        }
        .card-body .form-control {
            padding: 0.2rem 0.4rem;
            font-size: 0.85rem;
        }
        .is-invalid {
        border-color: #dc3545;
        background-color: #f8d7da;
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
                    <h1>Monitoring</h1>
                </div>
                <div class="section-body">
                    <?php if ($success_message): ?>
                        <div class="alert alert-success"><?= $success_message ?></div>
                    <?php endif; ?>

                    <form method="POST" id="userInputForm">
                        <input type="hidden" name="request_no_hidden" value="<?= htmlspecialchars($request_no) ?>">
                        <div class="row">
                            <!-- User Input Container -->
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header text-center font-weight-bold">User Input Container</div>
                                    <div class="card-body p-0">
                                        <table class="table table-sm table-bordered text-center mb-0">
                                            <thead class="thead-light">
                                            <tr>
                                                <th>Triad No.</th>
                                                <th colspan="3">Codes</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <?php
                                            $triads = [
                                                'ABB', 'AAB', 'ABA', 'BAA', 'BBA', 'BAB',
                                                'ABB', 'AAB', 'ABA', 'BAA', 'BBA', 'BAB',
                                            ];
                                            $codeLabels = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L'];
                                            foreach ($triads as $index => $triad) {
                                                $codeBase = $codeLabels[$index];
                                                echo "<tr><td>" . ($index + 1) . ". $triad</td>";
                                                for ($i = 1; $i <= 3; $i++) {
                                                    $code = $codeBase . $i;
                                                    echo "<td><input type='text' class='form-control form-control-sm text-center code-input' name='$code' id='$code' value='" . ($user_input_data[$index]['code' . $i] ?? '') . "'></td>";

                                                }
                                                echo "</tr>";
                                            }
                                            ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="card-footer text-center">
                                        <button type="reset" class="btn btn-outline-primary btn-sm" onclick="document.getElementById('userInputForm').reset(); syncAll();">CLEAR</button>
                                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="shuffleInputs()">SHUFFLE</button>
                                        <button type="submit" name="save_data" class="btn btn-outline-primary btn-sm">SAVE</button>
                                        <button type="button" class="btn btn-outline-success btn-sm" onclick="printPage()">PRINT</button>
                                    </div>

                                </div>
                            </div>

                            <!-- Samples Container -->
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header text-center font-weight-bold">Samples Container</div>
                                    <div class="card-body p-0">
                                        <div class="row no-gutters">
                                            <?php
                                            $sampleSets = [
                                                'Sample A' => [
                                                    ['A1', '1'], ['B1', '2'], ['B2', '*2'], ['C1', '3'], ['C3', '3'],
                                                    ['D2', '*4'], ['D3', '4'], ['E3', '5'], ['F2', '*6'], ['G1', '7'],
                                                    ['H1', '8'], ['H2', '*8'], ['I1', '9'], ['I3', '9'], ['J2', '*10'],
                                                    ['J3', '10'], ['K3', '11'], ['L2', '*12']
                                                ],
                                                'Sample B' => [
                                                    ['A2', '*1'], ['A3', '1'], ['B3', '2'], ['C2', '*3'], ['D1', '4'],
                                                    ['E1', '5'], ['E2', '*5'], ['F1', '6'], ['F3', '6'], ['G2', '*7'], ['G3', '7'],
                                                    ['H3', '8'], ['I2', '*9'], ['J1', '10'], ['K1', '11'], ['K2', '*11'],
                                                    ['L1', '12'], ['L3', '12']
                                                ]
                                            ];
                                            foreach ($sampleSets as $sampleLabel => $codes) {
                                                echo '<div class="col-md-6' . ($sampleLabel === 'Sample A' ? ' border-right' : '') . '">';
                                                echo "<div class='p-2'>
                                                    <label><strong>$sampleLabel:</strong></label>
                                                    <input type='text' class='form-control form-control-sm' name='" . strtolower(str_replace(' ', '_', $sampleLabel)) . "_label' placeholder='e.g., SHL-0000' value='" . ($sampleLabel === 'Sample A' ? htmlspecialchars($sample_a_label) : htmlspecialchars($sample_b_label)) . "'>
                                                </div>";
                                                echo "<table class='table table-sm table-bordered text-center mb-0'>";
                                                echo "<thead class='thead-light'><tr><th>Codes</th><th>Triad No.</th></tr></thead><tbody>";
                                                foreach ($codes as $entry) {
                                                    $code = $entry[0];
                                                    $triadNo = $entry[1];
                                                    echo "<tr><td id='sample_$code'></td><td>$triadNo</td></tr>";
                                                }
                                                echo "</tbody></table></div>";
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </section>

        </div>
        <?php include('includes/footer.php'); ?>
    </div>
</div>

<script src="assets/bundles/lib.vendor.bundle.js"></script>
<script src="js/CodiePie.js"></script>
<script src="assets/modules/datatables/datatables.min.js"></script>
<script src="assets/modules/datatables/DataTables-1.10.16/js/dataTables.bootstrap4.min.js"></script>
<script src="assets/modules/datatables/Select-1.2.4/js/dataTables.select.min.js"></script>
<script src="assets/modules/jquery-ui/jquery-ui.min.js"></script>
<script src="js/page/modules-datatables.js"></script>
<script src="js/scripts.js"></script>
<script src="js/custom.js"></script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


<script>
const syncCode = (code) => {
    const input = document.getElementById(code);
    const sample = document.getElementById('sample_' + code);
    if (input && sample) {
        sample.textContent = input.value;
    }
};

const syncAll = () => {
    document.querySelectorAll('.code-input').forEach(input => {
        syncCode(input.id);
    });
};

document.querySelectorAll('.code-input').forEach(input => {
    input.addEventListener('input', () => {
        syncCode(input.id);
    });
});





<?php if (isset($_SESSION['success_message'])) : ?>
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Success',
            text: '<?php echo $_SESSION['success_message']; ?>',
            showConfirmButton: false,
            timer: 2000
        });
    </script>
    <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error_message'])) : ?>
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: '<?php echo $_SESSION['error_message']; ?>',
        });
    </script>
    <?php unset($_SESSION['error_message']); ?>
<?php endif; ?>

</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('userInputForm');
    const inputs = form.querySelectorAll('input[type="text"]');
    let values = [];

    // Add an event listener to each input field to check for duplicates as the user types
    inputs.forEach(input => {
        input.addEventListener('input', function () {
            const value = input.value.trim();

            // Check for duplicates while typing
            if (values.includes(value) && value !== '') {
                input.classList.add('is-invalid');
                input.setCustomValidity('Duplicate code detected.');
            } else {
                input.classList.remove('is-invalid');
                input.setCustomValidity('');
            }
        });
    });

    // Form submission validation
    form.addEventListener('submit', function (e) {
        let isValid = true;
        values = [];  // Reset values on each form submission

        inputs.forEach(input => {
            const value = input.value.trim();

            // Check for empty input
            if (value === '') {
                input.classList.add('is-invalid');
                isValid = false;
            } else {
                input.classList.remove('is-invalid');
            }

            // Check for duplicates and add to values array
            if (values.includes(value) && value !== '') {
                input.classList.add('is-invalid');
                isValid = false;
            } else if (value !== '') {
                values.push(value);
            }
        });

        if (!isValid) {
            e.preventDefault(); // Stop form submission

            Swal.fire({
                icon: 'warning',
                title: 'Form Error',
                text: 'Please make sure all fields are filled in correctly and there are no duplicates.',
                confirmButtonText: 'OK'
            });
        }
    });
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('userInputForm');
    const inputs = Array.from(form.querySelectorAll('.code-input'));

    // Listen for PASTE event on the FIRST input
    inputs[0].addEventListener('paste', function(event) {
        event.preventDefault();

        let paste = (event.clipboardData || window.clipboardData).getData('text');
        paste = paste.trim();

        let values = paste.split(/[\s,]+/); // Split by commas, spaces, tabs, etc.

        // Fill the inputs one by one
        for (let i = 0; i < values.length && i < inputs.length; i++) {
            inputs[i].value = values[i];
            syncCode(inputs[i].id); // Update the Samples Container also
        }
    });
});

function shuffleInputs() {
    const inputs = Array.from(document.querySelectorAll('.code-input'));
    const values = inputs.map(input => input.value.trim()).filter(v => v !== '');

    // Fisher-Yates Shuffle Algorithm
    for (let i = values.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [values[i], values[j]] = [values[j], values[i]];
    }

    // Put shuffled values back into inputs
    inputs.forEach((input, index) => {
        input.value = values[index] || '';
        syncCode(input.id); // update sample container too
    });
}

function printPage() {
    const requestNo = "<?= htmlspecialchars($request_no) ?>";
    window.open('print_monitoring.php?request_no=' + requestNo, '_blank');
}

</script>




</body>
</html>
