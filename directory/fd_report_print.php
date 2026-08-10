<?php
session_start();
include '../app_common/db_connect.php';

// Fetch all FD transactions with individual interest sum
$sqldatarows = "SELECT id, date, fd_no, bank_name, amount, interest_rate, maturity_date, maturity_amount, status, description,
                COALESCE((SELECT SUM(amount) FROM tbl_fd_interest_credits WHERE fd_id = tbl_fd_transactions.id), 0) AS interest_received
                FROM tbl_fd_transactions ORDER BY date DESC, id DESC";
$result = app_exec_query($sqldatarows);

// Fetch totals
$sql_totals = "SELECT 
    COALESCE((SELECT SUM(amount) FROM tbl_fd_transactions), 0) AS total_principal,
    COALESCE((SELECT SUM(amount) FROM tbl_fd_interest_credits), 0) AS total_interest,
    (SELECT COUNT(*) FROM tbl_fd_transactions) AS total_count";
$totalsResult = app_exec_query($sql_totals);
$totalsRow = $totalsResult ? $totalsResult->fetch_assoc() : null;
$totalPrincipal = $totalsRow ? (float)$totalsRow['total_principal'] : 0;
$totalInterest = $totalsRow ? (float)$totalsRow['total_interest'] : 0;
$totalCount = $totalsRow ? (int)$totalsRow['total_count'] : 0;
$totalFdValue = $totalPrincipal + $totalInterest;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Fixed Deposits Report</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="../font-awesome/css/font-awesome.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            color: #1e293b;
            background-color: #ffffff;
            margin: 0;
            padding: 40px;
        }
        
        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .logo {
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }
        .logo span {
            color: #4f46e5;
        }
        
        .report-title {
            text-align: right;
        }
        .report-title h1 {
            font-size: 22px;
            font-weight: 700;
            margin: 0;
            color: #0f172a;
        }
        .report-title p {
            font-size: 13px;
            color: #64748b;
            margin: 4px 0 0 0;
        }
        
        /* Stats Grid Section */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            color: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            justify-content: space-between;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        
        .bg-purple { background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%); }
        .bg-blue { background: linear-gradient(135deg, #0284c7 0%, #0ea5e9 100%); }
        .bg-green { background: linear-gradient(135deg, #059669 0%, #10b981 100%); }
        .bg-dark { background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); }
        
        .stat-label {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: rgba(255, 255, 255, 0.85);
        }
        .stat-value {
            font-size: 20px;
            font-weight: 800;
            margin-top: 4px;
        }
        
        .stat-icon {
            font-size: 28px;
            opacity: 0.25;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th {
            background-color: #f8fafc;
            color: #475569;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #cbd5e1;
            padding: 12px 10px;
            text-align: left;
        }
        
        td {
            padding: 12px 10px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 13px;
            color: #334155;
        }
        
        tr:hover {
            background-color: #f8fafc;
        }
        
        .badge {
            font-size: 10px;
            font-weight: 700;
            padding: 4px 8px;
            border-radius: 30px;
            text-transform: uppercase;
            display: inline-block;
        }
        
        .badge-active { background-color: #d1fae5; color: #065f46; }
        .badge-matured { background-color: #e0f2fe; color: #0369a1; }
        .badge-closed { background-color: #fee2e2; color: #991b1b; }
        
        .footer {
            margin-top: 50px;
            border-top: 1px solid #f1f5f9;
            padding-top: 20px;
            font-size: 11px;
            color: #94a3b8;
            text-align: center;
        }
        
        @media print {
            body {
                padding: 20px;
            }
            .stats-grid {
                gap: 15px;
            }
            .stat-card {
                box-shadow: none;
                border: 1px solid #e2e8f0;
            }
            .stat-value {
                font-size: 18px;
            }
        }
    </style>
</head>
<body>

    <div class="header">
        <div class="logo">
            YMCA BCP <span>Poovathussery</span>
        </div>
        <div class="report-title">
            <h1>Fixed Deposits Report</h1>
            <p>Generated on <?php echo date('d-m-Y H:i'); ?></p>
        </div>
    </div>

    <!-- Summary Stats Row -->
    <div class="stats-grid">
        <!-- Stat 1: Count -->
        <div class="stat-card bg-purple">
            <div>
                <div class="stat-label">Total FD Count</div>
                <div class="stat-value"><?php echo $totalCount; ?></div>
            </div>
            <div class="stat-icon"><i class="fa fa-list-ol"></i></div>
        </div>
        
        <!-- Stat 2: Principal -->
        <div class="stat-card bg-blue">
            <div>
                <div class="stat-label">Total Principal</div>
                <div class="stat-value">₹<?php echo number_format($totalPrincipal, 2); ?></div>
            </div>
            <div class="stat-icon"><i class="fa fa-briefcase"></i></div>
        </div>
        
        <!-- Stat 3: Interest -->
        <div class="stat-card bg-green">
            <div>
                <div class="stat-label">Total Interest</div>
                <div class="stat-value">₹<?php echo number_format($totalInterest, 2); ?></div>
            </div>
            <div class="stat-icon"><i class="fa fa-percent"></i></div>
        </div>
        
        <!-- Stat 4: Combined Total -->
        <div class="stat-card bg-dark">
            <div>
                <div class="stat-label">Combined Value</div>
                <div class="stat-value">₹<?php echo number_format($totalFdValue, 2); ?></div>
            </div>
            <div class="stat-icon"><i class="fa fa-calculator"></i></div>
        </div>
    </div>

    <!-- Data Table -->
    <table>
        <thead>
            <tr>
                <th style="width: 5%; text-align: center;">No.</th>
                <th style="width: 10%;">Date</th>
                <th style="width: 10%;">FD No</th>
                <th style="width: 15%;">Bank Name</th>
                <th style="width: 12%; text-align: right;">Principal (₹)</th>
                <th style="width: 8%; text-align: center;">Int. Rate</th>
                <th style="width: 12%; text-align: right;">Int. Received (₹)</th>
                <th style="width: 10%;">Maturity Date</th>
                <th style="width: 10%; text-align: right;">Maturity Amount (₹)</th>
                <th style="width: 8%; text-align: center;">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $slno = 1;
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $status = $row['status'];
                    $badgeClass = 'badge-active';
                    if ($status === 'Matured') {
                        $badgeClass = 'badge-matured';
                    } elseif ($status === 'Closed') {
                        $badgeClass = 'badge-closed';
                    }
                    ?>
                    <tr>
                        <td style="text-align: center;"><?php echo $slno++; ?></td>
                        <td><?php echo date('d-m-Y', strtotime($row['date'])); ?></td>
                        <td><strong><?php echo htmlspecialchars($row['fd_no']); ?></strong></td>
                        <td><?php echo htmlspecialchars($row['bank_name']); ?></td>
                        <td style="text-align: right; font-weight: bold;">
                            <?php echo number_format($row['amount'], 2); ?>
                        </td>
                        <td style="text-align: center;"><?php echo htmlspecialchars($row['interest_rate']); ?>%</td>
                        <td style="text-align: right; font-weight: bold; color: #059669;">
                            <?php echo number_format($row['interest_received'], 2); ?>
                        </td>
                        <td><?php echo date('d-m-Y', strtotime($row['maturity_date'])); ?></td>
                        <td style="text-align: right; font-weight: bold;">
                            <?php echo number_format($row['maturity_amount'], 2); ?>
                        </td>
                        <td style="text-align: center;">
                            <span class="badge <?php echo $badgeClass; ?>">
                                <?php echo htmlspecialchars($row['status']); ?>
                            </span>
                        </td>
                    </tr>
                    <?php
                }
            } else {
                echo '<tr><td colspan="10" style="text-align: center; color: #64748b;">No Fixed Deposit transactions found</td></tr>';
            }
            ?>
        </tbody>
    </table>

    <div class="footer">
        © <?php echo date('Y'); ?> YMCA BCP Poovathussery. All rights reserved.
    </div>

    <script>
        window.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                window.print();
            }, 300);
        });
    </script>
</body>
</html>
