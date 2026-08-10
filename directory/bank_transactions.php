<?php
session_start();
?>
<!DOCTYPE html>
<html>

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Bank Transactions</title>

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
            loadData(1);
        });  

        function loadData(page) {
            $('#hdn_current_page').val(page);
            console.log("Loading bank data for page:", page);
            $.ajax({               
               type: "POST",
               url: "api/bank_transactions.php",
               data: {
                action: 'load_data',
                page: page
               },
                success: function(data) {
                    var obj = jQuery.parseJSON(data);
                    console.log(obj);
                                       
                    var totalrows = obj[0].total_rows;
                    var bankBalance = obj[2] ? obj[2].bank_balance : 0;
                    
                    var htm="";
                    // Modern available bank balance card
                    htm=htm+ "<div class='row m-b-md'>";
                    htm=htm+ "  <div class='col-lg-6 col-lg-offset-3'>";
                    htm=htm+ "    <div style='background: linear-gradient(135deg, #1d4ed8 0%, #3b82f6 100%); color: white; border-radius: 16px; padding: 25px; box-shadow: 0 10px 25px rgba(29, 78, 216, 0.25); position: relative; overflow: hidden; display: flex; align-items: center; justify-content: space-between;'>";
                    htm=htm+ "      <div style='position: absolute; right: -20px; top: -20px; width: 120px; height: 120px; background: rgba(255, 255, 255, 0.08); border-radius: 50%; pointer-events: none;'></div>";
                    htm=htm+ "      <div style='position: absolute; right: 40px; bottom: -40px; width: 150px; height: 150px; background: rgba(255, 255, 255, 0.05); border-radius: 50%; pointer-events: none;'></div>";
                    htm=htm+ "      <div style='display: flex; align-items: center; gap: 18px;'>";
                    htm=htm+ "        <div style='background: rgba(255, 255, 255, 0.15); width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(5px);'>";
                    htm=htm+ "          <i class='fa fa-university' style='font-size: 24px; color: #ffffff;'></i>";
                    htm=htm+ "        </div>";
                    htm=htm+ "        <div>";
                    htm=htm+ "          <span style='font-size: 11px; font-weight: 500; text-transform: uppercase; letter-spacing: 1px; color: rgba(255,255,255,0.75);'>Available Bank Balance</span>";
                    htm=htm+ "          <h1 style='font-size: 30px; font-weight: 800; margin: 2px 0 0 0; color: #ffffff; letter-spacing: -0.5px;'>₹"+Number(bankBalance).toLocaleString('en-IN')+"</h1>";
                    htm=htm+ "        </div>";
                    htm=htm+ "      </div>";
                    htm=htm+ "      <div style='font-size: 10px; font-weight: 500; background: rgba(255, 255, 255, 0.15); padding: 6px 12px; border-radius: 30px; letter-spacing: 0.5px; border: 1px solid rgba(255,255,255,0.2);'><i class='fa fa-refresh fa-spin'></i> Live Sync</div>";
                    htm=htm+ "    </div>";
                    htm=htm+ "  </div>";
                    htm=htm+ "</div>";

                    htm=htm+ "<div class='row'>";
                    htm=htm+ "<div class='col-lg-12'>";
                    htm=htm+ "<div class='ibox float-e-margins' style='box-shadow: 0 10px 30px rgba(0,0,0,0.02); border-radius: 16px; border: 1px solid #f1f5f9; overflow: hidden; background: #ffffff;'>";
                    htm=htm+ "<div class='ibox-title' style='background: #ffffff; border-bottom: 1px solid #f1f5f9; padding: 20px 24px;'>";
                    htm=htm+ "<h5 style='font-family: \"Outfit\", sans-serif; font-size: 16px; font-weight: 600; color: #1e293b; margin: 0;'>List of Bank Transactions</h5>";
                    htm=htm+ "</div>";
                    htm=htm+ "<div class='ibox-content' style='padding: 0;'>";
                    htm=htm+ "<div class='table-responsive'>";
                    htm=htm+ "<table class='table' style='margin: 0; width: 100%; border-collapse: collapse;'>";
                    htm=htm+ "<thead>";
                    htm=htm+ "<tr>";
                    htm=htm+ "<th style='font-family: \"Outfit\", sans-serif; font-size: 12px; font-weight: 600; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; padding: 18px 24px; border-bottom: 2px solid #f1f5f9; background: #f8fafc;'>No.</th>";
                    htm=htm+ "<th style='font-family: \"Outfit\", sans-serif; font-size: 12px; font-weight: 600; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; padding: 18px 24px; border-bottom: 2px solid #f1f5f9; background: #f8fafc;'>Date</th>";
                    htm=htm+ "<th style='font-family: \"Outfit\", sans-serif; font-size: 12px; font-weight: 600; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; padding: 18px 24px; border-bottom: 2px solid #f1f5f9; background: #f8fafc;'>Type</th>";
                    htm=htm+ "<th style='font-family: \"Outfit\", sans-serif; font-size: 12px; font-weight: 600; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; padding: 18px 24px; border-bottom: 2px solid #f1f5f9; background: #f8fafc;'>Amount</th>";
                    htm=htm+ "<th style='font-family: \"Outfit\", sans-serif; font-size: 12px; font-weight: 600; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; padding: 18px 24px; border-bottom: 2px solid #f1f5f9; background: #f8fafc;'>Reference / Cheque No</th>";
                    htm=htm+ "<th style='font-family: \"Outfit\", sans-serif; font-size: 12px; font-weight: 600; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; padding: 18px 24px; border-bottom: 2px solid #f1f5f9; background: #f8fafc;'>Description</th>";
                    htm=htm+ "<th style='font-family: \"Outfit\", sans-serif; font-size: 12px; font-weight: 600; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; padding: 18px 24px; border-bottom: 2px solid #f1f5f9; background: #f8fafc;'>Action</th>";
                    htm=htm+ "</tr>";
                    htm=htm+ "</thead>";
                    htm=htm+ "<tbody>";
                    for (var i = 0; i < obj[1].length; i++) {
                        var j= i+1;
                        var slno=((page-1)*8)+j;
                        var escDesc = obj[1][i].description ? obj[1][i].description.replace(/'/g, "\\'").replace(/"/g, "&quot;") : "";
                        var escRef = obj[1][i].reference_no ? obj[1][i].reference_no.replace(/'/g, "\\'").replace(/"/g, "&quot;") : "";
                        
                        var typeBadge = "";
                        if(obj[1][i].type === 'Deposit') {
                            typeBadge = "<span style='background: #ecfdf5; color: #059669; font-weight: 600; font-size: 11px; padding: 6px 12px; border-radius: 30px; text-transform: uppercase; letter-spacing: 0.5px; display: inline-block;'>Deposit</span>";
                        } else if(obj[1][i].type === 'Interest') {
                            typeBadge = "<span style='background: #f5f3ff; color: #7c3aed; font-weight: 600; font-size: 11px; padding: 6px 12px; border-radius: 30px; text-transform: uppercase; letter-spacing: 0.5px; display: inline-block;'><i class='fa fa-percent' style='font-size:9px; margin-right:4px;'></i>Interest</span>";
                        } else {
                            typeBadge = "<span style='background: #fef2f2; color: #dc2626; font-weight: 600; font-size: 11px; padding: 6px 12px; border-radius: 30px; text-transform: uppercase; letter-spacing: 0.5px; display: inline-block;'>Withdrawal</span>";
                        }
                        
                        var actionHtm = "<div style='display: inline-flex; gap: 8px;'>";
                        actionHtm += "  <button type='button' onclick='editTransaction("+obj[1][i].id+",\"" +obj[1][i].date+ "\",\"" +obj[1][i].type+ "\",\"" +obj[1][i].amount+ "\",\"" +escRef+ "\",\"" +escDesc+ "\");' style='border-radius: 8px; padding: 6px 14px; font-size: 12px; font-weight: 600; background: #e0f2fe; color: #0284c7; border: none; display: inline-flex; align-items: center; gap: 6px; cursor: pointer; transition: all 0.2s;' onmouseenter='this.style.background=\"#bae6fd\"' onmouseleave='this.style.background=\"#e0f2fe\"'><i class='fa fa-edit'></i> Edit</button>";
                        actionHtm += "  <button type='button' onclick='deleteTransaction("+obj[1][i].id+");' style='border-radius: 8px; padding: 6px 14px; font-size: 12px; font-weight: 600; background: #fee2e2; color: #ef4444; border: none; display: inline-flex; align-items: center; gap: 6px; cursor: pointer; transition: all 0.2s;' onmouseenter='this.style.background=\"#fecaca\"' onmouseleave='this.style.background=\"#fee2e2\"'><i class='fa fa-trash'></i> Delete</button>";
                        actionHtm += "</div>";

                        htm=htm+ "<tr>";
                        htm=htm+ "<td style='font-family: \"Outfit\", sans-serif; font-size: 14px; color: #475569; padding: 18px 24px; border-bottom: 1px solid #f1f5f9; vertical-align: middle;'>"+slno+"</td>";
                        htm=htm+ "<td style='font-family: \"Outfit\", sans-serif; font-size: 14px; color: #475569; padding: 18px 24px; border-bottom: 1px solid #f1f5f9; vertical-align: middle;'>"+obj[1][i].date+"</td>";
                        htm=htm+ "<td style='padding: 18px 24px; border-bottom: 1px solid #f1f5f9; vertical-align: middle;'>"+typeBadge+"</td>";
                        htm=htm+ "<td style='font-family: \"Outfit\", sans-serif; font-size: 14px; font-weight: 700; color: #1e293b; padding: 18px 24px; border-bottom: 1px solid #f1f5f9; vertical-align: middle;'>₹"+Number(obj[1][i].amount).toLocaleString('en-IN')+"</td>";
                        htm=htm+ "<td style='font-family: \"Outfit\", sans-serif; font-size: 14px; color: #475569; padding: 18px 24px; border-bottom: 1px solid #f1f5f9; vertical-align: middle;'>"+(obj[1][i].reference_no ? obj[1][i].reference_no : "-")+"</td>";
                        htm=htm+ "<td style='font-family: \"Outfit\", sans-serif; font-size: 14px; color: #475569; padding: 18px 24px; border-bottom: 1px solid #f1f5f9; vertical-align: middle;'>"+(obj[1][i].description ? obj[1][i].description : "-")+"</td>";
                        htm=htm+ "<td style='padding: 18px 24px; border-bottom: 1px solid #f1f5f9; vertical-align: middle;'>"+actionHtm+"</td>";
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
        }
    </script>

    <script>
        function editTransaction(id, date, type, amount, reference_no, description){
            $("#hdn_id").val(id);
            $("#date").val(date);
            $("#type").val(type);
            $("#amount").val(amount);
            $("#reference_no").val(reference_no);
            $("#description").val(description);
            $('#bankModal').modal('show');
        }

        function addTransaction(){
            $('#bank_form')[0].reset();
            $("#hdn_id").val(0);
            $('#bankModal').modal('show');
        }

        function closeModal(){
            $('#bank_form')[0].reset();
            $("#hdn_id").val(0);
            $('#bankModal').modal('hide');
        }

        function saveTransaction() {
            swal({
                title: "Are you sure?",
                text: "Do you want to save this transaction?",
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
                        action: 'save_transaction',
                        date: $('#date').val(),
                        type: $('#type').val(),
                        amount: $('#amount').val(),
                        reference_no: $('#reference_no').val(),
                        description: $('#description').val(),
                        id: $("#hdn_id").val(),      
                    };
                    
                    if(!data.date || !data.type || !data.amount) {
                        swal("Error", "Please fill in all required fields (Date, Type, Amount)", "error");
                        return;
                    }

                    load_overlay();
                    $.ajax({
                        type: "POST",
                        url: "api/bank_transactions.php",
                        data: data,
                        success: function(response) {
                            close_overlay();
                            closeModal();
                            alertsuccess(response);
                            loadData($('#hdn_current_page').val());
                        },
                        error: function (xhr, status){
                            close_overlay();
                            var msgObj = JSON.parse(xhr.responseText);
                            alerterror(msgObj, xhr);
                        }
                    });
                }
            });
        }

        function deleteTransaction(id) {
            swal({
                title: "Are you sure?",
                text: "Do you want to delete this transaction?",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "Yes, Delete!",
                cancelButtonText: "Cancel",
                closeOnConfirm: false,
                closeOnCancel: true
            },
            function (isConfirm) {
                if (isConfirm){
                    load_overlay();
                    $.ajax({
                        type: "POST",
                        url: "api/bank_transactions.php",
                        data: {
                            action: 'delete_transaction',
                            id: id
                        },
                        success: function(response) {
                            close_overlay();
                            alertwarning(response);
                            loadData($('#hdn_current_page').val());
                        },
                        error: function(xhr, status, error) {
                            close_overlay();
                            console.log('AJAX error:', status, error);
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
    <input type="hidden" id="hdn_current_page"  value="0">
    <input type="hidden" id="hdn_id"  value="0">

    <div id="wrapper">

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

        <div id="page-wrapper" class="gray-bg">
            <div class="row border-bottom">
                <nav class="navbar navbar-static-top" role="navigation" style="margin-bottom: 0">
                    <div class="navbar-header">
                        <a class="navbar-minimalize minimalize-styl-2 btn btn-primary " href="#"><i class="fa fa-bars"></i> </a>
                    </div>
                    <ul class="nav navbar-top-links navbar-right">     
                        <li>
                            <a href="../app_login_manager/logout.php" style="color: #147ad1;">
                                <i class="fa fa-sign-out"></i> Log out
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>

            <div class="row wrapper border-bottom white-bg page-heading" style="display: flex; align-items: center; justify-content: space-between; padding: 15px 20px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
                <div style="flex: 1;">
                    <h2 style="margin: 0; font-weight: 700; font-family: 'Outfit', sans-serif; font-size: 24px; color: #0f172a;">Bank Transactions</h2>
                </div>
                <div style="display: flex; gap: 10px; align-items: center;">
                     <a href="bank_report_print.php" target="_blank" class="btn btn-warning" style="margin: 0; border-radius: 8px; font-weight: 600; padding: 8px 16px; font-size: 13px; background: #f59e0b; border: none; display: inline-flex; align-items: center; gap: 8px; color: white;"><i class="fa fa-print"></i> Print Report</a>
                     <button type="button" class="btn btn-primary" onclick="addTransaction()" style="margin: 0; border-radius: 8px; font-weight: 600; padding: 8px 16px; font-size: 13px; background: #3b82f6; border: none; display: inline-flex; align-items: center; gap: 8px; color: white;"><i class="fa fa-plus"></i> Add Bank Transaction</button>
                </div>
            </div>
            
            <div class="wrapper wrapper-content animated fadeInRight" id="table_client">
                <!-- data injected Dynamically via ajax -->
            </div>
        </div>
       
        <div class="modal inmodal" id="bankModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content animated bounceInRight">
                    <form method="POST" id="bank_form">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                            <h4 class="modal-title">Bank Transaction</h4>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label>Date *</label>
                                <input type="date" id="date" name="date" required class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Type *</label>
                                <select id="type" name="type" required class="form-control">
                                    <option value="" disabled selected>Select Type</option>
                                    <option value="Deposit">Deposit</option>
                                    <option value="Withdrawal">Withdrawal</option>
                                    <option value="Interest">Interest</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Amount *</label>
                                <input type="number" id="amount" name="amount" required placeholder="Amount" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Reference / Cheque No</label>
                                <input type="text" id="reference_no" name="reference_no" placeholder="Reference / Cheque No" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Description</label>
                                <input type="text" id="description" name="description" placeholder="Description" class="form-control">
                            </div>
                        </div>
                        
                        <div class="modal-footer">
                            <button type="button" class="btn btn-white" onclick="closeModal();">Close</button>
                            <button type="button" class="btn btn-primary" onclick="saveTransaction();">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
       
    <script src="../js/jquery-3.1.1.min.js"></script>
    <script src="../js/bootstrap.min.js"></script>
    <script src="../js/plugins/metisMenu/jquery.metisMenu.js"></script>
    <script src="../js/plugins/slimscroll/jquery.slimscroll.min.js"></script>

    <script src="../js/inspinia.js"></script>
    <script src="../js/plugins/pace/pace.min.js"></script>
    <script src="../js/loadingoverlay.min.js"></script>
</body>

</html>
