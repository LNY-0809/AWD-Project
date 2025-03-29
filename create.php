<?php
// Database connection settings
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "bank";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $bank_name = $conn->real_escape_string($_POST['bank_name']);
    $branch_name = $conn->real_escape_string($_POST['branch_name']);
    $address = $conn->real_escape_string($_POST['address']);
    $district = $conn->real_escape_string($_POST['district']);
    $latitude = $conn->real_escape_string($_POST['latitude']);
    $longitude = $conn->real_escape_string($_POST['longitude']);
    $service_hours = $conn->real_escape_string($_POST['service_hours']);
    $barrier_free_access = $conn->real_escape_string($_POST['barrier_free_access']);

    // Insert data into database
    $sql = "INSERT INTO bank_branches (bank_name, branch_name, address, district, latitude, longitude, service_hours, barrier_free_access)
            VALUES ('$bank_name', '$branch_name', '$address', '$district', '$latitude', '$longitude', '$service_hours', '$barrier_free_access')";

    if ($conn->query($sql) === TRUE) {
        echo "New record created successfully";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Bank Branch</title>
</head>
<body>
    <h1>Create New Bank Branch</h1>
    <form method="POST" action="">
        <label for="bank_name">Bank Name:</label>
        <input type="text" id="bank_name" name="bank_name" required><br><br>

        <label for="branch_name">Branch Name:</label>
        <input type="text" id="branch_name" name="branch_name" required><br><br>

        <label for="address">Address:</label>
        <input type="text" id="address" name="address" required><br><br>

        <label for="district">District:</label>
        <input type="text" id="district" name="district" required><br><br>

        <label for="latitude">Latitude:</label>
        <input type="text" id="latitude" name="latitude" required><br><br>

        <label for="longitude">Longitude:</label>
        <input type="text" id="longitude" name="longitude" required><br><br>

        <label for="service_hours">Service Hours:</label>
        <input type="text" id="service_hours" name="service_hours" required><br><br>

        <label for="barrier_free_access">Barrier Free Access:</label>
        <input type="text" id="barrier_free_access" name="barrier_free_access" required><br><br>

        <button type="submit">Create</button>
    </form>
</body>
</html>