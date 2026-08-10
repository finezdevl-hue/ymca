$(document).ready(function () {
  // This function is moved outside of $(document).ready()
});

// function to upload the pdf starts 
function uploadButtonClicked(event) {
  event.preventDefault();

  // Create FormData from the file input
  let formData = new FormData();
  let fileInput = $('#pdfFile')[0].files[0];

  if (fileInput) {
    formData.append('pdfFile', fileInput);

    $.ajax({
      url: '../pdf_upload/upload.php',
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function (response) {
        $('#hdn_pdf_name').val(response);
      },
      error: function () {
        $('#responseMessage').text('Error uploading file.');
      }
    });
  } else {
    $('#responseMessage').text('Please select a PDF file.');
  }
}
// function to upload the pdf ends