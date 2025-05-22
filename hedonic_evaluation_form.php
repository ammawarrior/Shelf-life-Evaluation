<?php
include('db.php');

if (!isset($_GET['request_no'])) {
    echo "<script>alert('Missing request number in the URL'); window.location.href='public_dashboard.php';</script>";
    exit();
}

session_start();
$request_no = trim($_GET['request_no']); // Keep original case, just trim whitespace

// Check if evaluation is already full (50 panelists)
$countQuery = $conn->prepare("SELECT COUNT(*) as count FROM hedonic WHERE BINARY request_no = ?");
$countQuery->bind_param("s", $request_no);
$countQuery->execute();
$countResult = $countQuery->get_result();
$countRow = $countResult->fetch_assoc();

if ($countRow['count'] > 50) {
    echo "<script>alert('Evaluation is already full (50 panelists)'); window.location.href='public_dashboard.php';</script>";
    exit();
}

// Handle name submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['panelist_name']) && isset($_POST['p_id'])) {
    $panelist_name = strtoupper(trim($_POST['panelist_name'])); // Convert to uppercase
    $p_id = $_POST['p_id'];
    
    if (empty($panelist_name)) {
        echo "<script>alert('Please enter your code name');</script>";
    } else {
        // Check if code name exists for this request
        $check_stmt = $conn->prepare("SELECT p_id FROM hedonic WHERE BINARY request_no = ? AND name = ?");
        $check_stmt->bind_param("ss", $request_no, $panelist_name);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            echo "<script>alert('This name is already taken. Please use a different name.');</script>";
        } else {
            // Update the temporary record with the actual name
            $update_stmt = $conn->prepare("UPDATE hedonic SET name = ? WHERE BINARY request_no = ? AND p_id = ?");
            $update_stmt->bind_param("ssi", $panelist_name, $request_no, $p_id);
            
            if ($update_stmt->execute()) {
                // Clear the temporary p_id from session
                unset($_SESSION['temp_p_id_' . $request_no]);
                header("Location: hedonic_evaluation_form.php?request_no=" . urlencode($request_no) . "&show_form=1&p_id=" . $p_id . "&name=" . urlencode($panelist_name));
                exit();
            } else {
                echo "<script>alert('Error updating panelist name');</script>";
            }
        }
    }
}

