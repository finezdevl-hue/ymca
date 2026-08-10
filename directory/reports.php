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

    <title>Revenue by Head</title>

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

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 24px;
            margin-top: 10px;
        }

        .section-header-modern {
            font-size: 16px !important;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted, #475569);
            margin: 0 0 16px 0 !important;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-header-modern i {
            color: var(--primary-color, #4f46e5);
        }

        .card-inner-table {
            background: var(--card-bg, #ffffff);
            border: 1px solid var(--border-color, #e2e8f0);
            border-radius: var(--border-radius-md, 16px);
            overflow: hidden;
            box-shadow: var(--shadow-sm, 0 1px 2px 0 rgba(0, 0, 0, 0.05));
        }

        .dark-theme .card-inner-table {
            border-color: rgba(255, 255, 255, 0.06);
        }

        /* Heads checkboxes grid inside modal */
        .heads-grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 12px;
            margin-top: 15px;
            max-height: 320px;
            overflow-y: auto;
            padding: 4px;
        }

        .head-check-card {
            background: var(--card-bg, #ffffff);
            border: 1px solid var(--border-color, #e2e8f0);
            border-radius: 12px;
            padding: 10px 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
            margin: 0 !important;
        }

        .dark-theme .head-check-card {
            border-color: rgba(255, 255, 255, 0.06);
        }

        .head-check-card:hover {
            border-color: var(--primary-color, #4f46e5);
            background-color: rgba(99, 102, 241, 0.02);
        }

        .head-check-card input[type="checkbox"] {
            cursor: pointer;
            width: 16px;
            height: 16px;
            margin: 0;
        }

        .head-check-card span {
            font-size: 13.5px;
            font-weight: 600;
            cursor: pointer;
            color: var(--text-primary, #0f172a);
        }

        /* Checkbox wrapper custom modal styles */
        .modal-body label.title-label {
            font-weight: 700;
            font-size: 14px;
            color: var(--text-primary, #0f172a);
            margin-bottom: 8px;
            display: block;
        }

        .amount-highlight-green {
            color: #10b981;
            font-weight: 700;
            font-size: 15px;
        }

        .amount-highlight-red {
            color: #ef4444;
            font-weight: 700;
            font-size: 15px;
        }

        .amount-highlight-neutral {
            color: var(--text-primary, #0f172a);
            font-weight: 700;
            font-size: 15px;
        }
    </style>
    
    <script>
        $(document).ready(function() {          
            loadFees(); // Function to load data for a specific page       
        });  

        // function to load recieved amount detail start
        function loadFees() {
            
            $.ajax({               
               type: "POST",
               url: "api/reports.php",
               data: {
                action: 'load_fees',
               },
                success: function(data) {

                    var obj = jQuery.parseJSON(data);
                    console.log(obj);
                                       
                    var htm="";
                    htm=htm+ "<div class='card-inner-table'>";
                    htm=htm+ "<div class='table-responsive'>";
                    htm=htm+ "<table class='table table-striped' style='margin-bottom: 0;'>";
                    htm=htm+ "<thead>";
                    htm=htm+ "<tr>";
                    htm=htm+ "<th>Receivable</th>";
                    htm=htm+ "<th>Received</th>";
                    htm=htm+ "</tr>";
                    htm=htm+ "</thead>";
                    htm=htm+ "<tbody>";
                    for (var i = 0; i < obj[0].length; i++) {

                        htm=htm+ "<tr>";
                        htm=htm+ "<td class='amount-highlight-neutral'>₹ "+parseFloat(obj[0][i].receivable).toFixed(2)+"</td>";
                        htm=htm+ "<td class='amount-highlight-green'>₹ "+parseFloat(obj[0][i].received).toFixed(2)+"</td>";
                        htm=htm+ "</tr>";
                    }                
                    htm=htm+ "</tbody>";
                    htm=htm+ "</table>";
                    htm=htm+ "</div>";
                    htm=htm+ "</div>";

                    $('#table_fees').html(htm);
                },
                error: function(xhr, status, error) {
                   console.log('AJAX error: ', status, error);
                }
            });
            loadMenu(); // function to load the menu 
            load_heads();
            loadReceiveble();
        }
        
        function loadReceiveble() {
            
            $.ajax({               
               type: "POST",
               url: "api/reports.php",
               data: {
                action: 'load_receiveble',
               },
                success: function(data) {

                    var obj = jQuery.parseJSON(data);
                    console.log(obj);
                                       
                    var htm="";
                    htm=htm+ "<div class='card-inner-table'>";
                    htm=htm+ "<div class='table-responsive'>";
                    htm=htm+ "<table class='table table-striped' style='margin-bottom: 0;'>";
                    htm=htm+ "<thead>";
                    htm=htm+ "<tr>";
                    htm=htm+ "<th>Receivable</th>";
                    htm=htm+ "<th>Received</th>";
                    htm=htm+ "</tr>";
                    htm=htm+ "</thead>";
                    htm=htm+ "<tbody>";
                    for (var i = 0; i < obj[0].length; i++) {
                        var recValue = obj[0][i].receivable ? parseFloat(obj[0][i].receivable).toFixed(2) : "0.00";
                        var receivedValue = obj[0][i].received ? parseFloat(obj[0][i].received).toFixed(2) : "0.00";
                        htm=htm+ "<tr>";
                        htm=htm+ "<td class='amount-highlight-neutral'>₹ "+recValue+"</td>";
                        htm=htm+ "<td class='amount-highlight-green'>₹ "+receivedValue+"</td>";
                        htm=htm+ "</tr>";
                    }                
                    htm=htm+ "</tbody>";
                    htm=htm+ "</table>";
                    htm=htm+ "</div>";
                    htm=htm+ "</div>";

                    $('#table_receiveble').html(htm);
                },
                error: function(xhr, status, error) {
                   console.log('AJAX error: ', status, error);
                }
            });
            loadPayable();
        }

         function loadPayable() {
            
            $.ajax({               
               type: "POST",
               url: "api/reports.php",
               data: {
                action: 'load_payable',
               },
                success: function(data) {

                    var obj = jQuery.parseJSON(data);
                    console.log(obj);
                                       
                    var htm="";
                    htm=htm+ "<div class='card-inner-table'>";
                    htm=htm+ "<div class='table-responsive'>";
                    htm=htm+ "<table class='table table-striped' style='margin-bottom: 0;'>";
                    htm=htm+ "<thead>";
                    htm=htm+ "<tr>";
                    htm=htm+ "<th>Payable</th>";
                    htm=htm+ "<th>Paid</th>";
                    htm=htm+ "</tr>";
                    htm=htm+ "</thead>";
                    htm=htm+ "<tbody>";
                    for (var i = 0; i < obj[0].length; i++) {
                        var payableVal = obj[0][i].payable ? parseFloat(obj[0][i].payable).toFixed(2) : "0.00";
                        var paidVal = obj[0][i].paid ? parseFloat(obj[0][i].paid).toFixed(2) : "0.00";
                        htm=htm+ "<tr>";
                        htm=htm+ "<td class='amount-highlight-neutral'>₹ "+payableVal+"</td>";
                        htm=htm+ "<td class='amount-highlight-red'>₹ "+paidVal+"</td>";
                        htm=htm+ "</tr>";
                    }                
                    htm=htm+ "</tbody>";
                    htm=htm+ "</table>";
                    htm=htm+ "</div>";
                    htm=htm+ "</div>";

                    $('#table_payable').html(htm);
                    loadHeadsBreakdown();
                },
                error: function(xhr, status, error) {
                   console.log('AJAX error: ', status, error);
                }
            });
            
        }

        function loadHeadsBreakdown() {
            $.ajax({               
               type: "POST",
               url: "api/reports.php",
               data: {
                 action: 'load_heads_breakdown',
               },
                success: function(data) {
                    var obj = jQuery.parseJSON(data);
                    var htm = "";
                    htm += "<div class='card-inner-table'>";
                    htm += "<div class='table-responsive'>";
                    htm += "<table class='table table-striped table-hover' style='margin-bottom: 0;'>";
                    htm += "<thead>";
                    htm += "<tr>";
                    htm += "<th>Payment Head</th>";
                    htm += "<th>Type</th>";
                    htm += "<th style='text-align: right;'>Receivable / Payable</th>";
                    htm += "<th style='text-align: right;'>Received / Paid</th>";
                    htm += "<th style='text-align: right;'>Outstanding Balance</th>";
                    htm += "</tr>";
                    htm += "</thead>";
                    htm += "<tbody>";
                    
                    if (obj[0].length === 0) {
                        htm += "<tr><td colspan='5' class='text-center text-muted' style='padding: 20px;'>No active transaction data found for any payment head.</td></tr>";
                    } else {
                        for (var i = 0; i < obj[0].length; i++) {
                            var item = obj[0][i];
                            var typeClass = item.type === 'Credit' ? 'amount-highlight-green' : 'amount-highlight-red';
                            var typeBadge = item.type === 'Credit' ? '<span class="badge-type-custom credit" style="display:inline-flex; align-items:center; gap:4px; padding:4px 10px; border-radius:8px; font-size:12px; font-weight:700; text-transform:uppercase; background-color:rgba(16, 185, 129, 0.1); color:#10b981;"><i class="fa fa-arrow-down"></i> Credit</span>' : '<span class="badge-type-custom debit" style="display:inline-flex; align-items:center; gap:4px; padding:4px 10px; border-radius:8px; font-size:12px; font-weight:700; text-transform:uppercase; background-color:rgba(239, 44, 44, 0.1); color:#ef4444;"><i class="fa fa-arrow-up"></i> Debit</span>';
                            
                            var balanceVal = parseFloat(item.balance);
                            var balanceClass = 'amount-highlight-neutral';
                            if (balanceVal > 0) {
                                balanceClass = 'amount-highlight-red';
                            } else if (balanceVal < 0) {
                                balanceClass = 'amount-highlight-green';
                            }
                            
                            htm += "<tr>";
                            htm += "<td style='font-weight: 600; color: var(--text-primary);'>" + item.name + "</td>";
                            htm += "<td>" + typeBadge + "</td>";
                            htm += "<td style='text-align: right; font-weight: 600; color: var(--text-primary);'>₹ " + parseFloat(item.target).toFixed(2) + "</td>";
                            htm += "<td style='text-align: right;' class='" + typeClass + "'>₹ " + parseFloat(item.actual).toFixed(2) + "</td>";
                            htm += "<td style='text-align: right;' class='" + balanceClass + "'>₹ " + balanceVal.toFixed(2) + "</td>";
                            htm += "</tr>";
                        }
                    }
                    htm += "</tbody>";
                    htm += "</table>";
                    htm += "</div>";
                    htm += "</div>";

                    $('#table_heads_breakdown').html(htm);
                },
                error: function(xhr, status, error) {
                   console.log('AJAX error: ', status, error);
                }
            });
        }
        
    </script>

    <script>
        //function for poup to add new recieved amount details start
        function takeReport(){
            $('#paymentModel').modal('show');
        }

        //function for close the popup for add new recieved amount details start
        function closeReport(){
            $('#payment_form')[0].reset();
            $("#hdn_id").val(0);
            $('#paymentModel').modal('toggle');
        }

        function downloadReport() {
            // Get all selected checkbox values
            const selectedHeads = [];
            $("input[name='selected_head[]']:checked").each(function() {
                selectedHeads.push($(this).val());
            });

            if (selectedHeads.length === 0) {
                alertinfo("Select at least one value");
                return;
            }

            const url = "../invoice/report.php";
            const formData = new FormData();

            const only_credit = $('#only_credit').is(':checked') ? 1 : 0;

            // Append all selected values (as array or JSON)
            formData.append("head", JSON.stringify(selectedHeads));
            formData.append("only_credit", only_credit);

            fetch(url, {
                method: "POST",
                body: formData,
            })
            .then(async (response) => {
                const contentType = response.headers.get("Content-Type");

                if (!response.ok || !contentType.includes("application/pdf")) {
                    const text = await response.text();
                    alert("Server Error: " + text);
                    return;
                }

                const blob = await response.blob();
                const link = document.createElement("a");
                link.href = URL.createObjectURL(blob);
                link.download = "report.pdf";
                
                // Append to body to make cross-browser downloads stable
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                
                closeReport();
            })
            .catch((error) => {
                alert("AJAX Error: " + error);
            });
        }

        //function to inject heads dropdown to the popup for add recieved amount start
        function load_heads() {
            $.ajax({
                type: "POST",
                url: "api/reports.php",
                data: {
                    action: 'load_heads',
                },
                success: function(data) {
                    var obj = jQuery.parseJSON(data);
                    console.log(obj);
        
                    var htm = "<div class='heads-grid-container'>";
                    for (var i = 0; i < obj[0].length; i++) {
                        htm += "<label class='head-check-card' for='head_" + obj[0][i].id + "'>";
                        htm += "<input type='checkbox' name='selected_head[]' value='" + obj[0][i].id + "' id='head_" + obj[0][i].id + "'>";
                        htm += "<span>" + obj[0][i].name + "</span>";
                        htm += "</label>";
                    }
                    htm += "</div>";
        
                    $('#select_heads').html(htm); // Inject the data into the container
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
    <input type="hidden" id="hdn_payment_id"  value="0">
    <input type="hidden" id="hdn_recieveble_id"  value="0">
    <input type="hidden" id="hdn_amount"  value="0">

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
            <div class="row wrapper border-bottom white-bg page-heading" style="padding: 20px 30px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px; border-bottom: 1px solid var(--border-color, #e2e8f0) !important;">
                <div style="margin: 0;">
                    <h2 style="font-weight: 800; font-size: 24px; letter-spacing: -0.5px; margin: 0 !important; color: var(--text-primary, #0f172a);">Revenue by Head</h2>
                </div>
                <div style="display:flex; gap:10px; flex-wrap:wrap;">
                     <a href="monthly_financial_report.php" class="btn btn-default" style="border-radius: 12px; font-weight: 700; padding: 10px 20px; border: 1px solid #dbe4f0; color: #334155; background: #fff;"><i class="fa fa-line-chart"></i> Monthly Financial Report</a>
                     <button type="button" class="btn btn-primary" style="border-radius: 12px; font-weight: 700; padding: 10px 20px; box-shadow: 0 4px 10px rgba(99, 102, 241, 0.2);" onclick="takeReport(0)"><i class="fa fa-file-pdf-o"></i> Get Report</button>
                </div>
            </div>
            <!-- search bar ends -->
            <div class="settings-card-wrapper">
                <h3 class="section-header-modern" style="margin-bottom: 20px !important;"><i class="fa fa-pie-chart"></i> Payments by Head</h3>
                <div id="table_heads_breakdown" style="margin-bottom: 30px;">
                    <!-- data injected Dynamically via ajax -->
                </div>

                <div class="dashboard-grid">
                    <div>
                        <h3 class="section-header-modern"><i class="fa fa-graduation-cap"></i> Fees Summary</h3>
                        <div id="table_fees">
                            <!-- data injected Dynamically via ajax -->
                        </div>
                    </div>
                    
                    <div>
                        <h3 class="section-header-modern"><i class="fa fa-line-chart"></i> Other Receivables</h3>
                        <div id="table_receiveble">
                            <!-- data injected Dynamically via ajax -->
                        </div>
                    </div>
                    
                    <div>
                        <h3 class="section-header-modern"><i class="fa fa-credit-card"></i> Payables & Paid</h3>
                        <div id="table_payable">
                            <!-- data injected Dynamically via ajax -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
       

         <!-- popup modal for add payment starts -->
        <div class="modal inmodal" id="paymentModel" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content animated bounceInRight" style="border-radius: 20px; overflow: hidden; border: none; box-shadow: var(--shadow-lg);">
                    <form method="POST" id="payment_form">
                        <div class="modal-header" style="background: var(--primary-gradient, linear-gradient(135deg, #6366f1 0%, #4f46e5 100%)); padding: 24px 30px; color: #ffffff; text-align: left;">
                            <button type="button" class="close" data-dismiss="modal" style="color: #ffffff; opacity: 0.8; font-size: 24px;" onclick="closeReport();">&times;</button>
                            <h3 style="margin: 0; font-weight: 800; font-size: 20px; letter-spacing: -0.5px;"><i class="fa fa-file-pdf-o"></i> Generate Revenue Report</h3>
                            <p style="margin: 4px 0 0 0; opacity: 0.9; font-size: 13.5px;">Select the payment heads you want to include in the PDF report</p>
                        </div>
                        
                        <div class="modal-body" style="padding: 30px;">
                            <label class="title-label">Choose Payment Heads:</label>
                            <div id="select_heads">
                                <!-- dropdown injected via ajax -->
                            </div>
                            
                            <div class="form-group form-check" style="margin-top: 20px; padding-left: 0;">
                                <label class="head-check-card" for="only_credit" style="display: inline-flex; border-color: var(--primary-color);">
                                    <input type="checkbox" class="form-check-input" id="only_credit" name="only_credit" value="1">
                                    <span>Show only credit (receipts)</span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="modal-footer" style="background: var(--card-bg, #ffffff); border-top: 1px solid var(--border-color, #e2e8f0); padding: 18px 30px; display: flex; justify-content: flex-end; gap: 12px;">
                            <button type="button" class="btn btn-white" style="border-radius: 10px; font-weight: 700; padding: 8px 16px;" onclick="closeReport();">Close</button>
                            <button type="button" class="btn btn-primary" style="border-radius: 10px; font-weight: 700; padding: 8px 20px;" onclick="downloadReport();"><i class="fa fa-download"></i> Download PDF</button>
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

</body>

</html>
