<?php
include('db.php');

if (!isset($_GET['request_no'])) {
    echo "<script>alert('Missing request number in the URL'); window.location.href='public_dashboard.php';</script>";
    exit();
}

$request_no = $_GET['request_no'];

// Check if evaluation is already full (50 panelists)
$countQuery = $conn->prepare("SELECT COUNT(*) as count FROM hedonic WHERE request_no = ?");
$countQuery->bind_param("i", $request_no);
$countQuery->execute();
$countResult = $countQuery->get_result();
$countRow = $countResult->fetch_assoc();

if ($countRow['count'] > 50) {
    echo "<script>alert('Evaluation is already full (50 panelists)'); window.location.href='public_dashboard.php';</script>";
    exit();
}

// Handle name submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['panelist_name'])) {
    $panelist_name = strtoupper(trim($_POST['panelist_name'])); // Convert to uppercase
    
    if (empty($panelist_name)) {
        echo "<script>alert('Please enter your code name');</script>";
    } else {
        // Check if code name exists for this request
        $check_stmt = $conn->prepare("SELECT p_id FROM hedonic WHERE request_no = ? AND name = ?");
        $check_stmt->bind_param("is", $request_no, $panelist_name);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            // Code name exists, get the existing p_id and check if already submitted
            $row = $check_result->fetch_assoc();
            $p_id = $row['p_id'];
            
            // Check if evaluation is already submitted
            $submit_check = $conn->prepare("SELECT date_submitted FROM hedonic WHERE request_no = ? AND p_id = ?");
            $submit_check->bind_param("ii", $request_no, $p_id);
            $submit_check->execute();
            $submit_result = $submit_check->get_result();
            $submit_row = $submit_result->fetch_assoc();
            
            if ($submit_row['date_submitted'] !== null) {
                echo "<script>alert('You have already submitted your evaluation for this request.'); window.location.href='public_dashboard.php';</script>";
                exit();
            }
            
            header("Location: hedonic_evaluation_form.php?request_no=" . $request_no . "&show_form=1&p_id=" . $p_id . "&name=" . urlencode($panelist_name));
            exit();
        } else {
            // Check if we're at the 50 panelist limit before adding a new one
            if ($countRow['count'] >= 50) {
                echo "<script>alert('Evaluation is already full (50 panelists)'); window.location.href='public_dashboard.php';</script>";
                exit();
            }
            
            // Generate new p_id
            $maxQuery = $conn->prepare("SELECT MAX(p_id) as max_p_id FROM hedonic WHERE request_no = ?");
            $maxQuery->bind_param("i", $request_no);
            $maxQuery->execute();
            $maxResult = $maxQuery->get_result();
            $maxRow = $maxResult->fetch_assoc();
            $p_id = ($maxRow['max_p_id'] === null) ? 1 : $maxRow['max_p_id'] + 1;

            // Insert new p_id and code name into hedonic table
            $insert_stmt = $conn->prepare("INSERT INTO hedonic (p_id, request_no, name) VALUES (?, ?, ?)");
            $insert_stmt->bind_param("iis", $p_id, $request_no, $panelist_name);
            if ($insert_stmt->execute()) {
                header("Location: hedonic_evaluation_form.php?request_no=" . $request_no . "&show_form=1&p_id=" . $p_id . "&name=" . urlencode($panelist_name));
                exit();
            } else {
                echo "<script>alert('Error registering panelist');</script>";
            }
        }
    }
}

// If we're not showing the form yet, show the code name input page
if (!isset($_GET['show_form'])) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <?php include('includes/header.php'); ?>
        <title>Panelist Registration</title>
        <style>
            body {
                background-color: #f0eff1;
            }
            .code-name-input {
                text-transform: uppercase;
            }
        </style>
    </head>
    <body class="layout-4">
        <div id="app">
            <div class="main-wrapper main-wrapper-1">
                <?php include('includes/sidebar.php'); ?>
                <div class="main-content">
                    <section class="section">
                        <div class="section-header">
                            <h1>Panelist Registration</h1>
                        </div>
                        <div class="section-body">
                            <div class="row justify-content-center">
                                <div class="col-12 col-md-8 col-lg-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h4>Enter Your Name</h4>
                                        </div>
                                        <div class="card-body">
                                            <form method="POST">
                                                <div class="form-group">
                                                    <label>Code Name (e.g., JOHN DOE SMITH)</label>
                                                    <input type="text" class="form-control code-name-input" name="panelist_name" required 
                                                           oninput="this.value = this.value.toUpperCase()" 
                                                           placeholder="Enter your initials or code name">
                                                </div>
                                                <div class="form-group text-center">
                                                    <button type="submit" class="btn btn-primary">Continue to Evaluation</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
        <?php include('includes/footer.php'); ?>
        <script src="assets/bundles/lib.vendor.bundle.js"></script>
        <script src="js/CodiePie.js"></script>
        <script src="js/scripts.js"></script>
        <script src="js/custom.js"></script>

    </body>
    </html>
    <?php
    exit();
}

// If we get here, we're showing the evaluation form
$p_id = $_GET['p_id'];
$panelist_name = $_GET['name'];

// Validate p_id and name
if (!isset($p_id) || !isset($panelist_name)) {
    echo "<script>alert('Missing panelist information'); window.location.href='hedonic_evaluation_form.php?request_no=" . $request_no . "';</script>";
    exit();
}

