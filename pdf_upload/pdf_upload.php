<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Upload PDF using AJAX</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      padding: 20px;
    }
    .container {
      max-width: 400px;
      margin: 0 auto;
    }
    .upload-section {
      margin-top: 20px;
    }
  </style>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
  <div class="container">
    <h1>Upload a PDF File</h1>
    <div id="uploadContainer" class="upload-section">
      <input type="file" id="pdfFile" name="pdfFile" accept="application/pdf" required />
      <input id="uploadButton" type="button" value="Upload PDF" onclick="uploadButtonClicked(event);" />
      <input type="hidden" id="hdn_pdf_name" name="hdn_pdf_name" />
    </div>
    <div id="responseMessage" class="upload-section"></div>
  </div>

  <script src="upload_pdf.js"></script>

</body>
</html>
