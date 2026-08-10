<?php
session_start();
include_once '../app_common/db_connect.php';
include_once '../app_common/auth_helper.php';

$login_id = (int)($_SESSION['login_id'] ?? 0);
if (empty($login_id)) {
    header("Location: ../app_login_manager/logout.php");
    exit();
}
if (isNormalMember($login_id)) {
    header("Location: member_dashboard.php");
    exit();
}

if(isset($_POST['client_id'])){
    echo $_POST['client_id'];
    $_SESSION['client_id']=$_POST['client_id'];
}
?>
<!DOCTYPE html>
<html>

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Add Members</title>

    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../font-awesome/css/font-awesome.css" rel="stylesheet">

    <link href="../css/animate.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <link href="../css/plugins/sweetalert/sweetalert.css" rel="stylesheet">
    <script src="../js/plugins/sweetalert/sweetalert.min.js"></script>
    <script src="../app_js/sweetalert-finez.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cropperjs@1.5.13/dist/cropper.min.css">
    <link href="../image_upload/upload.css" rel="stylesheet">
  
    <!-- <script>
        $(document).ready(function() {          
            loadClients(1); // Function to load data for a specific page       
        });  
        
        // search box
        function searchClients(){
            if ($('#txt_search').val().trim()=='') {
                alert('Please enter a value.');
                return;
            }       
            loadClients(1);
            
        }

        function loadClients(page) {
            $('#hdn_current_page').val(page); //used for Status Update function
            console.log("Loading data for page:", page);
            $.ajax({               
               type: "POST",
               url: "api/members.php",
               data: {
               action: 'load_client_data',
               page: page, 
               val:$('#txt_search').val()
               },
                success: function(data) {                   
                    var obj = jQuery.parseJSON(data);
                    var totalrows = obj[0].total_rows;
                    var htm="";
                    htm=htm+"<div class='row'>";

                    for (var i = 0; i < obj[1].length; i++) {

                        htm=htm+"<div class='col-lg-4'>";
                        htm=htm+"<div class='contact-box'>";
                        htm=htm+"<a href='profile.html'>";
                        htm=htm+"<div class='col-sm-4'>";
                        htm=htm+"<div class='text-center'>";
                        htm=htm+"<img alt='image' class='img-circle m-t-xs img-responsive' src='../img/customer.png'>";
                        htm=htm+"<div class='m-t-xs font-bold'>"+obj[1][i].first_name+"</div>";
                        htm=htm+"</div>";
                        htm=htm+"</div>";
                        htm=htm+"<div class='col-sm-8'>";
                        htm=htm+"<p>"+obj[1][i].father_name+"</p>";
                        htm=htm+"<p>"+obj[1][i].mother_name+"</p>";
                        htm=htm+"<p>"+obj[1][i].blood_group+"</p>";
                        htm=htm+"<p>"+obj[1][i].phone+"</p>";
                        htm=htm+"<p>"+obj[1][i].email+"</p>";
                        htm=htm+"</div>";
                        htm=htm+"<div class='clearfix'></div>";
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
    </script> -->

    <script>
        // function to save client
        function saveClient() { 
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
                    action: 'save_client',

                    first_name: $('#txt_first_name').val(),
                    middle_name: $('#txt_middle_name').val(),
                    last_name: $('#txt_last_name').val(),

                    father_name: $('#txt_father_name').val(),
                    mother_name: $('#txt_mother_name').val(),

                    spouse1: $('#txt_spouse1').val(),
                    spouse2: $('#txt_spouse2').val(),
                    spouse3: $('#txt_spouse3').val(),

                    blood_group: $('#txt_blood_group').val(),
                    phone: $('#txt_phone').val(),
                    email: $('#txt_email').val(),

                    p_street: $('#txt_p_street').val(),
                    p_city: $('#txt_p_city').val(),
                    p_pincode: $('#txt_p_pincode').val(),
                    p_country: $('#txt_p_country').val(),

                    id: $("#hdn_id").val(),
                    img: $("#hdn_file_upload").val(),

                    };
                    $.ajax({
                    type: "POST",
                    url: "api/members.php",
                    data: data,
                    success: function(response) {
                        console.log('saved:', response);
                        
                        alertsuccess('Saved Sucessfully');
                        // loadData($('#hdn_current_page').val());
                    },
                    error: function (xhr, status){
                    var msgObj = JSON.parse(xhr.responseText);
                    alerterror(msgObj, xhr);
                    $('#client_form')[0].reset();
                    $("#photoInput").val('');
                    }
                });
            }
		    }); 
        }
    </script>
    <script src="../app_pagination/pagination.js"></script>
    <script src="../app_menu/menu.js"></script>

    <style>
        .ibox-content {
            min-height: 150px;
        }
        .row{
            padding-top: 10px;
            padding-bottom: 10px;
        }
    </style>
</head>

<body>
<!-- hidden values -->
<input type="hidden" id="hdn_current_page"  value="0">
<input type="hidden" id="hdn_id"  value="0">
<input type="hidden" id="hdn_file_upload" name="image" value="0">




    <div id="wrapper">
        <!-- navigation starts -->
        <nav class="navbar-default navbar-static-side" role="navigation">
            <div class="sidebar-collapse">
                <ul class="nav metismenu" id="side-menu">
                    
                    <div class="nav metismenu"  id="menu">
                        <!-- menu injected dynamically via ajax -->
                    </div>
                   
                </ul>
            </div>
        </nav>
        <!-- navigation starts -->
        <div id="page-wrapper" class="gray-bg">
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
            <!-- search bar started -->
            <div class="row wrapper border-bottom white-bg page-heading">
                <div class="col-sm-8">
                    <div class="search-form">
                        <h2>Clients</h2>
                            <form action="index.html" method="get">
                                <div class="input-group">
                                    <input type="text" placeholder="Search" id="txt_search" name="search" class="form-control">
                                    <div class="input-group-btn">
                                        <button class="btn btn-white" onclick="searchClients()" type="button">Search</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                </div>
                <!-- search bar end -->

                <!-- popup for add client -->
                
                <!-- popup for add client end-->

                <div class="wrapper wrapper-content animated fadeInRight">
                  
                            
                             
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="ibox float-e-margins">
                                        
                                        <div class="col-sm-10">
                                            <div class="row">
                                                <div class="col-md-4"><input type="text" placeholder="First Name" name="first_name" id="txt_first_name" class="form-control"></div>
                                                <div class="col-md-4"><input type="text" placeholder="Middle Name" name="middle_name" id="txt_middle_name" class="form-control"></div>
                                                <div class="col-md-4"><input type="text" placeholder="Last Name" name="last_name" id="txt_last_name" class="form-control"></div>
                                            </div>
                                        </div>
                                
                                        <div class="col-sm-10">
                                            <div class="row">
                                                <div class="col-md-4"><input type="text" placeholder="Father Name" name="father_name" id="txt_father_name" class="form-control"></div>
                                                <div class="col-md-4"><input type="text" placeholder="Mother Name" name="mother_name" id="txt_mother_name" class="form-control"></div>
                                            </div>
                                        </div>

                                        <div class="col-sm-10">
                                            <div class="row">
                                                <div class="col-md-4"><input type="text" placeholder="Spouse 1" name="spouse1" id="txt_spouse1" class="form-control"></div>
                                                <div class="col-md-4"><input type="text" placeholder="Spouse 2" name="spouse2" id="txt_spouse2" class="form-control"></div>
                                                <div class="col-md-4"><input type="text" placeholder="Spouse 3" name="spouse3" id="txt_spouse3" class="form-control"></div>
                                            </div>
                                        </div>

                                        <div class="col-sm-10">
                                            <div class="row">
                                                <div class="col-md-4"><input type="text" placeholder="Blood Group" name="blood_group" id="txt_blood_group" class="form-control"></div>
                                                <div class="col-md-4"><input type="text" placeholder="Phone" name="phone" id="txt_phone" class="form-control"></div>
                                                <div class="col-md-4"><input type="text" placeholder="Email" name="email" id="txt_email" class="form-control"></div>
                                            </div>
                                        </div>

                                        <div class="col-sm-10">
                                            <div class="row">
                                                <div class="col-md-3"><input type="text" placeholder="Street" name="p_street" id="txt_p_street" class="form-control"></div>
                                                <div class="col-md-3"><input type="text" placeholder="City" name="p_city" id="txt_p_city" class="form-control"></div>
                                                <div class="col-md-3"><input type="text" placeholder="Pincode" name="p_pincode" id="txt_p_pincode" class="form-control"></div>
                                                <div class="col-md-3"><input type="text" placeholder="Country" name="p_country" id="txt_p_country" class="form-control"></div>

                                            </div>
                                        </div>

                                        <!-- <div class="col-sm-10">
                                            <div class="row">
                                                <div class="col-md-4"><input type="text" placeholder="image" name="img" id="txt_img" class="form-control"></div>
                                            </div>
                                        </div> -->

                                    </div>
                                </div>
                            </div>
                        <div class="modal-footer">
                       
                        <input type="file" id="photoInput" onchange="photoInputChange();" accept="image/*">

                        <!-- popup for crop the image start -->
                        <div id="cropModal">
                            <div id="modalContent">
                                <button id="closeModal" onclick="closeModalClicked();" class="closeModal">&times;</button>
                                <img id="modalPreview" alt="Preview">
                                <button id="cropButton" onclick="cropButtonClicked();">Crop and Upload</button>
                            </div>
                        </div>
                        <!-- popup for crop the image end -->
                            <button type="button" class="btn btn-primary" onclick="saveClient();">Save</button>
                        </div>
                    
                </div>

               
            </div>
        </div>
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
    <script src="../image_upload/image_upload.js"></script>
    

</body>

</html>
