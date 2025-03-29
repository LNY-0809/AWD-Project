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

// Initialize variables
$id = $bank_name = $branch_name = $address = $district = $latitude = $longitude = $service_hours = $barrier_free_access = "";

// Check if form is submitted to fetch data
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['fetch'])) {
    $id = $conn->real_escape_string($_POST['id']);
    $sql = "SELECT * FROM bank_branches WHERE id = '$id'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $bank_name = $row['bank_name'];
        $branch_name = $row['branch_name'];
        $address = $row['address'];
        $district = $row['district'];
        $latitude = $row['latitude'];
        $longitude = $row['longitude'];
        $service_hours = $row['service_hours'];
        $barrier_free_access = $row['barrier_free_access'];
    } else {
        echo "No record found with ID: $id";
    }
}

// Check if form is submitted to update data
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update'])) {
    $id = $conn->real_escape_string($_POST['id']);
    $bank_name = $conn->real_escape_string($_POST['bank_name']);
    $branch_name = $conn->real_escape_string($_POST['branch_name']);
    $address = $conn->real_escape_string($_POST['address']);
    $district = $conn->real_escape_string($_POST['district']);
    $latitude = $conn->real_escape_string($_POST['latitude']);
    $longitude = $conn->real_escape_string($_POST['longitude']);
    $service_hours = $conn->real_escape_string($_POST['service_hours']);
    $barrier_free_access = $conn->real_escape_string($_POST['barrier_free_access']);

    $sql = "UPDATE bank_branches SET 
            bank_name='$bank_name', 
            branch_name='$branch_name', 
            address='$address', 
            district='$district', 
            latitude='$latitude', 
            longitude='$longitude', 
            service_hours='$service_hours', 
            barrier_free_access='$barrier_free_access' 
            WHERE id='$id'";

    if ($conn->query($sql) === TRUE) {
        echo "Record updated successfully";
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
    <title>Modify Bank Branch</title>
</head>
<body>
    <h1>Modify Bank Branch</h1>
    <form method="POST" action="">
        <label for="id">Branch ID:</label>
        <input type="text" id="id" name="id" required><br><br>
        <button type="submit" name="fetch">Fetch Data</button>
    </form>

    <?php if (!empty($bank_name)) : ?>
    <form method="POST" action="">
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($id); ?>">
        <label for="bank_name">Bank Name:</label>
        <input type="text" id="bank_name" name="bank_name" value="<?php echo htmlspecialchars($bank_name); ?>" required><br><br>

        <label for="branch_name">Branch Name:</label>
        <input type="text" id="branch_name" name="branch_name" value="<?php echo htmlspecialchars($branch_name);?>" required><br><br>
		
        <label for="address">Address:</label>
        <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($address); ?>" required><br><br>

        <label for="district">District:</label>
        <input type="text" id="district" name="district" value="<?php echo htmlspecialchars($district); ?>" required><br><br>

        <label for="latitude">Latitude:</label>
        <input type="text" id="latitude" name="latitude" value="<?php echo htmlspecialchars($latitude); ?>" required><br><br>

        <label for="longitude">Longitude:</label>
        <input type="text" id="longitude" name="longitude" value="<?php echo htmlspecialchars($longitude); ?>" required><br><br>

        <label for="service_hours">Service Hours:</label>
        <input type="text" id="service_hours" name="service_hours" value="<?php echo htmlspecialchars($service_hours); ?>" required><br><br>

        <label for="barrier_free_access">Barrier Free Access:</label>
        <input type="text" id="barrier_free_access" name="barrier_free_access" value="<?php echo htmlspecialchars($barrier_free_access); ?>" required><br><br>

        <button type="submit" name="update">Update</button>
    </form>
    <?php endif; ?>
</body>
</html>