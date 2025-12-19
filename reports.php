<?php
session_start();
$serverName = "DESKTOP-I9LLCAD\SQLEXPRESS";
$connectionOptions = [
    "Database" => "DLSU",
    "Uid" => "",
    "PWD" => ""
];

$conn = sqlsrv_connect($serverName, $connectionOptions);
if ($conn === false) {
    die(print_r(sqlsrv_errors(), true));
}

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$statsSql = "SELECT 
    COUNT(*) as total_transactions,
    SUM(TOTALAMOUNT) as total_revenue,
    AVG(TOTALAMOUNT) as avg_order_value,
    MIN(CREATEDATE) as first_sale,
    MAX(CREATEDATE) as last_sale
    FROM TRANSACTIONS";
$statsResult = sqlsrv_query($conn, $statsSql);
$stats = sqlsrv_fetch_array($statsResult, SQLSRV_FETCH_ASSOC);

// Get recent transactions
$txSql = "SELECT TOP 50 * FROM TRANSACTIONS ORDER BY CREATEDATE DESC";
$txStmt = sqlsrv_query($conn, $txSql);

// Get today's sales
$todaySql = "SELECT SUM(TOTALAMOUNT) as today_sales 
             FROM TRANSACTIONS 
             WHERE CAST(CREATEDATE AS DATE) = CAST(GETDATE() AS DATE)";
$todayResult = sqlsrv_query($conn, $todaySql);
$today = sqlsrv_fetch_array($todayResult, SQLSRV_FETCH_ASSOC);

// Get monthly sales
$monthSql = "SELECT SUM(TOTALAMOUNT) as month_sales 
             FROM TRANSACTIONS 
             WHERE MONTH(CREATEDATE) = MONTH(GETDATE()) 
             AND YEAR(CREATEDATE) = YEAR(GETDATE())";
$monthResult = sqlsrv_query($conn, $monthSql);
$month = sqlsrv_fetch_array($monthResult, SQLSRV_FETCH_ASSOC);

// Get daily data for the last 30 days
$dailySql = "SELECT 
    CAST(CREATEDATE AS DATE) as date,
    COUNT(*) as order_count,
    ISNULL(SUM(TOTALAMOUNT), 0) as daily_revenue
    FROM TRANSACTIONS
    WHERE CREATEDATE >= DATEADD(day, -30, GETDATE())
    GROUP BY CAST(CREATEDATE AS DATE)
    ORDER BY date ASC";
    
$dailyResult = sqlsrv_query($conn, $dailySql);
$dailyData = [];
if ($dailyResult) {
    while ($row = sqlsrv_fetch_array($dailyResult, SQLSRV_FETCH_ASSOC)) {
        $dailyData[] = $row;
    }
}

// If no daily data, create empty array for 30 days
if (empty($dailyData)) {
    for ($i = 29; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $dailyData[] = [
            'date' => $date,
            'order_count' => 0,
            'daily_revenue' => 0
        ];
    }
}

// Get weekly data for the last 12 weeks
$weeklySql = "SELECT 
    DATEPART(year, CREATEDATE) as year,
    DATEPART(week, CREATEDATE) as week_number,
    MIN(CREATEDATE) as week_start,
    COUNT(*) as order_count,
    ISNULL(SUM(TOTALAMOUNT), 0) as weekly_revenue
    FROM TRANSACTIONS
    WHERE CREATEDATE >= DATEADD(week, -12, GETDATE())
    GROUP BY DATEPART(year, CREATEDATE), DATEPART(week, CREATEDATE)
    ORDER BY year, week_number ASC";
    
$weeklyResult = sqlsrv_query($conn, $weeklySql);
$weeklyData = [];
if ($weeklyResult) {
    while ($row = sqlsrv_fetch_array($weeklyResult, SQLSRV_FETCH_ASSOC)) {
        $weeklyData[] = $row;
    }
}