// If we're not showing the form yet, check if we need to assign a new p_id
if (!isset($_GET['show_form'])) {
    // Check if we already have a temporary p_id for this request
    $session_key = 'temp_p_id_' . $request_no;
    if (isset($_SESSION[$session_key])) {
        $p_id = $_SESSION[$session_key];
        
        // Verify the temporary record still exists
        $verify_stmt = $conn->prepare("SELECT p_id FROM hedonic WHERE BINARY request_no = ? AND p_id = ? AND name LIKE 'TEMP_%'");
        $verify_stmt->bind_param("si", $request_no, $p_id);
        $verify_stmt->execute();
        $verify_result = $verify_stmt->get_result();
        
        if ($verify_result->num_rows > 0) {
            // Show the name input form with the existing p_id
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
                                                        <input type="hidden" name="p_id" value="<?php echo $p_id; ?>">
                                                        <div class="form-group">
                                                            <label>(e.g., JOHN DOE SMITH)</label>
                                                            <input type="text" class="form-control code-name-input" name="panelist_name" required 
                                                                   oninput="this.value = this.value.toUpperCase()" 
                                                                   placeholder="Enter your initials or name">
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

                <!-- Add jQuery if not already included -->
                <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
                
                <!-- Add Bootstrap JS if not already included -->
                <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>

                <!-- Add Font Awesome -->
                <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

                <!-- Add jQuery niceScroll -->
                <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.nicescroll/3.7.6/jquery.nicescroll.min.js"></script>

                <script>
                $(document).ready(function() {
                    // Initialize niceScroll with passive option
                    $("html").niceScroll({
                        cursorcolor: "#4e73df",
                        cursorwidth: "8px",
                        cursorborder: "none",
                        preventmultitouchscrolling: false,
                        touchbehavior: false
                    });

                    // Add passive event listeners
                    document.addEventListener('touchstart', function(){}, {passive: true});
                    document.addEventListener('touchmove', function(){}, {passive: true});
                    document.addEventListener('wheel', function(){}, {passive: true});
                    document.addEventListener('mousewheel', function(){}, {passive: true});

                    $('#evaluationForm').on('submit', function(e) {
                        e.preventDefault();
                        
                        // Disable submit button
                        $('#submitBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Submitting...');
                        
                        $.ajax({
                            url: 'submit_hedonic.php',
                            type: 'POST',
                            data: $(this).serialize(),
                            dataType: 'json',
                            success: function(response) {
                                if(response.success) {
                                    // Show success modal without backdrop
                                    $('body').addClass('modal-open');
                                    $('#successModal').modal({
                                        backdrop: false,
                                        keyboard: false
                                    });
                                    $('#successModal').modal('show');
                                    
                                    // Force modal to front
                                    $('#successModal').css('z-index', '99999');
                                    
                                    // Ensure buttons are clickable
                                    $('.modal button').css('z-index', '100000');
                                } else {
                                    alert('Error: ' + response.message);
                                    $('#submitBtn').prop('disabled', false).html('Submit');
                                }
                            },
                            error: function(xhr, status, error) {
                                console.log(xhr.responseText); // For debugging
                                alert('An error occurred while submitting the evaluation.');
                                $('#submitBtn').prop('disabled', false).html('Submit');
                            }
                        });
                    });

                    // Handle modal close
                    $('#successModal').on('hidden.bs.modal', function () {
                        $('body').removeClass('modal-open');
                        $('.modal-backdrop').remove();
                    });
                });
                </script>
            </body>
            </html>
            <?php
            exit();
        }
    }

    // If we get here, we need to create a new p_id
    // Check if we're at the 50 panelist limit
            if ($countRow['count'] >= 50) {
                echo "<script>alert('Evaluation is already full (50 panelists)'); window.location.href='public_dashboard.php';</script>";
                exit();
            }
            
            // Generate new p_id
    $maxQuery = $conn->prepare("SELECT MAX(p_id) as max_p_id FROM hedonic WHERE BINARY request_no = ?");
            $maxQuery->bind_param("s", $request_no);
            $maxQuery->execute();
            $maxResult = $maxQuery->get_result();
            $maxRow = $maxResult->fetch_assoc();
            $p_id = ($maxRow['max_p_id'] === null) ? 1 : $maxRow['max_p_id'] + 1;

    // Insert new p_id with temporary name
    $temp_name = "TEMP_" . $p_id;
            $insert_stmt = $conn->prepare("INSERT INTO hedonic (p_id, request_no, name) VALUES (?, ?, ?)");
    $insert_stmt->bind_param("iss", $p_id, $request_no, $temp_name);
    
            if ($insert_stmt->execute()) {
        // Store the p_id in session
        $_SESSION[$session_key] = $p_id;
        
        // Show the name input form with the assigned p_id
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
                                                    <input type="hidden" name="p_id" value="<?php echo $p_id; ?>">
                                                    <div class="form-group">
                                                        <label>(e.g., JOHN DOE SMITH)</label>
                                                        <input type="text" class="form-control code-name-input" name="panelist_name" required 
                                                               oninput="this.value = this.value.toUpperCase()" 
                                                               placeholder="Enter your initials or name">
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

            <!-- Add jQuery if not already included -->
            <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
            
            <!-- Add Bootstrap JS if not already included -->
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>

            <!-- Add Font Awesome -->
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

            <!-- Add jQuery niceScroll -->
            <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.nicescroll/3.7.6/jquery.nicescroll.min.js"></script>

            <script>
            $(document).ready(function() {
                // Initialize niceScroll with passive option
                $("html").niceScroll({
                    cursorcolor: "#4e73df",
                    cursorwidth: "8px",
                    cursorborder: "none",
                    preventmultitouchscrolling: false,
                    touchbehavior: false
                });

                // Add passive event listeners
                document.addEventListener('touchstart', function(){}, {passive: true});
                document.addEventListener('touchmove', function(){}, {passive: true});
                document.addEventListener('wheel', function(){}, {passive: true});
                document.addEventListener('mousewheel', function(){}, {passive: true});

                $('#evaluationForm').on('submit', function(e) {
                    e.preventDefault();
                    
                    // Disable submit button
                    $('#submitBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Submitting...');
                    
                    $.ajax({
                        url: 'submit_hedonic.php',
                        type: 'POST',
                        data: $(this).serialize(),
                        dataType: 'json',
                        success: function(response) {
                            if(response.success) {
                                // Show success modal without backdrop
                                $('body').addClass('modal-open');
                                $('#successModal').modal({
                                    backdrop: false,
                                    keyboard: false
                                });
                                $('#successModal').modal('show');
                                
                                // Force modal to front
                                $('#successModal').css('z-index', '99999');
                                
                                // Ensure buttons are clickable
                                $('.modal button').css('z-index', '100000');
                            } else {
                                alert('Error: ' + response.message);
                                $('#submitBtn').prop('disabled', false).html('Submit');
                            }
                        },
                        error: function(xhr, status, error) {
                            console.log(xhr.responseText); // For debugging
                            alert('An error occurred while submitting the evaluation.');
                            $('#submitBtn').prop('disabled', false).html('Submit');
                        }
                    });
                });

                // Handle modal close
                $('#successModal').on('hidden.bs.modal', function () {
                    $('body').removeClass('modal-open');
                    $('.modal-backdrop').remove();
                });
            });
            </script>
        </body>
        </html>
        <?php
                exit();
            } else {
                echo "<script>alert('Error registering panelist');</script>";
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
                                                    <label>(e.g., JOHN DOE SMITH)</label>
                                                    <input type="text" class="form-control code-name-input" name="panelist_name" required 
                                                           oninput="this.value = this.value.toUpperCase()" 
                                                           placeholder="Enter your initials or name">
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

        <!-- Add jQuery if not already included -->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        
        <!-- Add Bootstrap JS if not already included -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>

        <!-- Add Font Awesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

        <!-- Add jQuery niceScroll -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.nicescroll/3.7.6/jquery.nicescroll.min.js"></script>

        <script>
        $(document).ready(function() {
            // Initialize niceScroll with passive option
            $("html").niceScroll({
                cursorcolor: "#4e73df",
                cursorwidth: "8px",
                cursorborder: "none",
                preventmultitouchscrolling: false,
                touchbehavior: false
            });

            // Add passive event listeners
            document.addEventListener('touchstart', function(){}, {passive: true});
            document.addEventListener('touchmove', function(){}, {passive: true});
            document.addEventListener('wheel', function(){}, {passive: true});
            document.addEventListener('mousewheel', function(){}, {passive: true});

            $('#evaluationForm').on('submit', function(e) {
                e.preventDefault();
                
                // Disable submit button
                $('#submitBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Submitting...');
                
                $.ajax({
                    url: 'submit_hedonic.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if(response.success) {
                            // Show success modal without backdrop
                            $('body').addClass('modal-open');
                            $('#successModal').modal({
                                backdrop: false,
                                keyboard: false
                            });
                            $('#successModal').modal('show');
                            
                            // Force modal to front
                            $('#successModal').css('z-index', '99999');
                            
                            // Ensure buttons are clickable
                            $('.modal button').css('z-index', '100000');
                        } else {
                            alert('Error: ' + response.message);
                            $('#submitBtn').prop('disabled', false).html('Submit');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log(xhr.responseText); // For debugging
                        alert('An error occurred while submitting the evaluation.');
                        $('#submitBtn').prop('disabled', false).html('Submit');
                    }
                });
            });

            // Handle modal close
            $('#successModal').on('hidden.bs.modal', function () {
                $('body').removeClass('modal-open');
                $('.modal-backdrop').remove();
            });
        });
        </script>
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
$verify_stmt->bind_param("sis", $request_no, $p_id, $panelist_name);
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
    /* Modal styles */
    .modal-open {
        overflow: hidden;
    }
    .modal {
        position: fixed !important;
        top: 0 !important;
        right: 0 !important;
        bottom: 0 !important;
        left: 0 !important;
        z-index: 99999 !important;
        display: none;
        overflow: hidden;
        outline: 0;
    }
    .modal-backdrop {
        display: none !important;
    }
    .modal-dialog {
        position: relative !important;
        width: auto !important;
        margin: 1.75rem auto !important;
        max-width: 500px !important;
        z-index: 99999 !important;
        pointer-events: auto !important;
    }
    .modal-content {
        position: relative !important;
        display: flex !important;
        flex-direction: column !important;
        width: 100% !important;
        pointer-events: auto !important;
        background-color: #fff !important;
        border: 1px solid rgba(0,0,0,.2) !important;
        border-radius: .3rem !important;
        outline: 0 !important;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    }
    .modal-header, .modal-body, .modal-footer {
        pointer-events: auto !important;
    }
    .modal button {
        pointer-events: auto !important;
        z-index: 100000 !important;
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
                                $stmt->bind_param("s", $request_no);
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

                <form action="submit_hedonic.php" method="POST" id="evaluationForm">
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
                        <button type="submit" class="btn btn-primary" id="submitBtn">Submit</button>
                    </div>
                        </form>

                <!-- Success Modal -->
                <div class="modal fade" id="successModal" tabindex="-1" role="dialog" aria-labelledby="successModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="successModalLabel">Success!</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="z-index: 100000;">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body text-center">
                                <i class="fas fa-check-circle text-success" style="font-size: 48px;"></i>
                                <h4 class="mt-3">Evaluation Submitted Successfully!</h4>
                                <p>Thank you for using the Shelf Life Evaluation Portal</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-primary" data-dismiss="modal" onclick="window.location.href='public_dashboard.php'" style="z-index: 100000;">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </section>
        </div>
    </div>

    <?php include('includes/footer.php'); ?>
    <script src="assets/bundles/lib.vendor.bundle.js"></script>
    <script src="js/CodiePie.js"></script>
    <script src="js/scripts.js"></script>
    <script src="js/custom.js"></script>

    <!-- Add jQuery if not already included -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Add Bootstrap JS if not already included -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Add Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <!-- Add jQuery niceScroll -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.nicescroll/3.7.6/jquery.nicescroll.min.js"></script>

    <script>
    $(document).ready(function() {
        // Initialize niceScroll with passive option
        $("html").niceScroll({
            cursorcolor: "#4e73df",
            cursorwidth: "8px",
            cursorborder: "none",
            preventmultitouchscrolling: false,
            touchbehavior: false
        });

        $('#evaluationForm').on('submit', function(e) {
            e.preventDefault();
            
            // Disable submit button
            $('#submitBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Submitting...');
            
            $.ajax({
                url: 'submit_hedonic.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        // Show success modal without backdrop
                        $('body').addClass('modal-open');
                        $('#successModal').modal({
                            backdrop: false,
                            keyboard: false
                        });
                        $('#successModal').modal('show');
                        
                        // Force modal to front
                        $('#successModal').css('z-index', '99999');
                        
                        // Ensure buttons are clickable
                        $('.modal button').css('z-index', '100000');
                    } else {
                        alert('Error: ' + response.message);
                        $('#submitBtn').prop('disabled', false).html('Submit');
                    }
                },
                error: function(xhr, status, error) {
                    console.log(xhr.responseText); // For debugging
                    alert('An error occurred while submitting the evaluation.');
                    $('#submitBtn').prop('disabled', false).html('Submit');
                }
            });
        });

        // Handle modal close
        $('#successModal').on('hidden.bs.modal', function () {
            $('body').removeClass('modal-open');
            $('.modal-backdrop').remove();
        });
    });
    </script>
</body>
</html>
