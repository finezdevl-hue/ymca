<?php
session_start();
include '../app_common/db_connect.php';

// Fetch all bank transactions (custom only)
$sqldatarows = "SELECT id, date, type, amount, reference_no, description FROM tbl_bank_transactions ORDER BY date DESC, id DESC";
$result = app_exec_query($sqldatarows);

$sqlbalance = "SELECT 
                  COALESCE((SELECT SUM(amount) FROM tbl_bank_transactions WHERE type = 'Deposit'), 0) +
                  COALESCE((SELECT SUM(amount) FROM tbl_bank_transactions WHERE type = 'Interest'), 0) -
                  COALESCE((SELECT SUM(amount) FROM tbl_bank_transactions WHERE type = 'Withdrawal'), 0) AS bank_balance";
$balanceResult = app_exec_query($sqlbalance);
$bankBalance = $balanceResult ? $balanceResult->fetch_assoc()['bank_balance'] : 0;
if ($bankBalance === null) $bankBalance = 0;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Bank Transactions Report</title>
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
            color: #3b82f6;
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
        
        .balance-card {
            background: linear-gradient(135deg, #1d4ed8 0%, #3b82f6 100%);
            color: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 10px 25px rgba(29, 78, 216, 0.15);
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 40px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        
        .balance-label {
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(255, 255, 255, 0.80);
        }
        .balance-amount {
            font-size: 28px;
            font-weight: 800;
            margin-top: 4px;
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
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e2e8f0;
            padding: 14px 16px;
            text-align: left;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        
        td {
            padding: 14px 16px;
            font-size: 14px;
            color: #334155;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .badge-deposit {
            background-color: #ecfdf5;
            color: #059669;
        }
        .badge-withdrawal {
            background-color: #fef2f2;
            color: #dc2626;
        }
        
        .amount {
            font-weight: 600;
        }
        .amount-deposit {
            color: #059669;
        }
        .amount-withdrawal {
            color: #dc2626;
        }
        
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 12px;
            color: #94a3b8;
            border-top: 1px solid #f1f5f9;
            padding-top: 20px;
        }
        
        @media print {
            body {
                padding: 20px;
            }
            .balance-card {
                box-shadow: none;
                border: 1px solid #e2e8f0;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>

    <div class="header">
        <div class="logo">YMCA BCP <span>Poovathussery</span></div>
        <div class="report-title">
            <h1>Bank Transactions Report</h1>
            <p>Generated on: <?php echo date('d M Y, h:i A'); ?></p>
        </div>
    </div>

    <div class="balance-card">
        <div>
            <div class="balance-label">Available Bank Balance</div>
            <div class="balance-amount">₹<?php echo number_format($bankBalance, 2); ?></div>
        </div>
        <div class="no-print" style="margin-left: 20px;">
            <button onclick="window.print();" style="background-color: white; color: #1d4ed8; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; box-shadow: 0 4px 6px rgba(0,0,0,0.05); font-family: inherit;">
                <i class="fa fa-print"></i> Click to Print
            </button>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 8%; text-align: center;">Sl No</th>
                <th style="width: 15%;">Date</th>
                <th style="width: 15%;">Type</th>
                <th style="width: 18%; text-align: right;">Amount</th>
                <th style="width: 19%;">Ref / Cheque No</th>
                <th style="width: 25%;">Description</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $slno = 1;
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $isDeposit = strtolower($row['type']) == 'deposit';
                    $badgeClass = $isDeposit ? 'badge-deposit' : 'badge-withdrawal';
                    $amountClass = $isDeposit ? 'amount-deposit' : 'amount-withdrawal';
                    ?>
                    <tr>
                        <td style="text-align: center;"><?php echo $slno++; ?></td>
                        <td><?php echo date('d-m-Y', strtotime($row['date'])); ?></td>
                        <td>
                            <span class="badge <?php echo $badgeClass; ?>">
                                <?php echo htmlspecialchars($row['type']); ?>
                            </span>
                        </td>
                        <td class="amount <?php echo $amountClass; ?>" style="text-align: right;">
                            ₹<?php echo number_format($row['amount'], 2); ?>
                        </td>
                        <td><?php echo htmlspecialchars($row['reference_no'] ?: '-'); ?></td>
                        <td><?php echo htmlspecialchars($row['description'] ?: '-'); ?></td>
                    </tr>
                    <?php
                }
            } else {
                echo '<tr><td colspan="6" style="text-align: center; color: #64748b;">No transactions found</td></tr>';
            }
            ?>
        </tbody>
    </table>

    <div class="footer">
        © <?php echo date('Y'); ?> YMCA BCP Poovathussery. All rights reserved.
    </div>

    <script>
        // Trigger browser print dialog automatically after page loads
        window.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                window.print();
            }, 300);
        });
    </script>
</body>
</html>
