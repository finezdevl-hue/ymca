<?php
session_start();
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

    <!-- Mainly scripts -->
    <script src="../js/jquery-3.1.1.min.js"></script>
    <script src="../js/bootstrap.min.js"></script>
    <script src="../js/plugins/metisMenu/jquery.metisMenu.js"></script>
    <script src="../js/plugins/slimscroll/jquery.slimscroll.min.js"></script>
    <script src="../js/inspinia.js"></script>
    <script src="../js/plugins/pace/pace.min.js"></script>
</body>

</html>
