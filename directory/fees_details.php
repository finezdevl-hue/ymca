<?php
session_start();

if(isset($_REQUEST['member_id'])){
    $_SESSION['member_id']=$_REQUEST['member_id'];
}
if(isset($_REQUEST['first_name'])){
    $_SESSION['first_name']=$_REQUEST['first_name'];
}
if(isset($_REQUEST['middle_name'])){
    $_SESSION['middle_name']=$_REQUEST['middle_name'];
}
if(isset($_REQUEST['last_name'])){
    $_SESSION['last_name']=$_REQUEST['last_name'];
}
?>
<!DOCTYPE html>
<html>

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Fees Details</title>

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
        
        
        // Helper: extract month+year from date string (ignores description for monthly fee display)
        function getMonthNameFromRecord(dateStr) {
            var monthNames = ["", "January", "February", "March", "April", "May", "June",
                              "July", "August", "September", "October", "November", "December"];
            if (!dateStr) return '';
            var parts = dateStr.split('-');
            if (parts.length < 2) return '';
            var monthIndex = parseInt(parts[1], 10);
            var year = parts[0];
            var monthName = monthNames[monthIndex];
            return monthName ? monthName + ' ' + year : '';
        }

        // function to load all fee details of the member start
        function loadData(page) {
            $('#hdn_current_page').val(page); //used for Status Update function
            console.log("Loading data for page:", page);
            $.ajax({               
               type: "POST",
               url: "api/fees_details.php",
               data: {
               action: 'load_data',
               page: page, 
                //    val:$('#txt_search').val()
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
                    htm=htm+ "<th>Date</th>";
                    htm=htm+ "<th>Head</th>";
                    htm=htm+ "<th>Description</th>";
                    htm=htm+ "<th>Billed</th>";
                    htm=htm+ "<th>Received</th>";
                    htm=htm+ "<th>Balance</th>";
                    htm=htm+ "<th>Status</th>";
                    htm=htm+ "</tr>";
                    htm=htm+ "</thead>";
                    htm=htm+ "<tbody>";
                    for (var i = 0; i < obj[1].length; i++) {

                        var billed = parseFloat(obj[1][i].billed_amount);
                        var received = parseFloat(obj[1][i].amount_received);
                        var balance = billed - received;
                        var statusBadge = obj[1][i].iscomplete == 1
                            ? "<span style='color:green; font-weight:bold;'>Paid</span>"
                            : "<span style='color:red; font-weight:bold;'>Pending</span>";

                        var j= i+1;
                        var slno=((page-1)*8)+j;
                        htm=htm+ "<tr>";
                        htm=htm+ "<td>"+slno+"</td>";
                        htm=htm+ "<td>"+obj[1][i].date+"</td>";
                        htm=htm+ "<td>"+( obj[1][i].head ? obj[1][i].head : 'N/A')+"</td>";

                        // Description: for Monthly Fee show "Monthly Fee — Month Year"
                        var headName = obj[1][i].head ? obj[1][i].head.trim() : '';
                        var rawDesc  = obj[1][i].discription ? obj[1][i].discription.trim() : '';
                        var particularHtml = '';
                        if (headName.toLowerCase() === 'monthly fee' || headName.toLowerCase() === 'monthly fees') {
                            var monthYearStr = getMonthNameFromRecord(obj[1][i].date);
                            var monthLabel = monthYearStr ? headName + ' \u2014 ' + monthYearStr : headName;
                            particularHtml = "<strong>" + monthLabel + "</strong>";
                            if (rawDesc && rawDesc.toLowerCase() !== headName.toLowerCase()) {
                                particularHtml += "<br><span style='font-size:11px; color:#888;'>" + rawDesc + "</span>";
                            }
                        } else {
                            particularHtml = rawDesc || '\u2014';
                        }
                        htm=htm+ "<td>" + particularHtml + "</td>";

                        htm=htm+ "<td>₹ "+billed.toFixed(2)+"</td>";
                        htm=htm+ "<td>₹ "+received.toFixed(2)+"</td>";
                        htm=htm+ "<td>₹ "+balance.toFixed(2)+"</td>";
                        htm=htm+ "<td>"+statusBadge+"</td>";
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
            load_heads();
        }
        // function to load all fee details of the member end

    </script>

    <script>

        //function to inject heads dropdwon to the popup for add recieved amount start
        function load_heads(){
            $.ajax({
   
                type: "POST",
                url: "api/fees_details.php",
                data: {
                action: 'load_heads',
                },
                success: function(data) {
                    var obj = jQuery.parseJSON(data);
                    console.log(obj);
                                       
                    // var totalrows = obj[0].total_rows;
                    var htm="";
                    htm=htm+ "<div class='dropdown form-group'><select id='selected_head' class='form-control'>";
                    htm=htm+"<option  value='0' selected disabled>select head</option>";
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
        // function to inject heads dropdown to the popup for add recieved amount end

        // function for popup to edit the fee details start
        function addFees(id,date,fees,head,discription){
            
            $("#txt_date").val(date);
            $("#txt_fees").val(fees);
            $("#selected_head").val(head);
            $("#txt_discription").val(discription);
            $("#hdn_id").val(id);
            $('#feesModel').modal('show');
          
        }
        // function for popup to edit the fee details end

        // function to close popup start
        function closeaddFees(){
            $('#fees_form')[0].reset();
            $("hdn_id").val(0);
            $('#feesModel').modal('toggle');
        }
        // function to close popup end

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
                    date: $('#txt_date').val(),
                    // to_date: $('#txt_to_date').val(),  
                    fees: $('#txt_fees').val(),
                    head: $('#selected_head').val(),
                    discription: $('#txt_discription').val(), 
                    id: $('#hdn_id').val(),          
                };
                // AJAX call
                $.ajax({
                    type: "POST",
                    url: "api/fees_details.php",
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

        //function to delete fee details start
        function deleteFeeDetails(id,amount,receiveble_id) {
            $("#hdn_id").val(id);
            $("#hdn_amount").val(amount);
            $("#hdn_receiveble_id").val(receiveble_id);
            deleteRow();
        }

        function deleteRow() {
            // alert($("#hdn_receiveble_id").val());
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
                id: $("#hdn_id").val(),
                amount: $("#hdn_amount").val(),
                receiveble_id: $("#hdn_receiveble_id").val(),
            };
            // AJAX call
            $.ajax({
                type: "POST",
                url: "api/fees_details.php",
                data: data,
                success: function(response) {
                    $('#hdn_id').val(0);
                    // console.log('deleted:', response);
                    $('#hdn_amount').val(0);
                    $('#hdn_receiveble_id').val(0);
                    alertwarning('Deleted');
                    loadData($('#hdn_current_page').val());
                    
                },
                error: function(xhr, status, error) {
                    $('#hdn_id').val(0);
                    $('#hdn_amount').val(0);
                    $('#hdn_receiveble_id').val(0);
                    console.log('AJAX error:', status, error);
                }
            });
                
                    
            }
		});   
        }
        //function to delete fee details end
        
    </script>
    <script src="../app_pagination/pagination.js"></script>
    <script src="../app_menu/menu.js"></script>
</head>

<body>
    <input type="hidden" id="hdn_current_page"  value="0">
    <input type="hidden" id="hdn_id"  value="0">
    <input type="hidden" id="hdn_amount"  value="0">
    <input type="hidden" id="hdn_receiveble_id"  value="0">

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
                <div class="col-sm-4">
                    <h2><?php echo $_SESSION['first_name'] . " " . $_SESSION['middle_name'] . " " . $_SESSION['last_name']; ?></h2>
                    <!-- <div class="search-form">
                        <form action="index.html" method="get">
                            <div class="input-group">
                                <input type="text" placeholder="Search" id="txt_search" name="search" class="form-control">
                                <div class="input-group-btn">
                                    <button class="btn btn-white" onclick="searchFamily()" type="button">Search</button>
                                </div>
                            </div>
                        </form>
                    </div> -->
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
                            <div class="form-group"><label>Date</label><input type="date" id="txt_date" name="from_date" placeholder=""  class="form-control"></div>
                            <!-- <div class="form-group"><label>To Date</label><input type="date" id="txt_to_date" name="to_date" placeholder=""  class="form-control"></div> -->
                            <div class="form-group"><label>Fees</label><input type="text" id="txt_fees" name="fees" placeholder=""  class="form-control"></div>
                            <!-- <div class="form-group"><label>Discription</label>
                                <select id="selected_head" class="form-control">
                                    <option  value="0" selected disabled>Select Head</option>
                                    <option  value="monthly fee">Monthly Fee</option>
                                    <option  value="Membership fee">Membership fee</option>
                                </select>
                            </div> -->
                            <div id="select_heads">
                                <!-- dropdown injected via ajax -->
                            </div>
                            <div class="form-group"><label>Discription</label><textarea  id="txt_discription" name="discription" rows="4" placeholder="Maximum 150 charecters"  class="form-control"></textarea></div>
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
