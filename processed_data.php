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

// Fetch processed data from processeddata table
function fetchProcessedData($conn) {
    $sql = "SELECT * FROM processeddata ORDER BY timestamp DESC";
    $result = $conn->query($sql);

    $data = array();
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }

    return $data;
}

// Check if action is fetch_data and fetch processed data
if (isset($_GET['action']) && $_GET['action'] === 'fetch_data') {
    $processedData = fetchProcessedData($conn);
    echo json_encode($processedData);
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
            max-width: 1200px; /* Adjust width as needed */
            margin: auto;
            background-color: #fff;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .scrollable-table {
            overflow-y: auto; /* Only vertical scroll */
            max-height: 400px; /* Adjust height as needed */
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
        var latestData = data.slice(0, 10); // Get latest 10 data points

        // Extract data for charts
        var timestamps = latestData.map(function(entry) { return entry.timestamp; });

        // Update temperature chart
        var tempCtx = document.getElementById('temperatureChart').getContext('2d');
        var temperatureChart = new Chart(tempCtx, {
            type: 'line',
            data: {
                labels: timestamps,
                datasets: [{
                    label: 'Average Temperature',
                    data: latestData.map(function(entry) { return entry.avg_temperature; }),
                    borderColor: 'rgb(255, 99, 132)',
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    tension: 0.1,
                    fill: false
                }]
            },
            options: {
                scales: {
                    x: {
                        reverse: true, // Display latest timestamps on the right
                        display: true,
                        title: {
                            display: true,
                            text: 'Timestamp'
                        }
                    },
                    y: {
                        display: true,
                        title: {
                            display: true,
                            text: 'Average Temperature'
                        }
                    }
                }
            }
        });

        // Update humidity chart
        var humCtx = document.getElementById('humidityChart').getContext('2d');
        var humidityChart = new Chart(humCtx, {
            type: 'line',
            data: {
                labels: timestamps,
                datasets: [{
                    label: 'Average Humidity',
                    data: latestData.map(function(entry) { return entry.avg_humidity; }),
                    borderColor: 'rgb(54, 162, 235)',
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    tension: 0.1,
                    fill: false
                }]
            },
            options: {
                scales: {
                    x: {
                        reverse: true, // Display latest timestamps on the right
                        display: true,
                        title: {
                            display: true,
                            text: 'Timestamp'
                        }
                    },
                    y: {
                        display: true,
                        title: {
                            display: true,
                            text: 'Average Humidity'
                        }
                    }
                }
            }
        });

        // Update light chart
        var lightCtx = document.getElementById('lightChart').getContext('2d');
        var lightChart = new Chart(lightCtx, {
            type: 'line',
            data: {
                labels: timestamps,
                datasets: [{
                    label: 'Average Light',
                    data: latestData.map(function(entry) { return entry.avg_light; }),
                    borderColor: 'rgb(255, 205, 86)',
                    backgroundColor: 'rgba(255, 205, 86, 0.2)',
                    tension: 0.1,
                    fill: false
                }]
            },
            options: {
                scales: {
                    x: {
                        reverse: true, // Display latest timestamps on the right
                        display: true,
                        title: {
                            display: true,
                            text: 'Timestamp'
                        }
                    },
                    y: {
                        display: true,
                        title: {
                            display: true,
                            text: 'Average Light'
                        }
                    }
                }
            }
        });
    }

    // Fetch processed data initially and then every 30 seconds
    fetchAndDisplayData(); // Initial fetch
    setInterval(fetchAndDisplayData, 30000); // Refresh every 30 seconds
</script>

</body>
</html>