// Verify the p_id and name match in the database
$verify_stmt = $conn->prepare("SELECT p_id FROM hedonic WHERE request_no = ? AND p_id = ? AND name = ?");
$verify_stmt->bind_param("iis", $request_no, $p_id, $panelist_name);
$verify_stmt->execute();
$verify_result = $verify_stmt->get_result();

if ($verify_result->num_rows === 0) {
    echo "<script>alert('Invalid panelist information'); window.location.href='hedonic_evaluation_form.php?request_no=" . $request_no . "';</script>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include('includes/header.php'); ?>
    <title>Hedonic Scale Evaluation</title>
</head>

<style>
    body {
        background-color: #f0eff1;
    }
</style>
<body class="layout-4">
    <div id="app">
        <div class="main-wrapper main-wrapper-1">
            <?php include('includes/sidebar.php'); ?>
            <div class="main-content">
                <section class="section">
                <div class="section-header text-center">
                    <h4 class="mb-2">Shelf Life Evaluation Laboratory</h4>
                </div>
                <div class="section-body">
                    <div class="row">
                        <div class="col-12 col-md-12 col-lg-12">
                            <div class="card">
                                <div class="card-header">
                                    <h6>Score Sheet - Acceptability Test Using 9-Point Hedonic Scale</h6>
                                </div>
                                <?php
                                $sample_code_query = "SELECT sample_code_no FROM evaluation_requests WHERE request_no = ?";
                                $stmt = $conn->prepare($sample_code_query);
                                $stmt->bind_param("i", $request_no);
                                $stmt->execute();
                                $stmt->bind_result($sample_code);
                                $stmt->fetch();
                                $stmt->close();
                                ?>
                                <div class="card-body">
                                    
                                    <p><strong>Date of Evaluation: </strong><?php echo date("Y-m-d"); ?></p>
                                    <p><strong>Sample Code: </strong><?php echo htmlspecialchars($sample_code); ?></p>
                                    <p><strong>Panelist No.: </strong><?php echo htmlspecialchars($p_id); ?></p>
                                    <p><strong>Name: </strong><?php echo htmlspecialchars($panelist_name); ?></p>
                                </div>
                        </div>
                    </div>
                </div>

                <form action="submit_hedonic.php" method="POST">
                <input type="hidden" name="p_id" value="<?php echo $p_id; ?>">
                <input type="hidden" name="request_no" value="<?php echo $request_no; ?>">
                            <input type="hidden" name="name" value="<?php echo htmlspecialchars($panelist_name); ?>">
                <input type="hidden" name="date_submitted" value="<?php echo date('Y-m-d'); ?>">

                <div class="form-group">
                                <input type="text" class="form-control" id="agencySchool" name="institution_name" placeholder="Name of Agency/School" required>
                </div>
                <div class="card">
                    <div class="card-body">
                        
                    <p><strong>Instruction: </strong>Please evaluate the sample and select the option that best reflects how much you like or accept the product.</p>
                
                    <div class="form-group">
                        <div class="custom-control custom-radio">
                            <input type="radio" class="custom-control-input" id="customCheck1" name="rating" value="9" required>
                            <label class="custom-control-label" for="customCheck1">Like Extremely</label>
                        </div>
                        <div class="custom-control custom-radio">
                            <input type="radio" class="custom-control-input" id="customCheck2" name="rating" value="8" required>
                            <label class="custom-control-label" for="customCheck2">Like Very Much</label>
                        </div>
                        <div class="custom-control custom-radio">
                            <input type="radio" class="custom-control-input" id="customCheck3" name="rating" value="7" required>
                            <label class="custom-control-label" for="customCheck3">Like Moderately</label>
                        </div>
                        <div class="custom-control custom-radio">
                            <input type="radio" class="custom-control-input" id="customCheck4" name="rating" value="6" required>
                            <label class="custom-control-label" for="customCheck4">Like Slightly</label>
                        </div>
                        <div class="custom-control custom-radio">
                            <input type="radio" class="custom-control-input" id="customCheck5" name="rating" value="5" required>
                            <label class="custom-control-label" for="customCheck5">Neither Like nor Dislike</label>
                        </div>
                        <div class="custom-control custom-radio">
                            <input type="radio" class="custom-control-input" id="customCheck6" name="rating" value="4" required>
                            <label class="custom-control-label" for="customCheck6">Dislike Slightly</label>
                        </div>
                        <div class="custom-control custom-radio">
                            <input type="radio" class="custom-control-input" id="customCheck7" name="rating" value="3" required>
                            <label class="custom-control-label" for="customCheck7">Dislike Moderately</label>
                        </div>
                        <div class="custom-control custom-radio">
                            <input type="radio" class="custom-control-input" id="customCheck8" name="rating" value="2" required>
                            <label class="custom-control-label" for="customCheck8">Dislike Very Much</label>
                        </div>
                        <div class="custom-control custom-radio">
                            <input type="radio" class="custom-control-input" id="customCheck9" name="rating" value="1" required>
                            <label class="custom-control-label" for="customCheck9">Dislike Extremely</label>
                        </div>
                    </div>
</div>
                </div>
                    <div class="form-group">
                        <label for="exampleFormControlTextarea1">Remarks (Optional)</label>
                        <textarea class="form-control" id="exampleFormControlTextarea1" name="remarks" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group text-center">
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </div>
                        </form>
                </div>
                </section>
            </div>
        </div>
    </div>

    <?php include('includes/footer.php'); ?>
    <script src="assets/bundles/lib.vendor.bundle.js"></script>
    <script src="js/CodiePie.js"></script>
    <script src="js/scripts.js"></script>
    <script src="js/custom.js"></script>
</body>
</html>
