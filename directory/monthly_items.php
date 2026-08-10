<?php
session_start();
?>
<!DOCTYPE html>
<html>

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Monthly Items</title>

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

        // function to load item detail start
        function loadData(page) {
            $('#hdn_current_page').val(page); //used for Status Update function
            console.log("Loading data for page:", page);
            $.ajax({               
               type: "POST",
               url: "api/monthly_items.php",
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
                    htm=htm+ "<th>Month</th>";
                    htm=htm+ "<th>Total Amount</th>";
                    htm=htm+ "<th>Member Count</th>";
                    htm=htm+ "<th>per head amount</th>";
                    htm=htm+ "<th>set</th>";
                    htm=htm+ "</tr>";
                    htm=htm+ "</thead>";
                    htm=htm+ "<tbody>";
                    for (var i = 0; i < obj[1].length; i++) {

                        var j = i + 1;
                        var slno = ((page - 1) * 8) + j;
                        
                        htm += "<tr>";
                        htm += "<td>" + slno + "</td>";
                        htm += "<td>" + obj[1][i].month + "</td>";
                        htm += "<td>" + obj[1][i].total_amount + "</td>";
                        htm=htm+ "<td><button type='button' class='fa fa-user-o btn btn-default btn-xs' onclick='showMembers("+obj[1][i].month+");'>" + obj[1][i].member_count + "</button></td>";
                        // htm += "<td>" + obj[1][i].member_count + "</td>";

                        let total_amount = parseFloat(obj[1][i].total_amount);
                        let member_count = parseInt(obj[1][i].member_count);

                        let perHead = 0;

                        if (member_count > 0) {
                            perHead = total_amount / member_count;
                        }


                        htm += "<td>" + perHead.toFixed(2) + "</td>";
                        if(obj[1][i].isreceiveble==0){
                            htm=htm+ "<td><button type='button' class='fa fa-money btn btn-default btn-xs' onclick='setReceiveble(\""+obj[1][i].month+"\");'>Set receivable</button></td>";

                        }
                        else{
                            htm=htm+ "<td><button type='button' class='btn btn-default btn-xs' >Created</button></td>";

                        }

                        htm += "</tr>";
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
            load_heads();
            loadMenu();
            load_closing_years();
        }
        // function to load item details end
        
    </script>

    <script>

        function addMonthlyAttendance(){
            $('#attendanceModel').modal('show');
        }

        function saveReceivable() {
            // alert($("#selected_head").val());
            var discription = $("#txt_discription").val();
            let length = discription.length; 
            var head = $('#selected_head').val();

            if (head === "0" || head === 0 || head === null) {
                alertinfo("Select a head");
                return;
            }

            
            if (length >= 150) { // Changed from 15 to 150
                alertinfo("Description should be less than 150 characters.");
                return;
            }
            
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
                action: 'save_recieveble',
                date: $('#payable_date').val(),
                discription: $('#txt_discription').val(),
                head: $('#selected_head').val(),
                selected_year: $('#selected_year').val(),
                month: $('#txt_month').val()
            };
            // load_overlay();
            // AJAX call
            $.ajax({
                type: "POST",
                url: "api/monthly_items.php",
                data: data,
                success: function(response) {
                    // close_overlay();
                    console.log('saved:', response);                    
                    closeaddRecieveble();
                    alertsuccess('Saved Sucessfully');
                    loadData($('#hdn_current_page').val());
                },
                error: function (xhr, status){
                    var msgObj = JSON.parse(xhr.responseText);
                    alerterror(msgObj, xhr);
                    $('#payment_form')[0].reset();
                }
            });
                
            }
		    });   
        }

      

        function closeaddMonthlyAttendance(){
            $('#attendance_form')[0].reset();
            $('#attendanceModel').modal('toggle');
        }

        function saveMonthlyAttendance() {
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
                    action: 'save_attendance',
                    from_date: $("#txt_from_date").val(),
                    to_date: $("#txt_to_date").val(),
                };
                // AJAX call
                $.ajax({
                    type: "POST",
                    url: "api/monthly_items.php",
                    data: data,
                    success: function(response) {
                        console.log('saved:', response);                    
                        closeaddMonthlyAttendance();
                        alertsuccess('Saved Sucessfully');
                        loadData($('#hdn_current_page').val());
                    },
                    error: function (xhr, status) {
                        closeaddMonthlyAttendance();
                        try {
                            var msgObj = JSON.parse(xhr.responseText);  

                            // use only errmsg as the Message
                            if (msgObj.errmsg) {
                                msgObj.Message = msgObj.errmsg;
                            }

                            alerterror(msgObj, xhr); 
                        } catch (e) {
                            alerterror({ Message: "Unexpected error occurred." }, xhr);
                        }
                        $('#attendance_form')[0].reset();
                    }


                });
            }
		    });
        }

        

        function showMembers(month_year) {
            $('#hdn_current_page').val(); //used for Status Update function
            console.log("Loading data");
            $.ajax({               
                type: "POST",
                url: "api/monthly_items.php",
                data: {
                    action: 'show_members',
                    val: month_year,
                },
                success: function(data) {

                    var obj = jQuery.parseJSON(data);
                    console.log(obj);
                                       
                    var totalrows = obj[0].total_rows;
                    var htm="";
                    htm=htm+ "<div class='row' style='padding-bottom: 15px;'>";
                    for (var i = 0; i < obj[0].length; i++) {
                        
                        htm=htm+ "<div class='col-lg-6 col-md-6 col-sm-6' style='padding-bottom: 15px;'><input type='text' value='"+obj[0][i].first_name+" "+obj[0][i].middle_name+" "+obj[0][i].last_name+"' class='form-control' readonly></div>";      
                    }    
                    htm=htm+ "</div>";            
                    $('#total_attendance').html(htm);
                    $('#memberModal').modal('show');
                },
                error: function(xhr, status, error) {
                   console.log('AJAX error: ', status, error);
                }
            });
        }

        // function for popup to edit item details details  start
        // 
        // function for popup to edit Payment details end

        //function for poup to add new Payment details into master table start
        function addItems(){
            $('#used_date').val(new Date().toISOString().split('T')[0]);
            $('#itemModal').modal('show');
        }
        //function for pop to add new Payment details into master table end

        //function for close the popup for add new Payment details start
        function closeaddItems(){
            $('#payment_form')[0].reset();
            $("#hdn_id").val(0);
            $('#itemModal').modal('toggle');
        }
        // function for close the popup for add new Payment details end

        function closeAttendance(){
            $('#attendance')[0].reset();
            // $("hdn_id").val(0);
            $('#memberModal').modal('toggle');
        }

        function setReceiveble(month){
            // alert(attendance);
            let today = new Date().toISOString().split('T')[0];
            
            // Set it to the input with id payable_date
            $('#payable_date').val(today);
            $("#txt_month").val(month);
            // Get today's date in YYYY-MM-DD format
            
            
            $('#paymentModel').modal('show');
        }

        function closeaddRecieveble(){
            $('#payment_form')[0].reset();
            $('#hdn_member_id').val(0);
            $('#hdn_id').val(0);
            $('#paymentModel').modal('toggle');
        }

        function load_heads(){
            $.ajax({
   
                type: "POST",
                url: "api/monthly_attendance.php",
                data: {
                action: 'load_heads',
                },
                success: function(data) {
                    var obj = jQuery.parseJSON(data);
                    console.log(obj);
                                       
                    // var totalrows = obj[0].total_rows;
                    var htm="";
                    htm=htm+ "<div class='dropdown form-group'><select id='selected_head' class='form-control'>";
                    // htm=htm+"<option  value='0' selected disabled>select head</option>";
                    for (var i = 0; i < obj[0].length; i++) {
                        htm=htm+"<option  value='"+obj[0][i].id+"'>"+obj[0][i].name+"</option>";
                    }                
                    htm=htm+"</select></div>";

                    $('#select_heads').html(htm);// Inject the data into the container
                    
                    // loadData(1);
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error: ', status, error);
                }
            });
        }

        function load_closing_years(){
            $.ajax({
   
                type: "POST",
                url: "api/monthly_attendance.php",
                data: {
                action: 'load_closing_years',
                },
                success: function(data) {
                    var obj = jQuery.parseJSON(data);
                    // console.log(obj);
                                       
                    // var totalrows = obj[0].total_rows;
                    var htm="";
                    htm=htm+ "<div class='dropdown form-group'><select id='selected_year' class='form-control'>";
                    // htm=htm+"<option  value='0' selected disabled>select head</option>";
                    for (var i = 0; i < obj[0].length; i++) {
                        htm=htm+"<option  value='"+obj[0][i].id+"'>"+obj[0][i].from_year+" - "+obj[0][i].to_year+"</option>";
                    }                
                    htm=htm+"</select></div>";

                   

                    $('#select_from_year').html(htm);// Inject the data into the container
                   
                    
                    // loadData(1);
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error: ', status, error);
                }
            });
        }
    
    </script>
    <script src="../app_pagination/pagination.js"></script>
    <script src="../app_menu/menu.js"></script>
