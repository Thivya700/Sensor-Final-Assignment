<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sensormidterm";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch processed data from processeddata table including insights
function fetchProcessedData($conn) {
    $sql = "SELECT *, 
                   CASE
                       WHEN avg_temperature > 28 THEN 'Temperature is higher than usual.'
                       WHEN avg_temperature < 22 THEN 'Temperature is lower than usual.'
                       ELSE 'Temperature is within normal range.'
                   END AS temperature_insight,
                   CASE
                       WHEN avg_humidity > 70 THEN 'High humidity may cause discomfort.'
                       WHEN avg_humidity < 40 THEN 'Low humidity may cause dryness.'
                       ELSE 'Humidity levels are comfortable.'
                   END AS humidity_insight,
                   CASE
                       WHEN avg_light < 200 THEN 'Low light levels detected.'
                       WHEN avg_light > 800 THEN 'High light levels detected.'
                       ELSE 'Light levels are optimal.'
                   END AS light_insight
            FROM processeddata 
            ORDER BY timestamp DESC
            LIMIT 10"; // Limit to latest 10 entries for efficiency
    $result = $conn->query($sql);

    $data = array();
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }

    return $data;
}

// Function to calculate insights based on average, min, max values
function calculateInsights($data) {
    foreach ($data as &$entry) {
        // Temperature insights
        if ($entry['avg_temperature'] > 28) {
            $entry['temperature_insight'] = 'Temperature is higher than usual.';
        } elseif ($entry['avg_temperature'] < 22) {
            $entry['temperature_insight'] = 'Temperature is lower than usual.';
        } else {
            $entry['temperature_insight'] = 'Temperature is within normal range.';
        }

        // Humidity insights
        if ($entry['avg_humidity'] > 70) {
            $entry['humidity_insight'] = 'High humidity may cause discomfort.';
        } elseif ($entry['avg_humidity'] < 40) {
            $entry['humidity_insight'] = 'Low humidity may cause dryness.';
        } else {
            $entry['humidity_insight'] = 'Humidity levels are comfortable.';
        }

        // Light insights
        if ($entry['avg_light'] < 200) {
            $entry['light_insight'] = 'Low light levels detected.';
        } elseif ($entry['avg_light'] > 800) {
            $entry['light_insight'] = 'High light levels detected.';
        } else {
            $entry['light_insight'] = 'Light levels are optimal.';
        }
    }
    return $data;
}

// Check if action is fetch_data and fetch processed data
if (isset($_GET['action']) && $_GET['action'] === 'fetch_data') {
    $processedData = fetchProcessedData($conn);
    $processedDataWithInsights = calculateInsights($processedData);
    echo json_encode($processedDataWithInsights);
    exit;
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Processed Environmental Data</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f9f9f9;
        }

        h2 {
            text-align: center;
            color: #333;
        }

        .container {
            max-width: 1400px; /* Adjust width as needed */
            margin: auto;
            background-color: #fff;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .scrollable-table {
            overflow-y: auto; /* Only vertical scroll */
            max-height: 700px; /* Adjust height as needed */
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        table, th, td {
            border: 1px solid #ddd;
        }

        th, td {
            padding: 15px;
            text-align: center;
        }

        th {
            background-color: #4CAF50;
            color: white;
        }

        tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        tr:hover {
            background-color: #ddd;
        }

        .chart-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: 20px;
        }

        .chart {
            width: 70%; /* Full width of parent container */
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
        }

    </style>
</head>
<body>

<div class="container">
    <h2>Processed Environmental Data</h2>

    <div class="scrollable-table">
        <table id="processed-data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Average Temperature</th>
                    <th>Min Temperature</th>
                    <th>Max Temperature</th>
                    <th>Average Humidity</th>
                    <th>Min Humidity</th>
                    <th>Max Humidity</th>
                    <th>Average Light</th>
                    <th>Min Light</th>
                    <th>Max Light</th>
                    <th>Insights</th> <!-- New column for insights -->
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <!-- Table body will be populated dynamically -->
            </tbody>
        </table>
    </div>
</div>

