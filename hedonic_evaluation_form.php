<!DOCTYPE html>
<html lang="en">

<head>
    <?php include('includes/header.php');
    
    // Get p_id and request_no from URL
    $p_id = isset($_GET['p_id']) ? (int)$_GET['p_id'] : null;
    $request_no = isset($_GET['request_no']) ? (int)$_GET['request_no'] : null;

    if (!$request_no) {
        echo "<h4 style='color:red;'>Missing request number in the URL.</h4>";
        exit;
    }

    // Include database connection
    include('db.php'); // Include database connection here

    // Fetch the highest p_id for the given request_no
    $stmt = $conn->prepare("SELECT MAX(p_id) AS max_p_id FROM hedonic WHERE request_no = ?");
    $stmt->bind_param("i", $request_no);
    $stmt->execute();
    $stmt->bind_result($max_p_id);
    $stmt->fetch();
    $stmt->close();

    // Determine the new p_id
    if ($max_p_id === null) {
        $p_id = 1; // Start from 1 if no p_id exists
    } elseif ($max_p_id < 50) {
        $p_id = $max_p_id + 1; // Increment the highest p_id
    } else {
        echo "<h4 style='color:red;'>Maximum number of panelists reached (50).</h4>";
        exit; // Stop execution if the limit is reached
    }

    // Insert the new p_id and request_no into the hedonic table
    $insert_stmt = $conn->prepare("INSERT INTO hedonic (p_id, request_no) VALUES (?, ?)");
    $insert_stmt->bind_param("ii", $p_id, $request_no);
    if (!$insert_stmt->execute()) {
        echo "<h4 style='color:red;'>Error inserting panelist ID: " . $insert_stmt->error . "</h4>";
        exit;
    }
    $insert_stmt->close();

    ?>
</head>

<body class="layout-4">
    <div id="app">
        <div class="main-wrapper main-wrapper-1">
            <?php
            include('includes/topnav.php');
            include('includes/sidebar.php');
            ?>
            <!-- Start app main Content -->
            <div class="main-content">
                <br><br>
                <div class="section-header text-center">
                    <h4>Shelf Life Evaluation Laboratory</h4> <br>
                    <h6>Score Sheet - Acceptability Test Using 9-Point Hedonic Scale</h6>
                </div>
                <div class="section-body">
                    <div class="row">
                        <div class="col-12 col-md-12 col-lg-12">
                            <div class="card">
                                <?php
                                // Fetch Sample Code from evaluation_request table
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
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <form action="submit_hedonic.php" method="POST">
                    <!-- Add inside <form> -->
                <input type="hidden" name="p_id" value="<?php echo $p_id; ?>">
                <input type="hidden" name="request_no" value="<?php echo $request_no; ?>">
                <input type="hidden" name="date_submitted" value="<?php echo date('Y-m-d'); ?>">

                <div class="form-group">
                    <input type="text" class="form-control" id="panelistName" name="name" placeholder="Name of Panelist" required> <br>
                    <input type="text" class="form-control" id="agencySchool" name="institution_name" placeholder="Name of Agency/School" required> <br>
                </div>

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
                    <div class="form-group">
                        <label for="exampleFormControlTextarea1">Remarks (Optional)</label>
                        <textarea class="form-control" id="exampleFormControlTextarea1" name="remarks" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group text-center">
                        <button type="reset" class="btn btn-secondary">Reset</button>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </div>
                </div>
                </form>
                <script>
    window.addEventListener('DOMContentLoaded', function () {
        // Only allow one checkbox to be checked
        function onlyOne(checkbox) {
            const checkboxes = document.getElementsByName('rating[]');
            checkboxes.forEach((item) => {
                if (item !== checkbox) item.checked = false;
            });
        }

        // Add the onlyOne function to window so it's usable inline
        window.onlyOne = onlyOne;

        // Form validation
        const form = document.querySelector('form');
        form.addEventListener('submit', function (e) {
            const ratingChecked = document.querySelectorAll('input[name="rating[]"]:checked');
            if (ratingChecked.length === 0) {
                alert('Please select a rating before submitting.');
                e.preventDefault();
            }
        });
    });
</script>

            </div>

            <?php
            include('includes/footer.php');
            ?>
        </div>
    </div>

    <!-- General JS Scripts -->
    <script src="assets/bundles/lib.vendor.bundle.js"></script>
    <script src="js/CodiePie.js"></script>
    <script src="js/scripts.js"></script>
    <script src="js/custom.js"></script>
</body>

</html>
