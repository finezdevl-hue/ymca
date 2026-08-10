<?php
session_start();
include '../../app_common/db_connect.php';
include '../../app_common/auth_helper.php';

if (empty($_SESSION['login_id'])) {
    header("Location: ../../index.php");
    exit();
}

$login_id = (int)$_SESSION['login_id'];
if (isNormalMember($login_id)) {
    header("Location: home.php");
    exit();
}
$is_admin = isSuperAdmin($login_id) || isGroupAdmin($login_id);
$allowed_groups = getUserAllowedGroupIds($login_id);
$primary_role = getUserPrimaryRoleName($login_id);

$active_tab = 'accounts';

// Fetch holidays
$sql = "
    SELECT d.id, d.date, d.group_id, COALESCE(g.name, 'All Groups') AS group_name 
    FROM tbl_dates d 
    LEFT JOIN tbl_groups g ON d.group_id = g.id 
    ORDER BY d.date DESC 
    LIMIT 100
";
$res = app_exec_query($sql);

// Fetch groups for dropdown
$sql_grp = "SELECT id, name FROM tbl_groups WHERE status = 1 ORDER BY name";
$res_grp = app_exec_query($sql_grp);
$groups = [];
if ($res_grp) {
    while ($g = $res_grp->fetch_assoc()) {
        $groups[] = $g;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Manage Holidays - YMCA Mobile</title>
    
    <link href="../../css/bootstrap.min.css" rel="stylesheet">
    <link href="../../font-awesome/css/font-awesome.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="../../css/plugins/sweetalert/sweetalert.css" rel="stylesheet">
    <link href="mobile_layout.css" rel="stylesheet">

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif !important; background: #f8fafc !important; }
        .acc-hero {
            background: linear-gradient(135deg, #f43f5e 0%, #e11d48 100%);
            border-radius: 20px; padding: 18px 20px; color: #ffffff; margin-bottom: 16px;
            box-shadow: 0 8px 20px rgba(244, 63, 94, 0.2);
        }
        .acc-hero h2 { margin: 0 0 4px 0; font-weight: 800; font-size: 20px; }
        .acc-hero p { margin: 0; font-size: 12.5px; opacity: 0.9; }

        .search-box { position: relative; margin-bottom: 14px; }
        .search-box input {
            width: 100%; height: 42px; border-radius: 12px; border: 1px solid #e2e8f0;
            padding: 0 14px 0 38px; font-size: 13px; font-weight: 600; color: #1e293b;
            background: #ffffff; box-shadow: 0 2px 6px rgba(0,0,0,0.02); outline: none;
        }
        .search-box i { position: absolute; left: 14px; top: 13px; color: #94a3b8; font-size: 14px; }

        .hol-card {
            background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; padding: 14px 16px;
            margin-bottom: 10px; display: flex; align-items: center; justify-content: space-between;
            box-shadow: 0 2px 6px rgba(0,0,0,0.02); transition: transform 0.15s ease;
        }
        .hol-date { font-size: 14px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px; }
        .hol-badge {
            font-size: 10px; font-weight: 800; padding: 3px 8px; border-radius: 6px; text-transform: uppercase; margin-top: 4px; display: inline-block;
        }
        .badge-all { background: #fee2e2; color: #991b1b; }
        .badge-grp { background: #e0e7ff; color: #3730a3; }

        .btn-del-sm {
            background: rgba(239, 68, 68, 0.1); color: #ef4444; border: none; border-radius: 8px;
            padding: 6px 12px; font-size: 12px; font-weight: 800; display: inline-flex; align-items: center; gap: 5px;
            cursor: pointer; transition: background 0.2s ease;
        }
        .btn-del-sm:hover { background: rgba(239, 68, 68, 0.2); }

        .nav-pills-sm { display: flex; background: #f1f5f9; border-radius: 12px; padding: 3px; margin-bottom: 16px; }
        .nav-pills-sm .nav-link {
            flex: 1; text-align: center; border-radius: 10px; padding: 8px 12px; font-size: 12.5px; font-weight: 700;
            color: #64748b; text-decoration: none; cursor: pointer; transition: all 0.2s ease;
        }
        .nav-pills-sm .nav-link.active { background: #ffffff; color: #e11d48; box-shadow: 0 2px 6px rgba(0,0,0,0.05); }

        .form-group-custom { margin-bottom: 14px; }
        .form-group-custom label { font-size: 11.5px; font-weight: 800; color: #475569; text-transform: uppercase; margin-bottom: 6px; display: block; }
        .form-group-custom input, .form-group-custom select {
            width: 100%; height: 42px; border-radius: 12px; border: 1px solid #cbd5e1;
            padding: 0 12px; font-size: 13px; font-weight: 600; color: #0f172a; outline: none; background: #ffffff;
        }
    </style>
</head>
<body class="mob-body">

    <!-- Header -->
    <header class="mob-header">
        <div class="mob-header-brand">
            <a href="accounts.php" style="color:#ffffff; margin-right:8px; font-size:18px;"><i class="fa fa-arrow-left"></i></a>
            <div class="mob-header-title">
                Manage <span>Holidays</span>
            </div>
        </div>
        <div class="mob-header-actions">
            <a href="../../app_login_manager/logout.php" class="mob-header-btn" title="Logout"><i class="fa fa-sign-out"></i></a>
        </div>
    </header>

    <div class="mob-page">

        <div class="acc-hero" style="display:flex; justify-content:space-between; align-items:center;">
            <div>
                <h2>Club Holidays</h2>
                <p>Manage club closures & attendance holidays</p>
            </div>
            <button class="btn btn-light btn-sm" style="border-radius:12px; font-weight:800; color:#e11d48; padding:8px 14px; box-shadow: 0 4px 10px rgba(0,0,0,0.1);" data-toggle="modal" data-target="#addHolidayModal">
                <i class="fa fa-plus-circle"></i> Add Holiday
            </button>
        </div>

        <!-- Search Bar -->
        <div class="search-box">
            <i class="fa fa-search"></i>
            <input type="text" id="search_holidays" placeholder="Search by date or group..." onkeyup="filterHolidays()">
        </div>

        <!-- Holidays List -->
        <div id="holidays_container">
            <?php if ($res && $res->num_rows > 0): ?>
                <?php while ($r = $res->fetch_assoc()): ?>
                    <?php 
                    $date_str = date('D, d M Y', strtotime($r['date']));
                    $grp_name = htmlspecialchars($r['group_name']);
                    $is_all = ((int)$r['group_id'] === 0);
                    $badge_cls = $is_all ? 'badge-all' : 'badge-grp';
                    ?>
                    <div class="hol-card hol-item-row" data-search="<?php echo strtolower($date_str . ' ' . $grp_name); ?>">
                        <div>
                            <div class="hol-date">
                                <i class="fa fa-calendar-times-o" style="color:#e11d48;"></i> <?php echo $date_str; ?>
                            </div>
                            <span class="hol-badge <?php echo $badge_cls; ?>"><?php echo $grp_name; ?></span>
                        </div>
                        <div>
                            <button type="button" class="btn-del-sm" onclick="deleteHoliday(<?php echo $r['id']; ?>, '<?php echo addslashes($date_str); ?>')">
                                <i class="fa fa-trash-o"></i> Delete
                            </button>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="text-align:center; padding:40px 20px; color:#94a3b8;">
                    <i class="fa fa-calendar-o" style="font-size:36px; margin-bottom:10px; display:block;"></i>
                    No holidays added yet.
                </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- Add Holiday Modal -->
    <div class="modal fade" id="addHolidayModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content" style="border-radius:24px; border:none; background:#ffffff;">
                <div class="modal-header" style="border-bottom:1px solid #e2e8f0; padding:16px 20px;">
                    <h5 class="modal-title" style="font-weight:800; color:#0f172a; font-size:16px;">
                        <i class="fa fa-calendar-plus-o" style="color:#e11d48;"></i> Add Holiday / Exemption
                    </h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body" style="padding:20px;">
                    
                    <div class="nav-pills-sm">
                        <div class="nav-link active" id="tab_single" onclick="switchTab('single')">Single Date</div>
                        <div class="nav-link" id="tab_range" onclick="switchTab('range')">Date Range</div>
                    </div>

                    <form id="form_holiday">
                        <input type="hidden" id="holiday_type" value="single">

                        <div id="single_date_sec">
                            <div class="form-group-custom">
                                <label>Holiday Date</label>
                                <input type="date" id="holiday_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>

                        <div id="range_date_sec" style="display:none;">
                            <div class="form-group-custom">
                                <label>Start Date</label>
                                <input type="date" id="start_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="form-group-custom">
                                <label>End Date</label>
                                <input type="date" id="end_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>

                        <div class="form-group-custom">
                            <label>Applicable Group</label>
                            <select id="group_id" class="form-control">
                                <option value="0">All Groups</option>
                                <?php foreach ($groups as $g): ?>
                                    <option value="<?php echo $g['id']; ?>"><?php echo htmlspecialchars($g['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button type="button" onclick="submitHoliday()" class="btn btn-block" style="background:linear-gradient(135deg, #f43f5e, #e11d48); color:#ffffff; font-weight:800; border-radius:12px; height:44px; margin-top:20px;">
                            <i class="fa fa-check-circle"></i> Save Holiday
                        </button>
                    </form>

                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Bottom Navigation -->
    <?php include 'mobile_bottom_nav.php'; ?>

    <script src="../../js/jquery-3.1.1.min.js"></script>
    <script src="../../js/bootstrap.min.js"></script>
    <script src="../../js/plugins/sweetalert/sweetalert.min.js"></script>

    <script>
        function filterHolidays() {
            var val = $('#search_holidays').val().toLowerCase().trim();
            $('.hol-item-row').each(function() {
                var search = $(this).attr('data-search');
                if (!val || search.indexOf(val) > -1) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        }

        function switchTab(type) {
            $('#holiday_type').val(type);
            if (type === 'single') {
                $('#tab_single').addClass('active');
                $('#tab_range').removeClass('active');
                $('#single_date_sec').show();
                $('#range_date_sec').hide();
            } else {
                $('#tab_range').addClass('active');
                $('#tab_single').removeClass('active');
                $('#single_date_sec').hide();
                $('#range_date_sec').show();
            }
        }

        function submitHoliday() {
            var type = $('#holiday_type').val();
            var groupId = $('#group_id').val();

            if (type === 'single') {
                var hDate = $('#holiday_date').val();
                if (!hDate) {
                    swal("Warning", "Please select a holiday date.", "warning");
                    return;
                }

                $.post('../api/add_dates.php', {
                    action: 'save_date',
                    date: hDate,
                    group_id: groupId
                }, function(res) {
                    swal({
                        title: "Saved!",
                        text: "Holiday saved successfully.",
                        type: "success"
                    }, function() {
                        location.reload();
                    });
                }).fail(function(xhr) {
                    swal("Error", xhr.responseText || "Failed to save holiday.", "error");
                });

            } else {
                var sDate = $('#start_date').val();
                var eDate = $('#end_date').val();

                if (!sDate || !eDate) {
                    swal("Warning", "Please select both start and end dates.", "warning");
                    return;
                }

                if (sDate > eDate) {
                    swal("Warning", "Start date must be before or equal to end date.", "warning");
                    return;
                }

                $.post('../api/add_dates.php', {
                    action: 'save_range_dates',
                    start_date: sDate,
                    end_date: eDate,
                    group_id: groupId
                }, function(res) {
                    swal({
                        title: "Saved!",
                        text: "Holiday range saved successfully.",
                        type: "success"
                    }, function() {
                        location.reload();
                    });
                }).fail(function(xhr) {
                    swal("Error", xhr.responseText || "Failed to save holiday range.", "error");
                });
            }
        }

        function deleteHoliday(id, dateStr) {
            swal({
                title: "Delete Holiday?",
                text: "Are you sure you want to remove the holiday on " + dateStr + "?",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#ef4444",
                confirmButtonText: "Yes, Delete",
                closeOnConfirm: false
            }, function() {
                $.post('../api/add_dates.php', {
                    action: 'delete_date',
                    id: id
                }, function() {
                    swal({
                        title: "Deleted!",
                        text: "Holiday deleted successfully.",
                        type: "success"
                    }, function() {
                        location.reload();
                    });
                }).fail(function(xhr) {
                    swal("Error", "Failed to delete holiday.", "error");
                });
            });
        }
    </script>
</body>
</html>
