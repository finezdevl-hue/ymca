<?php
session_start();
?>
<!DOCTYPE html>
<html>

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Bulletin</title>

    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../font-awesome/css/font-awesome.css" rel="stylesheet">

    <link href="../css/animate.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <link href="../css/plugins/sweetalert/sweetalert.css" rel="stylesheet">
    <script src="../js/plugins/sweetalert/sweetalert.min.js"></script>
    <script src="../app_js/sweetalert-finez.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cropperjs@1.5.13/dist/cropper.min.css">
    <link href="../image_upload/bulletin/upload.css" rel="stylesheet">
    <script>
        $(document).ready(function() {          
            loadData(1); // Function to load data for a specific page       
        });  
        
        // function to load the bulletin starts
        function loadData(page) {
            $('#hdn_current_page').val(page); //used for Status Update function
            console.log("Loading data for page:", page);
            $.ajax({               
               type: "POST",
               url: "api/bulletin.php",
               data: {
                action: 'load_data',
                page: page, 
                // val:$('#txt_search').val()
               },
                success: function(data) {

                    var obj = jQuery.parseJSON(data);
                    console.log(obj);
                                       
                    var totalrows = obj[0].total_rows;
                    var htm="";
                    htm=htm+"<div class='row'>";
                    for (var i = 0; i < obj[1].length; i++) {
                        // var j= i+1;

                        htm=htm+"<div class='col-lg-3'>";
                        htm=htm+"<div class='contact-box center-version'>";
                        htm=htm+"<a href='../pdf_upload/uploads/"+obj[1][i].pdf_name+"' target='_blank'>";
                        htm=htm+"<img alt='image'src='../image_upload/bulletin/uploads/"+obj[1][i].img+"' style='width: 200px; height: 267px;'>";
                        htm=htm+"<p class='m-b-xs'><strong>"+obj[1][i].title+"</strong></p>";
                        htm=htm+"<div class='font-bold'></div>";
                        htm=htm+"</a>";
                        htm=htm+"<div class='contact-box-footer'>";
                        htm=htm+"<div class='m-t-xs btn-group'>";
                        htm=htm+"<a onclick='deletePdf("+obj[1][i].id+");'class='btn btn-danger btn-xs'><i class='fa fa-trash'></i> Delete </a>";
                        htm=htm+"<a href='../pdf_upload/uploads/"+obj[1][i].pdf_name+"' target='_blank' class='btn btn-xs btn-white'><i class='fa fa-eye' ></i>Bulletin</a>";
                        htm=htm+"</div>";
                        htm=htm+"</div>";
                        htm=htm+"</div>";
                        htm=htm+"</div>"; 
                            
                    }                
                    htm=htm+ "</div>";

                    $('#table_client').html(htm);
                    var htmpage= paginate(totalrows,page);
                    $('#table_client').append(htmpage);
                },
                error: function(xhr, status, error) {
                   console.log('AJAX error: ', status, error);
                }
            });
            loadMenu();
        }
        // function to load the buletin ends

    </script>

    <script>


        //function for popup to add new bulletin starts
        function bulletinModel(){
            $('#bulletinModel').modal('show');
        }
        //function for popup to add new bulletin ends

        //function for close the popup for add new bulletin starts
        function closebulletinModel(){
            $('#pdf_form')[0].reset();
            $("hdn_pdf_name").val(0);
            $("hdn_file_upload").val(0);
            $('#bulletinModel').modal('toggle');
        }
        // function for close the popup for add new bulletin ends

        //function to save new bulletin starts
        function saveBulletin() {
            swal({
                title: "Are you sure?",
                text: "Do you want to save this data!",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "Yes,Save!",
                cancelButtonText: "Cancel",
                closeOnConfirm: false,
                closeOnCancel: true
            },
            function (isConfirm) {
			if (isConfirm){
                var data = {
                    action: 'save_bulletin',
                    title: $('#txt_title').val(),
                    date: $('#date').val(),  
                    pdf: $('#hdn_pdf_name').val(),
                    img: $('#hdn_file_upload').val(),
                };
                // AJAX call
                $.ajax({
                    type: "POST",
                    url: "api/bulletin.php",
                    data: data,
                    success: function(response) {
                        console.log('saved:', response);                    
                        closebulletinModel();
                        alertsuccess('Saved Sucessfully');
                        loadData($('#hdn_current_page').val());
                    },
                    error: function (xhr, status){
        
                        var msgObj = JSON.parse(xhr.responseText);
                        alerterror(msgObj, xhr);
                        $('#pdf_form')[0].reset();
                        $("hdn_pdf_name").val(0);
                        $("hdn_file_upload").val(0);
                    
                    }
                });
            }
		    });
        }
        // function to save new bulletin ends
        
        //function to delete the bulletin starts
        function deletePdf(id) {
            $("#hdn_id").val(id);
            deleteRow();
        }
        
        function deleteRow() {
            swal({
                title: "Are you sure?",
                text: "Do you want to save this data!",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "Yes,delete!",
                cancelButtonText: "Cancel",
                closeOnConfirm: false,
                closeOnCancel: true
            },
               function (isConfirm) {
			if (isConfirm){
                var data = {
                action: 'delete_pdf',
                id: $("#hdn_id").val(),               
            };
            // AJAX call
            $.ajax({
                type: "POST",
                url: "api/bulletin.php",
                data: data,
                success: function(response) {
                    // console.log('deleted:', response);
                    alertwarning('Deleted');
                    $("#hdn_id").val(0);
                    loadData($('#hdn_current_page').val());
                    
                },
                error: function(xhr, status, error) {
                    console.log('AJAX error:', status, error);
                    $("#hdn_id").val(0);
                }
            });
                
                    
            }
		});   
        }
        //function to delete the buletin ends
    </script>
    <script src="../app_pagination/pagination.js"></script>
    <script src="../app_menu/menu.js"></script>