<div class="chart-container">
    <div class="chart" id="temperatureChartContainer">
        <canvas id="temperatureChart"></canvas>
    </div>
    <div class="chart" id="humidityChartContainer">
        <canvas id="humidityChart"></canvas>
    </div>
    <div class="chart" id="lightChartContainer">
        <canvas id="lightChart"></canvas>
    </div>
</div>

<script>
    // Global variables to store chart instances
    var temperatureChart, humidityChart, lightChart;

    // Function to fetch processed data and update both table and charts
    function fetchAndDisplayData() {
        var xhr = new XMLHttpRequest();
        xhr.open("GET", "processed_data.php?action=fetch_data", true);
        xhr.onload = function() {
            if (xhr.status === 200) {
                var data = JSON.parse(xhr.responseText);

                // Update table
                var tableBody = document.querySelector("#processed-data-table tbody");
                tableBody.innerHTML = ""; // Clear existing rows
                data.forEach(function(row) {
                    var tr = document.createElement("tr");
                    tr.innerHTML = `
                        <td>${row.id}</td>
                        <td>${row.avg_temperature}</td>
                        <td>${row.min_temperature}</td>
                        <td>${row.max_temperature}</td>
                        <td>${row.avg_humidity}</td>
                        <td>${row.min_humidity}</td>
                        <td>${row.max_humidity}</td>
                        <td>${row.avg_light}</td>
                        <td>${row.min_light}</td>
                        <td>${row.max_light}</td>
                        <td>${row.temperature_insight}<br>${row.humidity_insight}<br>${row.light_insight}</td> <!-- Display insights -->
                        <td>${row.timestamp}</td>
                    `;
                    tableBody.appendChild(tr);
                });

                // Update charts
                updateCharts(data);
            }
        };
        xhr.send();
    }

    // Function to update charts with new data
    function updateCharts(data) {
        // Extract latest data for charts
        var latestData = data.slice(0, 10).reverse(); // Get latest 10 data points and reverse the order

        // Extract data for charts
        var timestamps = latestData.map(function(entry) { return entry.timestamp; });
        var temperatures = latestData.map(function(entry) { return entry.avg_temperature; });
        var humidities = latestData.map(function(entry) { return entry.avg_humidity; });
        var lights = latestData.map(function(entry) { return entry.avg_light; });

        // Update temperature chart
        if (temperatureChart) {
            temperatureChart.data.labels = timestamps;
            temperatureChart.data.datasets[0].data = temperatures;
            temperatureChart.update();
        } else {
            var tempCtx = document.getElementById('temperatureChart').getContext('2d');
            temperatureChart = new Chart(tempCtx, {
                type: 'line',
                data: {
                    labels: timestamps,
                    datasets: [{
                        label: 'Average Temperature',
                        data: temperatures,
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1
                    }]
                }
            });
        }

        // Update humidity chart
        if (humidityChart) {
            humidityChart.data.labels = timestamps;
            humidityChart.data.datasets[0].data = humidities;
            humidityChart.update();
        } else {
            var humidityCtx = document.getElementById('humidityChart').getContext('2d');
            humidityChart = new Chart(humidityCtx, {
                type: 'line',
                data: {
                    labels: timestamps,
                    datasets: [{
                        label: 'Average Humidity',
                        data: humidities,
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                }
            });
        }

        // Update light chart
        if (lightChart) {
            lightChart.data.labels = timestamps;
            lightChart.data.datasets[0].data = lights;
            lightChart.update();
        } else {
            var lightCtx = document.getElementById('lightChart').getContext('2d');
            lightChart = new Chart(lightCtx, {
                type: 'line',
                data: {
                    labels: timestamps,
                    datasets: [{
                        label: 'Average Light Intensity',
                        data: lights,
                        backgroundColor: 'rgba(255, 206, 86, 0.2)',
                        borderColor: 'rgba(255, 206, 86, 1)',
                        borderWidth: 1
                    }]
                }
            });
        }
    }

    // Fetch and display data initially
    fetchAndDisplayData();

    // Set interval to fetch and update data every 30 seconds
    setInterval(fetchAndDisplayData, 30000);
</script>

</body>
</html>
