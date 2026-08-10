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
include '../app_common/enums.php';
?>
<!DOCTYPE html>
<html>

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Payments</title>

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
            load_groups();

            // Track previous paid values for smart auto-fill
            var prevPaid = '';
            var prevPaidDate = '';

            $('#paymentModel').on('shown.bs.modal', function() {
                prevPaid = $('#paid').val();
                prevPaidDate = $('#paid_date').val();
            });

            $('#paid').on('input change', function() {
                var currentPaid = $(this).val();
                var currentPayable = $('#payable').val();
                if (currentPayable === '' || currentPayable === prevPaid) {
                    $('#payable').val(currentPaid);
                }
                prevPaid = currentPaid;
            });

            $('#paid_date').on('input change', function() {
                var currentDate = $(this).val();
                var currentPayableDate = $('#payable_date').val();
                if (currentPayableDate === '' || currentPayableDate === prevPaidDate) {
                    $('#payable_date').val(currentDate);
                }
                prevPaidDate = currentDate;
            });
        });  

        function load_groups() {
            $.post('api/attendance.php', { action: 'load_groups' }, function(data) {
                try {
                    let parsed = typeof data === 'string' ? JSON.parse(data) : data;
                    let groups = Array.isArray(parsed[0]) ? parsed[0] : (Array.isArray(parsed) ? parsed : []);
                    let htm = '<div class="form-group"><label>Group</label><select id="selected_group" class="form-control">';
                    groups.forEach(function(g) {
                        htm += '<option value="' + g.id + '">' + g.name + '</option>';
                    });
                    htm += '</select></div>';
                    $('#select_group_container').html(htm);
                } catch(e) {}
            });
        }

        // function to load payment detail start
        function loadData(page) {
            $('#hdn_current_page').val(page); //used for Status Update function
            console.log("Loading data for page:", page);
            $.ajax({               
               type: "POST",
               url: "api/payment.php",
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
                    htm=htm+ "<th>No.</th>";
                    htm=htm+ "<th>Date</th>";
                    htm=htm+ "<th>Head</th>";
                    htm=htm+ "<th>Particuler</th>";
                    htm=htm+ "<th>Amount</th>";
                    // htm=htm+ "<th>Type</th>";
                    htm=htm+ "<th>Action</th>";
                    htm=htm+ "</tr>";
                    htm=htm+ "</thead>";
                    htm=htm+ "<tbody>";
                    for (var i = 0; i < obj[1].length; i++) {
                        var j= i+1;
                        var slno=((page-1)*8)+j;
                        var escParticuler = obj[1][i].particuler ? obj[1][i].particuler.replace(/'/g, "\\'").replace(/"/g, "&quot;") : "";
                        htm=htm+ "<tr>";
                        htm=htm+ "<td>"+slno+"</td>";
                        htm=htm+ "<td>"+obj[1][i].date+"</td>";
                        htm=htm+ "<td>"+obj[1][i].name+"</td>";
                        htm=htm+ "<td>"+obj[1][i].particuler+"</td>";
                        htm=htm+ "<td>"+obj[1][i].amount+"</td>";
                        // htm=htm+ "<td>"+obj[1][i].type+"</td>";
                        htm=htm+ "<td>";
                        if (obj[1][i].bill_photo) {
                            htm=htm+ "<button type='button' class='btn-custom-action btn-custom-invoice' onclick='window.open(\"../image_upload/payments/" + obj[1][i].bill_photo + "\", \"_blank\");'><i class='fa fa-eye'></i> Invoice</button>";
                        } else {
                            htm=htm+ "<button type='button' class='btn-custom-action btn-custom-invoice' disabled title='No bill uploaded'><i class='fa fa-eye'></i> Invoice</button>";
                        }
                        htm=htm+ "<button type='button' class='btn-custom-action btn-custom-edit' onclick='editPayment("+obj[1][i].id+",\"" +obj[1][i].payable_date+ "\",\"" +obj[1][i].date+ "\",\"" +escParticuler+ "\",\"" +obj[1][i].payable+ "\",\"" +obj[1][i].amount+ "\",\"" +obj[1][i].head+ "\",\"" +obj[1][i].flag+ "\",\"" +obj[1][i].transaction_type+ "\");'><i class='fa fa-edit'></i> Edit</button>";
                        htm=htm+ "<button type='button' class='btn-custom-action btn-custom-delete' onclick='deleteFeeDetails("+obj[1][i].id+","+obj[1][i].payable_id+",\"" +obj[1][i].amount+ "\");'><i class='fa fa-trash'></i> Delete</button></td>";
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
        // function to load payment details end
        
    </script>

    <script>

        // function for popup to edit Payment details  start
        function editPayment(id, payable_date, paid_date, particuler, payable, paid, head, flag, transaction_type){
          
            $("#hdn_id").val(id);
            $("#paid_date").val(paid_date);
            $("#particuler").val(particuler);
            $("#paid").val(paid);
            $("#selected_head").val(head);
            $("#selected_year").val(flag);
            $("#selected_transaction").val(transaction_type);

            // If payable is not given (null, undefined, 0, or empty), take paid as payable
            if (!payable || payable == 0 || payable == 'null' || payable == '') {
                $("#payable").val(paid);
            } else {
                $("#payable").val(payable);
            }

            // If payable_date is not given, default to paid_date
            if (!payable_date || payable_date == '0000-00-00' || payable_date == 'null' || payable_date == '') {
                $("#payable_date").val(paid_date);
            } else {
                $("#payable_date").val(payable_date);
            }

            $('#paymentModel').modal('show');
          
        }
        // function for popup to edit Payment details end

        //function for poup to add new payment  start
        function addPayment(){
            $('#paymentModel').modal('show');
        }
        //function for pop to add new payment end

        //function for close the popup for payment start
        function closeaddPayment(){
            $('#payment_form')[0].reset();
            $("#hdn_id").val(0);
            $('#paymentModel').modal('toggle');
        }
        // function for close the popup for payment end

        //function to save payment details start
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
                var formData = new FormData();
                formData.append('action', 'save_payment');
                formData.append('payable_date', $('#payable_date').val());
                formData.append('paid_date', $('#paid_date').val());
                formData.append('particuler', $('#particuler').val());
                formData.append('payable', $('#payable').val());
                formData.append('paid', $('#paid').val());
                formData.append('head', $("#selected_head").val());
                formData.append('group_id', $('#selected_group').val() || 2);
                formData.append('flag', $("#selected_year").val());
                formData.append('transaction_type', $('#selected_transaction').val());
                formData.append('id', $("#hdn_id").val());
                
                var fileInput = document.getElementById('bill_photo');
                if (fileInput && fileInput.files.length > 0) {
                    formData.append('bill_photo', fileInput.files[0]);
                }
                
                load_overlay();
                // AJAX call
                $.ajax({
                    type: "POST",
                    url: "api/payment.php",
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        close_overlay();
                        console.log('saved:', response);                    
                        closeaddPayment();
                        alertsuccess(response);
                        loadData($('#hdn_current_page').val());
                    },
                    error: function (xhr, status){
                        close_overlay();
                        var msg = xhr.responseText || 'Error processing payment';
                        try {
                            var msgObj = JSON.parse(xhr.responseText);
                            if (msgObj && typeof msgObj === 'object') {
                                msg = msgObj.Message || msgObj.message || msg;
                            }
                        } catch(e) {}
                        alerterror(msg);
                        $('#payment_form')[0].reset();
                        $("#hdn_id").val(0);
                    }
                });
            }
		    });
        }
        // function to save payment details end

        // function to delete payment start
        function deleteFeeDetails(id,payable_id,amount) {
            $("#hdn_payment_id").val(id);
            $("#hdn_payable_id").val(payable_id);
            $("#hdn_amount").val(amount)
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
                        action: 'delete_payment',
                        id: $("#hdn_payment_id").val(),
                        payable_id: $("#hdn_payable_id").val(),
                        amount: $("#hdn_amount").val(),     
                    };
                    load_overlay();
                    // AJAX call
                    $.ajax({
                        type: "POST",
                        url: "api/payment.php",
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
        // function to delete payment end
        
        //function to inject heads dropdwon to the popup for add payment start
        function load_heads(){
            $.ajax({
   
                type: "POST",
                url: "api/payment.php",
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
        // function to inject heads dropdown to the popup for add payment end
    
        function load_closing_years(){
            $.ajax({
   
                type: "POST",
                url: "api/payment.php",
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        .btn-custom-action {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            font-weight: 500;
            border-radius: 6px;
            padding: 6px 14px;
            font-size: 12px;
            line-height: 1.5;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s ease-in-out;
            border: none;
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(0,0,0,0.06);
            color: #ffffff !important;
            margin-right: 6px;
            margin-bottom: 6px;
        }

        .btn-custom-action i {
            font-size: 12px;
        }

        .btn-custom-invoice {
            background: linear-gradient(135deg, #10b981, #059669);
        }
        .btn-custom-invoice:hover {
            background: linear-gradient(135deg, #059669, #047857);
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(16, 185, 129, 0.2);
        }
        .btn-custom-invoice:active {
            transform: translateY(0);
        }
        .btn-custom-invoice:disabled {
            background: #cbd5e1 !important;
            opacity: 0.6;
            cursor: not-allowed;
            box-shadow: none;
            transform: none;
        }

        .btn-custom-edit {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
        }
        .btn-custom-edit:hover {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(59, 130, 246, 0.2);
        }
        .btn-custom-edit:active {
            transform: translateY(0);
        }

        .btn-custom-delete {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }
        .btn-custom-delete:hover {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(239, 68, 68, 0.2);
        }
        .btn-custom-delete:active {
            transform: translateY(0);
        }
    </style>
</head>

<body>
    <!-- hidden values start -->
    <input type="hidden" id="hdn_current_page"  value="0">
    <input type="hidden" id="hdn_id"  value="0">
    <input type="hidden" id="hdn_payment_id"  value="0">
    <input type="hidden" id="hdn_payable_id"  value="0">
    <input type="hidden" id="hdn_amount"  value="0">

    <!-- hidden values end -->

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
                    <h2>Payments</h2>
                </div>
                <div class="col-sm-4">
                    <div class="title-action">
                        <div class="ibox-tools">
                             <button type="button" class="btn btn-primary btn-xs" onclick="addPayment(0)">Add Payment</button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- search bar ends -->
            
            <div class="wrapper wrapper-content animated fadeInRight" id="table_client">
                <!-- data injected Dynamically via ajax -->
            </div>
        </div>
       

        <!-- popup modal for add payment starts -->
        <div class="modal inmodal" id="paymentModel" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content animated bounceInRight">
                    <form method="POST" id="payment_form">
                        <div class="modal-body">
                            <div class="form-group"><label>Payable Date</label><input type="date" id="payable_date" name="payable_date" value="<?php echo date('Y-m-d'); ?>" placeholder=""  class="form-control"></div>
                            <div class="form-group"><label>Paid Date</label><input type="date" id="paid_date" name="paid_date" value="<?php echo date('Y-m-d'); ?>" placeholder=""  class="form-control"></div>
                            <div class="form-group"><label>Particular</label><input type="text" id="particuler" name="particular" placeholder="Particular"  class="form-control"></div>
                            <div class="form-group"><label>Payable</label><input type="number" id="payable" name="payable" placeholder="payable"  class="form-control"></div>
                            <div class="form-group"><label>Paid</label><input type="number" id="paid" name="paid" placeholder="Paid"  class="form-control"></div>
                            <div class="form-group">
                                <label>Transaction Type</label>
                                <select class="dropdown form-group form-control" name="selected_transaction" id="selected_transaction">
                                    <option value="0">Transaction Type</option>
                                    <?php foreach (TransactionType::all() as $key => $label): ?>
                                        <option value="<?= $key ?>"><?= htmlspecialchars($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div id="select_group_container">
                                <!-- dropdown injected via ajax -->
                            </div>
                            <div id="select_from_year">
                                <!-- dropdown injected via ajax -->
                            </div>
                            <div id="select_heads">
                                <!-- dropdown injected via ajax -->
                            </div>
                            <div class="form-group" style="margin-top: 15px;">
                                <label style="display: block; font-weight: 600; margin-bottom: 6px;">Upload Bill Photo (Optional)</label>
                                <div class="custom-file" style="position: relative;">
                                    <input type="file" id="bill_photo" name="bill_photo" class="form-control" accept="image/*,application/pdf" style="padding: 6px 12px; border-radius: 8px;">
                                </div>
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
        <!-- popup modal for add payment ends -->
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