</head>

<body>
    <input type="hidden" id="hdn_current_page"  value="0">
    <input type="hidden" id="hdn_id"  value="0">
    <input type="hidden" id="hdn_pdf_name" name="hdn_pdf_name">
    <input type="hidden" id="hdn_file_upload"  value="0">

    <div id="wrapper">

        <!-- navigation start -->
        <nav class="navbar-default navbar-static-side" role="navigation">
            <div class="dropdown profile-element">
                <center>
                    <span><img alt="image" class="img-circle" src="../img/customer.png" style="padding-top: 20px;"/></span>
                    <span class="clear"> <span class="block m-t-xs"> <strong class="font-bold"><?php echo $_SESSION['name']; ?></strong>
                </center>
            </div>
            <div class="sidebar-collapse" id="divMenuContainer">
                <!-- menu injected via ajax -->
            </div>
        </nav>
        <!-- navigation end -->
        <div id="page-wrapper" class="gray-bg">
            <!-- header start -->
            <div class="row border-bottom">
                <nav class="navbar navbar-static-top" role="navigation" style="margin-bottom: 0">
                    <div class="navbar-header">
                        <a class="navbar-minimalize minimalize-styl-2 btn btn-primary " href="#"><i class="fa fa-bars"></i> </a>
                    </div>
                    <ul class="nav navbar-top-links navbar-right">     
                        <form action="../app_login_manager/logout.php" method="post"></form>        
                            <li>
                                <a href="../app_login_manager/logout.php" style="color: #147ad1";>
                                    <i class="fa fa-sign-out"></i> Log out
                                </a>
                            </li>
                        </form>
                    </ul>

                </nav>
            </div>
            <!-- header end -->
            <!-- search bar starts -->
            <div class="row wrapper border-bottom white-bg page-heading">
                <div class="col-sm-8">
                    <h2>Bulletin</h2>
                </div>
                <div class="col-sm-4">
                    <div class="title-action">
                        <div class="ibox-tools">
                             <button type="button" class="btn btn-primary btn-xs" onclick="bulletinModel()">Add date</button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- search bar ends -->
            
            <div class="wrapper wrapper-content animated fadeInRight" id="table_client">
                <!-- data injected Dynamically via ajax -->
            </div>
        </div>
        <!-- popup modal for update fee details of a member starts -->
        <div class="modal inmodal" id="bulletinModel" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content animated bounceInRight">
                    <form method="POST" id="pdf_form">
                        <div class="modal-body">
                        <div class="form-group"><label>Title</label><input type="text" id="txt_title" name="title" placeholder="Enter The Title"  class="form-control" oninput="titleValidation()"></div>
                            <div class="form-group"><label>Date</label><input type="date" id="date" name="from_date" placeholder=""  class="form-control"></div>
                                <div id="uploadContainer" class="upload-section">
                                    <input type="file" id="pdfFile" name="pdfFile" accept="application/pdf" required />
                                    <input id="uploadButton" type="button" value="Upload PDF" onclick="uploadButtonClicked(event);" />
                                    <div id="responseMessage" class="upload-section"></div>
                                </div>
                        </div>
                        <div class="modal-footer">
                            <input type="file" id="photoInput" onchange="photoInputChange(event);" accept="image/*">
                            <button type="button" class="btn btn-white" onclick="closebulletinModel();">Close</button>
                            <button type="button" class="btn btn-primary" onclick="saveBulletin();">Save</button>

                            <!-- popup for crop the image start -->
                            <div id="cropModal">
                                <div id="modalContent">
                                    <input type="button" id="closeModal" onclick="closeModalClicked();" class="closeModal" value="&times;">
                                    <!-- <button id="closeModal" onclick="closeModalClicked();" class="closeModal">&times;</button> -->
                                    <img id="modalPreview" alt="Preview">
                                    <input type="button" id="cropButton" onclick="cropButtonClicked();" value="Crop and Upload">
                                    <!-- <button id="cropButton" onclick="cropButtonClicked();">Crop and Upload</button> -->
                                </div>
                            </div>
                            <!-- popup for crop the image ends -->

                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- popup modal for update fee details of a member ends -->
    </div>
       

    <!-- Mainly scripts -->
    <script src="../js/jquery-3.1.1.min.js"></script>
    <script src="../js/bootstrap.min.js"></script>
    <script src="../js/plugins/metisMenu/jquery.metisMenu.js"></script>
    <script src="../js/plugins/slimscroll/jquery.slimscroll.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/cropperjs@1.5.13/dist/cropper.min.js"></script>

    <!-- Custom and plugin javascript -->
    <script src="../js/inspinia.js"></script>
    <script src="../js/plugins/pace/pace.min.js"></script>
    <script src="../image_upload/bulletin/image_upload.js"></script>
    <script src="../pdf_upload/upload_pdf.js"></script>
    <script src="../app_js/validation.js"></script>


</body>

</html>
