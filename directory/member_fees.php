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

    <title>Member Fees</title>

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

        /* Search input styling override */
        .search-form-container {
            max-width: 480px;
            margin: 0;
            width: 100%;
        }

        .custom-search-group {
            background: var(--bg-main, #f8fafc) !important;
            border: 1.5px solid var(--border-color, #e2e8f0) !important;
            border-radius: 16px !important;
            padding: 4px 6px !important;
            display: flex;
            align-items: center;
            transition: all 0.25s ease;
            width: 100%;
        }

        .dark-theme .custom-search-group {
            background: rgba(255,255,255,0.02) !important;
            border-color: rgba(255,255,255,0.08) !important;
        }

        .custom-search-group:focus-within {
            border-color: var(--primary-color, #6366f1) !important;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.12) !important;
        }

        .custom-search-input {
            flex: 1;
            background: transparent !important;
            border: none !important;
            padding: 8px 14px !important;
            height: auto !important;
            color: var(--text-primary, #0f172a) !important;
            font-size: 14.5px !important;
            font-weight: 500 !important;
            outline: none !important;
        }

        .search-form-container .btn-search-custom {
            background: var(--primary-gradient, linear-gradient(135deg, #6366f1 0%, #4f46e5 100%)) !important;
            color: #ffffff !important;
            border-radius: 12px !important;
            padding: 10px 20px !important;
            font-weight: 700 !important;
            border: none !important;
            box-shadow: 0 4px 10px rgba(99, 102, 241, 0.2) !important;
            transition: all 0.2s ease;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .search-form-container .btn-search-custom:hover {
            transform: translateY(-1px);
            opacity: 0.95;
        }

        /* Avatar thumbnail styling */
        .member-thumbnail-circle {
            width: 48px !important;
            height: 48px !important;
            border-radius: 50% !important;
            border: 2px solid var(--border-color, #e2e8f0);
            box-shadow: var(--shadow-sm, 0 1px 2px rgba(0,0,0,0.05));
            object-fit: cover;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .dark-theme .member-thumbnail-circle {
            border-color: rgba(255,255,255,0.06);
        }

        /* Table custom states */
        .wallet-amount-badge {
            font-weight: 700;
            font-size: 14px !important;
        }

        .wallet-amount-badge.has-balance {
            color: #10b981;
        }

        .wallet-amount-badge.empty-balance {
            color: var(--text-muted, #94a3b8);
        }

        .pending-balance-badge {
            font-weight: 700;
            font-size: 14px !important;
        }

        .pending-balance-badge.has-pending {
            color: #ef4444;
        }

        .pending-balance-badge.clear-balance {
            color: #10b981;
        }

        /* Table actions button group */
        .btn-table-action {
            border-radius: 10px !important;
            font-size: 12.5px !important;
            font-weight: 700 !important;
            padding: 6px 12px !important;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-right: 6px;
            transition: all 0.2s ease;
            border: 1px solid transparent;
        }

        .btn-table-action:last-child {
            margin-right: 0;
        }

        .btn-table-action.pay {
            background-color: rgba(16, 185, 129, 0.08) !important;
            color: #10b981 !important;
        }

        .btn-table-action.pay:hover {
            background-color: #10b981 !important;
            color: #ffffff !important;
            transform: translateY(-1px);
        }

        .btn-table-action.details {
            background-color: rgba(59, 130, 246, 0.08) !important;
            color: #3b82f6 !important;
        }

        .btn-table-action.details:hover {
            background-color: #3b82f6 !important;
            color: #ffffff !important;
            transform: translateY(-1px);
        }

        .btn-table-action.receivable {
            background-color: rgba(168, 85, 247, 0.08) !important;
            color: #a855f7 !important;
        }

        .btn-table-action.receivable:hover {
            background-color: #a855f7 !important;
            color: #ffffff !important;
            transform: translateY(-1px);
        }

        .btn-table-action.report {
            background-color: rgba(239, 68, 68, 0.08) !important;
            color: #ef4444 !important;
        }

        .btn-table-action.report:hover {
            background-color: #ef4444 !important;
            color: #ffffff !important;
            transform: translateY(-1px);
        }

        /* Modal labels and fields */
        .modal-body label {
            font-weight: 700 !important;
            font-size: 13px;
            color: var(--text-muted, #475569);
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-control {
            border-radius: 12px !important;
            border: 1px solid var(--border-color, #e2e8f0) !important;
            padding: 10px 14px !important;
            background-color: var(--card-bg, #ffffff) !important;
            color: var(--text-primary, #0f172a) !important;
            transition: all 0.2s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color, #6366f1) !important;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.12) !important;
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
        
        // search box for members starts
        function searchMembers(){
            loadData(1);
        }
        //search box for members ends

        // function to load all the members start
        function loadData(page) {
            $('#hdn_current_page').val(page); //used for Status Update function
            console.log("Loading data for page:", page);
            $.ajax({               
               type: "POST",
               url: "api/member_fees.php",
               data: {
               action: 'load_members_data',
               page: page, 
               val:$('#txt_search').val()
               },
                success: function(data) {  
                     
                    var obj = jQuery.parseJSON(data);
                    var totalrows = obj[0].total_rows;
                    var htm="";
                    
                    htm=htm+ "<div class='col-lg-12'>";
                    htm=htm+ "<div class='ibox float-e-margins' style='margin-bottom: 0;'>";
                    htm=htm+ "<div class='ibox-content'>";
                    htm=htm+ "<div class='table-responsive'>";
                    htm=htm+ "<table class='table table-striped table-hover'>";
                    htm=htm+ "<thead>";
                    htm=htm+ "<tr>";
                    htm=htm+ "<th>No</th>";
                    htm=htm+ "<th>Member</th>";
                    htm=htm+ "<th>Name</th>";
                    htm=htm+ "<th>Wallet Balance</th>";
                    htm=htm+ "<th>Pending Dues</th>";
                    htm=htm+ "<th style='text-align: right;'>Actions</th>";
                    htm=htm+ "</tr>";
                    htm=htm+ "</thead>";
                    htm=htm+ "<tbody>";
                    for (var i = 0; i < obj[1].length; i++) {
                        var j= i+1;
                        var slno=((page-1)*8)+j;
                        
                        var avatarUrl = (obj[1][i].img && obj[1][i].img !== '0' && obj[1][i].img !== 'customer.png') ? '../image_upload/members/uploads/' + obj[1][i].img : '../img/customer.png';
                        
                        var walletVal = parseFloat(obj[1][i].wallet_amount);
                        var walletClass = walletVal > 0 ? 'has-balance' : 'empty-balance';
                        
                        var pendingVal = parseFloat(obj[1][i].pending_balance);
                        var pendingClass = pendingVal > 0 ? 'has-pending' : 'clear-balance';
                        
                        var isGuest = parseInt(obj[1][i].member_type || 0) === 1;
                        var guestBadgeHtml = isGuest ? " <span class='badge' style='background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%); color: #c2410c; border: 1px solid rgba(249, 115, 22, 0.3); box-shadow: 0 2px 6px rgba(249, 115, 22, 0.12); font-size: 10px; font-weight: 800; padding: 3px 8px 3px 6px; border-radius: 16px; letter-spacing: 0.5px; margin-left: 6px; display: inline-flex; align-items: center; gap: 3px; vertical-align: middle;'><i class='fa fa-star' style='color:#f59e0b; font-size:9px;'></i> GUEST</span>" : "";

                        htm=htm+ "<tr>";
                        htm=htm+ "<td style='vertical-align:middle; font-weight: 700; color:var(--text-muted);'>" + slno+ "</td>";
                        htm=htm+ "<td style='vertical-align:middle;'><img alt='image' class='member-thumbnail-circle' src='"+avatarUrl+"' onerror=\"this.src='../img/customer.png'\"></td>";
                        htm=htm+ "<td style='vertical-align:middle; font-weight:700; color:var(--text-primary);'>"+obj[1][i].first_name+" " + (obj[1][i].middle_name ? obj[1][i].middle_name + " " : "") + obj[1][i].last_name + guestBadgeHtml + "</td>";
                        htm=htm+ "<td style='vertical-align:middle;' class='wallet-amount-badge " + walletClass + "'>₹ "+walletVal.toFixed(2)+"</td>";
                        htm=htm+ "<td style='vertical-align:middle;' class='pending-balance-badge " + pendingClass + "'>₹ "+pendingVal.toFixed(2)+"</td>";
                        
                        htm=htm+ "<td style='vertical-align:middle; text-align: right;'>";
                        htm=htm+ "<a onclick='addFees("+obj[1][i].id+",\"" + obj[1][i].first_name + "\",\"" + obj[1][i].middle_name + "\",\"" + obj[1][i].last_name + "\");' class='btn btn-table-action pay'><i class='fa fa-money'></i> Pay</a>";
                        htm=htm+ "<a onclick='navigateSeeFeeHostory(" + obj[1][i].id + ",\"" + obj[1][i].first_name + "\",\"" + obj[1][i].middle_name + "\",\"" + obj[1][i].last_name + "\");' class='btn btn-table-action details'><i class='fa fa-history' ></i> Ledger</a>";
                        htm=htm+ "<a onclick='addRecieveble("+obj[1][i].id+");' class='btn btn-table-action receivable'><i class='fa fa-plus-circle'></i> Receivable</a>";
                        htm=htm+ "<a onclick='downloadReport("+obj[1][i].id+");' class='btn btn-table-action report'><i class='fa fa-file-pdf-o'></i> PDF Report</a>";
                        htm=htm+ "</td>";
                        htm=htm+ "</tr>";
                    }                
                    
                    htm=htm+ "</tbody>";
                    htm=htm+ "</table>";
                    htm=htm+ "</div>";
                    htm=htm+ "</div>";
                    htm=htm+ "</div>";
                    htm=htm+ "</div>";
                    $('#table_members').html(htm);
                    var htmpage= paginate(totalrows,page);
                    $('#table_members').append(htmpage);
                },

                error: function(xhr, status, error) {
                    console.log('AJAX error: ', status, error);
                }
            });
            loadMenu();
            load_heads();
            load_closing_years();

        }

        function downloadReport(id) {
            let url = '../invoice/payment_report.php';
            
            const formData = new FormData();
            formData.append("member_id", id);

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
                link.download = "payment_report.pdf";
                link.click();
            })
            .catch((error) => {
                alert("AJAX Error: " + error);
            });
        }

        function addFees(id,first_name,middle_name,last_name){ 
            $.post("fees_receiveble.php", { 'member_id': id,'first_name':first_name, 'middle_name':middle_name, 'last_name':last_name })
            .done(function(response) {
                window.location.href = "fees_receiveble.php";
            });
        }

        function load_heads(){
            $.ajax({
                type: "POST",
                url: "api/member_fees.php",
                data: {
                action: 'load_heads',
                },
                success: function(data) {
                    var obj = jQuery.parseJSON(data);
                    
                    var htm="";
                    htm=htm+ "<div class='dropdown form-group'><select id='selected_head' class='form-control'>";
                    htm=htm+"<option value='0' selected disabled>Select Head</option>";
                    for (var i = 0; i < obj[0].length; i++) {
                        htm=htm+"<option value='"+obj[0][i].id+"'>"+obj[0][i].name+"</option>";
                    }                
                    htm=htm+"</select></div>";

                    var html="";
                    html=html+ "<div class='dropdown form-group'><select id='selected_recieveble_head' class='form-control'>";
                    html=html+"<option value='0' selected disabled>Select Head</option>";
                    for (var i = 0; i < obj[0].length; i++) {
                        html=html+"<option value='"+obj[0][i].id+"'>"+obj[0][i].name+"</option>";
                    }                
                    html=html+"</select></div>";

                    $('#select_heads').html(htm);
                    $('#select_head_recieveble').html(html);
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error: ', status, error);
                }
            });
        }

        function load_closing_years(){
            $.ajax({
                type: "POST",
                url: "api/member_fees.php",
                data: {
                action: 'load_closing_years',
                },
                success: function(data) {
                    var obj = jQuery.parseJSON(data);
                    var htm="";
                    htm=htm+ "<div class='dropdown form-group'><select id='selected_year' class='form-control'>";
                    for (var i = 0; i < obj[0].length; i++) {
                        htm=htm+"<option value='"+obj[0][i].id+"'>"+obj[0][i].from_year+" - "+obj[0][i].to_year+"</option>";
                    }                
                    htm=htm+"</select></div>";

                    $('#select_from_year').html(htm);
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error: ', status, error);
                }
            });
        }

        function load_wallet_balance(member_id){
            $.ajax({
                type: "POST",
                url: "api/fees_receiveble.php",
                data: {
                    action: 'load_wallet_balance',
                    id: member_id,
                },
                success: function(data) {
                    console.log("Wallet balance API response:", data);
                    var obj = jQuery.parseJSON(data);
                    var htm = "";

                    if (obj && obj[0] && obj[0].length > 0) {
                        for (var i = 0; i < obj[0].length; i++) {
                            var wallet_balance = parseFloat(obj[0][i].wallet_balance || 0);

                            htm += "<label>Wallet Balance</label><input type='number' id='wallet_amount' name='wallet_amount' value='" + wallet_balance.toFixed(2) + "' class='form-control' readonly style='margin-bottom: 10px;'>";

                            if (wallet_balance === 0) {
                                htm += "<div style='color:#ef4444; font-weight:700; margin-bottom: 12px;'><i class='fa fa-exclamation-circle'></i> Wallet is empty</div>";
                            } else {
                                htm += "<label class='head-check-card' style='display:inline-flex; width:auto; border-color:var(--primary-color); padding: 8px 16px; margin-bottom: 12px;'>";
                                htm += "<input type='checkbox' name='wallet' value='1' id='use_wallet' style='margin-right:8px;'> Add from Wallet (₹" + wallet_balance.toFixed(2) + " available)";
                                htm += "</label>";
                            }
                        }
                    } else {
                        // No wallet data returned - default to 0 balance
                        htm += "<label>Wallet Balance</label><input type='number' id='wallet_amount' name='wallet_amount' value='0.00' class='form-control' readonly style='margin-bottom: 10px;'>";
                        htm += "<div style='color:#ef4444; font-weight:700; margin-bottom: 12px;'><i class='fa fa-exclamation-circle'></i> Wallet is empty</div>";
                    }

                    $('#wallet_balance').html(htm);
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error loading wallet balance:', status, error);
                    // Display error state
                    var htm = "<label>Wallet Balance</label><input type='number' id='wallet_amount' name='wallet_amount' value='0.00' class='form-control' readonly style='margin-bottom: 10px;'>";
                    htm += "<div style='color:#ef4444; font-weight:700; margin-bottom: 12px;'><i class='fa fa-exclamation-circle'></i> Unable to load wallet balance</div>";
                    $('#wallet_balance').html(htm);
                }
            });
        }

        function addRecieveble(id){
            $("#hdn_member_id").val(id);
            $('#recievebleModel').modal('show');
            load_wallet_balance(id);
        }

        function closeaddRecieveble(){
            $('#recieveble_form')[0].reset();
            $('#recievebleModel').modal('toggle');
        }

        function closeaddFees(){
            $('#fees_form')[0].reset();
            $('#feesModel').modal('toggle');
        }

        function saveFees() {
            const fees = $('#txt_fees').val();
            const date = $('#fees_date').val();

            var discription = $("#txt_discription").val();
            let length = discription.length;
            
            if (fees == "") {
                alertinfo("Fees cannot be empty.");
                return;
            }
            
            if (date == "") {
                alertinfo("Date cannot be empty.");
                return;
            }

            if (length >= 150) {
                alertinfo("Description should be less than 150 characters.");
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
                        action: 'save_fees',
                        member_id: $("#hdn_member_id").val(),
                        fees: $('#txt_fees').val(), 
                        date: $('#fees_date').val(),
                        head: $("#selected_head").val(),
                        discription: $("#txt_discription").val(),          
                    };
                    $.ajax({
                        type: "POST",
                        url: "api/member_fees.php",
                        data: data,
                        success: function(response) {
                            alertsuccess('Saved Successfully');
                            loadData($('#hdn_current_page').val());
                            downloadInvoice();
                        },
                        error: function (xhr, status){
                            var msgObj = JSON.parse(xhr.responseText);
                            alerterror(msgObj, xhr);
                            $('#fees_form')[0].reset();
                        }
                    });
                }
            });   
        }

        function downloadInvoice() {
            const id= $("#hdn_member_id").val();
            const fees= $('#txt_fees').val(); 
            const date= $('#fees_date').val();
            const discription= $("#txt_discription").val();
            const name= $("#hdn_name").val();

            fetch("../invoice/generate_invoice.php", {
                method: "POST",
                body: new URLSearchParams({ id, fees, date, discription, name }),
            })
            .then(async (response) => {
                const contentType = response.headers.get("Content-Type");

                if (contentType !== "application/pdf") {
                    const text = await response.text();
                    alert("Server Error: " + text);
                    return;
                }

                const blob = await response.blob();
                const link = document.createElement("a");
                link.href = URL.createObjectURL(blob);
                link.download = "invoice.pdf";
                link.click();
                closeaddFees();
            })
            .catch((error) => {
                alert("AJAX Error: " + error);
            });
        }

        function saveRecieveble() {
            var isWalletChecked = $('#use_wallet').is(':checked');
            var wallet = parseFloat($('#wallet_amount').val()) || 0;
            var received = parseFloat($('#received').val()) || 0;

            if (isWalletChecked) {
                if (received > wallet) {
                    alertinfo("Received amount cannot be greater than wallet balance.");
                    return false;
                }
            }
            const receiveble = $('#receiveble').val();
            const receiveble_date = $('#txt_receiveble_date').val();

            var discription = $("#txt_reciveble_discription").val();
            let length = discription.length;

            if (receiveble == "") {
                alertinfo("Fees cannot be empty.");
                return;
            }
            
            if (receiveble_date == "") {
                alertinfo("Receivable date cannot be empty.");
                return;
            }

            if (length >= 150) {
                alertinfo("Description should be less than 150 characters.");
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
                        action: isWalletChecked ? 'save_payment_from_wallet' : 'save_recieveble',
                        member_id: $("#hdn_member_id").val(),
                        receiveble: $('#receiveble').val(),
                        received: $('#received').val(),
                        receiveble_date: $('#txt_receiveble_date').val(),
                        received_date: $('#txt_received_date').val(),
                        head: $("#selected_recieveble_head").val(),
                        flag: $("#selected_year").val(),
                        discription: $("#txt_reciveble_discription").val(),
                        transaction_type: $('#selected_transaction').val(),     
                    };
                    $.ajax({
                        type: "POST",
                        url: "api/member_fees.php",
                        data: data,
                        success: function(response) {
                            closeaddRecieveble();
                            alertsuccess('Saved Successfully');
                            loadData($('#hdn_current_page').val());
                        },
                        error: function (xhr, status){
                            var msgObj = JSON.parse(xhr.responseText);
                            alerterror(msgObj, xhr);
                            $('#recieveble_form')[0].reset();
                        }
                    });
                }
            });   
        }

         function navigateSeeFeeHostory(id,first_name,middle_name,last_name){
            var query = "fees_details.php?member_id=" + encodeURIComponent(id) +
                "&first_name=" + encodeURIComponent(first_name) +
                "&middle_name=" + encodeURIComponent(middle_name) +
                "&last_name=" + encodeURIComponent(last_name);
            window.location.href = query;
         }

    </script>
    <script src="../app_pagination/pagination.js"></script>
    <script src="../app_menu/menu.js"></script>

</head>

<body>
<input type="hidden" id="hdn_current_page"  value="0">
<input type="hidden" id="hdn_id"  value="0">
<input type="hidden" id="hdn_member_id"  value="0">
<input type="hidden" id="hdn_name"  value="0">

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
        <!-- navigation ends -->
        
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

            <!-- search bar started -->
            <div class="row wrapper border-bottom white-bg page-heading" style="padding: 20px 30px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:15px; border-bottom: 1px solid var(--border-color, #e2e8f0) !important;">
                <div>
                    <h2 style="font-weight: 800; font-size: 24px; letter-spacing: -0.5px; margin: 0 !important; color: var(--text-primary, #0f172a);">Fees Management</h2>
                </div>
                
                <div class="search-form-container" style="flex: 1; max-width: 360px;">
                    <div class="custom-search-group">
                        <input type="text" placeholder="Search by name..." id="txt_search" name="search" class="custom-search-input" onkeyup="searchMembers()">
                        <button class="btn-search-custom" onclick="searchMembers()" type="button"><i class="fa fa-search"></i> Search</button>
                    </div>
                </div>
            </div>
            <!-- search bar end -->
            
            <div class="settings-card-wrapper">
                <div class="wrapper wrapper-content animated fadeInRight" id="table_members" style="padding: 0;">
                    <!-- Data injected by ajax -->
                </div>
            </div>
        </div>
    </div>

    <!-- modal for add fees for a member starts-->
    <div class="modal inmodal" id="feesModel" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content animated bounceInRight" style="border-radius: 20px; overflow: hidden; border: none; box-shadow: var(--shadow-lg);">
                <form method="POST" id="fees_form">
                    <div class="modal-header" style="background: var(--primary-gradient, linear-gradient(135deg, #6366f1 0%, #4f46e5 100%)); padding: 24px 30px; color: #ffffff; text-align: left;">
                        <button type="button" class="close" data-dismiss="modal" style="color: #ffffff; opacity: 0.8; font-size: 24px;" onclick="closeaddFees();">&times;</button>
                        <h3 style="margin: 0; font-weight: 800; font-size: 20px; letter-spacing: -0.5px;"><i class="fa fa-money"></i> Record Fee Payment</h3>
                        <p style="margin: 4px 0 0 0; opacity: 0.9; font-size: 13.5px;">Enter the amount received and assign a payment head</p>
                    </div>

                    <div class="modal-body" style="padding: 30px; background: var(--card-bg, #ffffff);">
                        <div class="form-group">
                            <label>Fees Amount</label>
                            <input type="number" id="txt_fees" name="fees" placeholder="Enter amount (e.g. 500)" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Payment Date</label>
                            <input type="date" id="fees_date" name="from_date" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Payment Head</label>
                            <div id="select_heads">
                                <!-- dropdown injected via ajax -->
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea id="txt_discription" name="discription" rows="3" placeholder="Additional details (max 150 chars)" class="form-control"></textarea>
                        </div>
                    </div>
                    
                    <div class="modal-footer" style="background: var(--card-bg, #ffffff); border-top: 1px solid var(--border-color, #e2e8f0); padding: 18px 30px; display: flex; justify-content: flex-end; gap: 12px;">
                        <button type="button" class="btn btn-white" style="border-radius: 10px; font-weight: 700; padding: 8px 16px;" onclick="closeaddFees();">Close</button>
                        <button type="button" class="btn btn-primary" style="border-radius: 10px; font-weight: 700; padding: 8px 20px;" onclick="saveFees();">Save & Invoice</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- modal for add fees for a member end -->

    <!-- modal for add recieveble fee details start -->
    <div class="modal inmodal" id="recievebleModel" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-md">
            <div class="modal-content animated bounceInRight" style="border-radius: 20px; overflow: hidden; border: none; box-shadow: var(--shadow-lg);">
                <form method="POST" id="recieveble_form">
                    <div class="modal-header" style="background: var(--primary-gradient, linear-gradient(135deg, #6366f1 0%, #4f46e5 100%)); padding: 24px 30px; color: #ffffff; text-align: left;">
                        <button type="button" class="close" data-dismiss="modal" style="color: #ffffff; opacity: 0.8; font-size: 24px;" onclick="closeaddRecieveble();">&times;</button>
                        <h3 style="margin: 0; font-weight: 800; font-size: 20px; letter-spacing: -0.5px;"><i class="fa fa-plus-circle"></i> Add Receivable Fee</h3>
                        <p style="margin: 4px 0 0 0; opacity: 0.9; font-size: 13.5px;">Allocate a new receivable fee structure to this member</p>
                    </div>

                    <div class="modal-body" style="padding: 30px; max-height: 460px; overflow-y: auto; background: var(--card-bg, #ffffff);">
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label>Receivable Fee</label>
                                <input type="number" id="receiveble" name="receiveble" placeholder="Fees amount" class="form-control">
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Received Fee</label>
                                <input type="number" id="received" name="received" placeholder="Amount paid now" class="form-control">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label>Receivable Date</label>
                                <input type="date" id="txt_receiveble_date" name="receiveble_date" class="form-control">
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Received Date</label>
                                <input type="date" id="txt_received_date" name="received_date" class="form-control">
                            </div>
                        </div>

                        <div class="form-group" id="wallet_balance">
                            <!-- Wallet checkboxes injected dynamically -->
                        </div>

                        <div class="form-group">
                            <label>Payment Head</label>
                            <div id="select_head_recieveble">
                                <!-- dropdown injected via ajax -->
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label>Transaction Type</label>
                                <select class="dropdown form-control" name="selected_transaction" id="selected_transaction">
                                    <option value="0">Select Type</option>
                                    <?php foreach (TransactionType::all() as $key => $label): ?>
                                        <option value="<?= $key ?>"><?= htmlspecialchars($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6 form-group">
                                <label>Financial Year</label>
                                <div id="select_from_year">
                                    <!-- dropdown injected via ajax -->
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Description</label>
                            <textarea id="txt_reciveble_discription" name="discription" rows="3" placeholder="Maximum 150 characters" class="form-control"></textarea>
                        </div>
                    </div>

                    <div class="modal-footer" style="background: var(--card-bg, #ffffff); border-top: 1px solid var(--border-color, #e2e8f0); padding: 18px 30px; display: flex; justify-content: flex-end; gap: 12px;">
                        <button type="button" class="btn btn-white" style="border-radius: 10px; font-weight: 700; padding: 8px 16px;" onclick="closeaddRecieveble();">Close</button>
                        <button type="button" class="btn btn-primary" style="border-radius: 10px; font-weight: 700; padding: 8px 20px;" onclick="saveRecieveble();">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- modal for add recieveble fee details end -->
    
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
    <script src="../js/loadingoverlay.min.js"></script>

</body>

</html>
