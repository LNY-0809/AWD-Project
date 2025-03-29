<?php
// Database connection settings
$servername = "localhost";
$username = "root"; // default phpMyAdmin username, change if different
$password = "";     // default phpMyAdmin password, change if different
$dbname = "bank";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS bank_branches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bank_name VARCHAR(255),
    branch_name VARCHAR(255),
    address TEXT,
    district VARCHAR(100),
    telephone VARCHAR(50),
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === FALSE) {
    echo "Error creating table: " . $conn->error;
}

// URL of the API
$url = "https://api.hkma.gov.hk/public/bank-svf-info/banks-branch-locator?lang=en&pagesize=1080";

// Fetch JSON data
$json_data = file_get_contents($url);
$data = json_decode($json_data, true);

// Check if data was retrieved successfully
if ($data === null) {
    die("Error decoding JSON data");
}

// Process and insert data
if (isset($data['result']['records']) && is_array($data['result']['records'])) {
    $stmt = $conn->prepare("INSERT INTO bank_branches (bank_name, branch_name, address, district, telephone, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    foreach ($data['result']['records'] as $record) {
        // Prepare data with null checks
        $bank_name = $record['bank_name_en'] ?? '';
        $branch_name = $record['branch_name_en'] ?? '';
        $address = $record['branch_address_en'] ?? '';
        $district = $record['district_en'] ?? '';
        $telephone = $record['telephone'] ?? '';
        $latitude = $record['latitude'] ?? null;
        $longitude = $record['longitude'] ?? null;

        // Bind parameters and execute
        $stmt->bind_param("sssssdd", 
            $bank_name, 
            $branch_name, 
            $address, 
            $district, 
            $telephone, 
            $latitude, 
            $longitude
        );
        
        if ($stmt->execute() === FALSE) {
            echo "Error inserting record: " . $conn->error . "<br>";
        }
    }
    
    $stmt->close();
    echo "Data successfully imported into database!";
} else {
    echo "No records found in the API response";
}

// Close connection
$conn->close();
?>