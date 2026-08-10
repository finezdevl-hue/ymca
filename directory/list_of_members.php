<?php
session_start();

if(isset($_POST['id'])){
    $_SESSION['id']=$_POST['id'];
}
?>
<!DOCTYPE html>
<html>

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Members</title>

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
    <link href="../image_upload/members/upload.css" rel="stylesheet">
    
  
    <script>
        $(document).ready(function() {          
            loadData(1); // Function to load data for a specific page       
        });  
        
        // search box for members start
        function searchMembers(){
            if ($('#txt_search').val().trim()=='') {
                
                alertwarning('Please enter a value.');
                return;
            }       
            loadData(1);
        }
        //search box for members end

        // function to load all the members details start
        function loadData(page) {
            $('#hdn_current_page').val(page); //used for Status Update function
            console.log("Loading data for page:", page);
            $.ajax({               
               type: "POST",
               url: "api/list_of_members.php",
               data: {
                action: 'load_members_data',
                page: page, 
                val:$('#txt_search').val(),
               },
                success: function(data) {  
                     
                    var obj = jQuery.parseJSON(data);
                    var totalrows = obj[0].total_rows;
                    var htm="";

                     htm=htm+ "<div class='col-lg-12'>";
                    htm=htm+ "<div class='ibox float-e-margins'>";
                    htm=htm+ "<div class='ibox-title'>";
                    htm=htm+ "<div class='ibox-tools'></div>";
                    htm=htm+ "</div>";
                    htm=htm+ "<div class='ibox-content'>";
                    htm=htm+ "<div class='table-responsive'>";
                    htm=htm+ "<table class='table table-striped'>";
                    htm=htm+ "<thead>";
                    htm=htm+ "<tr>";
                    htm=htm+ "<th>No</th>";
                    htm=htm+ "<th>Name</th>";
                    htm=htm+ "<th>Phone</th>";
                    htm=htm+ "<th>Email</th>";
                    htm=htm+ "<th>Action</th>";
                    htm=htm+ "</tr>";
                    htm=htm+ "</thead>";
                    htm=htm+ "<tbody>";
                    for (var i = 0; i < obj[1].length; i++) {
                       
                        var j= i+1;
                        var slno=((page-1)*8)+j
                        
                        htm=htm+ "<tr>";
                        htm=htm+ "<td>" + slno+ "</td>";
                        htm=htm+ "<td>"+obj[1][i].first_name+" " + obj[1][i].middle_name + " " + obj[1][i].last_name + "</td>";
                        htm=htm+ "<td><a href='tel:"+obj[1][i].phone+"' style='user-select: none; -webkit-user-select: none; -ms-user-select: none;' onmousedown='return false;' onselectstart='return false;' oncopy='return false;'oncontextmenu='return false;'>"+obj[1][i].phone+"</a></td>";
                        htm=htm+ "<td><a href='mailto:"+obj[1][i].email+ "' >"+obj[1][i].email+ "</a></td>";
                        htm=htm+ "<td><button type='button' class='fa fa-edit btn btn-primary btn-xs' onclick='fetchmemberDetails(" + obj[1][i].id + ",\"" + obj[1][i].first_name + "\",\"" + obj[1][i].middle_name + "\",\"" + obj[1][i].last_name + "\",\"" + obj[1][i].father_name + "\",\"" + obj[1][i].mother_name + "\",\"" + obj[1][i].dob + "\",\"" + obj[1][i].blood_group + "\",\"" + obj[1][i].email + "\",\"" + obj[1][i].phone + "\",\"" + obj[1][i].whtsapp + "\",\"" + obj[1][i].p_street + "\",\"" + obj[1][i].p_city + "\"," + obj[1][i].p_pincode + ",\"" + obj[1][i].p_country + "\",\"" + obj[1][i].img + "\")'>Edit</button></td>";
                        htm=htm+ "</tr>";
                    }                
                    
                    htm=htm+ "</tbody>";
                    htm=htm+ "</table>";
                    htm=htm+ "</div>";
                    htm=htm+ "</div>";
                    htm=htm+ "</div>";
                    htm=htm+ "</div>";
                    htm=htm+ "</div>";
                    $('#table_members').html(htm);
                    var htmpage= paginate(totalrows,page);
                    $('#table_members').append(htmpage);
                },

                error: function(xhr, status, error) {
                    console.log('AJAX error: ', status, error);
                }
            });
            load_blood_groups();
            loadMenu();
        }
        // function to load all the members details end
    </script>

    <script>
        
        // function to create popup for groups start
        function load_groups(){
            $.ajax({
   
                type: "POST",
                url: "api/list_of_members.php",
                data: {
                action: 'load_groups',
                },
                success: function(data) { 
                    $('#group_container').html(data);
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error: ', status, error);
                }
            });
        }
        // function to create popup for groups end

        //functio to create popup for login to the memebrs start
        function popuCreateLogin(name,email) { 
            var cleanEmail = (email && email !== 'null' && email !== 'undefined') ? $.trim(email) : '';
            if (!cleanEmail) {
                alertwarning("Please add an email address in the member's profile first before creating a login.");
                return;
            }
            $("#name").val(name);
            $("#email").val(cleanEmail);
            $('#loginModal').modal('show');
        }
        //functio to create popup for login to the members end

        // function to save member login details strat
        function saveLogin() { 
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
                    if ($.trim($('#email').val()) == "") {
                        alertwarning("Please add an email address in the member's profile first before creating a login.");
                        return;
                    }
                    if ($('#password').val() !== $('#confirmpassword').val()) {
                        alertwarning('Passwords do not match!')
                    }
                    else{
                        var data = {
                        action: 'save_login',
                        name: $('#name').val(),
                        email: $('#email').val(),
                        password: $('#password').val(),
                        confirmPassword: $('#confirmpassword').val(),
                    };
                    $.ajax({
                        type: "POST",
                        url: "api/list_of_members.php",
                        data: data,
                        success: function(response) {
                            console.log('saved:', response);
                            closePopupLogin();
                            alertsuccess('Saved Sucessfully');
                        },
                        error: function (xhr, status){
                            var msgObj = JSON.parse(xhr.responseText);
                            alerterror(msgObj, xhr);
                            $('#login_form')[0].reset();
                        }
                });
                    }
                    
            }
		    }); 
        }
        // function to save member login details end

        // function to close the popup for create login strat
        function closePopupLogin(){
            $('#login_form')[0].reset();
            $('#loginModal').modal('toggle');
        }
        // function to close the popup for create login end

        //function for create popup for add member into group start
        let member_id = null; // To store the current row ID

        function showgroupsModal(id) {

            load_groups();
            fetchGrouopDetails(id);
            member_id = id;
            document.getElementById('groupsModal').style.display = 'block';
        }
         //function for create popup for add member into group end

        //function to fetch  group details of a member start
        function fetchGrouopDetails(id){
            $.ajax({               
                type: "POST",
                url: "api/list_of_members.php",
                data: {
                action: 'fetch_group_details',
                id:id, 
                },
                success: function(data) {  
                    var obj = jQuery.parseJSON(data);
                    for (var i = 0; i < obj.length; i++) {
                        document.getElementById(obj[i].group_id).checked = true; //Verified
                    }
                },
                error: function(xhr, status, error) {
                    console.log('AJAX error: ', status, error);
                }
            });
            
        }
        //function to fetch group details of a member end

        //close the popup for groups start
        function closegroupsModal() {
            document.getElementById('groupsModal').style.display = 'none';
            $("input[name='group']:checkbox").prop('checked',false);

        }
        //close the popup for group end

        //function to add members to groups start
        function addMemberToGroups() {
               
            var group_ids = [];
                $("input[type=checkbox]:checked").each(function () {
                    group_ids.push(this.value);
                });
    
            
            load_overlay();
            $.ajax({               
                type: "POST",
                url: "api/list_of_members.php",
                data: {
                action: 'add_member_to_groups',
                id: member_id, 
                group_ids: group_ids
                },
                success: function(data) { 
                close_overlay(); 
                console.log ('sucess');                
                closegroupsModal();
                loadData($('#hdn_current_page').val());                
                },
                error: function(xhr, status, error) {
                    console.log('AJAX error: ', status, error);
                }
            });     
        }
        //function to add memmbers to groups end

        // function to redirect the page to show members details start
        function navigateSeeDetails(id){ 
            $.post("member_details.php", { 'id': id })
            .done(function(response) {
                window.location.href = "member_details.php";
            })
        }
        //function to redirect the page to show member details end

        // function to fetch member details and popup for edit details strat
        function fetchmemberDetails(id,first_name,middle_name,last_name,father_name,mother_name,dob,blood_group,email,phone,whtsapp,p_street,p_city,p_pincode,p_country,img){
            $("#txt_first_name").val(first_name);
            $("#txt_middle_name").val(middle_name);
            $("#txt_last_name").val(last_name);
                     
            $("#txt_father_name").val(father_name);
            $("#txt_mother_name").val(mother_name);
            $("#dob").val(dob);
            $("#selected_blood_group").val(blood_group);
            $("#txt_phone").val(phone);
            $("#txt_email").val(email);
            $("#txt_whtsapp").val(whtsapp);

            $("#txt_p_street").val(p_street);
            $("#txt_p_city").val(p_city);
            $("#txt_p_pincode").val(p_pincode);
            $("#txt_p_country").val(p_country);

            $("#hdn_file_upload").val(img);

            $("#hdn_id").val(id);
            // load_blood_groups();
            $('#clientModal').modal('show');
        }
        //functio to fetch the member details and popup for eidt the details end

        // function for showing popup for add new members start
        function popupMemberDetails(id) {
            // load_blood_groups();            
            $("#hdn_id").val(id);
            $('#clientModal').modal('show');
        }
        //function for showing popup for add new members end

        // function to save the member details strat
        function saveMembers() { 

            const first_name = $('#txt_first_name').val();
            const last_name = $('#txt_last_name').val();
            const phone = $('#txt_phone').val();
            const email = $('#txt_email').val();
            const street = $('#txt_p_street').val();
            const city = $('#txt_p_city').val();
            const pincode = $('#txt_p_pincode').val();
            const country = $('#txt_p_country').val();
           
            if (first_name == "") {
                alertinfo("First Name cannot be empty.");
                return;
            }
            
            if (last_name == "") {
                alertinfo("Last Name cannot be empty.");
                return;
            }

            if (phone == "") {
                alertinfo("Phone cannot be empty.");
                return;
            }

            // if (email == "") {
            //     alertinfo("Email cannot be empty.");
            //     return;
            // }

            // if (street == "" || city == "" || pincode == "" || country == "") {
            //     alertinfo("Address fields cannot be empty.");
            //     return;
            // }
            

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
                    action: 'save_members',

                    first_name: $('#txt_first_name').val(),
                    middle_name: $('#txt_middle_name').val(),
                    last_name: $('#txt_last_name').val(),

                    father_name: $('#txt_father_name').val(),
                    mother_name: $('#txt_mother_name').val(),
                    dob: $('#dob').val(),

                    blood_group:$("#selected_blood_group").val(),
                    phone: $('#txt_phone').val(),
                    whtsapp: $('#txt_whtsapp').val(),
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
                    url: "api/list_of_members.php",
                    data: data,
                    success: function(response) {
                        console.log('saved:', response);
                        closepopupMemberDetails();
                        alertsuccess('Saved Sucessfully');
                        loadData($('#hdn_current_page').val());
                    },
                    error: function (xhr, status){
                    var msgObj = JSON.parse(xhr.responseText);
                    alerterror(msgObj, xhr);
                    $('#member_form')[0].reset();
                    $("#photoInput").val('');
                    }
                });
            }
		    }); 
        }
        // function to save the member details end

        // function to close the popup start
        function closepopupMemberDetails(){
            $("hdn_id").val(0);
            $('#member_form')[0].reset();
            $('#clientModal').modal('toggle');
        }
        // function to close the poup end

        // function to inject blood groups dropdwon to the popup for add new fees details start
        function load_blood_groups(){
            $.ajax({
   
                type: "POST",
                url: "api/list_of_members.php",
                data: {
                action: 'load_blood_groups',
                },
                success: function(data) {
                    var obj = jQuery.parseJSON(data);
                    console.log(obj);
                                       
                    // var totalrows = obj[0].total_rows;
                    var htm="";
                    htm=htm+ "<div class='dropdown'><select id='selected_blood_group' class='status-dropdown'>";
                    htm=htm+"<option  value='0'>select blood group</option>";
                    for (var i = 0; i < obj[0].length; i++) {
                        htm=htm+"<option  value='"+obj[0][i].id+"'>"+obj[0][i].name+"</option>";
                    }                
                    htm=htm+"</select></div>";

                    $('#blood_groups').html(htm);// Inject the data into the container
                    
                    // loadData(1);
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error: ', status, error);
                }
            });
        }
        // function to inject blood groups dropdown to the popup for add new fees details end

    </script>

    <script src="../app_pagination/pagination.js"></script>
    <script src="../app_menu/menu.js"></script>

