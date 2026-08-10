<?php
session_start();
if(isset($_POST['id'])){
    $_SESSION['family_id']=$_POST['id'];
}
?>
<!DOCTYPE html>
<html>

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Family</title>

    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../font-awesome/css/font-awesome.css" rel="stylesheet">
    <link href="../css/plugins/iCheck/custom.css" rel="stylesheet">

    <link href="../css/animate.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <link href="../css/plugins/sweetalert/sweetalert.css" rel="stylesheet">
    <script src="../js/plugins/sweetalert/sweetalert.min.js"></script>
    <script src="../app_js/sweetalert-finez.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cropperjs@1.5.13/dist/cropper.min.css">
    <link href="../image_upload/family/upload.css" rel="stylesheet">
    
  
    <script>
        $(document).ready(function() {          
            loadData(1); // Function to load data for a specific page       
        });  
        
        // search box for family starts
        function searchFamily(){
            if ($('#txt_search').val().trim()=='') {
                
                alertwarning('Please enter a value.');
                return;
            }       
            loadData(1);
            
        }
        // search box for family ends
       
        //search box for members starts
        function searchMember(){
            if ($('#txt_search_member').val().trim()=='') {
                
                alertwarning('Please enter a value.');
                return;
            }
            load_members();     
        }
        //search box for members ends 
        function searchSpouse(){
            if ($('#txt_search_spouse').val().trim()=='') {
                
                alertwarning('Please enter a value.');
                return;
            }
            load_spouse();     
        }
        //function to load thae family details starts
        function loadData(page) {
            $('#hdn_current_page').val(page); //used for Status Update function
            console.log("Loading data for page:", page);
            $.ajax({               
               type: "POST",
               url: "api/family.php",
               data: {
               action: 'load_family_data',
               page: page, 
               val:$('#txt_search').val()
               },
                success: function(data) {  
                     
                    var obj = jQuery.parseJSON(data);
                    var totalrows = obj[0].total_rows;
                    // console.log (obj);
                    var htm="";
                    htm=htm+"<div class='row'>";
                    for (var i = 0; i < obj[1].length; i++) {
                       
                        htm=htm+ "<div class='col-lg-3'>";
                        htm=htm+ "<div class='contact-box center-version'>";

                        htm=htm+"<div class='contact-box-footer'>";
                        htm=htm+"<div class='m-t-xs btn-group'>";
                        htm=htm+"<a onclick='showmembersModal("+obj[1][i].id+");'  class='btn btn-xs btn-white'><i class='fa fa-user-plus'></i>Add Member</a>";
                        htm=htm+"<a onclick='showgroupsModal(" +obj[1][i].id+ ")' class='btn btn-xs btn-white'><i class='fa fa-user-plus'></i>Add Group</a>";
                        htm=htm+"</div>";
                        htm=htm+"</div>";

                        htm=htm+ "<center><h3 class='m-b-xs'><strong>"+obj[1][i].name+ "</strong></h3></center>";
                        htm=htm+"<a onclick=popupFamily('"+obj[1][i].id+"')>";
                        htm=htm+"<center><img alt='image' src='../image_upload/family/thumbnails/"+obj[1][i].img+"' style='width: 200px; height: 200px;'></center>";
                        htm=htm+"</a>";
                        htm=htm+ "<div class='contact-box-footer'>";
                        htm=htm+ "<div class='m-t-xs btn-group'>";
                        htm=htm+ "<a onclick='fetchfamilyDetails("+obj[1][i].id+",\"" +obj[1][i].name+  "\",\"" +obj[1][i].parent_id+  "\",\"" +obj[1][i].spouse_id+  "\",\"" +obj[1][i].img+  "\");' class='btn btn-xs btn-white'><i class='fa fa-pencil'></i> edit </a>";
                        htm=htm+ "<a onclick=familyMembers('"+obj[1][i].id+"') class='btn btn-xs btn-white'><i class='fa fa-users'></i>members</a>";
                        htm=htm+ "<a onclick='deleteFamiy("+obj[1][i].id+");' class='btn btn-xs btn-white'><i class='fa fa-trash'></i> delete</a>";
                        htm=htm+ "</div>";
                        htm=htm+ "</div>";
                        htm=htm+ "</div>";
                        htm=htm+ "</div>"   
                    }                
                    
                    htm=htm+ "</div>";
                    $('#table_members').html(htm);
                    var htmpage= paginate(totalrows,page);
                    $('#table_members').append(htmpage);
                },

                error: function(xhr, status, error) {
                    console.log('AJAX error: ', status, error);
                }
            });
            loadMenu();
            load_members();
        }
        //function to load thae family details ends
    </script>

    <script>
        //function to show family groups popup starts
        let family_id = null; // To store the current row ID

        function showgroupsModal(id) {
            load_groups();
            fetchGrouopDetails(id);
            family_id = id;
            document.getElementById('groupsModal').style.display = 'flex';
        }
        //function to show family groups popup ends

        function popupFamily(id) { 
            
            load_family_details(id);
            $('#familyModal').modal('show');
        }

        // function to load group details
        function load_family_details(id){
            $.ajax({
   
                type: "POST",
                url: "api/family.php",
                data: {
                action: 'load_family_details',
                id:id,
                },
                success: function(data) {

                    var obj = jQuery.parseJSON(data);
                    console.log(obj);
                                    
                    var totalrows = obj[0].total_rows;
                    var htm="";

                    for (var i = 0; i < obj[1].length; i++) {
                        htm=htm+"<div class='modal-header'>";
                        htm=htm+"<button type='button' class='close' data-dismiss='modal'><span aria-hidden='true'>&times;</span><span class='sr-only'>Close</span></button>";
                        htm=htm+"<h4 class='modal-title'>"+obj[1][i].name+"</h4>";
                        htm=htm+"</div>";
                        htm=htm+"<div class='modal-body'>";
                        htm=htm+ "<img src='../image_upload/family/uploads/"+obj[1][i].img+"' alt='' style='width:200px; height: 260px'>";
                        htm=htm+"</div>";
                    }                

                    $('#family_details').html(htm);

                    },
                error: function(xhr, status, error) {
                    console.error('AJAX error: ', status, error);
                }
            });
        }

        // function to load groups for the popup starts
        function load_groups(){
            $.ajax({
   
                type: "POST",
                url: "api/family.php",
                data: {
                action: 'load_groups',
                },
                success: function(data) { 
                    var obj = jQuery.parseJSON(data);
                    // console.log (obj);
                    var htm="";
                    htm=htm+ "<span class='close' onclick='closeGroups()'>&times;</span>";
                    htm=htm+ "<h4>Select Group</h4>";
                    for (var i = 0; i < obj[0].length; i++) {
                        htm=htm+ "<div class='i-checks'><label><input type='checkbox' name='group' id='"+obj[0][i].id+"' value='"+obj[0][i].id+"'><i></i>"+obj[0][i].name+"</label></div>";                        
                    }
                    htm=htm+ "<button onclick='addFamilyToGroups();' class='save-button'>Save</button>";
                    // $('#groups').html(data);
                    $('#groups').html(htm);
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error: ', status, error);
                }
            });
        }
        // function to load groups for the popup ends

        // function to fetch family gropup details starts
        function fetchGrouopDetails(id){
            $.ajax({               
                type: "POST",
                url: "api/family.php",
                data: {
                action: 'fetch_group_details',
                family_id:id, 
                },
                success: function(data) {  
             
                    var obj = jQuery.parseJSON(data);
                    // console.log(obj[5].member_id);
              
                    for (var i = 0; i < obj.length; i++) {
                        console.log(obj[i].group_id);
                        document.getElementById(obj[i].group_id).checked = true; //Verified
                    
                    }
                },
                error: function(xhr, status, error) {
                    console.log('AJAX error: ', status, error);
                }
            });
            
        }
        // function to fetch family gropup details ends

        // function to add families into groups starts
        function addFamilyToGroups() {

            var group_ids = [];
            $("input[type=checkbox]:checked").each(function () {
                group_ids.push(this.value);
            });
       
               
               
            $.ajax({               
                type: "POST",
                url: "api/family.php",
                data: {
                   action: 'add_family_to_groups',
                   id: family_id, 
                   group_ids: group_ids
                },
                success: function(data) {  
                   alertsuccess('Saved Sucessfully');               
                   closeGroups();
                   loadData($('#hdn_current_page').val());                
                },
                error: function(xhr, status, error) {
                    console.log('AJAX error: ', status, error);
                }
            });     
        }
        // function to add families into groups ends

        // function to show the members starts
        function showmembersModal(id) {
            
            fetchMemberDetails(id)
            family_id = id;
            // $('#hdn_current_page').val(family_id);
            document.getElementById('membersModal').style.display = 'flex';
        }
        // function to show the members ends

        //function to load members starts
        function load_members(){
            $.ajax({
   
                type: "POST",
                url: "api/family.php",
                data: {
                action: 'load_members',
                member_val: $('#txt_search_member').val(),
                },
                
                success: function(data) { 
                    var obj = jQuery.parseJSON(data);
                    
                    var htm="";

                    htm=htm+ "<span class='close' onclick='closeMembersModal()'>&times;</span>";
                    htm=htm+ "<h4>Select Member</h4>";
                    
                    for (var i = 0; i < obj[0].length; i++) {

                        htm=htm+"<div class='i-checks members'><label><input type='checkbox' name='member' id='"+obj[0][i].id+"' value='"+obj[0][i].id+"'><img alt='image' class='img-circle member-img' src='../image_upload/members/thumbnails/"+obj[0][i].img+"'><i></i>"+obj[0][i].first_name+" " + obj[0][i].middle_name + " " + obj[0][i].last_name + " </label></div>";

                    }
                    htm=htm+"<button onclick='addMemberToGroups();' class='save-button'>Save</button>";
                    // $('#group_container').html(data);
                    $('#group_container').html(htm);
                   
                    // fetchMemberDetails( $('#hdn_current_page').val());
                },
                error: function(xhr, status, error) {
                        console.log('AJAX error: ', status, error);
                }
            });
        }
        function fetchMemberDetails(id){
            $.ajax({               
                type: "POST",
                url: "api/family.php",
                data: {
                action: 'fetch_member_details',
                id:id,
                },
               
                success: function(data) {

                    var obj = jQuery.parseJSON(data);
                    // console.log(obj[5].member_id);
              
                    for (var i = 0; i < obj.length; i++) {
                        
                        document.getElementById(obj[i].member_id).checked = true; //Verified
                    
                    }
                },
                error: function(xhr, status, error) {
                    console.log('AJAX error: ', status, error);
                }
            });
            
        }
        //function to load members ends

        // function to close members modal starts
        function closeMembersModal() {

            document.getElementById('membersModal').style.display = 'none';
            $("input[name='member']:checkbox").prop('checked',false);

        }
        // function to close members modal ends

        // function to close group modal starts
        function closeGroups() {

            document.getElementById('groupsModal').style.display = 'none';
            $("input[name='group']:checkbox").prop('checked',false);

        }
        // function to close group modal ends

        // function to add members into groups strats
        function addMemberToGroups() {
               
            var members_ids = [];
             $("input[type=checkbox]:checked").each(function () {
            members_ids.push(this.value);
            });
            
               
            $.ajax({               
                type: "POST",
                url: "api/family.php",
                data: {
                action: 'add_member_to_family',
                id: family_id, 
                members_ids: members_ids
                },
                success: function(data) {  
                    alertsuccess('Saved Sucessfully');                
                    closeMembersModal();
                    loadData($('#hdn_current_page').val());                
                },
                error: function(xhr, status, error) {
                    console.log('AJAX error: ', status, error);
                }
            });     
        }
        // function  to add members into groups ends
   
        // function to fetch family details for edit starts
        function fetchfamilyDetails(id,name,parent_id,spouse_id,img){

            $("#txt_family_name").val(name);
            $("#hdn_parent_id").val(parent_id);
            $("#hdn_spouse_id").val(spouse_id);
            $('#hdn_family_id').val(id),
            $('#hdn_file_upload').val(img),
            $('#clientModal').modal('show');
            // fetchParentDetails(parent_id,spouse_id);
        }
        // function to fetch family details for edit ends

        // function fetchParentDetails(parent_id,spouse_id){
        //     var members_Ids = [parent_id, spouse_id];
        //     $.ajax({
        //         type: "POST",
        //         url: "api/family.php",
        //         data: {
        //         action: 'load_parent_spouse',
        //         member_ids: members_Ids,
        //         },
        //         success: function(data) {
        //             var obj = jQuery.parseJSON(data);
        //             // console.log(obj);
        //             for (var i = 0; i < obj.length; i++) {
        //                 var fullName = obj[i].first_name + " " + (obj[i].middle_name || "") + " " + obj[i].last_name;
        //                 if (i === 0) {
        //                     $("#txt_parent_name").val(fullName.trim());
        //                 } else {
        //                     $("#txt_spouse_name").val(fullName.trim());
        //                 }
        //             }
        //         },
        //         error: function (xhr, status){
        //         var msgObj = JSON.parse(xhr.responseText);
        //         alerterror(msgObj, xhr);
        //          // $('#family_form')[0].reset();
        //         }
        //     });
        // }
        // function to fetch family details for edit ends

        // function for showing popup for edit family details starts
        function popupfamilyDetails(id) {            
            $("#hdn_id").val(id);
            $('#clientModal').modal('show');
        }
        // function for showing popup for edit family details ends

        // function to add family into group starts
        function saveFamily() { 
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
                    action: 'save_family',

                    family_name: $('#txt_family_name').val(),
                    parent_id: $('#hdn_parent_id').val(),
                    spouse_id: $('#hdn_spouse_id').val(),
                    img: $('#hdn_file_upload').val(),
                    id: $('#hdn_family_id').val(),
                };
                $.ajax({
                    type: "POST",
                    url: "api/family.php",
                    data: data,
                    success: function(response) {
                        console.log('saved:', response);
                        closepopupfamilyDetails();
                        alertsuccess('Saved Sucessfully');
                        loadData($('#hdn_current_page').val());
                    },
                    error: function (xhr, status){
                    var msgObj = JSON.parse(xhr.responseText);
                    alerterror(msgObj, xhr);
                    $('#family_form')[0].reset();
                    }
                });
            }
		    }); 
        }
        // function to add family into groups ends

        // function to close the family details popup starts
        function closepopupfamilyDetails(){
            $("hdn_id").val(0);
            $('#family_form')[0].reset();
            $('#clientModal').modal('toggle');
        }
        // function to close the family details popup ends
        
        // function to redirect the page to family members strats
        function familyMembers(id){ 
            $.post("family_members.php", { 'id': id })
            .done(function(response) {
                window.location.href = "family_members.php";
            })
        }
        // function to redirect the page to family members ends

        // function for delete family starts
        function deleteFamiy(id) {
            $("#hdn_family_id").val(id);
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
                action: 'delete_family',
                id: $("#hdn_family_id").val(),               
            };
            // AJAX call
            $.ajax({
                type: "POST",
                url: "api/family.php",
                data: data,
                success: function(response) {
                    $("#hdn_family_id").val(0);
                    console.log('deleted:', response);
                    alertwarning('Deleted');
                    loadData($('#hdn_current_page').val());
                    
                },
                error: function(xhr, status, error) {
                    console.log('AJAX error:', status, error);
                }
            });
            }
		});   
        }
        //function for delete family ends

        // function for popup spouse modal starts
        function spouseModal() {
            load_spouse();
            // $('#hdn_current_page').val(family_id);
            document.getElementById('spouseModal').style.display = 'flex';
        }
        // function for popup spouse modal ends

        // function to close spouse modal starts
        function closeSpouseModal(){
            $('#spouseModal').modal('toggle');
        }
        // function to close spouse modal ends

        // function to load spouse details starts
        function load_spouse(){
            $.ajax({
   
                type: "POST",
                url: "api/family.php",
                data: {
                action: 'load_Spouse',
                member_val: $('#txt_search_spouse').val(),
                },
                
                success: function(data) { 
                    var obj = jQuery.parseJSON(data);
                    // console.log(obj);
                    var htm="";

                    htm=htm+ "<span class='close' onclick='closeSpouseModal()'>&times;</span>";
                    htm=htm+ "<h4>Select Member</h4>";
                    
                    for (var i = 0; i < obj[0].length; i++) {
                        htm=htm+"<div class='i-checks'><label><input type='radio' name='spouse' id='"+obj[0][i].id+"' value='"+obj[0][i].id+"'><img alt='image' class='img-circle member-img' src='../image_upload/members/thumbnails/"+obj[0][i].img+"'><i></i>"+obj[0][i].first_name+" " + obj[0][i].middle_name + " " + obj[0][i].last_name + " </label></div>";
                    }
                    htm=htm+"<button onclick='saveSpouse()' class='save-button'>Save</button>";
                    // $('#group_container').html(data);
                    $('#member_container').html(htm);
                    
                    // fetchMemberDetails( $('#hdn_current_page').val());
                },
                error: function(xhr, status, error) {
                        console.log('AJAX error: ', status, error);
                }
            });
        }
        // function to load spouse details ends

        // function to save spouse starts
        function saveSpouse() {
            var text = $("input:checked").parent("label").text();
            const selectedSpouse = document.querySelector('input[name=\"spouse\"]:checked');
            $('#txt_spouse_id').val(text);
            $('#hdn_spouse_id').val(selectedSpouse.value);
            closeSpouseModal();
            
        }
        // function to save spouse ends

        // function to popup parent modal starts
        function parentModal() {
            load_parent();
            // $('#hdn_current_page').val(family_id);
            document.getElementById('parentModal').style.display = 'flex';
        }
        // functiom to popup parent modal emds

        // function to load parent details for poup starts
        function load_parent(){
            $.ajax({
   
                type: "POST",
                url: "api/family.php",
                data: {
                action: 'load_Spouse',
                member_val: $('#txt_search_parent').val(),
                },
                
                success: function(data) { 
                    var obj = jQuery.parseJSON(data);
                    // console.log(obj);
                    var htm="";

                    htm=htm+ "<span class='close' onclick='closeParentModal()'>&times;</span>";
                    htm=htm+ "<h4>Select Member</h4>";
                    
                    for (var i = 0; i < obj[0].length; i++) {
                        htm=htm+"<div class='i-checks'><label><input type='radio' name='parent' id='"+obj[0][i].id+"' value='"+obj[0][i].id+"'><img alt='image' class='img-circle member-img' src='../image_upload/members/thumbnails/"+obj[0][i].img+"'><i></i>"+obj[0][i].first_name+" " + obj[0][i].middle_name + " " + obj[0][i].last_name + " </label></div>";
                    }
                    htm=htm+"<button onclick='saveParent()' class='save-button'>Save</button>";
                    // $('#group_container').html(data);
                    $('#parent_container').html(htm);
                    
                    // fetchMemberDetails( $('#hdn_current_page').val());
                },
                error: function(xhr, status, error) {
                        console.log('AJAX error: ', status, error);
                }
            });
        }
        // function to load parent details for popup ends

        // function to close the parent modal starts
        function closeParentModal(){
            $('#parentModal').modal('toggle');
        }
        // function to close the parent modal ends

        // function to save parent starts
        function saveParent() {
            var text = $("input:checked").parent("label").text();
            const selectedParent = document.querySelector('input[name=\"parent\"]:checked');
            $('#txt_parent_id').val(text);
            $('#hdn_parent_id').val(selectedParent.value);
            closeParentModal();
           
        }
        // function to save parent ends
        
    </script>
    <script src="../app_pagination/pagination.js"></script>
    <script src="../app_menu/menu.js"></script>


