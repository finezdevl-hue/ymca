<?php
session_start();
include_once '../app_common/db_connect.php';
include_once '../app_common/auth_helper.php';
include_once '../app_common/enums.php';

if (empty($_SESSION['login_id'])) {
    header("Location: ../index.php");
    exit();
}
$login_id = (int)$_SESSION['login_id'];
if (isNormalMember($login_id)) {
    header("Location: member_dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html>

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Other Recievebles</title>

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
            loadData(1); // Function to load data for page 1     
        });  

        // function to load recieveble amount details start
        function loadData(page) {
            $('#hdn_current_page').val(page); //used for Status Update function
            console.log("Loading data for page:", page);
            $.ajax({               
               type: "POST",
               url: "api/other_recieveble.php",
               data: {
                action: 'load_data',
                page: page, 
                
               },
                success: function(data) {

                    var obj = jQuery.parseJSON(data);
                    // console.log(obj);
                                       
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
                    htm=htm+ "<th>Date</th>";
                    htm=htm+ "<th>Head</th>";
                    htm=htm+ "<th>Particuler</th>";
                    htm=htm+ "<th>Recieveble</th>";
                    htm=htm+ "<th>Payment</th>";
                    htm=htm+ "</tr>";
                    htm=htm+ "</thead>";
                    htm=htm+ "<tbody>";
                    for (var i = 0; i < obj[1].length; i++) {

                        htm=htm+ "<tr>";
                        htm=htm+ "<td>"+obj[1][i].date+"</td>";
                        htm=htm+ "<td>"+obj[1][i].name+"</td>";
                        htm=htm+ "<td>"+obj[1][i].particuler+"</td>";
                        htm = htm + "<td>" + (obj[1][i].recieveble_amount - obj[1][i].total_recieved_amount) + "</td>";
                        if(obj[1][i].iscomplete == 0){
                            htm=htm+ "<td><button type='button' class='fa fa-money btn btn-primary btn-xs' onclick='editPayment("+obj[1][i].recieveble_id+",\"" +obj[1][i].paid_date+ "\",\"" +obj[1][i].head+ "\",\"" +obj[1][i].total_recieved_amount+ "\",\"" +obj[1][i].recieveble_amount+"\",\"" +obj[1][i].particuler+"\"," +obj[1][i].flag+");'>Pay</button></td>";
                        }
                        else{
                            htm=htm+ "<td><button type='button' class=' btn btn-default btn-xs');'>Completed</button></td>";
                        }
                        // htm=htm+ "<td><button type='button' class='fa fa-trash btn btn-danger btn-xs' onclick='deleteFeeDetails("+obj[1][i].recieveble_id+");'>Delete</button></td>";

                        if(obj[1][i].cancel == 1){
                            htm=htm+ "<td><button type='button' class='btn btn-success btn-xs' onclick='cancelFeeDetails("+obj[1][i].recieveble_id+", "+obj[1][i].cancel+");'>Proceed</button></td>";
                        }
                        else{
                            htm=htm+ "<td><button type='button' class='btn btn-danger btn-xs' onclick='cancelFeeDetails("+obj[1][i].recieveble_id+", "+obj[1][i].cancel+");'>Cancel</button></td>";
                        }
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
            load_closing_years();
        }
        // function to load recieveble amount details end
        
    </script>

    <script>
         function cancelFeeDetails(receiveble_id,cancel) {
            var status;
            let text = '';
            if(cancel===1){
                status=0;
                text = 'Do you want to cancel the payment?';
            }
            else{
                status=1;
                text = 'Do you want to proceed the payment?';
            }
            // alert(status);
            swal({
                title: "Are you sure?",
                text: text,
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "Yes!",
                cancelButtonText: "Cancel",
                closeOnConfirm: false,
                closeOnCancel: true
            },
               function (isConfirm) {
			if (isConfirm){
                var data = {
                action: 'cancel_payment',
                id: receiveble_id,
                status: status,
                // amount: $("#hdn_amount").val(),               
            };
            // load_overlay();
            // AJAX call
            $.ajax({
                type: "POST",
                url: "api/other_recieveble.php",
                data: data,
                success: function(response) {
                    $("#hdn_payment_id").val(0);
                    // close_overlay();
                    console.log('Updated:', response);
                    alertwarning(response);
                    loadData($('#hdn_current_page').val());
                    
                },
                error: function (xhr, status){
        
                        var msgObj = JSON.parse(xhr.responseText);
                        alerterror(msgObj, xhr);
                        $('#payment_form')[0].reset();
                        $("hdn_id").val(0);
                    
                    }
            });
                
                    
            }
		});   
        }

        function deleteFeeDetails(receiveble_id) {
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
                action: 'delete_payment',
                id: receiveble_id,
                // recieveble_id: $("#hdn_recieveble_id").val(),
                // amount: $("#hdn_amount").val(),               
            };
            load_overlay();
            // AJAX call
            $.ajax({
                type: "POST",
                url: "api/other_recieveble.php",
                data: data,
                success: function(response) {
                    $("#hdn_payment_id").val(0);
                    close_overlay();
                    console.log('deleted:', response);
                    alertwarning(response);
                    loadData($('#hdn_current_page').val());
                    
                },
                error: function(xhr, status, error) {
                    console.log('AJAX error:', status, error);
                }
            });
                
                    
            }
		});   
        }
        // function for popup to pay recieveble balance amount start
        function editPayment(id,date,head,amount,payable,particuler,flag){
            var balance = payable-amount;
            // $("#date").val(date);
            $("#selected_head").val(head);
            $("#amount").val(balance);
            $("#amount_paid").val(amount);
            $("#particuler").val(particuler);
            $("#selected_year").val(flag);
            $("#hdn_id").val(id);
            $('#paymentModel').modal('show');
          
        }
        // function for popup to pay recieveble balance amount end

        // function for close the popup to pay recieveble amount start
        function closeaddPayment(){
            $('#payment_form')[0].reset();
            $("hdn_id").val(0);
            $('#paymentModel').modal('toggle');
        }
        // functiom for close the popup to pay reciveble amount end

        function load_closing_years(){
            $.ajax({
   
                type: "POST",
                url: "api/other_recieveble.php",
                data: {
                action: 'load_closing_years',
                },
                success: function(data) {
                    var obj = jQuery.parseJSON(data);
                    // console.log(obj);
                                       
                    // var totalrows = obj[0].total_rows;
                    var htm="";
                    htm=htm+ "<div class='dropdown form-group'><select id='selected_year' class='form-control' disabled>";
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
        // function to save recieved amount details start
        function savePayment() {
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
                    action: 'save_payment',

                    date: $('#date').val(),
                    particuler: $('#particuler').val(),
                    amount: $('#amount').val(), 
                    head:$("#selected_head").val(),
                    group_id: $('#selected_group').val() || 2,
                    flag: $("#selected_year").val(),
                    transaction_type: $('#selected_transaction').val(),
                    id:$("#hdn_id").val(),      
                };
                load_overlay();
                // AJAX call
                $.ajax({
                    type: "POST",
                    url: "api/other_recieveble.php",
                    data: data,
                    success: function(response) {
                        console.log('saved:', response);
                        close_overlay();                    
                        closeaddPayment();
                        alertsuccess(response);
                        loadData($('#hdn_current_page').val());
                    },
                    error: function (xhr, status){
        
                        var msgObj = JSON.parse(xhr.responseText);
                        alerterror(msgObj, xhr);
                        $('#payment_form')[0].reset();
                        $("hdn_id").val(0);
                    
                    }
                });
            }
		    });
        }
        // function to save recieveble amount details end

        // function to inject heads dropdown to the popup for add recieved amount start
        function load_heads(){
            $.ajax({
   
                type: "POST",
                url: "api/other_recieveble.php",
                data: {
                action: 'load_heads',
                },
                success: function(data) {
                    var obj = jQuery.parseJSON(data);
                    console.log(obj);
                                       
                    // var totalrows = obj[0].total_rows;
                    var htm="";
                    htm=htm+ "<div class='dropdown form-group'><select id='selected_head' class='form-control' disabled>";
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
        // function to inject heads dropdown to  the popup for add recieved amount end
    
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
                    <h2>Other Recieveble</h2>
                </div>
                <!-- <div class="col-sm-4">
                    <div class="title-action">
                        <div class="ibox-tools">
                             <button type="button" class="btn btn-primary btn-xs" onclick="addPayment(0)">Add Payment</button>
                        </div>
                    </div>
                </div> -->
            </div>
            <!-- search bar ends -->
            
            <div class="wrapper wrapper-content animated fadeInRight" id="table_client">
                <!-- data injected Dynamically via ajax -->
            </div>
        </div>
       

         <!-- popup modal for add recieved amount start -->
        <div class="modal inmodal" id="paymentModel" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content animated bounceInRight">
                    <form method="POST" id="payment_form">
                        <div class="modal-body">
                             <div class="form-group"><label>Date</label><input type="date" id="date" name="date" value="<?php echo date('Y-m-d'); ?>" placeholder=""  class="form-control"></div>
                            <div class="form-group"><label>Particular</label><input type="text" id="particuler" name="particular" placeholder="Particular"  class="form-control" readonly></div>
                            <div class="form-group"><label>Amount To Pay</label><input type="text" id="amount" name="amount" placeholder="Amount"  class="form-control"></div>
                            <div class="form-group"><label>Amount Paid</label><input type="text" id="amount_paid" name="amount_paid" placeholder="Amount"  class="form-control" readonly></div>
                            <div class="form-group">
                                <label>Transaction Type</label>
                                <select class="dropdown form-group form-control" name="selected_transaction" id="selected_transaction">
                                    <?php foreach (TransactionType::all() as $key => $label): ?>
                                        <option value="<?= $key ?>"><?= htmlspecialchars($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div id="select_heads">
                                <!-- dropdown injected via ajax -->
                            </div>
                            <div id="select_from_year">
                                <!-- dropdown injected via ajax -->
                            </div>
                        </div>
                        
                        <div class="modal-footer">
                            <button type="button" class="btn btn-white" onclick="closeaddPayment();">Close</button>
                            <button type="button" class="btn btn-primary" onclick="savePayment();">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- popup modal for add recieved amount end -->
    </div>
       

    <!-- Mainly scripts -->
    <script src="../js/jquery-3.1.1.min.js"></script>
    <script src="../js/bootstrap.min.js"></script>
    <script src="../js/plugins/metisMenu/jquery.metisMenu.js"></script>
    <script src="../js/plugins/slimscroll/jquery.slimscroll.min.js"></script>

    <!-- Custom and plugin javascript -->
    <script src="../js/inspinia.js"></script>
    <script src="../js/plugins/pace/pace.min.js"></script>
    <script src="../js/loadingoverlay.min.js"></script>
    <script src="../app_js/date_picker_auto_init.js"></script>


</body>

</html>
