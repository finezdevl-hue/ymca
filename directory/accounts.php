<?php
    session_start();
    $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html>

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Accounts</title>

    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../font-awesome/css/font-awesome.css" rel="stylesheet">

    <link href="../css/animate.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <link href="../css/plugins/sweetalert/sweetalert.css" rel="stylesheet">
    <script src="../js/plugins/sweetalert/sweetalert.min.js"></script>
    <script src="../app_js/sweetalert-finez.js"></script>
    <script>
        // Function to read query string params
        function getParameterByName(name) {
            let url = window.location.href;
            name = name.replace(/[\[\]]/g, "\\$&");
            let regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
                results = regex.exec(url);
            if (!results) return null;
            if (!results[2]) return '';
            return decodeURIComponent(results[2].replace(/\+/g, " "));
        }
        $(document).ready(function() {          
            loadData(1); // Function to load data for a specific page       
        });  
        
        // function to search the fee by month start
        function searchDate(){
            if ($('#date_search').val().trim()=='') {
                alertwarning('Please enter a date.');
                return;
            }       
            loadData(1);
        }
        // function to search the fee by month end

        // function to load all fee details of the member who logged in starts
        function loadData(page) {

            // let login_id = getParameterByName("login_id");
            // let user_id  = getParameterByName("user_id");
            // let name     = getParameterByName("name");
            // let email    = getParameterByName("email");
            // alert(login_id);
            $('#hdn_current_page').val(page); //used for Status Update function
            console.log("Loading data for page:", page);
            $.ajax({               
                type: "POST",
                 url: "api/accounts.php",
                data: {
                    action: 'load_data',
                    page: page,
                    // user_id: user_id,
                    // login_id:login_id,
                    val:$('#date_search').val(),
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
                    htm=htm+ "<h3>Payments Against Payable<h3>";
                    htm=htm+ "<div class='ibox-tools'></div>";
                    htm=htm+ "</div>";
                    htm=htm+ "<div class='ibox-content'>";
                    htm=htm+ "<div class='table-responsive'>";
                    htm=htm+ "<table class='table table-striped'>";
                    htm=htm+ "<thead>";
                    htm=htm+ "<tr>";
                    htm=htm+ "<th>SlNo</th>";
                    htm=htm+ "<th>Date</th>";
                    htm=htm+ "<th>Payable</th>";
                    htm=htm+ "<th>Paid</th>";
                    htm=htm+ "<th>Head</th>";
                    htm=htm+ "<th>Discription</th>";
                    htm=htm+ "</tr>";
                    htm=htm+ "</thead>";
                    htm=htm+ "<tbody>";
                    for (var i = 0; i < obj[1].length; i++) {
                        var j= i+1;
                        var slno=((page-1)*8)+j
                        htm=htm+ "<tr>";
                        htm=htm+ "<td>"+slno+"</td>";
                        htm=htm+ "<td>"+obj[1][i].date+"</td>";
                        htm=htm+ "<td>"+obj[1][i].receivable+"</td>";
                        htm=htm+ "<td>"+obj[1][i].received+"</td>";
                        htm=htm+ "<td>"+obj[1][i].head+"</td>";
                        htm=htm+ "<td>"+obj[1][i].discription+"</td>";
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
            load_pending_payment();
            loadPaymentHistory();
        }
        // function to load all fee details of the member who logged in ends

        // function to load pending balance amount strat
        function load_pending_payment(){
            $.ajax({
   
                type: "POST",
                url: "api/accounts.php",
                data: {
                    action: 'load_pending_payment',
                    val:$('#date_search').val(),
                },
                success: function(data) {
                    var obj = jQuery.parseJSON(data);
                    console.log(obj);
                    var htm="";
                   
                    htm=htm+ "<div class='row'>";

                    htm=htm+ "<div class='col-lg-12'>";
                    htm=htm+ "<div class='ibox float-e-margins'>";
                    htm=htm+ "<div class='ibox-title'>";
                    htm=htm+ "<h3>Summary<h3>";
                    htm=htm+ "<div class='ibox-tools'></div>";
                    htm=htm+ "</div>";
                    htm=htm+ "<div class='ibox-content'>";
                    htm=htm+ "<div class='table-responsive'>";
                    htm=htm+ "<table class='table table-striped'>";
                    htm=htm+ "<thead>";
                    htm=htm+ "<tr>";
                    htm=htm+ "<th>Payable</th>";
                    htm=htm+ "<th>Paid</th>";
                    htm=htm+ "<th>Pending</th>";
                    htm=htm+ "<th>Wallet</th>";
                    htm=htm+ "</tr>";
                    htm=htm+ "</thead>";
                    htm=htm+ "<tbody>";
                    for (var i = 0; i < obj[0].length; i++) {
                        htm=htm+ "<tr>";
                        // htm=htm+ "<h5>Payable: "+obj[0][i].receivable_fees+"    Paid: "+obj[0][i].received_fees+"</h5>";
                        // htm=htm+ "<h5 style='color: red;'> Pending Balance: " + (obj[0][i].receivable_fees - obj[0][i].received_fees) + "</h5>";
                        htm=htm+ "<td>"+obj[0][i].receivable_fees+"</td>";
                        htm=htm+ "<td>"+obj[0][i].received_fees+"</td>";
                        htm = htm + "<td>" + (obj[0][i].receivable_fees - obj[0][i].received_fees) + "</td>";
                        htm=htm+ "<td>"+obj[0][i].wallet_balance+"</td>";
                       
                        htm=htm+ "</tr>";
                        // htm=htm+ "</tr>";
                    }                
                    htm=htm+ "</tbody>";
                    htm=htm+ "</table>";
                    htm=htm+ "</div>";

                    htm=htm+ "</div>";
                    htm=htm+ "</div>";
                    htm=htm+ "</div>";
                    htm=htm+ "</div>";
                   
                 
                    $('#pending_balace_table').html(htm);  
                   
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error: ', status, error);
                }
            });
        }
        // function to load pending balance amount end

        function loadPaymentHistory() {
            
            
            $.ajax({               
                type: "POST",
                 url: "api/accounts.php",
                data: {
                    action: 'payments_history_data',
                   
                    val:$('#date_search').val(),
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
                    htm=htm+ "<h3>Payment History</h3>";
                    htm=htm+ "<div class='ibox-tools'></div>";
                    htm=htm+ "</div>";
                    htm=htm+ "<div class='ibox-content'>";
                    htm=htm+ "<div class='table-responsive'>";
                    htm=htm+ "<table class='table table-striped'>";
                    htm=htm+ "<thead>";
                    htm=htm+ "<tr>";
                    // htm=htm+ "<th>SlNo</th>";
                    htm=htm+ "<th>Date</th>";
                    htm=htm+ "<th>Amount</th>";
                    htm=htm+ "<th>Wallet</th>";
                    htm=htm+ "<th>Type</th>";
                    htm=htm+ "</tr>";
                    htm=htm+ "</thead>";
                    htm=htm+ "<tbody>";
                    for (var i = 0; i < obj[0].length; i++) {
                        // var j= i+1;
                        // var slno=((page-1)*8)+j
                        htm=htm+ "<tr>";
                        // htm=htm+ "<td>"+slno+"</td>";
                        htm=htm+ "<td>"+obj[0][i].date+"</td>";
                        htm=htm+ "<td>"+obj[0][i].payment+"</td>";
                        // htm=htm+ "<td>"+obj[0][i].iswallet+"</td>";
                        // htm=htm+ "<td>"+obj[0][i].transaction_type+"</td>";
                        if(obj[0][i].iswallet==1){
                            htm=htm+ "<td>Yes</td>";
                        }
                        else{
                            htm=htm+ "<td>No</td>";
                        }
                        if(obj[0][i].transaction_type==1){
                            htm=htm+ "<td>Cash</td>";
                        }
                        else{
                            htm=htm+ "<td>Cash</td>";
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

                    $('#table_payment_history').html(htm);
                    // var htmpage= paginate(totalrows,page);
                    // $('#table_client').append(htmpage);
                },
                error: function(xhr, status, error) {
                   console.log('AJAX error: ', status, error);
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
                <div class="col-sm-4">
                    <div class="search-form">
                        <h2>Payment History</h2>
                        <form action="index.html" method="get">
                            <div class="input-group">
                                <input type="month" placeholder="Search" id="date_search" name="date_search" class="form-control">
                                <div class="input-group-btn">
                                    <button class="btn btn-white" onclick="searchDate()" type="button">Search</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <!-- search bar ends -->

            <div class="wrapper wrapper-content animated fadeInRight" id="pending_balace_table">
                <!-- data injected Dynamically via ajax -->
            </div>
            
            <!-- section for fee details start -->
            <div class="wrapper wrapper-content animated fadeInRight" id="table_client">
                <!-- data injected Dynamically via ajax -->
            </div>
            <!-- section for fee details end -->

            <div class="wrapper wrapper-content animated fadeInRight" id="table_payment_history">
                <!-- data injected Dynamically via ajax -->
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
