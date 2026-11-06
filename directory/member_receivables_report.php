<?php
session_start();
include_once __DIR__ . '/../app_common/auth_helper.php';

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

    <title>Pending Payments Report</title>

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

        function searchMembers() {
            loadData(1);
        }

        function clearSearch() {
            $('#txt_search').val('');
            loadData(1);
        }

        function loadData(page) {
            $('#hdn_current_page').val(page);
            var val = $('#txt_search').val();
            var onlyDues = $('#chk_only_dues').is(':checked') ? 1 : 0;

            $.ajax({               
                type: "POST",
                url: "api/member_receivables_report.php",
                data: {
                    action: 'load_data',
                    page: page,
                    val: val,
                    only_dues: onlyDues
                },
                success: function(response) {
                    try {
                        var obj = jQuery.parseJSON(response);
                        var totalrows = obj[0].total_rows;
                        var members = obj[1];
                        var summary = obj[2];

                        // Update top-level cards
                        $('#card_overdue_members').text(summary.count_dues);
                        $('#card_overdue_amount').text('₹' + summary.sum_dues.toLocaleString('en-IN'));

                        var htm = "";
                        htm += "<div class='row'>";
                        htm += "<div class='col-lg-12'>";
                        htm += "<div class='ibox float-e-margins' style='box-shadow: 0 4px 20px rgba(0,0,0,0.05); border-radius: 12px; overflow: hidden;'>";
                        htm += "<div class='ibox-content'>";
                        htm += "<div class='table-responsive'>";
                        htm += "<table class='table table-striped table-hover'>";
                        htm += "<thead>";
                        htm += "<tr>";
                        htm += "<th>No</th>";
                        htm += "<th>Member Name</th>";
                        htm += "<th>Phone</th>";
                        htm += "<th>Total Expected (₹)</th>";
                        htm += "<th>Total Received (₹)</th>";
                        htm += "<th>Balance Due (₹)</th>";
                        htm += "<th>Action</th>";
                        htm += "</tr>";
                        htm += "</thead>";
                        htm += "<tbody>";

                        if (members.length === 0) {
                            htm += "<tr><td colspan='7' style='text-align: center; padding: 20px; color: #64748b;'>No records found</td></tr>";
                        } else {
                            for (var i = 0; i < members.length; i++) {
                                var m = members[i];
                                var slno = ((page - 1) * 10) + (i + 1);
                                var fullName = m.first_name + " " + (m.middle_name ? m.middle_name + " " : "") + m.last_name;

                                htm += "<tr>";
                                htm += "<td>" + slno + "</td>";
                                htm += "<td><strong>" + fullName + "</strong></td>";
                                htm += "<td>" + (m.phone ? m.phone : '<span class="text-muted">N/A</span>') + "</td>";
                                htm += "<td>" + parseFloat(m.total_expected).toLocaleString('en-IN') + "</td>";
                                htm += "<td>" + parseFloat(m.total_received).toLocaleString('en-IN') + "</td>";

                                var balance = parseFloat(m.balance_due);
                                if (balance > 0) {
                                    htm += "<td><span class='label label-danger' style='font-size: 11.5px; border-radius: 4px; padding: 3px 8px;'>₹" + balance.toLocaleString('en-IN') + "</span></td>";
                                } else {
                                    htm += "<td><span class='label label-primary' style='font-size: 11.5px; border-radius: 4px; padding: 3px 8px;'>Paid</span></td>";
                                }

                                htm += "<td>";
                                htm += "<button type='button' class='btn btn-primary btn-xs' style='border-radius: 6px; padding: 3px 10px;' onclick='viewMemberStatement(" + m.id + ", \"" + m.first_name + "\", \"" + (m.middle_name || "") + "\", \"" + m.last_name + "\")'>";
                                htm += "<i class='fa fa-list'></i> View Statement";
                                htm += "</button>";

                                if (balance > 0) {
                                    var safeName = fullName.replace(/'/g, "\\'").replace(/"/g, '&quot;');
                                    htm += " <button type='button' class='btn btn-success btn-xs' style='border-radius: 6px; padding: 3px 10px; margin-left: 6px; background-color: #10b981; border-color: #10b981;' onclick='openWhatsAppAlertModal(" + m.id + ", \"" + safeName + "\", " + balance + ")'>";
                                    htm += "<i class='fa fa-whatsapp'></i> Send Alert";
                                    htm += "</button>";
                                }
                                htm += "</td>";
                                htm += "</tr>";
                            }
                        }

                        htm += "</tbody>";
                        htm += "</table>";
                        htm += "</div>"; // table-responsive
                        htm += "</div>"; // ibox-content
                        htm += "</div>"; // ibox
                        htm += "</div>"; // col-lg-12
                        htm += "</div>"; // row

                        $('#table_receivables').html(htm);
                        var htmpage = paginate(totalrows, page);
                        $('#table_receivables').append(htmpage);
                    } catch (e) {
                        console.error("Data render error:", e);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error: ', status, error);
                }
            });
            loadMenu();
        }

        function viewMemberStatement(memberId, firstName, middleName, lastName) {
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = 'fees_receiveble.php';
            
            var inputId = document.createElement('input');
            inputId.type = 'hidden';
            inputId.name = 'member_id';
            inputId.value = memberId;
            form.appendChild(inputId);

            var inputFN = document.createElement('input');
            inputFN.type = 'hidden';
            inputFN.name = 'first_name';
            inputFN.value = firstName;
            form.appendChild(inputFN);

            var inputMN = document.createElement('input');
            inputMN.type = 'hidden';
            inputMN.name = 'middle_name';
            inputMN.value = middleName || '';
            form.appendChild(inputMN);

            var inputLN = document.createElement('input');
            inputLN.type = 'hidden';
            inputLN.name = 'last_name';
            inputLN.value = lastName;
            form.appendChild(inputLN);

            document.body.appendChild(form);
            form.submit();
        }

        function openWhatsAppAlertModal(memberId, memberName, balance) {
            $('#wa_modal_member_id').val(memberId);
            $('#wa_modal_balance').val(balance);
            $('#wa_modal_name').text(memberName);
            $('#wa_modal_phone').text('Loading phone...');
            $('#wa_modal_balance_display').text('₹' + parseFloat(balance).toLocaleString('en-IN', {minimumFractionDigits: 2}));
            $('#wa_modal_text').val('Loading template...');
            $('#wa_modal_url').val('');
            $('#modalWhatsAppAlert').modal('show');

            $.ajax({
                type: "POST",
                url: "api/whatsapp.php",
                data: {
                    action: 'get_balance_alert_preview',
                    member_id: memberId,
                    balance: balance
                },
                success: function(res) {
                    if (res.status === 'success') {
                        $('#wa_modal_phone').text(res.phone || res.clean_phone || 'No Phone');
                        $('#wa_modal_text').val(res.message_text);
                        $('#wa_modal_url').val(res.wa_url);
                    } else {
                        alerterror(res.message || "Could not generate message preview.");
                    }
                },
                error: function() {
                    alerterror("Failed to load message preview.");
                }
            });
        }

        function dispatchAlertFromModal() {
            var memberId = $('#wa_modal_member_id').val();
            var balance = $('#wa_modal_balance').val();
            var btn = $('#btn_modal_send_api');
            btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Sending...');

            $.ajax({
                type: "POST",
                url: "api/whatsapp.php",
                data: {
                    action: 'send_balance_alert',
                    member_id: memberId,
                    balance: balance
                },
                success: function(res) {
                    btn.prop('disabled', false).html('<i class="fa fa-paper-plane"></i> Send via WhatsApp API');
                    $('#modalWhatsAppAlert').modal('hide');
                    if (res.status === 'success') {
                        alertsuccess("Pending balance alert sent successfully to member!");
                    } else if (res.status === 'warning') {
                        swal({
                            title: "WhatsApp Web Ready",
                            text: res.message + "\nClick 'Open WhatsApp' to view or send.",
                            type: "info",
                            showCancelButton: true,
                            confirmButtonColor: "#25D366",
                            confirmButtonText: "<i class='fa fa-whatsapp'></i> Open WhatsApp",
                            cancelButtonText: "Done",
                            html: true
                        }, function(isConfirm) {
                            if (isConfirm && res.wa_url) {
                                window.open(res.wa_url, '_blank');
                            }
                        });
                    } else {
                        alerterror(res.message || "Failed to send WhatsApp alert.");
                    }
                },
                error: function(xhr) {
                    btn.prop('disabled', false).html('<i class="fa fa-paper-plane"></i> Send via WhatsApp API');
                    var msg = "Failed to send WhatsApp alert.";
                    try {
                        var json = JSON.parse(xhr.responseText);
                        if (json.message) msg = json.message;
                    } catch(e) {}
                    alerterror(msg);
                }
            });
        }

        function openDirectWhatsAppFromModal() {
            var waUrl = $('#wa_modal_url').val();
            if (!waUrl) {
                var phone = $('#wa_modal_phone').text().replace(/[^0-9]/g, '');
                var text = encodeURIComponent($('#wa_modal_text').val());
                waUrl = 'https://wa.me/' + phone + '?text=' + text;
            }
            window.open(waUrl, '_blank');
        }
    </script>
    <script src="../app_pagination/pagination.js"></script>
    <script src="../app_menu/menu.js"></script>
</head>

<body>
    <input type="hidden" id="hdn_current_page" value="1">

    <div id="wrapper">
        <!-- Navigation Sidebar -->
        <nav class="navbar-default navbar-static-side" role="navigation">
            <div class="dropdown profile-element">
                <center>
                    <span><img alt="image" class="img-circle" src="../img/customer.png" style="padding-top: 20px;"/></span>
                    <span class="clear"> 
                        <span class="block m-t-xs"> 
                            <strong class="font-bold"><?php echo $_SESSION['name']; ?></strong>
                        </span>
                    </span>
                </center>
            </div>
            <div class="sidebar-collapse" id="divMenuContainer">
                <!-- Loaded via AJAX -->
            </div>
        </nav>

        <div id="page-wrapper" class="gray-bg">
            <!-- Topbar Header -->
            <div class="row border-bottom">
                <nav class="navbar navbar-static-top" role="navigation" style="margin-bottom: 0">
                    <div class="navbar-header">
                        <a class="navbar-minimalize minimalize-styl-2 btn btn-primary" href="#"><i class="fa fa-bars"></i> </a>
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

            <!-- Page Title Header -->
            <div class="row wrapper border-bottom white-bg page-heading" style="padding: 15px 20px;">
                <div class="col-sm-6">
                    <h2 style="margin-top: 10px; font-weight: 600;">Pending Payments Report</h2>
                </div>
                <div class="col-sm-6" style="margin-top: 15px;">
                    <div style="display: flex; gap: 10px; justify-content: flex-end; align-items: center; flex-wrap: wrap;">
                        
                        <!-- Toggle switch filter -->
                        <div style="display: flex; align-items: center; gap: 6px; margin-right: 10px; font-weight: 500; font-size: 13.5px; color: #475569;">
                            <input type="checkbox" id="chk_only_dues" checked onchange="loadData(1)" style="cursor: pointer; width: 16px; height: 16px; margin: 0;">
                            <label for="chk_only_dues" style="margin: 0; cursor: pointer;">Overdue Only</label>
                        </div>

                        <!-- Search fields -->
                        <div class="input-group" style="width: 260px; margin: 0;">
                            <input type="text" id="txt_search" placeholder="Search by name or phone..." class="form-control input-sm" style="border-radius: 8px 0 0 8px; height: 32px;"> 
                            <span class="input-group-btn">
                                <button type="button" class="btn btn-sm btn-primary" onclick="searchMembers()" style="height: 32px; border-radius: 0 8px 8px 0; padding: 4px 12px;"> 
                                    Search
                                </button>
                            </span>
                        </div>
                        <button type="button" class="btn btn-sm btn-default" onclick="clearSearch()" style="height: 32px; border-radius: 8px; padding: 4px 12px;">
                            Clear
                        </button>
                    </div>
                </div>
            </div>

            <!-- KPI Cards Overview -->
            <div class="row m-b-md" style="margin-top: 20px; padding: 0 15px;">
                <div class="col-lg-4 col-md-6 col-sm-12">
                    <div class="ibox float-e-margins" style="border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.04); overflow: hidden; margin-bottom: 15px;">
                        <div class="ibox-title" style="background: #fff; border: none; padding: 16px 20px 6px;">
                            <span class="label label-danger pull-right" style="border-radius: 4px; padding: 2px 6px; font-weight: 600;">Dues</span>
                            <h5 style="margin: 0; font-size: 13px; color: #64748b; font-weight: 500;">Total Overdue Members</h5>
                        </div>
                        <div class="ibox-content" style="background: #fff; border: none; padding: 4px 20px 18px;">
                            <h1 class="no-margins font-bold text-danger" id="card_overdue_members" style="font-size: 32px; letter-spacing: -0.5px;">—</h1>
                            <small style="color: #94a3b8; font-size: 11px;">Active members with balance due</small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 col-sm-12">
                    <div class="ibox float-e-margins" style="border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.04); overflow: hidden; margin-bottom: 15px;">
                        <div class="ibox-title" style="background: #fff; border: none; padding: 16px 20px 6px;">
                            <span class="label label-primary pull-right" style="border-radius: 4px; padding: 2px 6px; font-weight: 600;">Total</span>
                            <h5 style="margin: 0; font-size: 13px; color: #64748b; font-weight: 500;">Total Outstanding Amount</h5>
                        </div>
                        <div class="ibox-content" style="background: #fff; border: none; padding: 4px 20px 18px;">
                            <h1 class="no-margins font-bold text-navy" id="card_overdue_amount" style="font-size: 32px; letter-spacing: -0.5px;">—</h1>
                            <small style="color: #94a3b8; font-size: 11px;">Accumulated pending fees</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table Container -->
            <div class="wrapper wrapper-content animated fadeInRight" id="table_receivables" style="padding-top: 0;">
                <!-- Dynamically Injected -->
            </div>
        </div>
    </div>

    <!-- WhatsApp Balance Alert Modal -->
    <div class="modal fade" id="modalWhatsAppAlert" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-md">
            <div class="modal-content" style="border-radius: 16px; overflow: hidden; border: none; box-shadow: 0 10px 40px rgba(0,0,0,0.15);">
                <div class="modal-header" style="background: linear-gradient(135deg, #10b981, #059669); color: #fff; padding: 18px 24px;">
                    <button type="button" class="close" data-dismiss="modal" style="color:#fff; opacity:0.8; font-size:24px;">&times;</button>
                    <h4 class="modal-title" style="font-weight: 800; font-size: 16px; display: flex; align-items: center; gap: 8px;">
                        <i class="fa fa-whatsapp" style="font-size: 20px;"></i> Send Pending Balance Alert
                    </h4>
                </div>
                <div class="modal-body" style="padding: 22px 24px; background: #fafbfc;">
                    <input type="hidden" id="wa_modal_member_id">
                    <input type="hidden" id="wa_modal_balance">
                    <input type="hidden" id="wa_modal_url">

                    <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 18px; margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-size: 14px; font-weight: 800; color: #0f172a;" id="wa_modal_name">—</div>
                            <div style="font-size: 12px; color: #64748b; margin-top: 2px;">
                                <i class="fa fa-phone"></i> <span id="wa_modal_phone">—</span>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Due Balance</div>
                            <div style="font-size: 18px; font-weight: 900; color: #ef4444;" id="wa_modal_balance_display">₹0.00</div>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 12px;">
                        <label style="font-size: 12px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px; display: block;">
                            Message Preview / Text:
                        </label>
                        <textarea class="form-control" id="wa_modal_text" rows="9" style="font-family: monospace; font-size: 12.5px; border-radius: 10px; background: #fff;"></textarea>
                    </div>
                    <div style="font-size: 11.5px; color: #64748b;">
                        <i class="fa fa-info-circle"></i> This message is generated from your configured pending balance template. You can edit before sending.
                    </div>
                </div>
                <div class="modal-footer" style="padding: 14px 24px; background: #fff; border-top: 1px solid #f1f5f9; display: flex; justify-content: space-between;">
                    <button type="button" class="btn btn-default" data-dismiss="modal" style="border-radius: 8px;">Cancel</button>
                    <div style="display: flex; gap: 8px;">
                        <button type="button" class="btn btn-default" onclick="openDirectWhatsAppFromModal()" style="border-radius: 8px; font-weight: 600; color: #059669; border-color: #a7f3d0;">
                            <i class="fa fa-external-link"></i> Open in WhatsApp Web
                        </button>
                        <button type="button" class="btn btn-success" id="btn_modal_send_api" onclick="dispatchAlertFromModal()" style="border-radius: 8px; font-weight: 700; background: linear-gradient(135deg, #10b981, #059669); border: none;">
                            <i class="fa fa-paper-plane"></i> Send via WhatsApp API
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mainly scripts -->
    <script src="../js/jquery-3.1.1.min.js"></script>
    <script src="../js/bootstrap.min.js"></script>
    <script src="../js/plugins/metisMenu/jquery.metisMenu.js"></script>
    <script src="../js/plugins/slimscroll/jquery.slimscroll.min.js"></script>
    <script src="../js/inspinia.js"></script>
    <script src="../js/plugins/pace/pace.min.js"></script>
</body>

</html>
