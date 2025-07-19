<?php
session_start();

// Ensure user is a logged-in admin
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    header("location: login.php");
    exit;
}

require_once "config.php";
$link = get_db_connection();

// Fetch chart data
$chart_data = [];
$sql_chart = "
    SELECT
        DATE_FORMAT(issue_date, '%Y-%m') AS issue_month,
        'Zoning' AS type,
        COUNT(*) as count
    FROM zoning_certificates
    WHERE issue_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY issue_month
    UNION ALL
    SELECT
        DATE_FORMAT(issue_date, '%Y-%m') AS issue_month,
        'Locational' AS type,
        COUNT(*) as count
    FROM locational_clearances
    WHERE issue_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY issue_month
    UNION ALL
    SELECT
        DATE_FORMAT(issue_date, '%Y-%m') AS issue_month,
        'Fishing' AS type,
        COUNT(*) as count
    FROM fishing_gear_permits
    WHERE issue_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY issue_month
    ORDER BY issue_month ASC, type ASC;
";

$results = mysqli_query($link, $sql_chart);
$data = [];
$labels = [];
while($row = mysqli_fetch_assoc($results)) {
    $labels[] = $row['issue_month'];
    $data[$row['type']][$row['issue_month']] = $row['count'];
}
$labels = array_unique($labels);
sort($labels);

$datasets = [];
$colors = ['rgba(54, 162, 235, 1)', 'rgba(255, 99, 132, 1)', 'rgba(75, 192, 192, 1)'];
$i = 0;
foreach ($data as $type => $values) {
    $dataset = [
        'label' => $type,
        'data' => [],
        'borderColor' => $colors[$i],
        'backgroundColor' => str_replace('1)', '0.2)', $colors[$i]),
        'fill' => true,
    ];
    foreach ($labels as $label) {
        $dataset['data'][] = $values[$label] ?? 0;
    }
    $datasets[] = $dataset;
    $i++;
}

mysqli_close($link);

$chart_labels = json_encode($labels);
$chart_datasets = json_encode($datasets);

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
        #chart-card {
            position: relative;
            height: 40vh; /* vh is viewport height, adjust as needed */
            max-height: 350px; /* Set a max height */
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include 'navigation.php'; ?>

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
        const chartDatasets = <?php echo $chart_datasets; ?>;

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartLabels,
                datasets: chartDatasets
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
