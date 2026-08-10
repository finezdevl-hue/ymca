<?php
session_start();
?>
<!DOCTYPE html>
<html>

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Fees</title>

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
            loadData(1); // Function to load data for a specific page       
        });  

        // function to load fee detail start
        function loadData(page) {
            $('#hdn_current_page').val(page); //used for Status Update function
            console.log("Loading data for page:", page);
            $.ajax({               
               type: "POST",
               url: "api/fees.php",
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
                    htm=htm+ "<div class='row'>";

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
                    htm=htm+ "<th>From Date</th>";
                    htm=htm+ "<th>To Date</th>";
                    htm=htm+ "<th>Fees</th>";
                    htm=htm+ "<th>Group</th>";
                    htm=htm+ "<th>Action</th>";
                    htm=htm+ "</tr>";
                    htm=htm+ "</thead>";
                    htm=htm+ "<tbody>";
                    for (var i = 0; i < obj[1].length; i++) {
                        var j= i+1;
                        htm=htm+ "<tr>";
                        htm=htm+ "<td>"+j+"</td>";
                        htm=htm+ "<td>"+obj[1][i].from_date+"</td>";
                        htm=htm+ "<td>"+obj[1][i].to_date+"</td>";
                        htm=htm+ "<td>"+obj[1][i].fee+"</td>";
                        htm=htm+ "<td>"+obj[1][i].name+"</td>";
                        htm=htm+ "<td><button type='button' class='fa fa-edit btn btn-primary btn-xs' onclick='addFees("+obj[1][i].id+",\"" +obj[1][i].from_date+ "\",\"" +obj[1][i].to_date+ "\",\"" +obj[1][i].fee+ "\");'>Edit</button>";
                        htm=htm+ "<button type='button' class='fa fa-trash btn btn-danger btn-xs' onclick='deleteFeeDetails("+obj[1][i].id+");'>Delete</button></td>";
                        htm=htm+ "</tr>";
                            
                    }                
                    htm=htm+ "</tbody>";
                    htm=htm+ "</table>";
                    htm=htm+ "</div>";

                    htm=htm+ "</div>";
                    htm=htm+ "</div>";
                    htm=htm+ "</div>";
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
        // function to load fee details end
        
    </script>

    <script>

        // function for popup to edit fee details  start
        function addFees(id,from_date,to_date,fee){
          
            $("#txt_from_date").val(from_date);
            $("#txt_to_date").val(to_date);
            $("#txt_fees").val(fee);
            $("#hdn_id").val(id);
            $('#feesModel').modal('show');
          
        }
        // function for popup to edit fee details end

        //function for poup to add new fee details start
        function addNewFees(){
            
            load_groups();
            $('#newFeesModel').modal('show');
          
        }
        //function for pop to add new fee details end

        //function for close the popup for add new fee details start
        function closeaddNewFees(){
            $('#newfees_form')[0].reset();
            // $("hdn_id").val(0);
            $('#newFeesModel').modal('toggle');
        }
        // function for close the popup for add new fee details end

        // function for close popup start
        function closeaddFees(){
            $('#fees_form')[0].reset();
            $("hdn_id").val(0);
            $('#feesModel').modal('toggle');
        }
        // function for close popup end

        // function to save fee details of a member start
        function saveFees() {
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
                    action: 'save_fees',
                    // member_id: $("#hdn_member_id").val(),
                    from_date: $('#txt_from_date').val(),  
                    to_date: $('#txt_to_date').val(),
                    fees: $('#txt_fees').val(), 
                    id: $('#hdn_id').val(),          
                };
                // AJAX call
                $.ajax({
                    type: "POST",
                    url: "api/fees.php",
                    data: data,
                    success: function(response) {
                        console.log('saved:', response);                    
                        closeaddFees();
                        alertsuccess('Saved Sucessfully');
                        loadData($('#hdn_current_page').val());
                    },
                    error: function (xhr, status){
        
                        var msgObj = JSON.parse(xhr.responseText);
                        alerterror(msgObj, xhr);
                        $('#fees_form')[0].reset();
                        $("hdn_id").val(0);
                    
                    }
                });
            }
		    });
        }
        // function to save fee details of a member end

        //function to save new fees details start
        function saveNewFees() {
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
                    action: 'save_new_fees',
                    from_date: $('#from_date').val(),  
                    to_date: $('#to_date').val(),
                    fees: $('#fees').val(), 
                    group_id:$("#selected_group").val(),        
                };
                // AJAX call
                $.ajax({
                    type: "POST",
                    url: "api/fees.php",
                    data: data,
                    success: function(response) {
                        console.log('saved:', response);                    
                        closeaddNewFees();
                        alertsuccess('Saved Sucessfully');
                        loadData($('#hdn_current_page').val());
                    },
                    error: function (xhr, status){
        
                        var msgObj = JSON.parse(xhr.responseText);
                        alerterror(msgObj, xhr);
                        $('#fees_form')[0].reset();
                        $("hdn_id").val(0);
                    
                    }
                });
            }
		    });
        }
        // function to save new fees details end
        
        //function to delete fee details start
        function deleteFeeDetails(id) {
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
                action: 'delete_fee_details',
                id: $("#hdn_group_id").val(),               
            };
            // AJAX call
            $.ajax({
                type: "POST",
                url: "api/fees.php",
                data: data,
                success: function(response) {
                    $("#hdn_group_id").val(0);
                    // console.log('deleted:', response);
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
        //function to delete fee details end

        //function to inject groups dropdwon to the popup for add new fees details start
        function load_groups(){
            $.ajax({
   
                type: "POST",
                url: "api/fees.php",
                data: {
                action: 'load_groups',
                },
                success: function(data) {
                    var obj = jQuery.parseJSON(data);
                    console.log(obj);
                                       
                    // var totalrows = obj[0].total_rows;
                    var htm="";
                    htm=htm+ "<div class='dropdown form-group'><select id='selected_group' class='status-dropdown form-control'>";
                    htm=htm+"<option  value='0'>select group</option>";
                    for (var i = 0; i < obj[0].length; i++) {
                        htm=htm+"<option  value='"+obj[0][i].id+"'>"+obj[0][i].name+"</option>";
                    }                
                    htm=htm+"</select></div>";

                    $('#select_groups').html(htm);// Inject the data into the container
                    
                    // loadData(1);
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error: ', status, error);
                }
            });
        }
        // function to inject groups dropdown to the popup fo add new fees detail
    
    </script>
    <script src="../app_pagination/pagination.js"></script>
    <script src="../app_menu/menu.js"></script>
</head>

<body>
    <input type="hidden" id="hdn_current_page"  value="0">
    <input type="hidden" id="hdn_id"  value="0">
    <input type="hidden" id="hdn_group_id"  value="0">


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
                    <h2>Fees Details</h2>
                    <!-- <div class="search-form">
                        <form action="index.html" method="get">
                            <div class="input-group">
                                <input type="text" placeholder="Search" id="txt_search" name="search" class="form-control">
                                <div class="input-group-btn">
                                    <button class="btn btn-white" onclick="searchMember()" type="button">Search</button>
                                </div>
                            </div>
                        </form>
                    </div> -->
                </div>
                <div class="col-sm-4">
                    <div class="title-action">
                        <div class="ibox-tools">
                             <button type="button" class="btn btn-primary btn-xs" onclick="addNewFees()">Add date</button>
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
        <div class="modal inmodal" id="feesModel" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content animated bounceInRight">
                    <form method="POST" id="fees_form">
                        <div class="modal-body">
                            <div class="form-group"><label>From Date</label><input type="date" id="txt_from_date" name="from_date" value="<?php echo date('Y-m-d'); ?>" placeholder=""  class="form-control"></div>
                            <div class="form-group"><label>To Date</label><input type="date" id="txt_to_date" name="to_date" value="<?php echo date('Y-m-d'); ?>" placeholder=""  class="form-control"></div>
                            <div class="form-group"><label>Fees</label><input type="text" id="txt_fees" name="fees" placeholder=""  class="form-control"></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-white" onclick="closeaddFees();">Close</button>
                            <button type="button" class="btn btn-primary" onclick="saveFees();">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- popup modal for update fee details of a member ends -->

         <!-- popup modal for insert fee details of a member starts -->
        <div class="modal inmodal" id="newFeesModel" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content animated bounceInRight">
                    <form method="POST" id="newfees_form">
                        <div class="modal-body">
                            <div class="form-group"><label>From Date</label><input type="date" id="from_date" name="from_date" value="<?php echo date('Y-m-d'); ?>" placeholder=""  class="form-control"></div>
                            <div class="form-group"><label>To Date</label><input type="date" id="to_date" name="to_date" value="<?php echo date('Y-m-d'); ?>" placeholder=""  class="form-control"></div>
                            <div class="form-group"><label>Fees</label><input type="text" id="fees" name="fees" placeholder=""  class="form-control"></div>
                            <div id="select_groups">
                                <!-- dropdown injected via ajax -->
                            </div>
                        </div>
                        
                        <div class="modal-footer">
                            <button type="button" class="btn btn-white" onclick="closeaddNewFees();">Close</button>
                            <button type="button" class="btn btn-primary" onclick="saveNewFees();">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- popup modal for insert fee details of a member ends -->
    </div>
       

    <!-- Mainly scripts -->
    <script src="../js/jquery-3.1.1.min.js"></script>
    <script src="../js/loadingoverlay.min.js"></script>
    <script src="../app_js/date_picker_auto_init.js"></script>
    <script src="../js/plugins/metisMenu/jquery.metisMenu.js"></script>
    <script src="../js/plugins/slimscroll/jquery.slimscroll.min.js"></script>

    <!-- Custom and plugin javascript -->
    <script src="../js/inspinia.js"></script>
    <script src="../js/plugins/pace/pace.min.js"></script>


</body>

</html>
