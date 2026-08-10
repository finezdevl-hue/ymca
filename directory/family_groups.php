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

    <title>Family Groups</title>

    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../font-awesome/css/font-awesome.css" rel="stylesheet">

    <link href="../css/animate.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <link href="../css/plugins/sweetalert/sweetalert.css" rel="stylesheet">
    <script src="../js/plugins/sweetalert/sweetalert.min.js"></script>
    <script src="../app_js/sweetalert-finez.js"></script>

    <script>

        $(document).ready(function() {
          load_group_status_for_select();
        });

        //function to load the family groups status start
        function load_group_status_for_select(){
            $.ajax({
   
                type: "POST",
                url: "api/family_groups.php",
                data: {
                action: 'load_group_status',
                },
                success: function(data) {
                    $('#status-container').html(data);  // Inject the data into the container
                    loadData(1);
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error: ', status, error);
                }
            });
        }
        //function to load the family groups status end
    
        // function to load the family groups start
        function loadData(page) {
            $('#hdn_current_page').val(page); //used for Status Update function
            console.log("Loading data for page:", page);
            $.ajax({               
               type: "POST",
               url: "api/family_groups.php",
               data: {
               action: 'load_group_data',
               page: page, 
               group_status:$("#select_status").val()
               },
                success: function(data) {                    
                  
                    var obj = jQuery.parseJSON(data);
                    console.log(obj);
                                       
                    var totalrows = obj[0].total_rows;
                    var htm="";
                    htm=htm+"<div class='row'>";
                    for (var i = 0; i < obj[1].length; i++) {
                        var btnclass="";
                        var btntext="";
                       
                        switch(obj[1][i].status) {
                            case '1':
                            btnclass='btn-primary';
                            btntext='Active';
                            break;
                            case '2':
                            btnclass='btn-warning';
                            btntext='Not-Active';
                            break;
                            default:
                            btnclass='btn-default';
                            btntext='No Group'
                        }

                        htm=htm+ "<div class='col-lg-3'>";
                        htm=htm+ "<div class='contact-box center-version'>";
                        htm=htm+ "<div style='padding:10px'><button onclick='showStatusModal(" +obj[1][i].id+ ")' class='btn btn-xs "+btnclass+"'>"+btntext+"</button></div>";
                        htm=htm+ "<h3 class='m-b-xs'><strong>"+obj[1][i].name+ "</strong></h3>";
                        htm=htm+ "<div class='contact-box-footer'>";
                        htm=htm+ "<div class='m-t-xs btn-group'>";
                        htm=htm+ "<a onclick='fetchGroupDetails("+obj[1][i].id+",\"" +obj[1][i].name+  "\");' class='btn btn-xs btn-white'><i class='fa fa-pencil'></i> edit </a>";
                        htm=htm+ "<a onclick=groupMembers('"+obj[1][i].id+"') class='btn btn-xs btn-white'><i class='fa fa-users'></i>Families</a>";
                        htm=htm+ "<a onclick='deleteGroup("+obj[1][i].id+");' class='btn btn-xs btn-white'><i class='fa fa-trash'></i> delete</a>";
                        htm=htm+ "</div>";
                        htm=htm+ "</div>";
                        htm=htm+ "</div>";
                        htm=htm+ "</div>"      
                    }
                    htm=htm+ "</div>";
                   
                    $('#table-container').html(htm);
                    var htmpage= paginate(totalrows,page);
                    $('#table-container').append(htmpage);

                },
               error: function(xhr, status, error) {
                   console.log('AJAX error: ', status, error);
               }
            });
            loadMenu();
        }
        // function to load the family groups end

        // functions to change the family group status start
        let mail_data_id = null; // To store the current row ID

        function showStatusModal(id) {
            mail_data_id = id;
            document.getElementById('statusModal').style.display = 'block';
        }
        // function to change the family group status end
    
        // function to close the status modal start
        function closeStatusModal() {
            document.getElementById('statusModal').style.display = 'none';
        }
        // function to close the status modal end
    
        // function to save the family group status start
        function saveStatus() {
            const selectedStatus = document.querySelector('input[name=\"status\"]:checked');
            
            if (!selectedStatus) {
                alertwarning('Please select a status first.');
              
                return;
            }       
            $.ajax({               
                type: "POST",
                url: "api/family_groups.php",
                data: {
                action: 'update_group_status',
                id: mail_data_id, 
                status: selectedStatus.value
                },
                success: function(data) {                    
                closeStatusModal();
                loadData($('#hdn_current_page').val());                
                },
                error: function(xhr, status, error) {
                    console.log('AJAX error: ', status, error);
                }
            });   
        }
        // functions to save the family group status end

        //function to redirect the page to show families of the group start
        function groupMembers(id){ 
            $.post("group_family.php", { 'id': id })
            .done(function(response) {
                window.location.href = "group_family.php";
            })
        }
        //function to redirect the page to show families of the group end

        // function to fetch the details of group start
        function fetchGroupDetails(id,group_name){
            $("#txt_group_name").val(group_name);
            $("#hdn_group_id").val(id);
            $('#groupModel').modal('show');
          
        }
        // function to fetch the details of group end

        // function for popup group details start
        function popupGroupDetails(id) {
            
            $("#hdn_group_id").val(id);
            $('#groupModel').modal('show');
        }
        // function for popup group details end

        // function  to save group details start
        function saveGroup() {
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
                action: 'save_group',
                id: $("#hdn_group_id").val(),
                group_name: $('#txt_group_name').val(),             
            };
            // AJAX call
            $.ajax({
                type: "POST",
                url: "api/family_groups.php",
                data: data,
                success: function(response) {
                    console.log('saved:', response);                    
                    closePopupGroupDetails();
                    alertsuccess('Saved Sucessfully');
                    loadData($('#hdn_current_page').val());
                },
                error: function (xhr, status){
    
                var msgObj = JSON.parse(xhr.responseText);
                alerterror(msgObj, xhr);
                $('#group_form')[0].reset();
                
            }
            });
                
                    
            }
		    });   
        }
        // function  to save group details end

        // close popup start
        function closePopupGroupDetails(){
            $('#group_form')[0].reset();
            $("hdn_group_id").val(0);
            $('#groupModel').modal('toggle');

        }
        // close popup end

        // function to delete a family group start
        function deleteGroup(id) {
            $("#hdn_group_id").val(id);
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
                action: 'delete_group',
                id: $("#hdn_group_id").val(),               
            };
            // AJAX call
            $.ajax({
                type: "POST",
                url: "api/family_groups.php",
                data: data,
                success: function(response) {
                    $("#hdn_group_id").val(0);
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
        // function to delete a family group end

    </script>
    <script src="../app_pagination/pagination.js"></script>
    <script src="../app_menu/menu.js"></script>
</head>

<body>
    <input type="hidden" id="hdn_current_page"  value="0">
    <input type="hidden" id="hdn_group_id"  value="0">

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
        <!-- navigation end -->

        <div id="page-wrapper" class="gray-bg">
            <div class="row border-bottom">
                <nav class="navbar navbar-static-top" role="navigation" style="margin-bottom: 0">
                    <div class="navbar-header">
                        <a class="navbar-minimalize minimalize-styl-2 btn btn-primary " href="#"><i class="fa fa-bars"></i> </a>
                    </div>
                    <ul class="nav navbar-top-links navbar-right" style="padding-top: 20px;">
                        <li>
                            <form action="../app_login_manager/logout.php" method="post">
                                <a href="../app_login_manager/logout.php">
                                    <i class="fa fa-sign-out"></i> Log out
                                </a>
                            </form>
                        </li>
                    </ul>

                </nav>
            </div>
            
            <div class="row wrapper border-bottom white-bg page-heading">
                <div class="col-lg-9">
                    <h2>Family Groups</h2>
                    <div class="status-container" id="status-container" style=" padding: 10px;"><!-- Data will be injected dynamically via AJAX --></div>
                </div>
                <div class="add_group">
                <button type="button" id="insertButton" class="btn btn-primary btn-xs" onclick="popupGroupDetails('0')">
                Add Group
                </button>
                </div>
                
            </div>

            
            <div class="wrapper wrapper-content animated fadeInRight text-center" id="table-container">
             <!-- Data will be injected dynamically via AJAX -->
            </div>
            <!-- popup for add new family group starts-->
            <div class="modal inmodal" id="groupModel" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content animated bounceInRight">
                            <form method="POST" id="group_form">
                                <div class="modal-body">
                                    <div class="form-group"><label>New Group</label><input type="text" id="txt_group_name" name="group_name" placeholder="Enter Group Name"  class="form-control" oninput="group_name_validate()"></div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-white" onclick="closePopupGroupDetails();">Close</button>
                                    <button type="button" class="btn btn-primary" onclick="saveGroup();">Save</button>
                                </div>
                            </form>
                                        
                        </div>
                    </div>
                </div>
            </div>
            <!-- popup for add new family group end -->

            <!-- popup for update family group status start -->
            <div id='statusModal' class='modal groupstatus'>
                <div class='modal-content'>
                    <span class='close' onclick='closeStatusModal()'>&times;</span>
                    <h4>Select Status</h4>
                    <div class="i-checks"><label><input type='radio' name='status' value='1'> Active</label></div>
                    <div class="i-checks"><label><input type='radio' name='status' value='2'> Not Active</label></div>
                    <button onclick='saveStatus()' class='save-button'>Save</button>
                </div>
            </div>
            <!-- popup for update family group status start -->
        
        </div>
    </div>

    <!-- Mainly scripts -->
 
    <script src="../js/bootstrap.min.js"></script>
    <script src="../js/plugins/metisMenu/jquery.metisMenu.js"></script>
    <script src="../js/plugins/slimscroll/jquery.slimscroll.min.js"></script>

    <!-- Custom and plugin javascript -->
    <script src="../js/inspinia.js"></script>
    <script src="../js/plugins/pace/pace.min.js"></script>
    <script src="../app_js/validation.js"></script>
    <!-- check -->
    

</body>

</html>
