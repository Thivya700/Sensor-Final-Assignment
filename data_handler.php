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

// Handle data insertion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $temperature = $_POST['temperature'];
    $humidity = $_POST['humidity'];
    $light = $_POST['light'];

    $sql = "INSERT INTO sensordata (temperature, humidity, light) VALUES ('$temperature', '$humidity', '$light')";

    if ($conn->query($sql) === TRUE) {
        echo "Data inserted successfully";
        
        // Calculate averages, min, max for the latest 10 data entries
        $sql_process = "SELECT 
                            AVG(temperature) AS avg_temperature, 
                            MIN(temperature) AS min_temperature, 
                            MAX(temperature) AS max_temperature, 
                            AVG(humidity) AS avg_humidity, 
                            MIN(humidity) AS min_humidity, 
                            MAX(humidity) AS max_humidity, 
                            AVG(light) AS avg_light, 
                            MIN(light) AS min_light, 
                            MAX(light) AS max_light 
                        FROM 
                            (SELECT temperature, humidity, light FROM sensordata ORDER BY timestamp DESC LIMIT 10) AS subquery";
        
        $result_process = $conn->query($sql_process);
        
        if ($result_process->num_rows > 0) {
            $row_process = $result_process->fetch_assoc();
            
            $avg_temperature = $row_process['avg_temperature'];
            $min_temperature = $row_process['min_temperature'];
            $max_temperature = $row_process['max_temperature'];
            $avg_humidity = $row_process['avg_humidity'];
            $min_humidity = $row_process['min_humidity'];
            $max_humidity = $row_process['max_humidity'];
            $avg_light = $row_process['avg_light'];
            $min_light = $row_process['min_light'];
            $max_light = $row_process['max_light'];
            
            // Insert processed data into processeddata table
            $sql_insert_processed = "INSERT INTO processeddata 
                                        (avg_temperature, min_temperature, max_temperature, 
                                        avg_humidity, min_humidity, max_humidity, 
                                        avg_light, min_light, max_light) 
                                    VALUES 
                                        ('$avg_temperature', '$min_temperature', '$max_temperature', 
                                        '$avg_humidity', '$min_humidity', '$max_humidity', 
                                        '$avg_light', '$min_light', '$max_light')";
            
            if ($conn->query($sql_insert_processed) === TRUE) {
                echo "Processed data inserted successfully";
            } else {
                echo "Error: " . $sql_insert_processed . "<br>" . $conn->error;
            }
        }
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
    exit;
}

// Fetch and return data as JSON for AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['fetchData'])) {
    $sql = "SELECT id, temperature, humidity, light, timestamp FROM sensordata ORDER BY timestamp DESC";
    $result = $conn->query($sql);

    $data = array();
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }

    echo json_encode($data);
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Environmental Data</title>
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

        .container {
            max-width: 800px;
            margin: auto;
            background-color: #fff;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Environmental Data</h2>

    <table id="data-table">
        <tr>
            <th>ID</th>
            <th>Temperature</th>
            <th>Humidity</th>
            <th>Light</th>
            <th>Timestamp</th>
        </tr>
    </table>
</div>

<script>
    function fetchData() {
        var xhr = new XMLHttpRequest();
        xhr.open("GET", "data_handler.php?fetchData=true", true);
        xhr.onload = function() {
            if (xhr.status == 200) {
                var data = JSON.parse(xhr.responseText);
                var table = document.getElementById("data-table");

                // Clear the existing rows except the header
                table.innerHTML = `
                    <tr>
                        <th>ID</th>
                        <th>Temperature</th>
                        <th>Humidity</th>
                        <th>Light</th>
                        <th>Timestamp</th>
                    </tr>
                `;

                // Populate the table with new data
                data.forEach(function(row) {
                    var tr = document.createElement("tr");
                    tr.innerHTML = `
                        <td>${row.id}</td>
                        <td>${row.temperature}</td>
                        <td>${row.humidity}</td>
                        <td>${row.light}</td>
                        <td>${row.timestamp}</td>
                    `;
                    table.appendChild(tr);
                });
            }
        };
        xhr.send();
    }

    // Fetch data every 30 seconds
    setInterval(fetchData, 30000);

    // Initial data fetch
    fetchData();
</script>

</body>
</html>
