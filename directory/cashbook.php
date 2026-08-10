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
?>
<!DOCTYPE html>
<html>

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>CASH BOOK</title>

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
            loadData(); // Function to load data for a specific page       
        });  

        // function to search accounts between from and to date start
        function checkDetails(){
            if ($('#from_date').val().trim()=='') {
                alertwarning('Please enter a date.');
                return;
            }
            if ($('#to_date').val().trim()=='') {
                alertwarning('Please enter a date.');
                return;
            }    
            loadData();
        }
        // function to search accounts between from and to date end

        // function to load Payment detail start
        function loadData() {
            $.ajax({               
               type: "POST",
               url: "api/cashbook.php",
               data: {
                action: 'load_payment',
                // page: page, 
                from_date:$('#from_date').val(),
                to_date:$('#to_date').val(),
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
                    htm=htm+ "<h2>Payed And Payable</h2>";
                    htm=htm+ "<div class='ibox-tools'></div>";
                    htm=htm+ "</div>";
                    htm=htm+ "<div class='ibox-content'>";
                    htm=htm+ "<div class='table-responsive'>";
                    htm=htm+ "<table class='table table-striped'>";
                    htm=htm+ "<thead>";
                    htm=htm+ "<tr>";
                    htm=htm+ "<th>Payable Amount</th>";
                    htm=htm+ "<th>Paid Amount</th>";
                    htm=htm+ "<th>Pending</th>";
                    htm=htm+ "</tr>";
                    htm=htm+ "</thead>";
                    htm=htm+ "<tbody>";

                    for (var i = 0; i < obj[0].length; i++) {

                        htm=htm+ "<tr>";
                        htm=htm+ "<td>"+obj[0][i].payable_amount+"</td>";
                        htm=htm+ "<td>"+obj[0][i].paid_amount+"</td>";
                        htm=htm+ "<td>"+(obj[0][i].payable_amount - obj[0][i].paid_amount)+"</td>";
                        htm=htm+ "</tr>";
                    }                
                    htm=htm+ "</tbody>";
                    htm=htm+ "</table>";
                    htm=htm+ "</div>";

                    htm=htm+ "</div>";
                    htm=htm+ "</div>";
                    htm=htm+ "</div>";
                    htm=htm+ "</div>";

                    $('#table_payments').html(htm);
                },
                error: function(xhr, status, error) {
                   console.log('AJAX error: ', status, error);
                }
            });
            loadMenu();
            loadFeeData();
        }
        // function to load Payment details end

        // function to load the fees details start
        function loadFeeData() {
            $.ajax({               
               type: "POST",
               url: "api/cashbook.php",
               data: {
                action: 'load_fees',
                from_date:$('#from_date').val(),
                to_date:$('#to_date').val(),
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
                    htm=htm+ "<h2>Fees Recieved And Recieveble</h2>";
                    htm=htm+ "<div class='ibox-tools'></div>";
                    htm=htm+ "</div>";
                    htm=htm+ "<div class='ibox-content'>";
                    htm=htm+ "<div class='table-responsive'>";
                    htm=htm+ "<table class='table table-striped'>";
                    htm=htm+ "<thead>";
                    htm=htm+ "<tr>";
                    htm=htm+ "<th>Receiveble Amount</th>";
                    htm=htm+ "<th>Received Amount</th>";
                    htm=htm+ "<th>Balance</th>";
                    htm=htm+ "</tr>";
                    htm=htm+ "</thead>";
                    htm=htm+ "<tbody>";
                    
                    for (var i = 0; i < obj[0].length; i++) {

                        htm=htm+ "<tr>";
                        htm=htm+ "<td>"+obj[0][i].receivable_amount+"</td>";
                        htm=htm+ "<td>"+obj[0][i].received_amount+"</td>";
                        
                        htm=htm+ "<td>"+(obj[0][i].receivable_amount - obj[0][i].received_amount)+"</td>";

                        htm=htm+ "</tr>";
                        
                    }                
                    htm=htm+ "</tbody>";
                    htm=htm+ "</table>";
                    htm=htm+ "</div>";

                    htm=htm+ "</div>";
                    htm=htm+ "</div>";
                    htm=htm+ "</div>";
                    htm=htm+ "</div>";

                    $('#table_fees').html(htm);
                },
                error: function(xhr, status, error) {
                   console.log('AJAX error: ', status, error);
                }
            });
            // loadBalance();
            loadOtherData();
        }
        // function to load the fees details end

        // function to load the fees details start
        function loadOtherData() {
            $.ajax({               
               type: "POST",
               url: "api/cashbook.php",
               data: {
                action: 'load_other_receiveble',
                from_date:$('#from_date').val(),
                to_date:$('#to_date').val(),
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
                    htm=htm+ "<h2>Other Recieved And Recieveble</h2>";
                    htm=htm+ "<div class='ibox-tools'></div>";
                    htm=htm+ "</div>";
                    htm=htm+ "<div class='ibox-content'>";
                    htm=htm+ "<div class='table-responsive'>";
                    htm=htm+ "<table class='table table-striped'>";
                    htm=htm+ "<thead>";
                    htm=htm+ "<tr>";
                    htm=htm+ "<th>Receiveble Amount</th>";
                    htm=htm+ "<th>Received Amount</th>";
                    htm=htm+ "<th>Balance</th>";
                    htm=htm+ "</tr>";
                    htm=htm+ "</thead>";
                    htm=htm+ "<tbody>";
                    
                    for (var i = 0; i < obj[0].length; i++) {

                        htm=htm+ "<tr>";
                        htm=htm+ "<td>"+obj[0][i].receivable_amount+"</td>";
                        htm=htm+ "<td>"+obj[0][i].received_amount+"</td>";
                        
                        htm=htm+ "<td>"+(obj[0][i].receivable_amount - obj[0][i].received_amount)+"</td>";

                        htm=htm+ "</tr>";
                        
                    }                
                    htm=htm+ "</tbody>";
                    htm=htm+ "</table>";
                    htm=htm+ "</div>";

                    htm=htm+ "</div>";
                    htm=htm+ "</div>";
                    htm=htm+ "</div>";
                    htm=htm+ "</div>";

                    $('#table_other').html(htm);
                },
                error: function(xhr, status, error) {
                   console.log('AJAX error: ', status, error);
                }
            });
            loadBalance();
        }
        // function to load the fees details end

        // function to load the cash in hands start
        function loadBalance() {
            $.ajax({               
               type: "POST",
               url: "api/cashbook.php",
               data: {
                action: 'load_balance',
                from_date:$('#from_date').val(),
                to_date:$('#to_date').val(),
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
                    htm=htm+ "<h2>Cash In Hand</h2>";
                    htm=htm+ "<div class='ibox-tools'></div>";
                    htm=htm+ "</div>";
                    htm=htm+ "<div class='ibox-content'>";
                    htm=htm+ "<div class='table-responsive'>";
                    htm=htm+ "<table class='table table-striped'>";
                    htm=htm+ "<thead>";
                    htm=htm+ "<tr>";
                    htm=htm+ "<th>Opening Balance</th>";
                    htm=htm+ "<th>Fees Recieved</th>";
                    htm=htm+ "<th>Other Recieved</th>";
                    htm=htm+ "<th>Wallet</th>";
                    htm=htm+ "<th>Total Recieved</th>";
                    htm=htm+ "<th>Paid Amount</th>";
                    htm=htm+ "<th>Balance</th>";
                    htm=htm+ "</tr>";
                    htm=htm+ "</thead>";
                    htm=htm+ "<tbody>";
                    let totalAmount = 0;
                    for (var i = 0; i < obj[0].length; i++) {

                        htm=htm+ "<tr>";
                        htm=htm+ "<td>"+obj[0][i].opening_balance+"</td>";
                        htm=htm+ "<td>"+obj[0][i].received_amount+"</td>";
                        htm=htm+ "<td>"+obj[0][i].other_recieved+"</td>";
                        htm=htm+ "<td>"+obj[0][i].wallet_balance+"</td>";

                        let rowTotal =
                        (parseFloat(obj[0][i].opening_balance) || 0) +
                        (parseFloat(obj[0][i].other_recieved) || 0) +
                        (parseFloat(obj[0][i].received_amount) || 0) +
                        (parseFloat(obj[0][i].wallet_balance) || 0);

                        htm += "<td>" + rowTotal + "</td>";

                        totalAmount += rowTotal;
                    
                        htm=htm+ "<td>"+obj[0][i].paid_amount+"</td>";
                        htm=htm+ "<td>"+(rowTotal - obj[0][i].paid_amount)+"</td>";
                        htm=htm+ "</tr>";
                        
                    }                
                    htm=htm+ "</tbody>";
                    htm=htm+ "</table>";
                    htm=htm+ "</div>";

                    htm=htm+ "</div>";
                    htm=htm+ "</div>";
                    htm=htm+ "</div>";
                    htm=htm+ "</div>";

                    $('#total_balance').html(htm);
                },
                error: function(xhr, status, error) {
                   console.log('AJAX error: ', status, error);
                }
            });
        }
        // function to load the cash in hand end

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
                <div class="col-sm-3">
                    <h2>cashbook</h2>
                </div>
                <div class="col-sm-3">
                    <div class="form-group"><label>From Date</label><input type="date" id="from_date" name="from_date" placeholder="From Date"  class="form-control"></div>
                </div>
                <div class="col-sm-3">
                    <div class="form-group"><label>To Date</label><input type="date" id="to_date" name="to_date" placeholder="To Date"  class="form-control"></div>
                </div>
                <div class="col-sm-3">
                    <div class="title-action">
                        <div class="ibox-tools">
                            <button type="button" class="btn btn-primary" onclick="checkDetails()">Check Now</button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- search bar ends -->

            <div class="wrapper wrapper-content animated fadeInRight" id="table_fees">
                <!-- data of fees injected Dynamically via ajax -->
            </div>
             

            <div class="wrapper wrapper-content animated fadeInRight" id="table_other">
                <!-- data of fees injected Dynamically via ajax -->
            </div>

            <div class="wrapper wrapper-content animated fadeInRight" id="table_payments">
                <!-- data of payments injected Dynamically via ajax -->
            </div>

            

            <div class="wrapper wrapper-content animated fadeInRight" id="total_balance">
                <!-- data of cash in hand injected Dynamically via ajax -->
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