</head>

<body>
<!-- hidden values -->
<input type="hidden" id="hdn_current_page"  value="0">
<input type="hidden" id="hdn_family_id"  value="0">
<input type="hidden" id="hdn_search_member"  value="0">
<input type="hidden" id="hdn_parent_id"  value="0">
<input type="hidden" id="hdn_spouse_id"  value="0">
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
                        <h2>Families</h2>
                            <form action="index.html" method="get">
                                <div class="input-group">
                                    <input type="text" placeholder="Search" id="txt_search" name="search" class="form-control">
                                    <div class="input-group-btn">
                                        <button class="btn btn-white" onclick="searchFamily()" type="button">Search</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="col-sm-4">
                        <div class="title-action">
                            <div class="ibox-tools">
                                <button type="button" class="btn btn-primary btn-xs" onclick="popupfamilyDetails('0')">Add Family</button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- search bar end -->

                <!-- popup for add member -->
                <div class="modal inmodal" id="clientModal" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content animated bounceInRight">
                            <form method="POST" id="family_form" enctype="multipart/form-data">
                                <div class="modal-body">
                                    <div class="form-group"><label>Family Name</label><input type="text" id="txt_family_name" name="family_name"  placeholder="Enter Family Name" class="form-control" oninput="familyNameValidation()"></div>
                                    <div class="form-group"><label>Parent Name</label><input type="text" id="txt_parent_id" name="parent_id" placeholder="Enter Parent Name" class="form-control">
                                        <input type="button" onclick="parentModal();" value="Parent">
                                    </div>
                                    <div class="form-group"><label>Spouse Name</label><input type="text" id="txt_spouse_id" name="spouse_id"  placeholder="Enter Spouse Name" class="form-control">
                                        <input type="button" onclick="spouseModal();" value="Spouse">
                                    </div>
                                </div>
                             
                                <div class="modal-footer">
                                    <input type="file" id="photoInput" onchange="photoInputChange(event);" accept="image/*">
                                    <button type="button" class="btn btn-white" onclick="closepopupfamilyDetails();">Close</button>
                                    <button type="button" class="btn btn-primary" onclick="saveFamily();">Save</button>

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
                                <!-- popup for add spouse into family ends -->
                            </form>
                            <!-- popup for add spouse into family starts -->
                            <div id='spouseModal' class='modal custom-modal'>
                                <div class='modal-content'>
                                    <div class="input-group">
                                        <input type="text" placeholder="Search" id="txt_search_spouse" name="search" class="form-control">
                                        <div class="input-group-btn">
                                            <button class="btn btn-white" onclick="searchSpouse()" type="button">Search</button>
                                        </div>
                                    </div>
                                    <div id="member_container">
                                        <!-- popup data injected by ajax -->
                                    </div>
                                </div>
                            </div>
                            <!-- popup for add spouse into family ends -->

                            <div id='parentModal' class='modal custom-modal'>
                                <div class='modal-content'>
                                    <div class="input-group">
                                        <input type="text" placeholder="Search" id="txt_search_parent" name="search" class="form-control">
                                        <div class="input-group-btn">
                                            <button class="btn btn-white" onclick="searchParent()" type="button">Search</button>
                                        </div>
                                    </div>
                                    <div id="parent_container">
                                        <!-- popup data injected by ajax -->
                                    </div>
                                </div>
                            </div>
                            
                        </div>
                    </div>
                </div> 
                <!-- popup for add member end-->

                <!-- popup for login -->
                <div class="modal inmodal" id="loginModal" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content animated bounceInRight">
                            <form method="POST" id="login_form">
                                <div class="modal-body">
                                <div class="form-group"><label>Name</label><input type="text" id="name" name="name" placeholder="Enter Your Name" class="form-control"></div>
                                    <div class="form-group"><label>User name</label><input type="email" id="email" name="email" placeholder="Enter User Name" class="form-control"></div>
                                    <div class="form-group"><label>Password</label><input type="password" id="password" name="password" placeholder="Enter password" class="form-control"></div>
                                    <div class="form-group"><label>confirmPassword</label><input type="password" id="confirmpassword" name="confirmpassword" placeholder="Confirm your password" class="form-control"></div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-white" onclick="closePopupLogin();">Close</button>
                                    <button type="button" class="btn btn-primary" onclick="saveLogin();">Save</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <!-- popup for login end-->

                <div class="wrapper wrapper-content animated fadeInRight" id="table_members">
                    <!-- Data injected by ajax -->
                </div>

                <!-- popup for add members into family starts -->
                <div id='membersModal' class='modal custom-modal'>
                    <div class='modal-content'>
                        <div class="input-group">
                            <input type="text" placeholder="Search" id="txt_search_member" name="search" class="form-control">
                            <div class="input-group-btn">
                                <button class="btn btn-white" onclick="searchMember()" type="button">Search</button>
                            </div>
                        </div>
                        <div id="group_container">
                            <!-- popup data injected by ajax -->
                        </div>
                        
                    </div>
                </div>
                <!-- popup for add members into family ends -->

                
                
                <!-- popup for add family into groups starts -->
                <div id='groupsModal' class='modal custom-modal'>
                    <div class='modal-content' id="groups">
                        <!-- popup data injected by ajax -->
                    </div>
                </div>
                <!-- popup for add family into groups ends -->

                <!-- popup for add family into groups starts -->
                <!-- <div id='familyModal' class='modal'>
                    <div class='modal-content' id="family_details">
                        
                    </div>
                </div> -->
                <div class="modal inmodal" id="familyModal" tabindex="-1" role="dialog"  aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content animated fadeIn">
                            <div id="family_details">

                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-white" data-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- popup for add family into groups ends -->
                
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
    <script src="../image_upload/family/image_upload.js"></script>
    <script src="../js/plugins/iCheck/icheck.min.js"></script>
    <script src="../app_js/validation.js"></script>
        <script>
            $(document).ready(function () {
                $('.i-checks').iCheck({
                    checkboxClass: 'icheckbox_square-green',
                    radioClass: 'iradio_square-green',
                });
            });
        </script>

</body>

</html>
