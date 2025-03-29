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

// Fetch JSON data with error checking
$json_data = @file_get_contents($url);
if ($json_data === FALSE) {
    die("Error fetching data from API");
}

$data = json_decode($json_data, true);
if ($data === null) {
    die("Error decoding JSON: " . json_last_error_msg());
}

// Print sample of the data structure for debugging
echo "<pre>First record structure:\n";
print_r($data['result']['records'][0]);
echo "</pre>";

// Process and insert data
if (isset($data['result']['records']) && is_array($data['result']['records'])) {
    $stmt = $conn->prepare("INSERT INTO bank_branches (bank_name, branch_name, address, district, telephone, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    foreach ($data['result']['records'] as $record) {
        // Adjusted field names based on likely API structure
        // These might need further adjustment based on the debug output
        $bank_name = $record['Bank_Name_EN'] ?? $record['bank_name'] ?? '';
        $branch_name = $record['Branch_Name_EN'] ?? $record['branch_name'] ?? '';
        $address = $record['Branch_Address_EN'] ?? $record['address'] ?? '';
        $district = $record['District_EN'] ?? $record['district'] ?? '';
        $telephone = $record['Telephone'] ?? $record['telephone_no'] ?? '';
        $latitude = $record['Latitude'] ?? $record['lat'] ?? null;
        $longitude = $record['Longitude'] ?? $record['lng'] ?? null;

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
    echo "Data import completed!";
} else {
    echo "No records found in the API response";
}

// Close connection
$conn->close();
?>