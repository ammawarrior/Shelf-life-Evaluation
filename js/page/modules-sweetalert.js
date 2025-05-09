"use strict";

// When the submit button is clicked
$(".swal-button").click(function () {
  // Collect data from the form fields (you might adjust the selector to match your form)
  const requestNo = $("input[name='request_no']").val();
  const labCodeNo = $("input[name='lab_code_no']").val();
  const sampleCodeNo = $("input[name='sample_code_no']").val();
  const dateOfComputation = $("input[name='date_of_computation']").val();

  // Ensure all required fields are filled out
  if (!requestNo || !labCodeNo || !sampleCodeNo) {
    swal("Error", "All fields are required", "error");
    return;
  }

  // Show confirmation alert
  swal({
    title: "Confirm Submission",
    text: "Are you sure you want to submit this evaluation request?",
    icon: "question",
    showCancelButton: true,
    confirmButtonText: "Yes, submit it!",
    cancelButtonText: "Cancel",
  }).then((result) => {
    if (result.isConfirmed) {
      // Show processing alert
      swal({
        title: "Processing...",
        text: "Please wait while we save your data.",
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
          swal.showLoading();
        }
      });

      // Prepare the data for submission
      $.ajax({
        url: "submit_evaluation.php", // Update to your backend PHP script
        type: "POST",
        data: {
          request_no: requestNo,
          lab_code_no: labCodeNo,
          sample_code_no: sampleCodeNo,
          date_of_computation: dateOfComputation
        },
        dataType: "json",
        success: function (response) {
          if (response.status === "success") {
            swal("Success", "Evaluation request submitted successfully!", "success").then(() => {
              location.reload(); // Optionally reload the page to show updated data
            });
          } else {
            swal("Error", response.message, "error");
          }
        },
        error: function () {
          swal("Error", "Failed to submit. Please try again later.", "error");
        }
      });
    }
  });
});
