<?php
session_start();

// Ensure user is a logged-in admin
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    header("location: login.php");
    exit;
}

require_once "config.php";

// Fetch chart data
$chart_data = [];
$sql_chart = "
    SELECT
        DATE_FORMAT(issue_date, '%Y-%m') AS issue_month,
        COUNT(*) as count
    FROM (
        SELECT issue_date FROM zoning_certificates
        UNION ALL
        SELECT issue_date FROM locational_clearances
    ) AS all_issuances
    WHERE issue_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY issue_month
    ORDER BY issue_month ASC;
";

if($result_chart = mysqli_query($link, $sql_chart)) {
    while($row_chart = mysqli_fetch_assoc($result_chart)) {
        $chart_data[] = $row_chart;
    }
}
mysqli_close($link);

// Prepare data for JavaScript
$chart_labels = json_encode(array_column($chart_data, 'issue_month'));
$chart_values = json_encode(array_column($chart_data, 'count'));

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .dashboard-card {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .dashboard-card h3 {
            margin-top: 0;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <nav>
            <a href="main_dashboard.php">Dashboard</a>
            <a href="manage_zoning.php">Zoning Certificates</a>
            <a href="manage_locational.php">Locational Clearances</a>
            <div class="nav-item dropdown">
                <a href="javascript:void(0)" class="dropbtn">Settings</a>
                <div class="dropdown-content">
                    <a href="settings.php">Application Settings</a>
                    <a href="admin_dashboard.php">User Management</a>
                </div>
            </div>
            <a href="logout.php" style="float:right; margin-right:20px;">Sign Out</a>
        </nav>

        <div class="page-header" style="margin-top: 20px;">
            <h1>Admin Dashboard</h1>
        </div>

        <div class="dashboard-grid">
            <div class="dashboard-card" id="search-card">
                <h3>Search Records</h3>
                <form action="search_results.php" method="get">
                    <div class="form-group">
                        <input type="text" name="query" class="form-control" placeholder="Search by name, classification, zone, or location..." required>
                    </div>
                    <div class="form-group">
                        <input type="submit" class="btn btn-primary" value="Search">
                    </div>
                </form>
            </div>
            <div class="dashboard-card" id="chart-card">
                <h3>Monthly Issuance Overview (Last 12 Months)</h3>
                <canvas id="issuanceChart"></canvas>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('issuanceChart');
        const chartLabels = <?php echo $chart_labels; ?>;
        const chartValues = <?php echo $chart_values; ?>;

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: chartLabels,
                datasets: [{
                    label: '# of Issuances',
                    data: chartValues,
                    backgroundColor: 'rgba(54, 162, 235, 0.6)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1 // Ensure y-axis increments by whole numbers
                        }
                    }
                },
                responsive: true,
                maintainAspectRatio: false
            }
        });
    </script>
</body>
</html>