// If no weekly data, create empty array for 12 weeks
if (empty($weeklyData)) {
    for ($i = 11; $i >= 0; $i--) {
        $week_start = date('Y-m-d', strtotime("-$i weeks"));
        $weeklyData[] = [
            'week_number' => date('W', strtotime($week_start)),
            'week_start' => $week_start,
            'order_count' => 0,
            'weekly_revenue' => 0
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sales Analytics • Forresta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Playfair+Display:wght@400;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --morning-sun: #FFF8E1;
            --morning-light: #FFE082;
            --afternoon-sky: #B3E5FC;
            --afternoon-green: #A5D6A7;
            --evening-gold: #FFD54F;
            --evening-amber: #FFB74D;
            --evening-twilight: #5D4037;
            --text-dark: #333333;
            --text-light: #FFFFFF;
            --shadow-light: rgba(0, 0, 0, 0.1);
            --admin-purple: #8a2be2;
            --success-green: #28a745;
            --info-blue: #17a2b8;
            --chart-yellow: #FFD54F;
            --chart-purple: #8a2be2;
            --chart-blue: #4a8fe7;
            --chart-green: #66BB6A;
        }

        body {
            color: var(--text-dark);
            font-family: 'Montserrat', Helvetica, sans-serif;
            font-weight: 400;
            min-height: 100vh;
            background: linear-gradient(135deg, rgba(255, 248, 225, 0.95) 0%, rgba(179, 229, 252, 0.95) 100%),
                url('https://images.unsplash.com/photo-1578662996442-48f60103fc96?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80');
            background-size: cover;
            background-attachment: fixed;
        }

        .admin-navbar {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(15px);
            border-bottom: 1px solid rgba(165, 214, 167, 0.3);
            padding: 1rem 0;
            transition: all 0.3s ease;
            font-family: 'Montserrat', Helvetica, sans-serif;
            font-weight: 500;
        }

        .admin-navbar:hover {
            background: rgba(255, 255, 255, 1);
        }

        .admin-brand {
            font-family: 'Playfair Display', serif;
            font-weight: 800;
            font-size: 2rem;
            color: var(--evening-twilight);
            letter-spacing: 0.5px;
            position: relative;
            padding-left: 2.5rem;
        }

        .admin-brand::before {
            content: '👑';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.8rem;
            animation: gentleSway 4s infinite ease-in-out;
        }

        @keyframes gentleSway {
            0%, 100% { transform: translateY(-50%) rotate(0deg); }
            50% { transform: translateY(-50%) rotate(5deg); }
        }

        .analytics-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        .page-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(25px);
            border-radius: 24px;
            margin: 2rem auto;
            padding: 3rem;
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.08);
            position: relative;
            overflow: hidden;
            animation: fadeIn 0.8s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 20% 80%, rgba(179, 229, 252, 0.15) 0%, transparent 50%),
                        radial-gradient(circle at 80% 20%, rgba(255, 213, 79, 0.15) 0%, transparent 50%);
            pointer-events: none;
        }

        .page-title {
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            font-size: 3rem;
            color: var(--evening-twilight);
            margin-bottom: 1rem;
            line-height: 1.2;
        }

        .page-subtitle {
            font-family: 'Montserrat', Helvetica, sans-serif;
            font-weight: 400;
            font-size: 1.2rem;
            color: #555;
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        .sales-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            border: 1px solid rgba(0, 0, 0, 0.08);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        }

        .stat-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
            border-color: var(--evening-amber);
        }

        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            background: linear-gradient(45deg, var(--evening-amber), var(--afternoon-green));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .stat-value {
            font-family: 'Playfair Display', serif;
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--evening-amber);
            margin-bottom: 0.5rem;
            line-height: 1;
        }

        .stat-label {
            color: var(--evening-twilight);
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .charts-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .chart-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            border: 1px solid rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            min-height: 400px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        }

        .chart-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
            border-color: var(--evening-amber);
        }

        .chart-title {
            font-family: 'Playfair Display', serif;
            color: var(--evening-twilight);
            font-size: 1.3rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .transactions-container {
            background: white;
            border-radius: 24px;
            margin: 2rem auto;
            padding: 2rem;
            border: 1px solid rgba(0, 0, 0, 0.08);
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.08);
            position: relative;
            overflow: hidden;
        }

        .transactions-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 20% 80%, rgba(179, 229, 252, 0.05) 0%, transparent 50%),
                        radial-gradient(circle at 80% 20%, rgba(255, 213, 79, 0.05) 0%, transparent 50%);
            pointer-events: none;
        }

        .transaction-card {
            background: white;
            border-radius: 16px;
            border: 1px solid rgba(0, 0, 0, 0.08);
            margin-bottom: 1.5rem;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        .transaction-card:hover {
            transform: translateX(5px);
            border-color: var(--evening-amber);
            box-shadow: 0 10px 30px rgba(255, 183, 77, 0.15);
        }

        .transaction-header {
            background: linear-gradient(90deg, rgba(165, 214, 167, 0.2) 0%, rgba(255, 213, 79, 0.2) 100%);
            padding: 1.2rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .transaction-id {
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            color: var(--evening-twilight);
            font-size: 1.2rem;
        }

        .transaction-amount {
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            color: var(--evening-amber);
            font-size: 1.4rem;
        }

        .transaction-body {
            padding: 1.5rem;
        }

        .transaction-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-label {
            font-size: 0.85rem;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.3rem;
            font-weight: 600;
        }

        .info-value {
            font-weight: 600;
            color: var(--evening-twilight);
        }

        .items-table {
            width: 100%;
            color: var(--text-dark);
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 1.5rem;
        }

        .items-table thead th {
            background: rgba(165, 214, 167, 0.2);
            color: var(--evening-twilight);
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid var(--afternoon-green);
        }

        .items-table tbody td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.08);
        }

        .items-table tbody tr:last-child td {
            border-bottom: none;
        }

        .items-table tbody tr:hover {
            background: rgba(255, 213, 79, 0.05);
        }

        .item-price {
            color: var(--evening-amber);
            font-weight: 600;
        }

        .item-subtotal {
            color: var(--evening-twilight);
            font-weight: 700;
        }

        .grand-total-card {
            background: linear-gradient(135deg, rgba(255, 213, 79, 0.1) 0%, rgba(165, 214, 167, 0.1) 100%);
            border-radius: 20px;
            border: 2px solid var(--evening-amber);
            padding: 2rem;
            text-align: center;
            margin: 3rem 0 2rem 0;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        }

        .grand-total-label {
            font-size: 1.2rem;
            color: var(--evening-twilight);
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .grand-total-value {
            font-family: 'Playfair Display', serif;
            font-size: 3rem;
            font-weight: 900;
            color: var(--evening-amber);
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .btn-back {
            background: linear-gradient(135deg, var(--evening-amber), #FFA726);
            color: white;
            border: none;
            border-radius: 50px;
            padding: 1rem 2.5rem;
            font-family: 'Montserrat', Helvetica, sans-serif;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            box-shadow: 0 5px 20px rgba(255, 183, 77, 0.3);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-back:hover {
            background: linear-gradient(135deg, #FF9800, var(--evening-amber));
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(255, 183, 77, 0.4);
            color: white;
        }

        .btn-outline-light {
            border: 2px solid var(--afternoon-green);
            color: var(--afternoon-green);
            border-radius: 50px;
            padding: 0.7rem 1.5rem;
            font-family: 'Montserrat', Helvetica, sans-serif;
            font-weight: 600;
            transition: all 0.3s ease;
            background: transparent;
        }

        .btn-outline-light:hover {
            background: var(--afternoon-green);
            color: white;
            transform: translateY(-2px);
        }

        .user-greeting {
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            color: var(--evening-twilight);
            font-size: 1.1rem;
        }

        .empty-state {
            text-align: center;
            padding: 4rem;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            border: 2px dashed var(--afternoon-green);
        }

        .empty-state i {
            font-size: 4rem;
            color: var(--afternoon-green);
            margin-bottom: 1.5rem;
        }

        .section-divider {
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--afternoon-green), transparent);
            margin: 3rem 0;
            opacity: 0.5;
        }

        .date-filter {
            background: white;
            border: 2px solid var(--afternoon-green);
            border-radius: 50px;
            padding: 0.9rem 1.5rem;
            color: var(--text-dark);
            width: 200px;
            font-family: 'Montserrat', Helvetica, sans-serif;
            font-weight: 400;
            transition: all 0.3s ease;
        }

        .date-filter:focus {
            outline: none;
            border-color: var(--evening-amber);
            box-shadow: 0 0 0 4px rgba(255, 183, 77, 0.15);
        }

        @media (max-width: 992px) {
            .page-header {
                padding: 2rem;
            }
            
            .page-title {
                font-size: 2.5rem;
            }
            
            .charts-container {
                grid-template-columns: 1fr;
            }
            
            .chart-card {
                min-height: 350px;
            }
        }

        @media (max-width: 768px) {
            .page-title {
                font-size: 2rem;
            }
            
            .admin-brand {
                font-size: 1.6rem;
                padding-left: 2rem;
            }
            
            .admin-brand::before {
                font-size: 1.4rem;
            }
            
            .transaction-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .transaction-info {
                grid-template-columns: 1fr;
            }
            
            .grand-total-value {
                font-size: 2.5rem;
            }
            
            .sales-stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 576px) {
            .sales-stats {
                grid-template-columns: 1fr;
            }
            
            .charts-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

    <nav class="navbar navbar-expand-lg admin-navbar">
        <div class="container">
            <a class="navbar-brand admin-brand" href="admin_dashboard.php">Forresta Admin</a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="navbar-nav ms-auto align-items-center gap-3">
                    <span class="user-greeting me-3">
                        <i class="fas fa-user-circle me-2"></i>
                        <?php echo htmlspecialchars($_SESSION['user']['username']); ?>
                    </span>
                    <a href="logout.php" class="btn btn-outline-light me-2">
                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                    </a>
                    <a href="index.php" class="btn btn-back">
                        <i class="fas fa-arrow-left me-2"></i>Back to Café
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="analytics-container">
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-chart-line me-2"></i>
                Sales Analytics Dashboard
            </h1>
            <p class="page-subtitle">Track revenue, analyze trends, and monitor the financial performance of Forresta</p>

            <div class="sales-stats">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-value">₱<?php echo number_format($stats['total_revenue'] ?? 0, 0); ?></div>
                    <div class="stat-label">Total Revenue</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <div class="stat-value"><?php echo $stats['total_transactions'] ?? 0; ?></div>
                    <div class="stat-label">Total Transactions</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-tag"></i>
                    </div>
                    <div class="stat-value">₱<?php echo number_format($stats['avg_order_value'] ?? 0, 2); ?></div>
                    <div class="stat-label">Avg Order Value</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div class="stat-value">₱<?php echo number_format($today['today_sales'] ?? 0, 2); ?></div>
                    <div class="stat-label">Today's Sales</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="stat-value">₱<?php echo number_format($month['month_sales'] ?? 0, 0); ?></div>
                    <div class="stat-label">Monthly Revenue</div>
                </div>
            </div>

            <div class="section-divider"></div>

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div class="d-flex gap-2">
                    <a href="admin_dashboard.php" class="btn btn-back">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>
            </div>
        </div>

        <!-- DAILY AND WEEKLY CHARTS SECTION -->
        <div class="charts-container">
            <div class="chart-card">
                <h3 class="chart-title">
                    <i class="fas fa-calendar-day me-2"></i>
                    Daily Revenue Trend (Last 30 Days)
                </h3>
                <div style="height: 300px;">
                    <canvas id="dailyRevenueChart"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <h3 class="chart-title">
                    <i class="fas fa-shopping-cart me-2"></i>
                    Daily Orders (Last 30 Days)
                </h3>
                <div style="height: 300px;">
                    <canvas id="dailyOrdersChart"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <h3 class="chart-title">
                    <i class="fas fa-calendar-week me-2"></i>
                    Weekly Revenue Trend (Last 12 Weeks)
                </h3>
                <div style="height: 300px;">
                    <canvas id="weeklyRevenueChart"></canvas>
                </div>
            </div>

            <div class="chart-card">
                <h3 class="chart-title">
                    <i class="fas fa-chart-bar me-2"></i>
                    Weekly Orders (Last 12 Weeks)
                </h3>
                <div style="height: 300px;">
                    <canvas id="weeklyOrdersChart"></canvas>
                </div>
            </div>
        </div>

        <div class="transactions-container">
            <h3 class="chart-title mb-4">
                <i class="fas fa-history me-2"></i>
                Recent Transactions
            </h3>

            <?php
            $grandTotal = 0;
            $hasTransactions = false;
            
            // Reset and check transactions
            $txStmt = sqlsrv_query($conn, $txSql);
            if ($txStmt) {
                $hasTransactions = sqlsrv_has_rows($txStmt);
            }

            if (!$hasTransactions) {
                echo '<div class="empty-state">
                        <i class="fas fa-receipt"></i>
                        <h3 class="mb-3" style="font-family: \'Playfair Display\', serif; color: var(--evening-twilight);">No Transactions Found</h3>
                        <p>Your sales history is empty. Make your first sale to see data here!</p>
                    </div>';
            } else {
                $txStmt = sqlsrv_query($conn, $txSql);
                while ($tx = sqlsrv_fetch_array($txStmt, SQLSRV_FETCH_ASSOC)) :
                    $tid = $tx['TRANSACTIONID'];
                    $cust = htmlspecialchars($tx['CUSTOMERNAME'] ?? '');
                    $contact = htmlspecialchars($tx['CONTACT'] ?? '');
                    $notes = htmlspecialchars($tx['NOTES'] ?? '');
                    
                    if (isset($tx['CREATEDATE']) && $tx['CREATEDATE'] instanceof DateTime) {
                        $date = $tx['CREATEDATE']->format("F j, Y \\a\\t g:i A");
                    } else {
                        $date = 'Date not available';
                    }
                    
                    $total = number_format($tx['TOTALAMOUNT'] ?? 0, 2);
                    $grandTotal += $tx['TOTALAMOUNT'] ?? 0;

                    // Calculate time ago
                    $timeAgo = 'Recently';
                    if (isset($tx['CREATEDATE']) && $tx['CREATEDATE'] instanceof DateTime) {
                        $txDate = $tx['CREATEDATE'];
                        $now = new DateTime();
                        $interval = $txDate->diff($now);
                        
                        if ($interval->y > 0) $timeAgo = $interval->y . ' year' . ($interval->y > 1 ? 's' : '') . ' ago';
                        elseif ($interval->m > 0) $timeAgo = $interval->m . ' month' . ($interval->m > 1 ? 's' : '') . ' ago';
                        elseif ($interval->d > 0) $timeAgo = $interval->d . ' day' . ($interval->d > 1 ? 's' : '') . ' ago';
                        elseif ($interval->h > 0) $timeAgo = $interval->h . ' hour' . ($interval->h > 1 ? 's' : '') . ' ago';
                        elseif ($interval->i > 0) $timeAgo = $interval->i . ' minute' . ($interval->i > 1 ? 's' : '') . ' ago';
                        else $timeAgo = 'Just now';
                    }
            ?>

                    <div class="transaction-card">
                        <div class="transaction-header">
                            <div>
                                <span class="transaction-id">
                                    <i class="fas fa-hashtag me-1"></i>
                                    Transaction #<?= $tid ?>
                                </span>
                                <span class="ms-3" style="color: #666;">
                                    <i class="fas fa-clock me-1"></i>
                                    <?= $timeAgo ?>
                                </span>
                            </div>
                            <div class="transaction-amount">₱<?= $total ?></div>
                        </div>

                        <div class="transaction-body">
                            <div class="transaction-info">
                                <div class="info-item">
                                    <span class="info-label">Customer</span>
                                    <span class="info-value">
                                        <i class="fas fa-user me-1"></i>
                                        <?= $cust ?: 'Walk-in Customer' ?>
                                    </span>
                                </div>

                                <div class="info-item">
                                    <span class="info-label">Contact</span>
                                    <span class="info-value">
                                        <i class="fas fa-phone me-1"></i>
                                        <?= $contact ?: 'N/A' ?>
                                    </span>
                                </div>

                                <div class="info-item">
                                    <span class="info-label">Date & Time</span>
                                    <span class="info-value">
                                        <i class="fas fa-calendar me-1"></i>
                                        <?= $date ?>
                                    </span>
                                </div>

                                <div class="info-item">
                                    <span class="info-label">Notes</span>
                                    <span class="info-value">
                                        <i class="fas fa-sticky-note me-1"></i>
                                        <?= $notes ?: 'No notes' ?>
                                    </span>
                                </div>
                            </div>

                            <h5 style="font-family: 'Playfair Display', serif; color: var(--evening-twilight); margin-bottom: 1rem;">
                                <i class="fas fa-list-alt me-2"></i>
                                Items Ordered
                            </h5>

                            <table class="items-table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Qty</th>
                                        <th>Unit Price</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $itemSql = "SELECT * FROM TRANSACTIONITEMS WHERE TRANSACTIONID = ?";
                                    $itemParams = array($tid);
                                    $itemStmt = sqlsrv_query($conn, $itemSql, $itemParams);
                                    
                                    if ($itemStmt) {
                                        while ($item = sqlsrv_fetch_array($itemStmt, SQLSRV_FETCH_ASSOC)) :
                                            $name = htmlspecialchars($item['PRODUCTNAME'] ?? '');
                                            $qty = $item['QUANTITY'] ?? 0;
                                            $price = number_format($item['PRICE'] ?? 0, 2);
                                            $subtotal = number_format(($qty * ($item['PRICE'] ?? 0)), 2);
                                    ?>
                                        <tr>
                                            <td><?= $name ?></td>
                                            <td><?= $qty ?></td>
                                            <td class="item-price">₱<?= $price ?></td>
                                            <td class="item-subtotal">₱<?= $subtotal ?></td>
                                        </tr>
                                    <?php 
                                        endwhile;
                                    } else {
                                        echo '<tr><td colspan="4" class="text-center">No items found</td></tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

            <?php endwhile; ?>

                <div class="grand-total-card">
                    <div class="grand-total-label">Total Revenue from Recent Transactions</div>
                    <div class="grand-total-value">₱<?= number_format($grandTotal, 2) ?></div>
                    <div style="color: #666; margin-top: 0.5rem;">
                        <i class="fas fa-chart-pie me-1"></i>
                        Based on <?= $stats['total_transactions'] ?? 0 ?> total transactions
                    </div>
                </div>

            <?php } ?>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, initializing charts...');
            
            // Prepare daily data for charts
            const dailyDates = [
                <?php
                if (!empty($dailyData)) {
                    foreach ($dailyData as $day) {
                        if (isset($day['date'])) {
                            $dateStr = is_string($day['date']) ? $day['date'] : $day['date']->format('Y-m-d');
                            $date = date('M j', strtotime($dateStr));
                            echo "'$date', ";
                        }
                    }
                }
                ?>
            ];

            const dailyRevenue = [
                <?php
                if (!empty($dailyData)) {
                    foreach ($dailyData as $day) {
                        echo ($day['daily_revenue'] ?? 0) . ', ';
                    }
                }
                ?>
            ];

            const dailyOrders = [
                <?php
                if (!empty($dailyData)) {
                    foreach ($dailyData as $day) {
                        echo ($day['order_count'] ?? 0) . ', ';
                    }
                }
                ?>
            ];

            // Prepare weekly data for charts
            const weeklyLabels = [
                <?php
                if (!empty($weeklyData)) {
                    foreach ($weeklyData as $week) {
                        $weekNum = $week['week_number'] ?? 'N/A';
                        if (isset($week['week_start'])) {
                            $startDate = is_string($week['week_start']) ? $week['week_start'] : $week['week_start']->format('Y-m-d');
                            $start = date('M j', strtotime($startDate));
                            $end = date('M j', strtotime($startDate . ' +6 days'));
                            echo "'Week $weekNum\\n($start - $end)', ";
                        } else {
                            echo "'Week $weekNum', ";
                        }
                    }
                }
                ?>
            ];

            const weeklyRevenue = [
                <?php
                if (!empty($weeklyData)) {
                    foreach ($weeklyData as $week) {
                        echo ($week['weekly_revenue'] ?? 0) . ', ';
                    }
                }
                ?>
            ];

            const weeklyOrders = [
                <?php
                if (!empty($weeklyData)) {
                    foreach ($weeklyData as $week) {
                        echo ($week['order_count'] ?? 0) . ', ';
                    }
                }
                ?>
            ];

            console.log('Daily data:', dailyDates, dailyRevenue, dailyOrders);
            console.log('Weekly data:', weeklyLabels, weeklyRevenue, weeklyOrders);

            // Chart color configurations
            const chartColors = {
                yellow: '#FFD54F',
                purple: '#8a2be2',
                blue: '#4a8fe7',
                green: '#66BB6A'
            };

            // Initialize Daily Revenue Chart
            if (document.getElementById('dailyRevenueChart')) {
                console.log('Initializing Daily Revenue Chart...');
                try {
                    const dailyRevenueCtx = document.getElementById('dailyRevenueChart').getContext('2d');
                    const dailyRevenueChart = new Chart(dailyRevenueCtx, {
                        type: 'line',
                        data: {
                            labels: dailyDates.length > 0 ? dailyDates : ['No data'],
                            datasets: [{
                                label: 'Daily Revenue',
                                data: dailyRevenue.length > 0 ? dailyRevenue : [0],
                                borderColor: chartColors.yellow,
                                backgroundColor: 'rgba(255, 213, 79, 0.1)',
                                borderWidth: 3,
                                fill: true,
                                tension: 0.3,
                                pointBackgroundColor: chartColors.yellow,
                                pointBorderColor: '#fff',
                                pointBorderWidth: 2,
                                pointRadius: 4,
                                pointHoverRadius: 6
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    labels: {
                                        color: '#333',
                                        font: {
                                            family: "'Montserrat', sans-serif",
                                            size: 12
                                        }
                                    }
                                },
                                tooltip: {
                                    backgroundColor: 'rgba(255, 255, 255, 0.9)',
                                    titleColor: '#333',
                                    bodyColor: '#333',
                                    borderColor: chartColors.yellow,
                                    borderWidth: 1,
                                    callbacks: {
                                        label: function(context) {
                                            return '₱' + context.parsed.y.toLocaleString();
                                        }
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    ticks: {
                                        color: '#666',
                                        maxRotation: 45,
                                        font: {
                                            family: "'Montserrat', sans-serif"
                                        }
                                    },
                                    grid: {
                                        color: 'rgba(0, 0, 0, 0.05)'
                                    }
                                },
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        color: '#666',
                                        callback: function(value) {
                                            return '₱' + value.toLocaleString();
                                        },
                                        font: {
                                            family: "'Montserrat', sans-serif"
                                        }
                                    },
                                    grid: {
                                        color: 'rgba(0, 0, 0, 0.05)'
                                    }
                                }
                            }
                        }
                    });
                    console.log('Daily Revenue Chart initialized successfully');
                } catch (error) {
                    console.error('Error initializing Daily Revenue Chart:', error);
                }
            }

            // Initialize Daily Orders Chart
            if (document.getElementById('dailyOrdersChart')) {
                console.log('Initializing Daily Orders Chart...');
                try {
                    const dailyOrdersCtx = document.getElementById('dailyOrdersChart').getContext('2d');
                    const dailyOrdersChart = new Chart(dailyOrdersCtx, {
                        type: 'bar',
                        data: {
                            labels: dailyDates.length > 0 ? dailyDates : ['No data'],
                            datasets: [{
                                label: 'Daily Orders',
                                data: dailyOrders.length > 0 ? dailyOrders : [0],
                                backgroundColor: chartColors.purple,
                                borderColor: chartColors.purple,
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    labels: {
                                        color: '#333',
                                        font: {
                                            family: "'Montserrat', sans-serif",
                                            size: 12
                                        }
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    ticks: {
                                        color: '#666',
                                        maxRotation: 45,
                                        font: {
                                            family: "'Montserrat', sans-serif"
                                        }
                                    },
                                    grid: {
                                        color: 'rgba(0, 0, 0, 0.05)'
                                    }
                                },
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        color: '#666',
                                        precision: 0,
                                        font: {
                                            family: "'Montserrat', sans-serif"
                                        }
                                    },
                                    grid: {
                                        color: 'rgba(0, 0, 0, 0.05)'
                                    }
                                }
                            }
                        }
                    });
                    console.log('Daily Orders Chart initialized successfully');
                } catch (error) {
                    console.error('Error initializing Daily Orders Chart:', error);
                }
            }

            // Initialize Weekly Revenue Chart
            if (document.getElementById('weeklyRevenueChart')) {
                console.log('Initializing Weekly Revenue Chart...');
                try {
                    const weeklyRevenueCtx = document.getElementById('weeklyRevenueChart').getContext('2d');
                    const weeklyRevenueChart = new Chart(weeklyRevenueCtx, {
                        type: 'line',
                        data: {
                            labels: weeklyLabels.length > 0 ? weeklyLabels : ['No data'],
                            datasets: [{
                                label: 'Weekly Revenue',
                                data: weeklyRevenue.length > 0 ? weeklyRevenue : [0],
                                borderColor: chartColors.blue,
                                backgroundColor: 'rgba(74, 143, 231, 0.1)',
                                borderWidth: 3,
                                fill: true,
                                tension: 0.3,
                                pointBackgroundColor: chartColors.blue,
                                pointBorderColor: '#fff',
                                pointBorderWidth: 2,
                                pointRadius: 4,
                                pointHoverRadius: 6
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    labels: {
                                        color: '#333',
                                        font: {
                                            family: "'Montserrat', sans-serif",
                                            size: 12
                                        }
                                    }
                                },
                                tooltip: {
                                    backgroundColor: 'rgba(255, 255, 255, 0.9)',
                                    titleColor: '#333',
                                    bodyColor: '#333',
                                    borderColor: chartColors.blue,
                                    borderWidth: 1,
                                    callbacks: {
                                        label: function(context) {
                                            return '₱' + context.parsed.y.toLocaleString();
                                        }
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    ticks: {
                                        color: '#666',
                                        maxRotation: 45,
                                        font: {
                                            family: "'Montserrat', sans-serif"
                                        }
                                    },
                                    grid: {
                                        color: 'rgba(0, 0, 0, 0.05)'
                                    }
                                },
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        color: '#666',
                                        callback: function(value) {
                                            return '₱' + value.toLocaleString();
                                        },
                                        font: {
                                            family: "'Montserrat', sans-serif"
                                        }
                                    },
                                    grid: {
                                        color: 'rgba(0, 0, 0, 0.05)'
                                    }
                                }
                            }
                        }
                    });
                    console.log('Weekly Revenue Chart initialized successfully');
                } catch (error) {
                    console.error('Error initializing Weekly Revenue Chart:', error);
                }
            }

            // Initialize Weekly Orders Chart
            if (document.getElementById('weeklyOrdersChart')) {
                console.log('Initializing Weekly Orders Chart...');
                try {
                    const weeklyOrdersCtx = document.getElementById('weeklyOrdersChart').getContext('2d');
                    const weeklyOrdersChart = new Chart(weeklyOrdersCtx, {
                        type: 'bar',
                        data: {
                            labels: weeklyLabels.length > 0 ? weeklyLabels : ['No data'],
                            datasets: [{
                                label: 'Weekly Orders',
                                data: weeklyOrders.length > 0 ? weeklyOrders : [0],
                                backgroundColor: chartColors.green,
                                borderColor: chartColors.green,
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    labels: {
                                        color: '#333',
                                        font: {
                                            family: "'Montserrat', sans-serif",
                                            size: 12
                                        }
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    ticks: {
                                        color: '#666',
                                        maxRotation: 45,
                                        font: {
                                            family: "'Montserrat', sans-serif"
                                        }
                                    },
                                    grid: {
                                        color: 'rgba(0, 0, 0, 0.05)'
                                    }
                                },
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        color: '#666',
                                        precision: 0,
                                        font: {
                                            family: "'Montserrat', sans-serif"
                                        }
                                    },
                                    grid: {
                                        color: 'rgba(0, 0, 0, 0.05)'
                                    }
                                }
                            }
                        }
                    });
                    console.log('Weekly Orders Chart initialized successfully');
                } catch (error) {
                    console.error('Error initializing Weekly Orders Chart:', error);
                }
            }

            // Add animation to content
            const contentElements = document.querySelectorAll('.page-header, .charts-container, .transactions-container');
            contentElements.forEach((element, index) => {
                setTimeout(() => {
                    element.style.animation = 'fadeIn 0.8s ease forwards';
                    element.style.opacity = '0';
                }, index * 200);
            });

            // Add hover effect to stat cards
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-8px) scale(1.02)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                });
            });

            // Add hover effect to chart cards
            const chartCards = document.querySelectorAll('.chart-card');
            chartCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });

            // Add hover effect to transaction cards
            const transactionCards = document.querySelectorAll('.transaction-card');
            transactionCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateX(5px)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateX(0)';
                });
            });
            
            console.log('All charts initialized');
        });
    </script>
</body>
</html>