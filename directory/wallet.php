<?php
session_start();
if(isset($_POST['id'])){
    $_SESSION['id']=$_POST['id'];
}
?>
<!DOCTYPE html>
<html>

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Wallet</title>

    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../font-awesome/css/font-awesome.css" rel="stylesheet">

    <link href="../css/animate.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <link href="../css/custom_modern.css" rel="stylesheet">
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <link href="../css/plugins/sweetalert/sweetalert.css" rel="stylesheet">
    <script src="../js/plugins/sweetalert/sweetalert.min.js"></script>
    <script src="../app_js/sweetalert-finez.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cropperjs@1.5.13/dist/cropper.min.css">
    <link href="../image_upload/members/upload.css" rel="stylesheet">
    
    <style>
        /* Custom styles for Wallet Management */
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

        .header-section {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.03) 0%, rgba(79, 70, 229, 0.03) 100%);
            border-radius: var(--border-radius-lg, 24px);
            padding: 30px;
            margin-bottom: 24px;
            border: 1px solid var(--border-color, #e2e8f0);
        }

        .dark-theme .header-section {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.08) 0%, rgba(79, 70, 229, 0.08) 100%);
            border-color: rgba(255, 255, 255, 0.06);
        }

        .header-title-area {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .header-title-area h2 {
            margin: 0 !important;
            font-weight: 800;
            letter-spacing: -0.5px;
            background: var(--primary-gradient, linear-gradient(135deg, #6366f1 0%, #4f46e5 100%));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .search-container-modern {
            position: relative;
            max-width: 480px;
            width: 100%;
        }

        .search-input-modern {
            width: 100%;
            padding: 14px 20px 14px 48px !important;
            border-radius: 16px !important;
            border: 1px solid var(--border-color, #e2e8f0) !important;
            background-color: var(--card-bg, #ffffff) !important;
            color: var(--text-primary, #0f172a) !important;
            font-size: 15px !important;
            box-shadow: var(--shadow-sm, 0 1px 2px 0 rgba(0, 0, 0, 0.05)) !important;
            transition: all 0.25s ease !important;
        }

        .search-input-modern:focus {
            border-color: var(--primary-color, #4f46e5) !important;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.15) !important;
        }

        .search-icon-inside {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 16px;
            pointer-events: none;
            transition: color 0.2s ease;
        }

        .search-input-modern:focus + .search-icon-inside {
            color: var(--primary-color, #4f46e5);
        }

        .btn-wallet-action-custom {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background-color: rgba(16, 185, 129, 0.08) !important;
            color: #10b981 !important;
            border: 1px solid rgba(16, 185, 129, 0.15) !important;
            border-radius: 12px !important;
            padding: 8px 14px !important;
            font-size: 13.5px !important;
            font-weight: 600 !important;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none !important;
            margin-right: 8px;
        }

        .btn-wallet-action-custom:hover {
            background-color: #10b981 !important;
            color: #ffffff !important;
            border-color: transparent !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2) !important;
        }

        .btn-details-action-custom {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background-color: rgba(99, 102, 241, 0.08) !important;
            color: var(--primary-color, #4f46e5) !important;
            border: 1px solid rgba(99, 102, 241, 0.15) !important;
            border-radius: 12px !important;
            padding: 8px 14px !important;
            font-size: 13.5px !important;
            font-weight: 600 !important;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none !important;
        }

        .btn-details-action-custom:hover {
            background-color: var(--primary-color, #4f46e5) !important;
            color: #ffffff !important;
            border-color: transparent !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.2) !important;
        }

        .dark-theme .btn-wallet-action-custom {
            background-color: rgba(52, 211, 153, 0.12) !important;
            color: #34d399 !important;
            border-color: rgba(52, 211, 153, 0.2) !important;
        }
        .dark-theme .btn-wallet-action-custom:hover {
            background-color: #34d399 !important;
            color: #0f172a !important;
        }

        .dark-theme .btn-details-action-custom {
            background-color: rgba(129, 140, 248, 0.12) !important;
            color: #818cf8 !important;
            border-color: rgba(129, 140, 248, 0.2) !important;
        }
        .dark-theme .btn-details-action-custom:hover {
            background-color: #818cf8 !important;
            color: #0f172a !important;
        }

        /* Modal styling */
        #walletModal .modal-content {
            background-color: var(--card-bg, #ffffff) !important;
            color: var(--text-primary, #0f172a) !important;
            border-radius: var(--border-radius-lg, 24px) !important;
            border: 1px solid var(--border-color, #e2e8f0) !important;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25) !important;
            overflow: hidden;
            padding: 0 !important;
        }

        .modal-header-custom {
            background: var(--primary-gradient, linear-gradient(135deg, #6366f1 0%, #4f46e5 100%));
            color: #ffffff;
            padding: 20px 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .modal-title-custom {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
            color: #ffffff !important;
            letter-spacing: -0.3px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .close-custom {
            color: rgba(255, 255, 255, 0.8);
            font-size: 28px;
            font-weight: 300;
            cursor: pointer;
            transition: all 0.2s ease;
            line-height: 1;
        }

        .close-custom:hover {
            color: #ffffff;
            transform: scale(1.1);
        }

        #walletModal .modal-body {
            padding: 28px !important;
        }

        #walletModal .modal-footer {
            padding: 16px 28px;
            background-color: var(--bg-main, #f8fafc);
            border-top: 1px solid var(--border-color, #e2e8f0);
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin: 0;
        }

        .dark-theme #walletModal .modal-footer {
            background-color: rgba(255, 255, 255, 0.02);
        }

        /* Table pagination styles */
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
        
        function searchMembers(){
            loadData(1);
        }

        // function to load all the client details for add amount to wallet start
        function loadData(page) {
            $('#hdn_current_page').val(page); //used for Status Update function
            console.log("Loading data for page:", page);
            $.ajax({               
               type: "POST",
               url: "api/wallet.php",
               data: {
               action: 'load_clients_data',
               page: page, 
               val:$('#txt_search').val()
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
                    htm=htm+ "<th style='width: 80px;'>No</th>";
                    htm=htm+ "<th>Name</th>";
                    htm=htm+ "<th style='text-align: right;'>Action</th>";
                    htm=htm+ "</tr>";
                    htm=htm+ "</thead>";
                    htm=htm+ "<tbody>";
                    for (var i = 0; i < obj[1].length; i++) {
                        
                        var j= i+1;
                        var slno=((page-1)*8)+j
                        
                        htm=htm+ "<tr>";
                        htm=htm+ "<td style='font-weight: 600; color: var(--text-muted);'>" + slno+ "</td>";
                        htm=htm+ "<td style='font-weight: 600;'>" + obj[1][i].first_name + " " + obj[1][i].middle_name + " " + obj[1][i].last_name + "</td>";
                        htm=htm+ "<td style='text-align: right;'>";
                        htm=htm+ "<a onclick='addtowallet(" + obj[1][i].id + ");' class='btn-wallet-action-custom'><i class='fa fa-plus-circle' style='font-family: FontAwesome !important;'></i> Add Funds</a>";
                        htm=htm+ "<a onclick='wallet(" + obj[1][i].id + ",\"" + obj[1][i].first_name + "\",\"" + obj[1][i].middle_name + "\",\"" + obj[1][i].last_name + "\");' class='btn-details-action-custom'><i class='fa fa-eye' style='font-family: FontAwesome !important;'></i> View History</a>";
                        htm=htm+ "</td>";
                        htm=htm+ "</tr>";
                    }                
                    htm=htm+ "</tbody>";
                    htm=htm+ "</table>";
                    htm=htm+ "</div>";
                    htm=htm+ "</div>";
                    htm=htm+ "</div>";
                    htm=htm+ "</div>";
                    $('#table_clients').html(htm);
                    var htmpage= paginate(totalrows,page);
                    $('#table_clients').append(htmpage);
                },

                error: function(xhr, status, error) {
                    console.log('AJAX error: ', status, error);
                }
            });
            loadMenu();
        }
    </script>

    <script>
        // function to navigate the page to see the wallet amount details start
        function wallet(id,first_name,middle_name,last_name){ 
            $.post("wallet_details.php", { 'id': id,'first_name':first_name, 'middle_name':middle_name, 'last_name':last_name})
            .done(function(response) {
                window.location.href = "wallet_details.php";
            })
        }
        
        //function for popup to add new amount to wallet starts
        function addtowallet(id){
            const currentDate = new Date();
            const year = currentDate.getFullYear();
            const month = String(currentDate.getMonth() + 1).padStart(2, '0');
            const day = String(currentDate.getDate()).padStart(2, '0');
            const formattedDate = `${year}-${month}-${day}`;

            $("#hdn_id").val(id);
            $("#date").val(formattedDate);
            $('#walletModal').modal('show');
        }

        //function for close the popup for add new amount to wallet starts
        function closeaddtowallet(){
            $('#wallet_form')[0].reset();
            $('#hdn_id').val(0);
            $('#hdn_login_id').val(0);
            $('#walletModal').modal('hide');
        }

        // function to save new amount to the wallet start
        function saveWallet() {
            const date = $('#date').val();
            const amount = $('#amount').val();
            const selected_type = $('#selected_type').val();
            
            if (date == "") {
                alertinfo("Date cannot be empty.");
                return;
            }

            if (amount == "") {
                alertinfo("Amount cannot be empty.");
                return;
            }

            if (selected_type === null || selected_type === "") {
                alertinfo("Select a type.");
                return;
            }
           
            swal({
                title: "Are you sure?",
                text: "Do you want to save this data!",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "Yes, Save!",
                cancelButtonText: "Cancel",
                closeOnConfirm: false,
                closeOnCancel: true
            },
            function (isConfirm) {
                if (isConfirm){
                    var data = {
                        action: 'save_wallet',
                        date: $('#date').val(),
                        amount: $('#amount').val(),
                        selected_type: $('#selected_type').val(), 
                        id: $('#hdn_id').val(),     
                    };
                    load_overlay();
                    $.ajax({
                        type: "POST",
                        url: "api/wallet.php",
                        data: data,
                        success: function(response) {
                            close_overlay();
                            alertsuccess(response);
                            closeaddtowallet();
                            loadData($('#hdn_current_page').val());
                        },
                        error: function (xhr, status){
                            close_overlay(); 
                            try {
                                var msgObj = JSON.parse(xhr.responseText);  
                                alerterror(msgObj, xhr); 
                            } catch (e) {
                                alerterror({ Message: "Unexpected error occurred." }, xhr);
                            }
                            $('#wallet_form')[0].reset();
                        }
                    });
                }
            });   
        }
    </script>

    <script src="../app_pagination/pagination.js"></script>
    <script src="../app_menu/menu.js"></script>

</head>

<body>
<!-- hidden values start -->
<input type="hidden" id="hdn_current_page"  value="0">
<input type="hidden" id="hdn_id"  value="0">
<input type="hidden" id="hdn_file_upload" name="image" value="0">
<!-- hidden values end -->

    <div id="wrapper">

        <!-- navigation starts -->
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
        <!-- navigation starts -->

        <div id="page-wrapper" class="gray-bg">
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

            <div class="settings-card-wrapper">
                <!-- header & search bar started -->
                <div class="header-section">
                    <div class="header-title-area">
                        <h2><i class="fa fa-google-wallet" style="font-family: FontAwesome !important;"></i> Wallet Management</h2>
                        <div class="search-container-modern">
                            <form onsubmit="event.preventDefault(); searchMembers();">
                                <input type="text" placeholder="Search client by name..." id="txt_search" name="search" class="search-input-modern" onkeyup="if(event.key === 'Enter') searchMembers();">
                                <i class="fa fa-search search-icon-inside"></i>
                            </form>
                        </div>
                    </div>
                </div>
                <!-- header & search bar end -->

                <!-- popup modal for insert new proposal details starts -->
                <div class="modal inmodal" id="walletModal" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content animated bounceInRight">
                            <div class="modal-header-custom">
                                <h3 class="modal-title-custom"><i class="fa fa-google-wallet" style="font-family: FontAwesome !important;"></i> Add/Deduct Funds</h3>
                                <span class="close-custom" onclick="closeaddtowallet()">&times;</span>
                            </div>
                            <form method="POST" id="wallet_form">
                                <div class="modal-body">
                                    <div class="row" style="padding-bottom: 15px;">
                                        <div class="col-md-6">
                                            <label style="font-weight: 600; font-size: 13.5px !important; margin-bottom: 6px;">Date</label>
                                            <input type="date" id="date" name="date" placeholder="Date" class="form-control">
                                        </div>
                                        <div class="col-md-6">
                                            <label style="font-weight: 600; font-size: 13.5px !important; margin-bottom: 6px;">Amount</label>
                                            <input type="number" id="amount" name="amount" placeholder="Amount" class="form-control">
                                        </div>
                                    </div>
                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label style="font-weight: 600; font-size: 13.5px !important; margin-bottom: 6px;">Type</label>
                                        <select id="selected_type" class="form-control">
                                            <option value="" selected disabled>Select Type</option>
                                            <option value="credit">Credit (Add)</option>
                                            <option value="debit">Debit (Deduct)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-white" onclick="closeaddtowallet();">Cancel</button>
                                    <button type="button" class="btn btn-primary" onclick="saveWallet();">Save</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <!-- popup modal for insert new proposal details ends -->

                <div class="wrapper wrapper-content animated fadeInRight" id="table_clients" style="padding: 0;">
                    <!-- Data injected by ajax -->
                </div>
            </div>

        </div>
    </div>
    
    <!-- Mainly scripts -->
    <script src="../js/jquery-3.1.1.min.js"></script>
    <script src="../js/bootstrap.min.js"></script>
    <script src="../js/plugins/metisMenu/jquery.metisMenu.js"></script>
    <script src="../js/plugins/slimscroll/jquery.slimscroll.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/cropperjs@1.5.13/dist/cropper.min.js"></script>
    <!-- Custom and plugin javascript -->
    <script src="../js/inspinia.js"></script>
    <script src="../js/plugins/pace/pace.min.js"></script>
    <script src="../image_upload/members/image_upload.js"></script>
    <script src="../app_js/validation.js"></script>

    <script src="../js/plugins/iCheck/icheck.min.js"></script>
    <script src="../js/loadingoverlay.min.js"></script>

</body>

</html>
