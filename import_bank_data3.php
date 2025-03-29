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

// Create table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS bank_branches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bank_name VARCHAR(255),
    branch_name VARCHAR(255),
    address TEXT,
    district VARCHAR(100),
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    service_hours TEXT,
    barrier_free_access TEXT
)";

if ($conn->query($sql) === FALSE) {
    echo "Error creating table: " . $conn->error;
}

// URL of the API
$url = "https://api.hkma.gov.hk/public/bank-svf-info/banks-branch-locator?lang=en&pagesize=1080";

// Fetch JSON data with error checking
$json_data = @file_get_contents($url);
if ($json_data === FALSE) {
    die("Error fetching data from API");
}

$data = json_decode($json_data, true);
if ($data === null) {
    die("Error decoding JSON: " . json_last_error_msg());
}

// Process and insert data
if (isset($data['result']['records']) && is_array($data['result']['records'])) {
    $stmt = $conn->prepare("INSERT INTO bank_branches (bank_name, branch_name, address, district, latitude, longitude, service_hours, barrier_free_access) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    foreach ($data['result']['records'] as $record) {
        // Use exact field names from the API structure
        $bank_name = $record['bank_name'] ?? '';
        $branch_name = $record['branch_name'] ?? '';
        $address = $record['address'] ?? '';
        $district = $record['district'] ?? '';
        $latitude = $record['latitude'] ?? null;
        $longitude = $record['longitude'] ?? null;
        $service_hours = $record['service_hours'] ?? '';
        $barrier_free_access = $record['barrier-free_access'] ?? '';

        // Bind parameters and execute
        // "sssssddss" corresponds to string, string, string, string, double, double, string, string
        $stmt->bind_param("ssssddss", 
            $bank_name, 
            $branch_name, 
            $address, 
            $district,  
            $latitude, 
            $longitude,
            $service_hours,
            $barrier_free_access
        );
        
        if ($stmt->execute() === FALSE) {
            echo "Error inserting record: " . $conn->error . "<br>";
        }
    }
    
    $stmt->close();
    echo "Data import completed!";
} else {
    echo "No records found in the API response";
}

// Close connection
$conn->close();
?>