</head>

<body>
<!-- hidden values -->
<input type="hidden" id="hdn_current_page"  value="0">
<input type="hidden" id="hdn_id"  value="0">
<input type="hidden" id="hdn_file_upload" name="image" value="0">

    <div id="wrapper">
        <!-- navigation starts -->
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
                        <h2>Members</h2>
                            <form action="index.html" method="get">
                                <div class="input-group">
                                    <input type="text" placeholder="Search" id="txt_search" name="search" class="form-control">
                                    <div class="input-group-btn">
                                        <button class="btn btn-white" onclick="searchMembers()" type="button">Search</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="col-sm-4">
                        <div class="title-action">
                            <div class="ibox-tools">
                                <button type="button" class="btn btn-primary btn-xs" onclick="popupMemberDetails('0')">Add Members</button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- search bar end -->

                <!-- popup for add member -->
                <div class="modal inmodal" id="clientModal" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content animated bounceInRight">
                            <form method="POST" id="member_form" enctype="multipart/form-data">
                                <div class="modal-body">
                                    <div class="row" style="padding-bottom: 15px;">
                                        <div class="col-md-6"><input type="text" id="txt_first_name" name="first_name"  placeholder=" First Name" class="form-control" oninput="nameValidation()"></div>
                                        <div class="col-md-6"><input type="text" id="txt_middle_name" name="middle_name" placeholder="Middle Name" class="form-control" oninput="nameValidation()"></div>
                                    </div>

                                    <div class="row" style="padding-bottom: 15px;">
                                        <div class="col-md-6"><input type="text" id="txt_last_name" name="last_name"  placeholder="Last Name" class="form-control" oninput="nameValidation()"></div>
                                        <div class="col-md-6"><input type="text" id="txt_father_name" name="father_name"  placeholder="Father Name" class="form-control" oninput="nameValidation()"></div>

                                        <!-- <div class="col-md-6"><input type="text" id="txt_gender" name="gender"  placeholder="gender" class="form-control"></div> -->
                                    </div>

                                    <div class="row" style="padding-bottom: 15px;">
                                        <div class="col-md-6"><input type="text" id="txt_mother_name" name="mother_name"  placeholder="Mother Name" class="form-control" oninput="nameValidation()"></div>
                                        <div class="col-md-6"><input type="date" id="dob" name="dob"  placeholder="date of birth" class="form-control"></div>
                                    </div>

                                    <div class="row" style="padding-bottom: 15px;">
                                        <div class="col-md-6" id="blood_groups">
                                            <!-- blood group injected via ajax -->
                                        </div>
                                        <div class="col-md-6"><input type="number" id="txt_phone" name="phone"  placeholder="Phone Number" class="form-control"></div>
                                    </div>

                                    <div class="row" style="padding-bottom: 15px;">
                                        <div class="col-md-6"><input type="number" id="txt_whtsapp" name="whtsapp"  placeholder="Whatsapp Number" class="form-control"></div>
                                        <div class="col-md-6"><input type="text" id="txt_email" name="email" placeholder="Email" class="form-control" ></div>
                                    </div>

                                    <div class="row" style="padding-bottom: 15px;">
                                        <div class="form-group"><label>Permenent Address</label></div>
                                        <div class="col-md-6"><input type="text" id="txt_p_street" name="p_street"  placeholder="Street" class="form-control"></div>
                                        <div class="col-md-6"><input type="text" id="txt_p_city" name="p_city"  placeholder="City" class="form-control" ></div>
                                    </div>

                                    <div class="row" style="padding-bottom: 15px;">
                                        <div class="col-md-6"><input type="number" id="txt_p_pincode" name="p_pincode"  placeholder="Pincode" class="form-control"></div>
                                        <div class="col-md-6"><input type="text" id="txt_p_country" name="p_country"  placeholder="Country Name" class="form-control"></div>
                                    </div>

                                </div>
                             
                                <div class="modal-footer">
                                    <!-- <input type="file" id="photoInput" onchange="photoInputChange();" accept="image/*"> -->
                                    <input type="file" id="photoInput" onchange="photoInputChange(event);" accept="image/*">
                                    
                                    <button type="button" class="btn btn-white" onclick="closepopupMemberDetails();">Close</button>
                                    <button type="button" class="btn btn-primary" onclick="saveMembers();">Save</button>

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
                <!-- popup for add member end-->
                <div class="modal inmodal" id="loginModal" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content animated bounceInRight">
                            <form method="POST" id="login_form">
                                <div class="modal-body">
                                    <div class="form-group"><label>Name</label><input type="text" id="name" name="Name" placeholder="Name"  class="form-control" readonly></div>
                                    <div class="form-group"><label>Email</label><input type="text" id="email" name="Email" placeholder="Email"  class="form-control" readonly></div>
                                    <div class="form-group"><label>Password</label><input type="text" id="password" name="password" placeholder="Password"  class="form-control"></div>
                                    <div class="form-group"><label>Confirm Password</label><input type="text" id="confirmpassword" name="confirmpassword" placeholder="Confirm Password"  class="form-control"></div>
                                </div>
                                
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-white" onclick="closePopupLogin();">Close</button>
                                    <button type="button" class="btn btn-primary" onclick="saveLogin();">Save</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                

                <div class="wrapper wrapper-content animated fadeInRight" id="table_members">
                    <!-- Data injected by ajax -->
                </div>
                <div id='familyModal' class='modal groupstatus'>
                <div class='modal-content' id="family_container">
                    <!-- popup data injected by ajax -->
                </div>
            </div>

                <div id='groupsModal' class='modal'>
                    <div class='modal-content' id="group_container">
                        <!-- popup data injected by ajax -->
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
    <script src="../image_upload/members/image_upload.js"></script>
    <script src="../app_js/validation.js"></script>
    <script src="../js/loadingoverlay.min.js"></script>

</body>

</html>
