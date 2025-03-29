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

// Set timezone
date_default_timezone_set('Asia/Hong_Kong');
$current_time = date('H:i');
$current_day = strtolower(date('l'));
$date = date('d/m H:i');
$weekday = date('l');

// Get unique districts and bank names for dropdowns
$districts_sql = "SELECT DISTINCT district FROM bank_branches ORDER BY district";
$districts_result = $conn->query($districts_sql);

$banks_sql = "SELECT DISTINCT bank_name FROM bank_branches ORDER BY bank_name";
$banks_result = $conn->query($banks_sql);

// Handle search parameters
$where_clauses = [];
$params = [];
$types = "";

// Search by district
if (isset($_GET['district']) && !empty($_GET['district'])) {
    $district = $conn->real_escape_string($_GET['district']);
    $where_clauses[] = "district = ?";
    $params[] = $district;
    $types .= "s";
}

// Search by bank name
if (isset($_GET['bank_name']) && !empty($_GET['bank_name'])) {
    $bank_name = $conn->real_escape_string($_GET['bank_name']);
    $where_clauses[] = "bank_name = ?";
    $params[] = $bank_name;
    $types .= "s";
}

// Search by service hours (still active)
if (isset($_GET['in_service']) && $_GET['in_service'] == '1') {
    // Function to check if branch is currently in service
    function isBranchInService($service_hours, $current_day, $current_time) {
        $lines = explode("\n", str_replace("\r", "", $service_hours));
        $day_pattern = "/\b" . ucfirst($current_day) . "\b.*?(?:\d{2}:\d{2}.*?\d{2}:\d{2}|Closed)/i";
        
        foreach ($lines as $line) {
            if (preg_match($day_pattern, $line, $matches)) {
                $day_info = $matches[0];
                if (stripos($day_info, 'Closed') !== false) {
                    return false;
                }
                
                preg_match_all('/(\d{2}:\d{2})\s*(?:-|to|\s)\s*(\d{2}:\d{2})/i', $day_info, $time_matches);
                for ($i = 0; $i < count($time_matches[0]); $i++) {
                    $start_time = $time_matches[1][$i];
                    $end_time = $time_matches[2][$i];
                    
                    if ($current_time >= $start_time && $current_time <= $end_time) {
                        return true;
                    }
                }
                return false;
            }
        }
        return false;
    }
}

// Search by barrier free access
if (isset($_GET['barrier_free']) && !empty($_GET['barrier_free'])) {
    $barrier_free = $conn->real_escape_string($_GET['barrier_free']);
    $where_clauses[] = "barrier_free_access LIKE ?";
    $params[] = "%$barrier_free%";
    $types .= "s";
}

// Build and execute the initial query
$where_clause = empty($where_clauses) ? "" : "WHERE " . implode(" AND ", $where_clauses);
$sql = "SELECT * FROM bank_branches $where_clause ORDER BY id";
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

// Filter results for in-service branches if requested
$filtered_result = [];
while ($row = $result->fetch_assoc()) {
    if (!isset($_GET['in_service']) || $_GET['in_service'] != '1' || 
        isBranchInService($row['service_hours'], $current_day, $current_time)) {
        $filtered_result[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bank Branches Search</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 14px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .search-container {
            margin: 20px 0;
        }
        select, input[type="text"], input[type="checkbox"] {
            padding: 5px;
            font-size: 14px;
            margin-right: 10px;
        }
        label {
            margin-right: 10px;
        }
        button {
            padding: 5px 10px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <h1>Hong Kong Bank Branches Information</h1>
    <a href="new.php">Manage Bank Branches</a>
    
    <!-- Search form -->
    <div class="search-container">
        <form method="GET">
            <label for="district">District:</label>
            <select name="district" id="district">
                <option value="">All Districts</option>
                <?php
                while ($district_row = $districts_result->fetch_assoc()) {
                    $district = $district_row['district'];
                    $selected = (isset($_GET['district']) && $_GET['district'] === $district) ? 'selected' : '';
                    echo "<option value='$district' $selected>$district</option>";
                }
                ?>
            </select>

            <label for="bank_name">Bank Name:</label>
            <select name="bank_name" id="bank_name">
                <option value="">All Banks</option>
                <?php
                while ($bank_row = $banks_result->fetch_assoc()) {
                    $bank = $bank_row['bank_name'];
                    $selected = (isset($_GET['bank_name']) && $_GET['bank_name'] === $bank) ? 'selected' : '';
                    echo "<option value='$bank' $selected>$bank</option>";
                }
                ?>
            </select>

            <label for="in_service">In Service Now:</label>
            <input type="checkbox" name="in_service" id="in_service" value="1" <?php echo (isset($_GET['in_service']) && $_GET['in_service'] == '1') ? 'checked' : ''; ?>>

            <label for="barrier_free">Barrier Free Access:</label>
            <input type="text" name="barrier_free" id="barrier_free" value="<?php echo isset($_GET['barrier_free']) ? htmlspecialchars($_GET['barrier_free']) : ''; ?>" placeholder="e.g., Wheelchair">

            <button type="submit">Search</button>
            <button type="reset">Clear</button>
        </form>
    </div>

    <!-- Data table -->
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Bank Name</th>
                <th>Branch Name</th>
                <th>Address</th>
                <th>District</th>
                <th>Service Hours</th>
                <th>Barrier Free Access</th>
				<th>Location</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if (count($filtered_result) > 0) {
                foreach ($filtered_result as $row) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['bank_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['branch_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['address']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['district']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['service_hours']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['barrier_free_access']) . "</td>";
					echo "<td><a href='https://www.google.com/maps?q=" . htmlspecialchars($row['bank_name']) . "," . htmlspecialchars($row['branch_name']) . "' target='_blank'>View on Map</a></td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='8'>No records found</td></tr>";
            }
            ?>
        </tbody>
    </table>

    <?php
    echo "<p>Total Records: " . count($filtered_result) . "</p>";
    echo "<p>Current Time: " . $date . " (" . $weekday . ") (Device Time)</p>";

    // Clean up
    $stmt->close();
    $conn->close();
    ?>
</body>
</html>