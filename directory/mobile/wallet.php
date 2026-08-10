<?php
session_start();
session_write_close();

if (empty($_SESSION['login_id']) || $_SESSION['login_id'] == 1) {
    header("Location: ../../index.php");
    exit();
}

include '../../app_common/db_connect.php';

$page_title = 'Wallet';
$active_tab = 'reports'; // Wallet belongs under Reports tab
$back_url   = 'reports.php';

$client_id = (int)($_SESSION['user_id'] ?? 0);
$balance   = 0.00;
$total_credit = 0.00;
$total_debit  = 0.00;

if ($client_id > 0) {
    $res = app_exec_query("SELECT
        SUM(CASE WHEN type='credit' THEN amount ELSE 0 END) AS total_credit,
        SUM(CASE WHEN type='debit'  THEN amount ELSE 0 END) AS total_debit
        FROM tbl_wallet WHERE client_id = $client_id");
    if ($res && $row = $res->fetch_assoc()) {
        $total_credit = (float)($row['total_credit'] ?? 0);
        $total_debit  = (float)($row['total_debit']  ?? 0);
        $balance      = $total_credit - $total_debit;
    }
}

$member_name = $_SESSION['name'] ?? 'Member';
$initial     = strtoupper(substr($member_name, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="description" content="My Wallet - YMCA Member Portal">
    <title>YMCA | Wallet</title>
    <link rel="shortcut icon" href="../../favicon.ico" type="image/x-icon">
    <link href="../../font-awesome/css/font-awesome.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="../../css/plugins/sweetalert/sweetalert.css" rel="stylesheet">
    <link href="mobile_layout.css" rel="stylesheet">

    <style>
        /* Wallet hero */
        .wallet-hero {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-radius: 18px;
            padding: 24px 20px;
            color: #fff;
            position: relative;
            overflow: hidden;
        }
        .wallet-hero::before {
            content:'';
            position:absolute;
            width:180px; height:180px;
            background: radial-gradient(circle, rgba(255,255,255,0.12) 0%, transparent 70%);
            top:-50px; right:-50px; border-radius:50%;
        }
        .wallet-hero-label {
            font-size:11px; font-weight:700; letter-spacing:1px;
            text-transform:uppercase; opacity:.75; margin:0 0 8px;
        }
        .wallet-hero-amount {
            font-size:36px; font-weight:900; letter-spacing:-1.5px;
            margin: 0 0 14px;
        }
        .wallet-hero-row {
            display:flex; gap:18px; flex-wrap:wrap;
        }
        .wallet-mini-stat {
            display:flex; align-items:center; gap:7px;
            font-size:13px; font-weight:600;
            background:rgba(255,255,255,0.15);
            border-radius:10px; padding:7px 12px;
        }

        /* Tx entry */
        .tx-entry {
            display:flex; align-items:center; gap:12px;
            padding:12px 0; border-bottom:1px solid #f1f5f9;
        }
        .tx-entry:last-child { border-bottom:none; }
        .tx-dot {
            width:40px; height:40px; border-radius:11px;
            display:flex; align-items:center; justify-content:center;
            font-size:16px; flex-shrink:0;
        }
        .tx-dot.credit { background:rgba(16,185,129,.10); color:#10b981; }
        .tx-dot.debit  { background:rgba(239,68,68,.10);  color:#ef4444; }
        .tx-info { flex:1; min-width:0; }
        .tx-title { font-size:13.5px; font-weight:600; color:#1e293b; }
        .tx-date  { font-size:11.5px; color:#94a3b8; margin-top:2px; }
        .tx-amount { font-size:14px; font-weight:800; flex-shrink:0; }
        .tx-amount.credit { color:#10b981; }
        .tx-amount.debit  { color:#ef4444; }

        /* Filter row */
        .mob-filter-row { display:flex; gap:8px; align-items:center; }

        /* Load more */
        .load-more-btn {
            width:100%; padding:12px;
            background:#f8faff; border:1.5px solid #e2e8f0;
            border-radius:10px; color:#4f46e5; font-size:13px;
            font-weight:700; font-family:'Inter',sans-serif;
            cursor:pointer; transition:all .2s;
        }
        .load-more-btn:hover { background:#eef2ff; border-color:#4f46e5; }
    </style>
</head>
<body class="mob-body">

<?php include 'mobile_header.php'; ?>

<div class="mob-page">

    <div>
        <a href="reports.php" class="mob-btn mob-btn-outline" style="width:auto; padding:8px 16px; font-size:12.5px; border-radius:10px;">
            <i class="fa fa-arrow-left"></i> Back to Reports
        </a>
    </div>

    <!-- Balance Hero -->
    <div class="wallet-hero">
        <div class="wallet-hero-label">My Wallet Balance</div>
        <div class="wallet-hero-amount">₹<?php echo number_format($balance, 2); ?></div>
        <div class="wallet-hero-row">
            <div class="wallet-mini-stat">
                <i class="fa fa-arrow-circle-down"></i>
                In: ₹<?php echo number_format($total_credit, 2); ?>
            </div>
            <div class="wallet-mini-stat">
                <i class="fa fa-arrow-circle-up"></i>
                Out: ₹<?php echo number_format($total_debit, 2); ?>
            </div>
        </div>
    </div>

    <!-- Transactions Card -->
    <div class="mob-card">
        <div class="mob-card-header">
            <div class="mob-card-icon" style="background:linear-gradient(135deg,#10b981,#059669);">
                <i class="fa fa-exchange"></i>
            </div>
            <div class="mob-card-title">Transactions</div>
            <!-- Date filter -->
            <div style="margin-left:auto;">
                <input type="date" class="mob-input" id="wallet-date-filter"
                    style="padding:7px 10px;font-size:12px;width:auto;"
                    onchange="loadWalletTx(1)">
            </div>
        </div>
        <div class="mob-card-body" style="padding-top:8px;" id="wallet-tx-list">
            <div style="text-align:center;color:#94a3b8;padding:24px 0;">
                <i class="fa fa-spinner fa-spin"></i> Loading…
            </div>
        </div>
        <div style="padding:0 16px 14px;" id="wallet-pagination"></div>
    </div>

</div>

<?php include 'mobile_bottom_nav.php'; ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script>
var walletPage = 1;
var walletTotal = 0;
var perPage = 15;

$(document).ready(function(){
    loadWalletTx(1);
});

function loadWalletTx(page){
    walletPage = page;
    const dateFilter = $('#wallet-date-filter').val();
    $('#wallet-tx-list').html('<div style="text-align:center;color:#94a3b8;padding:24px 0;"><i class="fa fa-spinner fa-spin"></i> Loading…</div>');

    $.post('../api/user_wallet.php', {
        action: 'load_data',
        page: page,
        val: dateFilter
    }, function(data){
        try {
            const obj = JSON.parse(data);
            const totalRows = obj[0].total_rows;
            const rows = obj[1];
            walletTotal = totalRows;

            if(rows.length === 0){
                $('#wallet-tx-list').html('<div style="text-align:center;color:#94a3b8;padding:24px 0;font-size:13px;">No transactions found.</div>');
                $('#wallet-pagination').html('');
                return;
            }

            let html = '';
            rows.forEach(function(r){
                const isCredit = (r.type === 'credit');
                const icon  = isCredit ? 'fa-arrow-circle-down' : 'fa-arrow-circle-up';
                const cls   = isCredit ? 'credit' : 'debit';
                const sign  = isCredit ? '+' : '-';
                const label = isCredit ? 'Wallet Credited' : 'Fees Payment';
                html += '<div class="tx-entry">' +
                    '<div class="tx-dot '+cls+'"><i class="fa '+icon+'"></i></div>' +
                    '<div class="tx-info">' +
                    '<div class="tx-title">'+label+'</div>' +
                    '<div class="tx-date"><i class="fa fa-calendar"></i> '+r.date+'</div>' +
                    '</div>' +
                    '<div class="tx-amount '+cls+'">'+sign+'₹'+parseFloat(r.amount).toFixed(2)+'</div>' +
                    '</div>';
            });
            $('#wallet-tx-list').html(html);

            // Pagination
            const totalPages = Math.ceil(totalRows / perPage);
            let pagHtml = '';
            if(totalPages > 1){
                if(page > 1) pagHtml += '<button class="mob-btn mob-btn-outline" style="width:auto;padding:8px 16px;font-size:12px;margin-right:6px;" onclick="loadWalletTx('+(page-1)+')"><i class="fa fa-chevron-left"></i> Prev</button>';
                pagHtml += '<span style="color:#64748b;font-size:12px;font-weight:600;">Page '+page+' / '+totalPages+'</span>';
                if(page < totalPages) pagHtml += '<button class="mob-btn mob-btn-outline" style="width:auto;padding:8px 16px;font-size:12px;margin-left:6px;" onclick="loadWalletTx('+(page+1)+')">Next <i class="fa fa-chevron-right"></i></button>';
            }
            $('#wallet-pagination').html(pagHtml ? '<div style="display:flex;align-items:center;justify-content:center;gap:4px;padding:8px 0;">'+pagHtml+'</div>' : '');
        } catch(ex){
            $('#wallet-tx-list').html('<div style="text-align:center;color:#ef4444;padding:24px 0;font-size:13px;"><i class="fa fa-exclamation-circle"></i> Failed to load.</div>');
        }
    }).fail(function(){
        $('#wallet-tx-list').html('<div style="text-align:center;color:#ef4444;padding:24px 0;font-size:13px;"><i class="fa fa-exclamation-circle"></i> Server error.</div>');
    });
}
</script>

</body>
</html>
