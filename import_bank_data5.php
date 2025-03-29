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

	$sql2 = "DROP TABLE bank_branches";

	if ($conn->query($sql2) !== TRUE) {
		echo "Error deleting record: " . $conn->error;
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

// Function to standardize district names
function standardizeDistrict($district) {
    // Remove spaces and 'District' first as per original code
    $district = str_replace([' ', 'District'], '', $district);

    // Define specific replacements
    $replacements = [
        'Central&Western' => 'CentralAndWestern',
        'CentralandWestern' => 'CentralAndWestern',
        'CentralNWestern' => 'CentralAndWestern',
        'Northern' => 'North',
        'OutlyingIslands' => 'Islands',
        'OutlyingIsland' => 'Islands',
        'ShumShuiPo' => 'ShamShuiPo',
		'Shatin' => 'ShaTin',
		'Wanchai' => 'WanChai',
        'YauTsuiMong' => 'YauTsimMong',
		'CheungChau' => 'Islands',
		'LammaIsland' => 'Islands',	
		'LantauIsland' => 'Islands',
		'PengChau' => 'Islands'
    ];
    
    // Apply specific replacements
    $district = str_replace(array_keys($replacements), array_values($replacements), $district);
    
    // Add space before capital letters (except first)
    $result = $district[0];
    for ($i = 1; $i < strlen($district); $i++) {
        if (ctype_upper($district[$i])) {
            $result .= ' ' . $district[$i];
        } else {
            $result .= $district[$i];
        }
    }
    return $result;
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
        
        // Standardize district before insertion
        $district_raw = $record['district'] ?? '';
        $district = standardizeDistrict($district_raw);
        
        $latitude = $record['latitude'] ?? null;
        $longitude = $record['longitude'] ?? null;
        
        // Remove <br> from service_hours
        $service_hours_raw = $record['service_hours'] ?? '';
        $service_hours = str_replace('<br>', ' ', $service_hours_raw);
        $service_hours = str_replace(';', ' ', $service_hours);
		
        $barrier_free_access = $record['barrier-free_access'] ?? '';

        // Bind parameters and execute
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