</head>

<body>
    <input type="hidden" id="hdn_current_page"  value="0">
    <input type="hidden" id="hdn_id"  value="0">
    
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
                    <h2>Monthly Items</h2>
                </div>
                <div class="col-sm-4">
                    <div class="title-action">
                        <div class="ibox-tools">
                            <button type="button" class="btn btn-primary btn-xs" onclick="addMonthlyAttendance()">Add Monthly attendance</button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- search bar ends -->
            
            <div class="wrapper wrapper-content animated fadeInRight" id="table_client">
                <!-- data injected Dynamically via ajax -->
            </div>
        </div>
    
        <!-- popup modal for add new payment into master starts -->
        <div class="modal inmodal" id="memberModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content animated bounceInRight">
                    <form method="POST" id="attendance">
                        <div class="modal-body">
                            <div id="total_attendance">
                                <!-- dates of attendance marked injected via ajax -->
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-white" onclick="closeAttendance();">Close</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- popup modal for add new payment into master ends -->

        <!-- popup modal for add attendace of a month starts -->
        <div class="modal inmodal" id="attendanceModel" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content animated bounceInRight">
                    <form method="POST" id="attendance_form">
                        <div class="modal-body">
                            <div class="form-group"><label>From date</label><input type="date" id="txt_from_date" name="from_date" class="form-control"></div>
                            <div class="form-group"><label>To Date</label><input type="date" id="txt_to_date" name="to_date" class="form-control"></div>
                        </div>
                        
                        <div class="modal-footer">
                            <button type="button" class="btn btn-white" onclick="closeaddMonthlyAttendance();">Close</button>
                            <button type="button" class="btn btn-primary" onclick="saveMonthlyAttendance();">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal inmodal" id="paymentModel" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content animated bounceInRight">
                    <form method="POST" id="payment_form">
                        <div class="modal-body">
                            <div class="form-group"><label>Month</label><input type="text" id="txt_month" name="txt_month" class="form-control" readonly></div>
                            <div class="form-group"><label>Receiveble Date</label><input type="date" id="payable_date" name="payable_date" placeholder="Date"  class="form-control"></div>
                            
                            <div id="select_heads">
                                <!-- dropdown injected via ajax -->
                            </div>
                            <div id="select_from_year">
                                <!-- dropdown injected via ajax -->
                            </div>
                            <div class="form-group"><label>Discription</label><textarea  id="txt_discription" name="discription" rows="4" placeholder="Maximum 150 charecters"  class="form-control"></textarea></div>
                            <div class="modal-body">
                        </div>
                        
                        </div>
                        
                        <div class="modal-footer">
                            <button type="button" class="btn btn-white" onclick="closeaddRecieveble();">Close</button>
                            <button type="button" class="btn btn-primary" onclick="saveReceivable();">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
       

    <!-- Mainly scripts -->
    <script src="../js/jquery-3.1.1.min.js"></script>
    <script src="../js/bootstrap.min.js"></script>
    <script src="../js/plugins/metisMenu/jquery.metisMenu.js"></script>
    <script src="../js/plugins/slimscroll/jquery.slimscroll.min.js"></script>

    <!-- Custom and plugin javascript -->
    <script src="../js/inspinia.js"></script>
    <script src="../js/plugins/pace/pace.min.js"></script>


</body>

</html>
