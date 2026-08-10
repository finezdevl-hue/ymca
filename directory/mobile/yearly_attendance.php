<?php
session_start();
include '../../app_common/db_connect.php';
include '../../app_common/auth_helper.php';

if (empty($_SESSION['login_id'])) {
    header("Location: ../../index.php");
    exit();
}

$login_id = (int)$_SESSION['login_id'];
$allowed_groups = getUserAllowedGroupIds($login_id);
$primary_role = getUserPrimaryRoleName($login_id);

$active_tab = 'reports';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Yearly Attendance Summary - YMCA Mobile</title>
    
    <link href="../../css/bootstrap.min.css" rel="stylesheet">
    <link href="../../font-awesome/css/font-awesome.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="mobile_layout.css" rel="stylesheet">

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif !important; background: #f8fafc !important; }
        .rep-hero {
            background: linear-gradient(135deg, #059669 0%, #10b981 100%);
            border-radius: 20px; padding: 18px 20px; color: #ffffff; margin-bottom: 16px;
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.2);
        }
        .rep-hero h2 { margin: 0 0 4px 0; font-weight: 800; font-size: 20px; }
        .rep-hero p { margin: 0; font-size: 12.5px; opacity: 0.9; }

        .rep-filter-box {
            background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0;
            padding: 14px; margin-bottom: 14px; box-shadow: 0 2px 8px rgba(0,0,0,0.02);
            display: flex; flex-direction: column; gap: 10px;
        }
        .rep-field { display: flex; flex-direction: column; gap: 4px; }
        .rep-field label { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; margin: 0; }
        .rep-field select {
            padding: 10px 14px; border-radius: 12px; border: 1.5px solid #cbd5e1;
            background: #f8fafc; font-size: 13.5px; font-weight: 600; color: #0f172a; outline: none;
        }

        .mob-mem-att-card {
            background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0;
            padding: 14px; margin-bottom: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.02);
        }
        .mem-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; }
        .mem-name { font-size: 14px; font-weight: 800; color: #0f172a; margin: 0; }
        .mem-days-pill { font-size: 12px; font-weight: 800; background: #ecfdf5; color: #059669; padding: 4px 10px; border-radius: 10px; }

        .months-bar-grid { display: grid; grid-template-columns: repeat(6, 1fr); gap: 4px; margin-top: 8px; }
        .month-box {
            background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0; padding: 6px 2px; text-align: center;
        }
        .month-lbl { font-size: 9px; font-weight: 700; color: #64748b; text-transform: uppercase; }
        .month-val { font-size: 12px; font-weight: 800; color: #059669; margin-top: 2px; }
    </style>
</head>
<body class="mob-body">

    <!-- Header -->
    <header class="mob-header">
        <div class="mob-header-brand">
            <a href="reports.php" style="color:#ffffff; margin-right:8px; font-size:18px;"><i class="fa fa-arrow-left"></i></a>
            <div class="mob-header-title">
                Yearly Attendance <span>Summary</span>
            </div>
        </div>
        <div class="mob-header-actions">
            <a href="../../app_login_manager/logout.php" class="mob-header-btn" title="Logout"><i class="fa fa-sign-out"></i></a>
        </div>
    </header>

    <div class="mob-page">

        <div class="rep-hero">
            <h2>Yearly Attendance Matrix</h2>
            <p>Financial year attendance totals and monthly breakdown</p>
        </div>

        <div class="rep-filter-box">
            <div class="rep-field">
                <label>Select Financial Year</label>
                <select id="rep_year" onchange="loadReport()"></select>
            </div>
        </div>

        <div id="yearly_att_cards">
            <div style="text-align:center; padding:40px 20px; color:#94a3b8;">
                <i class="fa fa-spinner fa-spin" style="font-size:24px; margin-bottom:8px;"></i><br>
                Loading yearly attendance...
            </div>
        </div>

    </div>

    <!-- Mobile Bottom Navigation (5 Tabs) -->
    <?php include 'mobile_bottom_nav.php'; ?>

    <script src="../../js/jquery-3.1.1.min.js"></script>
    <script src="../../js/bootstrap.min.js"></script>
    <script>
    $(document).ready(function() {
        let currentYear = new Date().getFullYear();
        let currentMonth = new Date().getMonth();
        let defaultFinancialYear = (currentMonth < 3) ? currentYear - 1 : currentYear;

        let htmYear = "";
        for (let y = defaultFinancialYear + 1; y >= 2020; y--) {
            htmYear += '<option value="' + y + '">' + y + ' - ' + (y + 1) + '</option>';
        }
        $('#rep_year').html(htmYear).val(defaultFinancialYear);
        loadReport();
    });

    function loadReport() {
        let year = $('#rep_year').val();
        if (!year) return;

        $('#yearly_att_cards').html('<div style="text-align:center; padding:40px 20px; color:#94a3b8;"><i class="fa fa-spinner fa-spin" style="font-size:24px; margin-bottom:8px;"></i><br>Loading...</div>');

        $.post('../api/yearly_attendance_report.php', { action: 'load_yearly_consolidated_report', year: year }, function(res) {
            try {
                let data = typeof res === 'string' ? JSON.parse(res) : res;
                let members = data.members || [];
                let htm = '';

                if (members.length === 0) {
                    $('#yearly_att_cards').html('<div style="text-align:center; padding:40px 20px; color:#94a3b8;">No attendance records found for this year.</div>');
                    return;
                }

                let monthNames = ['Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec','Jan','Feb','Mar'];

                members.forEach(function(m) {
                    let totalDays = parseInt(m.total_days_present || 0, 10);
                    if (totalDays <= 0) return;
                    let mHtm = '';
                    
                    for (let i = 1; i <= 12; i++) {
                        let cnt = m.months && m.months[i] ? m.months[i] : 0;
                        mHtm += `
                            <div class="month-box">
                                <div class="month-lbl">${monthNames[i-1]}</div>
                                <div class="month-val">${cnt}</div>
                            </div>
                        `;
                    }

                    htm += `
                        <div class="mob-mem-att-card">
                            <div class="mem-head">
                                <h4 class="mem-name">${m.first_name} ${m.last_name || ''}</h4>
                                <span class="mem-days-pill">${totalDays} Days Present</span>
                            </div>
                            <div class="months-bar-grid">${mHtm}</div>
                        </div>
                    `;
                });

                $('#yearly_att_cards').html(htm);
            } catch(e) {
                $('#yearly_att_cards').html('<div style="text-align:center; padding:40px 20px; color:#ef4444;">Error loading yearly summary.</div>');
            }
        });
    }
    </script>
</body>
</html>
