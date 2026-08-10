<?php
session_start();
?>
<!DOCTYPE html>
<html>

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Wallet Ledger</title>

    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../font-awesome/css/font-awesome.css" rel="stylesheet">

    <link href="../css/animate.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <link href="../css/custom_modern.css" rel="stylesheet">
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <link href="../css/plugins/sweetalert/sweetalert.css" rel="stylesheet">
    <script src="../js/plugins/sweetalert/sweetalert.min.js"></script>
    <script src="../app_js/sweetalert-finez.js"></script>
    
    <style>
        .settings-card-wrapper {
            background-color: var(--card-bg, #ffffff);
            border-radius: var(--border-radius-lg, 24px);
            border: 1px solid var(--border-color, #e2e8f0);
            box-shadow: var(--shadow-md, 0 10px 30px -10px rgba(99, 102, 241, 0.08));
            padding: 30px;
            margin-top: 24px;
            transition: all 0.3s ease;
        }

        .dark-theme .settings-card-wrapper {
            border-color: rgba(255, 255, 255, 0.06);
        }

        /* Top summary balance widget */
        .overall-wallet-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.02) 0%, rgba(79, 70, 229, 0.02) 100%);
            border: 1px solid var(--border-color, #e2e8f0);
            border-radius: var(--border-radius-lg, 24px);
            padding: 24px 30px;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .dark-theme .overall-wallet-header {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.08) 0%, rgba(79, 70, 229, 0.08) 100%);
            border-color: rgba(255, 255, 255, 0.06);
        }

        .widget-info-block {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .widget-icon-circle {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: var(--primary-gradient, linear-gradient(135deg, #6366f1 0%, #4f46e5 100%));
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25);
        }

        .widget-text-details h2 {
            margin: 0 !important;
            font-weight: 800;
            letter-spacing: -0.5px;
            color: var(--text-primary, #0f172a);
        }

        .widget-text-details p {
            margin: 4px 0 0 0 !important;
            color: var(--text-muted, #475569);
            font-size: 13.5px !important;
            font-weight: 500;
        }

        .balance-amount-card {
            background: var(--card-bg, #ffffff);
            border: 1px solid var(--border-color, #e2e8f0);
            border-radius: var(--border-radius-md, 16px);
            padding: 16px 24px;
            min-width: 240px;
            box-shadow: var(--shadow-sm, 0 1px 2px 0 rgba(0, 0, 0, 0.05));
            display: flex;
            flex-direction: column;
            gap: 4px;
            text-align: right;
        }

        .dark-theme .balance-amount-card {
            border-color: rgba(255, 255, 255, 0.06);
        }

        .balance-amount-card .lbl {
            font-size: 12px !important;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 700;
            color: var(--text-muted, #475569);
        }

        .balance-amount-card .val {
            font-size: 26px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        /* Type Badges */
        .badge-type-custom {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            border-radius: 8px;
            font-size: 12px !important;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .badge-type-custom.credit {
            background-color: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }

        .badge-type-custom.debit {
            background-color: rgba(239, 44, 44, 0.1);
            color: #ef4444;
        }

        .amount-text-custom {
            font-weight: 700;
            font-size: 15px !important;
        }

        .amount-text-custom.credit {
            color: #10b981;
        }

        .amount-text-custom.debit {
            color: #ef4444;
        }

        /* Pagination buttons override */
        .text-center > .btn-white {
            border-radius: 10px !important;
            padding: 8px 14px !important;
            margin: 0 3px;
            font-weight: 600 !important;
            font-size: 14px !important;
            border: 1px solid var(--border-color, #e2e8f0) !important;
            background: var(--card-bg, #ffffff) !important;
            color: var(--text-muted, #475569) !important;
            box-shadow: none !important;
            transition: all 0.2s ease;
        }

        .text-center > .btn-white:hover {
            border-color: var(--primary-color, #4f46e5) !important;
            color: var(--primary-color, #4f46e5) !important;
            background-color: rgba(99, 102, 241, 0.04) !important;
            transform: translateY(-1px);
        }

        .text-center > .btn-white.active {
            background: var(--primary-gradient, linear-gradient(135deg, #6366f1 0%, #4f46e5 100%)) !important;
            color: #ffffff !important;
            border-color: transparent !important;
            box-shadow: 0 4px 10px rgba(99, 102, 241, 0.25) !important;
        }

        .dark-theme .text-center > .btn-white {
            border-color: rgba(255, 255, 255, 0.08) !important;
            background: rgba(255, 255, 255, 0.02) !important;
            color: #94a3b8 !important;
        }

        .dark-theme .text-center > .btn-white.active {
            color: #ffffff !important;
        }
    </style>
    
    <script>
        $(document).ready(function() {          
            loadData(1); // Function to load data for a specific page       
        });  
        
        // function to load all payment details start
        function loadData(page) {
            $('#hdn_current_page').val(page); //used for Status Update function
            console.log("Loading data for page:", page);
            $.ajax({               
               type: "POST",
               url: "api/wallet_transactions.php",
               data: {
               action: 'load_data',
               page: page,
               },
                success: function(data) {

                    var obj = jQuery.parseJSON(data);
                    console.log(obj);
                                       
                    var totalrows = obj[0].total_rows;
                    var htm="";
                    
                    htm=htm+ "<div class='col-lg-12'>";
                    htm=htm+ "<div class='ibox float-e-margins' style='margin-bottom:0;'>";
                    htm=htm+ "<div class='ibox-content'>";
                    htm=htm+ "<div class='table-responsive'>";
                    htm=htm+ "<table class='table table-striped table-hover'>";
                    htm=htm+ "<thead>";
                    htm=htm+ "<tr>";
                    htm=htm+ "<th>Member Name</th>";
                    htm=htm+ "<th>Date</th>";
                    htm=htm+ "<th style='text-align:right;'>Amount</th>";
                    htm=htm+ "<th>Transaction Type</th>";
                    htm=htm+ "</tr>";
                    htm=htm+ "</thead>";
                    htm=htm+ "<tbody>";
                    for (var i = 0; i < obj[1].length; i++) {
                        var typeClass = obj[1][i].type === 'credit' ? 'credit' : 'debit';
                        var typeText = obj[1][i].type === 'credit' ? 'Credit' : 'Debit';
                        var typeIcon = obj[1][i].type === 'credit' ? 'fa-arrow-down' : 'fa-arrow-up';
                        
                        htm=htm+ "<tr>";
                        htm=htm+ "<td style='font-weight:600; color:var(--text-primary);'>"+obj[1][i].first_name+" "+obj[1][i].middle_name+" "+obj[1][i].last_name+"</td>";
                        htm=htm+ "<td><i class='fa fa-calendar' style='color:#94a3b8; margin-right:6px;'></i> "+obj[1][i].date+"</td>";
                        htm=htm+ "<td style='text-align:right;' class='amount-text-custom " + typeClass + "'>₹ " + parseFloat(obj[1][i].amount).toFixed(2) + "</td>";
                        htm=htm+ "<td><span class='badge-type-custom " + typeClass + "'><i class='fa " + typeIcon + "'></i> " + typeText + "</span></td>";
                        htm=htm+ "</tr>";       
                    }                
                    htm=htm+ "</tbody>";
                    htm=htm+ "</table>";
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
            load_wallet_amount();
            loadMenu();
        }
        
        function load_wallet_amount(){
            $.ajax({
                type: "POST",
                url: "api/wallet_transactions.php",
                data: {
                action: 'load_wallet_amount',
                },
                success: function(data) {
                    var obj = jQuery.parseJSON(data);
                    console.log(obj);
                    var htm="";                
                    for (var i = 0; i < obj[0].length; i++) {
                        var bal = parseFloat(obj[0][i].wallet_balance);
                        var balClass = bal >= 0 ? 'text-success' : 'text-danger';
                        htm=htm+ "<span class='lbl'><i class='fa fa-google-wallet'></i> Total Pool Balance</span>";
                        htm=htm+ "<span class='val " + balClass + "'>₹ "+bal.toFixed(2)+"</span>";
                    }     
                     
                    $('#wallet_balance').html(htm);  
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
            <div class="row wrapper border-bottom white-bg page-heading" style="padding: 20px 30px; border-bottom: 1px solid var(--border-color, #e2e8f0) !important;">
                <div class="col-sm-4">
                    <h2 style="font-weight: 800; font-size: 24px; letter-spacing: -0.5px; margin: 0 !important; color: var(--text-primary, #0f172a);">Wallet Ledger</h2>
                </div>
            </div>
            <!-- search bar ends -->
            
            <div class="settings-card-wrapper">
                <!-- Header Wallet Summary -->
                <div class="overall-wallet-header">
                    <div class="widget-info-block">
                        <div class="widget-icon-circle">
                            <i class="fa fa-university"></i>
                        </div>
                        <div class="widget-text-details">
                            <h2>Overall Wallet Ledger</h2>
                            <p>Global view of all member deposits and debit transactions</p>
                        </div>
                    </div>
                    
                    <div class="balance-amount-card" id="wallet_balance">
                        <!-- Loaded dynamically -->
                    </div>
                </div>
                
                <div class="wrapper wrapper-content animated fadeInRight" id="table_client" style="padding: 0;">
                    <!-- data injected Dynamically via ajax -->
                </div>
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
