<?php
session_start();
?>
<!DOCTYPE html>
<html>

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>FD Transactions</title>

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
            console.log("Loading FD data for page:", page);
            $.ajax({               
               type: "POST",
               url: "api/fd_transactions.php",
               data: {
                action: 'load_data',
                page: page
               },
                success: function(data) {
                    var obj = jQuery.parseJSON(data);
                    console.log(obj);
                                       
                    var totalrows = obj[0].total_rows;
                    var stats = obj[2] || { total_principal: 0, total_interest: 0, total_count: 0 };
                    
                    var fmt = function(v) {
                        return new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR', minimumFractionDigits: 2 }).format(v);
                    };

                    var htm="";
                    
                    // Modern Premium KPI Summary Cards
                    htm += "<div class='row m-b-md'>";
                    // Card 1: Total FD Count
                    htm += "  <div class='col-lg-4 col-md-4'>";
                    htm += "    <div style='background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%); color: white; border-radius: 16px; padding: 20px; box-shadow: 0 4px 20px rgba(99, 102, 241, 0.12); display: flex; align-items: center; justify-content: space-between; border: 1px solid rgba(255,255,255,0.1); position: relative; overflow: hidden; min-height: 100px;'>";
                    htm += "      <div style='position: absolute; right: -10px; bottom: -10px; font-size: 60px; color: rgba(255,255,255,0.1);'><i class='fa fa-list-ol'></i></div>";
                    htm += "      <div style='position: relative; z-index: 2;'>";
                    htm += "        <span style='font-size: 11.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.8px; color: rgba(255,255,255,0.8);'>Total FD Count</span>";
                    htm += "        <h2 style='font-size: 24px; font-weight: 800; margin: 4px 0 0 0; color: white;'>" + stats.total_count + "</h2>";
                    htm += "      </div>";
                    htm += "    </div>";
                    htm += "  </div>";
                    // Card 2: Total Principal Invested
                    htm += "  <div class='col-lg-4 col-md-4'>";
                    htm += "    <div style='background: linear-gradient(135deg, #0284c7 0%, #0ea5e9 100%); color: white; border-radius: 16px; padding: 20px; box-shadow: 0 4px 20px rgba(14, 165, 233, 0.12); display: flex; align-items: center; justify-content: space-between; border: 1px solid rgba(255,255,255,0.1); position: relative; overflow: hidden; min-height: 100px;'>";
                    htm += "      <div style='position: absolute; right: -10px; bottom: -10px; font-size: 60px; color: rgba(255,255,255,0.1);'><i class='fa fa-briefcase'></i></div>";
                    htm += "      <div style='position: relative; z-index: 2;'>";
                    htm += "        <span style='font-size: 11.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.8px; color: rgba(255,255,255,0.8);'>Total Principal</span>";
                    htm += "        <h2 style='font-size: 24px; font-weight: 800; margin: 4px 0 0 0; color: white;'>" + fmt(stats.total_principal) + "</h2>";
                    htm += "      </div>";
                    htm += "    </div>";
                    htm += "  </div>";
                    // Card 3: Total Interest Received
                    htm += "  <div class='col-lg-4 col-md-4'>";
                    htm += "    <div style='background: linear-gradient(135deg, #059669 0%, #10b981 100%); color: white; border-radius: 16px; padding: 20px; box-shadow: 0 4px 20px rgba(16, 185, 129, 0.12); display: flex; align-items: center; justify-content: space-between; border: 1px solid rgba(255,255,255,0.1); position: relative; overflow: hidden; min-height: 100px;'>";
                    htm += "      <div style='position: absolute; right: -10px; bottom: -10px; font-size: 60px; color: rgba(255,255,255,0.1);'><i class='fa fa-percent'></i></div>";
                    htm += "      <div style='position: relative; z-index: 2;'>";
                    htm += "        <span style='font-size: 11.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.8px; color: rgba(255,255,255,0.8);'>Total Interest Received</span>";
                    htm += "        <h2 style='font-size: 24px; font-weight: 800; margin: 4px 0 0 0; color: white;'>" + fmt(stats.total_interest) + "</h2>";
                    htm += "      </div>";
                    htm += "    </div>";
                    htm += "  </div>";
                    htm += "</div>";

                    htm=htm+ "<div class='row'>";
                    htm=htm+ "<div class='col-lg-12'>";
                    htm=htm+ "<div class='ibox float-e-margins' style='box-shadow: 0 10px 30px rgba(0,0,0,0.02); border-radius: 16px; border: 1px solid #f1f5f9; overflow: hidden; background: #ffffff;'>";
                    htm=htm+ "<div class='ibox-title' style='background: #ffffff; border-bottom: 1px solid #f1f5f9; padding: 20px 24px;'>";
                    htm=htm+ "<h5 style='font-family: \"Outfit\", sans-serif; font-size: 16px; font-weight: 600; color: #1e293b; margin: 0;'>List of Fixed Deposits (FD)</h5>";
                    htm=htm+ "</div>";
                    htm=htm+ "<div class='ibox-content' style='padding: 0;'>";
                    htm=htm+ "<div class='table-responsive'>";
                    htm=htm+ "<table class='table' style='margin: 0; width: 100%; border-collapse: collapse;'>";
                    htm=htm+ "<thead>";
                    htm=htm+ "<tr>";
                    htm=htm+ "<th style='font-family: \"Outfit\", sans-serif; font-size: 12px; font-weight: 600; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; padding: 18px 24px; border-bottom: 2px solid #f1f5f9; background: #f8fafc;'>No.</th>";
                    htm=htm+ "<th style='font-family: \"Outfit\", sans-serif; font-size: 12px; font-weight: 600; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; padding: 18px 24px; border-bottom: 2px solid #f1f5f9; background: #f8fafc;'>Date</th>";
                    htm=htm+ "<th style='font-family: \"Outfit\", sans-serif; font-size: 12px; font-weight: 600; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; padding: 18px 24px; border-bottom: 2px solid #f1f5f9; background: #f8fafc;'>FD No</th>";
                    htm=htm+ "<th style='font-family: \"Outfit\", sans-serif; font-size: 12px; font-weight: 600; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; padding: 18px 24px; border-bottom: 2px solid #f1f5f9; background: #f8fafc;'>Bank Name</th>";
                    htm=htm+ "<th style='font-family: \"Outfit\", sans-serif; font-size: 12px; font-weight: 600; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; padding: 18px 24px; border-bottom: 2px solid #f1f5f9; background: #f8fafc;'>Principal Amount</th>";
                    htm=htm+ "<th style='font-family: \"Outfit\", sans-serif; font-size: 12px; font-weight: 600; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; padding: 18px 24px; border-bottom: 2px solid #f1f5f9; background: #f8fafc;'>Interest Rate (%)</th>";
                    htm=htm+ "<th style='font-family: \"Outfit\", sans-serif; font-size: 12px; font-weight: 600; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; padding: 18px 24px; border-bottom: 2px solid #f1f5f9; background: #f8fafc;'>Interest Received</th>";
                    htm=htm+ "<th style='font-family: \"Outfit\", sans-serif; font-size: 12px; font-weight: 600; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; padding: 18px 24px; border-bottom: 2px solid #f1f5f9; background: #f8fafc;'>Maturity Date</th>";
                    htm=htm+ "<th style='font-family: \"Outfit\", sans-serif; font-size: 12px; font-weight: 600; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; padding: 18px 24px; border-bottom: 2px solid #f1f5f9; background: #f8fafc;'>Maturity Amount</th>";
                    htm=htm+ "<th style='font-family: \"Outfit\", sans-serif; font-size: 12px; font-weight: 600; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; padding: 18px 24px; border-bottom: 2px solid #f1f5f9; background: #f8fafc;'>Status</th>";
                    htm=htm+ "<th style='font-family: \"Outfit\", sans-serif; font-size: 12px; font-weight: 600; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; padding: 18px 24px; border-bottom: 2px solid #f1f5f9; background: #f8fafc;'>Description</th>";
                    htm=htm+ "<th style='font-family: \"Outfit\", sans-serif; font-size: 12px; font-weight: 600; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; padding: 18px 24px; border-bottom: 2px solid #f1f5f9; background: #f8fafc;'>Action</th>";
                    htm=htm+ "</tr>";
                    htm=htm+ "</thead>";
                    htm=htm+ "<tbody>";
                    for (var i = 0; i < obj[1].length; i++) {
                        var j= i+1;
                        var slno=((page-1)*8)+j;
                        var escFdNo = obj[1][i].fd_no ? obj[1][i].fd_no.replace(/'/g, "\\'").replace(/"/g, "&quot;") : "";
                        var escBankName = obj[1][i].bank_name ? obj[1][i].bank_name.replace(/'/g, "\\'").replace(/"/g, "&quot;") : "";
                        var escDesc = obj[1][i].description ? obj[1][i].description.replace(/'/g, "\\'").replace(/"/g, "&quot;") : "";
                        
                        var statusBadge = "";
                        if(obj[1][i].status === 'Active') {
                            statusBadge = "<span style='background: #ecfdf5; color: #059669; font-weight: 600; font-size: 11px; padding: 6px 12px; border-radius: 30px; text-transform: uppercase; letter-spacing: 0.5px; display: inline-block;'>Active</span>";
                        } else if(obj[1][i].status === 'Matured') {
                            statusBadge = "<span style='background: #e0f2fe; color: #0284c7; font-weight: 600; font-size: 11px; padding: 6px 12px; border-radius: 30px; text-transform: uppercase; letter-spacing: 0.5px; display: inline-block;'>Matured</span>";
                        } else {
                            statusBadge = "<span style='background: #fef2f2; color: #dc2626; font-weight: 600; font-size: 11px; padding: 6px 12px; border-radius: 30px; text-transform: uppercase; letter-spacing: 0.5px; display: inline-block;'>Closed</span>";
                        }
                        
                        var actionHtm = "<div style='display: inline-flex; gap: 8px;'>";
                        actionHtm += "  <button type='button' onclick='editTransaction("+obj[1][i].id+",\"" +obj[1][i].date+ "\",\"" +escFdNo+ "\",\"" +escBankName+ "\",\"" +obj[1][i].amount+ "\",\"" +obj[1][i].interest_rate+ "\",\"" +obj[1][i].maturity_date+ "\",\"" +obj[1][i].maturity_amount+ "\",\"" +obj[1][i].status+ "\",\"" +escDesc+ "\");' style='border-radius: 8px; padding: 6px 14px; font-size: 12px; font-weight: 600; background: #e0f2fe; color: #0284c7; border: none; display: inline-flex; align-items: center; gap: 6px; cursor: pointer; transition: all 0.2s;' onmouseenter='this.style.background=\"#bae6fd\"' onmouseleave='this.style.background=\"#e0f2fe\"'><i class='fa fa-edit'></i> Edit</button>";
                        actionHtm += "  <button type='button' onclick='manageInterest("+obj[1][i].id+",\"" +escFdNo+ "\");' style='border-radius: 8px; padding: 6px 14px; font-size: 12px; font-weight: 600; background: #fef3c7; color: #d97706; border: none; display: inline-flex; align-items: center; gap: 6px; cursor: pointer; transition: all 0.2s;' onmouseenter='this.style.background=\"#fde68a\"' onmouseleave='this.style.background=\"#fef3c7\"'><i class='fa fa-percent'></i> Interest</button>";
                        if(obj[1][i].status !== 'Closed') {
                            actionHtm += "  <button type='button' onclick='closeFdPrompt("+obj[1][i].id+",\"" +escFdNo+ "\",\"" +escBankName+ "\",\"" +obj[1][i].amount+ "\",\"" +obj[1][i].maturity_amount+ "\");' style='border-radius: 8px; padding: 6px 14px; font-size: 12px; font-weight: 600; background: #dcfce7; color: #15803d; border: none; display: inline-flex; align-items: center; gap: 6px; cursor: pointer; transition: all 0.2s;' onmouseenter='this.style.background=\"#bbf7d0\"' onmouseleave='this.style.background=\"#dcfce7\"'><i class='fa fa-check-circle'></i> Close</button>";
                        }
                        actionHtm += "  <button type='button' onclick='deleteTransaction("+obj[1][i].id+");' style='border-radius: 8px; padding: 6px 14px; font-size: 12px; font-weight: 600; background: #fee2e2; color: #ef4444; border: none; display: inline-flex; align-items: center; gap: 6px; cursor: pointer; transition: all 0.2s;' onmouseenter='this.style.background=\"#fecaca\"' onmouseleave='this.style.background=\"#fee2e2\"'><i class='fa fa-trash'></i> Delete</button>";
                        actionHtm += "</div>";

                        htm=htm+ "<tr>";
                        htm=htm+ "<td style='font-family: \"Outfit\", sans-serif; font-size: 14px; color: #475569; padding: 18px 24px; border-bottom: 1px solid #f1f5f9; vertical-align: middle;'>"+slno+"</td>";
                        htm=htm+ "<td style='font-family: \"Outfit\", sans-serif; font-size: 14px; color: #475569; padding: 18px 24px; border-bottom: 1px solid #f1f5f9; vertical-align: middle;'>"+obj[1][i].date+"</td>";
                        htm=htm+ "<td style='font-family: \"Outfit\", sans-serif; font-size: 14px; font-weight: 600; color: #1e293b; padding: 18px 24px; border-bottom: 1px solid #f1f5f9; vertical-align: middle;'>"+obj[1][i].fd_no+"</td>";
                        htm=htm+ "<td style='font-family: \"Outfit\", sans-serif; font-size: 14px; color: #475569; padding: 18px 24px; border-bottom: 1px solid #f1f5f9; vertical-align: middle;'>"+obj[1][i].bank_name+"</td>";
                        htm=htm+ "<td style='font-family: \"Outfit\", sans-serif; font-size: 14px; font-weight: 700; color: #1e293b; padding: 18px 24px; border-bottom: 1px solid #f1f5f9; vertical-align: middle;'>₹"+Number(obj[1][i].amount).toLocaleString('en-IN')+"</td>";
                        htm=htm+ "<td style='font-family: \"Outfit\", sans-serif; font-size: 14px; color: #475569; padding: 18px 24px; border-bottom: 1px solid #f1f5f9; vertical-align: middle;'>"+obj[1][i].interest_rate+"%</td>";
                        htm=htm+ "<td style='font-family: \"Outfit\", sans-serif; font-size: 14px; font-weight: 700; color: #10b981; padding: 18px 24px; border-bottom: 1px solid #f1f5f9; vertical-align: middle;'>₹"+Number(obj[1][i].interest_received).toLocaleString('en-IN')+"</td>";
                        htm=htm+ "<td style='font-family: \"Outfit\", sans-serif; font-size: 14px; color: #475569; padding: 18px 24px; border-bottom: 1px solid #f1f5f9; vertical-align: middle;'>"+obj[1][i].maturity_date+"</td>";
                        htm=htm+ "<td style='font-family: \"Outfit\", sans-serif; font-size: 14px; font-weight: 700; color: #1e293b; padding: 18px 24px; border-bottom: 1px solid #f1f5f9; vertical-align: middle;'>₹"+Number(obj[1][i].maturity_amount).toLocaleString('en-IN')+"</td>";
                        htm=htm+ "<td style='padding: 18px 24px; border-bottom: 1px solid #f1f5f9; vertical-align: middle;'>"+statusBadge+"</td>";
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
        function editTransaction(id, date, fd_no, bank_name, amount, interest_rate, maturity_date, maturity_amount, status, description){
            $("#hdn_id").val(id);
            $("#date").val(date);
            $("#fd_no").val(fd_no);
            $("#bank_name").val(bank_name);
            $("#amount").val(amount);
            $("#interest_rate").val(interest_rate);
            $("#maturity_date").val(maturity_date);
            $("#maturity_amount").val(maturity_amount);
            $("#status").val(status);
            $("#description").val(description);
            $('#fdModal').modal('show');
        }

        function addTransaction(){
            $('#fd_form')[0].reset();
            $("#hdn_id").val(0);
            $('#fdModal').modal('show');
        }

        function closeModal(){
            $('#fd_form')[0].reset();
            $("#hdn_id").val(0);
            $('#fdModal').modal('hide');
        }

        function saveTransaction() {
            swal({
                title: "Are you sure?",
                text: "Do you want to save this FD transaction?",
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
                        fd_no: $('#fd_no').val(),
                        bank_name: $('#bank_name').val(),
                        amount: $('#amount').val(),
                        interest_rate: $('#interest_rate').val(),
                        maturity_date: $('#maturity_date').val(),
                        maturity_amount: $('#maturity_amount').val(),
                        status: $('#status').val(),
                        description: $('#description').val(),
                        id: $("#hdn_id").val(),      
                    };
                    
                    if(!data.date || !data.fd_no || !data.bank_name || !data.amount || !data.interest_rate || !data.maturity_date || !data.maturity_amount || !data.status) {
                        swal("Error", "Please fill in all required fields marked with *", "error");
                        return;
                    }

                    load_overlay();
                    $.ajax({
                        type: "POST",
                        url: "api/fd_transactions.php",
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
                text: "Do you want to delete this FD transaction?",
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
                        url: "api/fd_transactions.php",
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

        function manageInterest(fdId, fdNo) {
            $("#interest_fd_id").val(fdId);
            $("#interestModalTitle").text("Interest Credits - FD No: " + fdNo);
            $('#interest_form')[0].reset();
            // Default date to today
            var today = new Date().toISOString().split('T')[0];
            $("#interest_date").val(today);
            loadInterestCredits(fdId);
            $('#interestModal').modal('show');
        }

        function loadInterestCredits(fdId) {
            $.ajax({
                type: "POST",
                url: "api/fd_transactions.php",
                data: {
                    action: 'load_interest_credits',
                    fd_id: fdId
                },
                success: function(response) {
                    var data = JSON.parse(response);
                    var html = "";
                    if (data.length === 0) {
                        html = "<tr><td colspan='4' class='text-center' style='color: #64748b;'>No interest credits recorded for this FD.</td></tr>";
                    } else {
                        for (var i = 0; i < data.length; i++) {
                            html += "<tr>";
                            html += "  <td>" + data[i].date + "</td>";
                            html += "  <td style='font-weight: bold; color: #10b981;'>₹" + Number(data[i].amount).toLocaleString('en-IN') + "</td>";
                            html += "  <td>" + (data[i].description ? data[i].description : "-") + "</td>";
                            html += "  <td><button type='button' class='btn btn-xs btn-danger' onclick='deleteInterestCredit(" + data[i].id + ", " + fdId + ")'><i class='fa fa-trash'></i> Delete</button></td>";
                            html += "</tr>";
                        }
                    }
                    $("#tbody_interest_credits").html(html);
                },
                error: function(xhr, status, error) {
                    console.log("AJAX error: ", status, error);
                }
            });
        }

        function saveInterestCredit() {
            var fdId = $("#interest_fd_id").val();
            var date = $("#interest_date").val();
            var amount = $("#interest_amount").val();
            var description = $("#interest_description").val();

            if (!date || !amount) {
                swal("Error", "Please fill in all required fields *", "error");
                return;
            }

            $.ajax({
                type: "POST",
                url: "api/fd_transactions.php",
                data: {
                    action: 'save_interest_credit',
                    fd_id: fdId,
                    date: date,
                    amount: amount,
                    description: description
                },
                success: function(response) {
                    alertsuccess(response);
                    $('#interest_form')[0].reset();
                    var today = new Date().toISOString().split('T')[0];
                    $("#interest_date").val(today);
                    loadInterestCredits(fdId);
                },
                error: function(xhr, status, error) {
                    var msg = JSON.parse(xhr.responseText);
                    alerterror(msg, xhr);
                }
            });
        }

        function deleteInterestCredit(id, fdId) {
            swal({
                title: "Are you sure?",
                text: "Do you want to delete this interest credit transaction?",
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
                    $.ajax({
                        type: "POST",
                        url: "api/fd_transactions.php",
                        data: {
                            action: 'delete_interest_credit',
                            id: id
                        },
                        success: function(response) {
                            alertwarning(response);
                            loadInterestCredits(fdId);
                        },
                        error: function(xhr, status, error) {
                            console.log('AJAX error:', status, error);
                        }
                    });
                }
            });
        }

        function closeFdPrompt(id, fd_no, bank_name, principal_amount, maturity_amount) {
            $("#close_fd_id").val(id);
            $("#close_fd_no").val(fd_no);
            $("#close_bank_name").val(bank_name);
            $("#close_maturity_amount_expected").val("₹" + Number(maturity_amount).toLocaleString('en-IN'));
            
            var today = new Date().toISOString().split('T')[0];
            $("#close_date").val(today);
            $("#close_reference_no").val(fd_no);
            $("#close_description").val("FD :" + fd_no + " Closed Amount");
            
            load_overlay();
            $.ajax({
                type: "POST",
                url: "api/fd_transactions.php",
                data: {
                    action: 'load_interest_credits',
                    fd_id: id
                },
                success: function(response) {
                    close_overlay();
                    var data = JSON.parse(response);
                    var interest_sum = 0;
                    for (var i = 0; i < data.length; i++) {
                        interest_sum += parseFloat(data[i].amount) || 0;
                    }
                    var total_received = parseFloat(principal_amount) + interest_sum;
                    $("#close_amount_received").val(total_received);
                    $('#closeFdModal').modal('show');
                },
                error: function(xhr, status, error) {
                    close_overlay();
                    console.log("Error loading interest credits:", error);
                    // Fallback to maturity amount if interest load fails
                    $("#close_amount_received").val(maturity_amount);
                    $('#closeFdModal').modal('show');
                }
            });
        }

        function confirmCloseFd() {
            var fdId = $("#close_fd_id").val();
            var closeDate = $("#close_date").val();
            var amountReceived = $("#close_amount_received").val();
            var referenceNo = $("#close_reference_no").val();
            var description = $("#close_description").val();

            if (!closeDate || !amountReceived) {
                swal("Error", "Please fill in all required fields marked with *", "error");
                return;
            }

            swal({
                title: "Are you sure?",
                text: "Do you want to close this FD? A bank deposit transaction of ₹" + Number(amountReceived).toLocaleString('en-IN') + " will be recorded.",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#16a34a",
                confirmButtonText: "Yes, Close FD!",
                cancelButtonText: "Cancel",
                closeOnConfirm: false,
                closeOnCancel: true
            },
            function (isConfirm) {
                if (isConfirm){
                    load_overlay();
                    $.ajax({
                        type: "POST",
                        url: "api/fd_transactions.php",
                        data: {
                            action: 'close_fd',
                            fd_id: fdId,
                            date: closeDate,
                            amount: amountReceived,
                            reference_no: referenceNo,
                            description: description
                        },
                        success: function(response) {
                            close_overlay();
                            $('#closeFdModal').modal('hide');
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
                    <h2 style="margin: 0; font-weight: 700; font-family: 'Outfit', sans-serif; font-size: 24px; color: #0f172a;">FD Transactions</h2>
                </div>
                <div style="display: flex; gap: 10px; align-items: center;">
                     <a href="fd_report_print.php" target="_blank" class="btn btn-warning" style="margin: 0; border-radius: 8px; font-weight: 600; padding: 8px 16px; font-size: 13px; background: #f59e0b; border: none; display: inline-flex; align-items: center; gap: 8px; color: white;"><i class="fa fa-print"></i> Print Report</a>
                     <button type="button" class="btn btn-primary" onclick="addTransaction()" style="margin: 0; border-radius: 8px; font-weight: 600; padding: 8px 16px; font-size: 13px; background: #3b82f6; border: none; display: inline-flex; align-items: center; gap: 8px; color: white;"><i class="fa fa-plus"></i> Add FD Transaction</button>
                </div>
            </div>
            
            <div class="wrapper wrapper-content animated fadeInRight" id="table_client">
                <!-- data injected Dynamically via ajax -->
            </div>
        </div>
       
        <div class="modal inmodal" id="fdModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content animated bounceInRight">
                    <form method="POST" id="fd_form">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                            <h4 class="modal-title">FD Transaction</h4>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label>Date *</label>
                                <input type="date" id="date" name="date" required class="form-control">
                            </div>
                            <div class="form-group">
                                <label>FD Number *</label>
                                <input type="text" id="fd_no" name="fd_no" required placeholder="FD Certificate No" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Bank Name *</label>
                                <input type="text" id="bank_name" name="bank_name" required placeholder="Bank Name" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Principal Amount *</label>
                                <input type="number" id="amount" name="amount" required placeholder="Principal Amount" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Interest Rate (%) *</label>
                                <input type="number" step="0.01" id="interest_rate" name="interest_rate" required placeholder="Interest Rate % (e.g. 6.5)" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Maturity Date *</label>
                                <input type="date" id="maturity_date" name="maturity_date" required class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Maturity Amount *</label>
                                <input type="number" id="maturity_amount" name="maturity_amount" required placeholder="Maturity Amount" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Status *</label>
                                <select id="status" name="status" required class="form-control">
                                    <option value="Active">Active</option>
                                    <option value="Matured">Matured</option>
                                    <option value="Closed">Closed</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Description</label>
                                <input type="text" id="description" name="description" placeholder="Description/Remarks" class="form-control">
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

        <!-- Interest Credits Modal -->
        <div class="modal inmodal" id="interestModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content animated bounceInRight">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                        <h4 class="modal-title" id="interestModalTitle">Interest Credits - FD No: </h4>
                    </div>
                    <div class="modal-body">
                        <!-- Add Interest Form -->
                        <form method="POST" id="interest_form" style="margin-bottom: 24px; padding-bottom: 24px; border-bottom: 1px solid #e2e8f0;">
                            <input type="hidden" id="interest_fd_id" name="fd_id" value="0">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Date of Credit *</label>
                                        <input type="date" id="interest_date" name="date" required class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Interest Amount (₹) *</label>
                                        <input type="number" id="interest_amount" name="amount" required placeholder="Amount" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Description/Remarks</label>
                                        <input type="text" id="interest_description" name="description" placeholder="Optional remarks" class="form-control">
                                    </div>
                                </div>
                            </div>
                            <div class="text-right">
                                <button type="button" class="btn btn-primary" onclick="saveInterestCredit();" style="border-radius: 8px; font-weight: 600; padding: 8px 20px; font-size: 13px; background: #3b82f6; border: none; color: white;">
                                    <i class="fa fa-plus"></i> Add Interest Credit
                                </button>
                            </div>
                        </form>

                        <!-- Logged Interest List -->
                        <h5 style="font-family: 'Outfit', sans-serif; font-size: 15px; font-weight: 600; color: #1e293b; margin-bottom: 12px;">Logged Interest Credits</h5>
                        <div class="table-responsive">
                            <table class="table table-striped" id="tbl_interest_credits" style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Description</th>
                                        <th style="width: 100px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="tbody_interest_credits">
                                    <!-- Dynamic rows loaded via AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-white" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Close FD Modal -->
        <div class="modal inmodal" id="closeFdModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content animated bounceInRight">
                    <form method="POST" id="close_fd_form">
                        <input type="hidden" id="close_fd_id" name="fd_id" value="0">
                        <div class="modal-header" style="background: #16a34a; color: white; padding: 20px 15px;">
                            <button type="button" class="close" data-dismiss="modal" style="color: white; opacity: 0.8;"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                            <h4 class="modal-title" style="color: white; font-family: 'Outfit', sans-serif; font-weight: 600;">Close Fixed Deposit</h4>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label>FD Number</label>
                                <input type="text" id="close_fd_no" class="form-control" readonly>
                            </div>
                            <div class="form-group">
                                <label>Bank Name</label>
                                <input type="text" id="close_bank_name" class="form-control" readonly>
                            </div>
                            <div class="form-group">
                                <label>Maturity Amount (Expected)</label>
                                <input type="text" id="close_maturity_amount_expected" class="form-control" readonly>
                            </div>
                            <div class="form-group">
                                <label>Closing Date *</label>
                                <input type="date" id="close_date" name="close_date" required class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Total Amount Received (to Bank Account) *</label>
                                <input type="number" id="close_amount_received" name="amount" required placeholder="Amount received" class="form-control">
                                <span class="help-block m-b-none" style="font-size: 11px; color: #64748b;">This amount will be added to the bank account as a Deposit transaction.</span>
                            </div>
                            <div class="form-group">
                                <label>Reference / Cheque No</label>
                                <input type="text" id="close_reference_no" name="reference_no" placeholder="Reference or Certificate Number" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Description / Remarks</label>
                                <input type="text" id="close_description" name="description" placeholder="Optional details (e.g. FD Closed)" class="form-control">
                            </div>
                        </div>
                        
                        <div class="modal-footer">
                            <button type="button" class="btn btn-white" data-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" onclick="confirmCloseFd();" style="background: #16a34a; border-color: #16a34a;">Confirm Closure</button>
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
