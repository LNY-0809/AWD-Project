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
    // Get the ID of the record to delete
    $id = $conn->real_escape_string($_POST['id']);

    // Delete the record from the database
    $sql = "DELETE FROM bank_branches WHERE id = '$id'";

    if ($conn->query($sql) === TRUE) {
        echo "Record deleted successfully";
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
    <title>Delete Bank Branch</title>
</head>
<body>
    <h1>Delete Bank Branch</h1>
    <form method="POST" action="">
        <label for="id">Branch ID:</label>
        <input type="text" id="id" name="id" required><br><br>
        <button type="submit">Delete</button>
    </form>
</body>
</html>