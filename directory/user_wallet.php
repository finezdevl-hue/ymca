<?php
session_start();

include '../app_common/db_connect.php';
$client_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$balance = 0.00;
if ($client_id > 0) {
    $bal_res = app_exec_query("SELECT SUM(CASE WHEN type='credit' THEN amount WHEN type='debit' THEN -amount ELSE 0 END) AS balance FROM tbl_wallet WHERE client_id = " . $client_id);
    if ($bal_res && $row = $bal_res->fetch_assoc()) {
        $balance = $row['balance'] ? (float)$row['balance'] : 0.00;
    }
}
?>
<!DOCTYPE html>
<html>

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Wallet</title>
    <!-- Mobile redirect: send non-admin member logins to mobile portal on small screens -->
    <script>
        (function(){
            if(<?php echo (isset($_SESSION['login_id']) && $_SESSION['login_id'] != 1) ? 'true' : 'false'; ?> && window.innerWidth < 768 && !window.location.href.includes('desktop=1')){
                window.location.replace('mobile/wallet.php');
            }
        })();
    </script>

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
        /* Custom styles for User Wallet */
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

        /* Profile & Balance Card Area */
        .wallet-profile-header {
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

        .dark-theme .wallet-profile-header {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.08) 0%, rgba(79, 70, 229, 0.08) 100%);
            border-color: rgba(255, 255, 255, 0.06);
        }

        .user-info-block {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .user-avatar-circle {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: var(--primary-gradient, linear-gradient(135deg, #6366f1 0%, #4f46e5 100%));
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: 700;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25);
        }

        .user-name-details h2 {
            margin: 0 !important;
            font-weight: 800;
            letter-spacing: -0.5px;
            color: var(--text-primary, #0f172a);
        }

        .user-name-details p {
            margin: 4px 0 0 0 !important;
            color: var(--text-muted, #475569);
            font-size: 13.5px !important;
            font-weight: 500;
        }

        .balance-widget-card {
            background: var(--card-bg, #ffffff);
            border: 1px solid var(--border-color, #e2e8f0);
            border-radius: var(--border-radius-md, 16px);
            padding: 16px 24px;
            min-width: 220px;
            box-shadow: var(--shadow-sm, 0 1px 2px 0 rgba(0, 0, 0, 0.05));
            display: flex;
            flex-direction: column;
            gap: 4px;
            text-align: right;
        }

        .dark-theme .balance-widget-card {
            border-color: rgba(255, 255, 255, 0.06);
        }

        .balance-widget-card .lbl {
            font-size: 12px !important;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 700;
            color: var(--text-muted, #475569);
        }

        .balance-widget-card .val {
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .balance-widget-card .val.positive {
            color: #10b981;
        }
        .balance-widget-card .val.negative {
            color: #ef4444;
        }

        /* Date Search container */
        .controls-row-modern {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .search-container-modern {
            position: relative;
            max-width: 320px;
            width: 100%;
        }

        .search-input-modern {
            width: 100%;
            padding: 12px 18px 12px 42px !important;
            border-radius: 14px !important;
            border: 1px solid var(--border-color, #e2e8f0) !important;
            background-color: var(--card-bg, #ffffff) !important;
            color: var(--text-primary, #0f172a) !important;
            font-size: 14.5px !important;
            box-shadow: var(--shadow-sm, 0 1px 2px 0 rgba(0, 0, 0, 0.05)) !important;
            transition: all 0.25s ease !important;
        }

        .search-input-modern:focus {
            border-color: var(--primary-color, #4f46e5) !important;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.15) !important;
        }

        .search-icon-inside {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 15px;
            pointer-events: none;
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

        /* Pagination buttons */
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
            loadData(1);
        });  
        
        function searchMembers(){
            loadData(1);
        }

        // function to load wallet details starts
        function loadData(page) {
            $('#hdn_current_page').val(page); //used for Status Update function
            console.log("Loading data for page:", page);
            $.ajax({               
               type: "POST",
               url: "api/user_wallet.php",
               data: {
                action: 'load_data',
                page: page,
                val:$('#txt_search').val(),
               },
                success: function(data) {
                    var obj = jQuery.parseJSON(data);
                    var totalrows = obj[0].total_rows;
                    var htm="";
                    
                    htm=htm+ "<div class='col-lg-12'>";
                    htm=htm+ "<div class='ibox float-e-margins'>";
                    htm=htm+ "<div class='ibox-content'>";
                    htm=htm+ "<div class='table-responsive'>";
                    htm=htm+ "<table class='table table-striped'>";
                    htm=htm+ "<thead>";
                    htm=htm+ "<tr>";
                    htm=htm+ "<th>Date</th>";
                    htm=htm+ "<th>Amount</th>";
                    htm=htm+ "<th>Type</th>";
                    htm=htm+ "</tr>";
                    htm=htm+ "</thead>";
                    htm=htm+ "<tbody>";
                    for (var i = 0; i < obj[1].length; i++) {
                        var typeClass = obj[1][i].type === 'credit' ? 'credit' : 'debit';
                        var typeText = obj[1][i].type === 'credit' ? 'Credit' : 'Debit';
                        var typeIcon = obj[1][i].type === 'credit' ? 'fa-arrow-down' : 'fa-arrow-up';
                        
                        htm=htm+ "<tr>";
                        htm=htm+ "<td><i class='fa fa-calendar' style='color:#94a3b8; margin-right:6px;'></i> "+obj[1][i].date+"</td>";
                        htm=htm+ "<td class='amount-text-custom " + typeClass + "'>₹ " + parseFloat(obj[1][i].amount).toFixed(2) + "</td>";
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
           
            loadMenu();
        }
    </script>
   
    <script src="../app_pagination/pagination.js"></script>
    <script src="../app_menu/menu.js"></script>
</head>

<body>
    <input type="hidden" id="hdn_current_page"  value="0">
    <input type="hidden" id="hdn_id"  value="0">
    <input type="hidden" id="hdn_login_id"  value="0">

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
            
            <div class="settings-card-wrapper">
                <!-- Profile & Balance widget Header -->
                <div class="wallet-profile-header">
                    <div class="user-info-block">
                        <div class="user-avatar-circle">
                            <?php 
                                $initial = strtoupper(substr($_SESSION['name'], 0, 1));
                                echo $initial;
                            ?>
                        </div>
                        <div class="user-name-details">
                            <h2><?php echo $_SESSION['name']; ?></h2>
                            <p><i class="fa fa-google-wallet"></i> My Personal Wallet Account</p>
                        </div>
                    </div>
                    
                    <div class="balance-widget-card">
                        <span class="lbl"><i class="fa fa-google-wallet"></i> Current Balance</span>
                        <span class="val <?php echo $balance >= 0 ? 'positive' : 'negative'; ?>">
                            ₹ <?php echo number_format($balance, 2); ?>
                        </span>
                    </div>
                </div>
                <!-- Profile Header End -->
                
                <!-- Controls Row (search date) -->
                <div class="controls-row-modern">
                    <div class="search-container-modern">
                        <form onsubmit="event.preventDefault(); searchMembers();">
                            <input type="date" placeholder="Search by date" id="txt_search" name="search" class="search-input-modern" onchange="searchMembers();">
                            <i class="fa fa-calendar search-icon-inside"></i>
                        </form>
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
    <script src="../js/loadingoverlay.min.js"></script>

</body>

</html>
