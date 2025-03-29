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
    $current_time = date('H:i'); // Current time in 24-hour format
    $current_day = strtolower(date('l')); // Current day (e.g., "monday")
    
    // Complex condition to check if current time/day falls within service hours
	/*
    $where_clauses[] = "(
        service_hours REGEXP '$current_day[^:]*:.*$current_time' 
        OR service_hours NOT LIKE '%Closed on $current_day%'
        AND service_hours NOT REGEXP '$current_day.*Closed'
    )";
	*/
}

// Search by barrier free access (partial match)
if (isset($_GET['barrier_free']) && !empty($_GET['barrier_free'])) {
    $barrier_free = $conn->real_escape_string($_GET['barrier_free']);
    $where_clauses[] = "barrier_free_access LIKE ?";
    $params[] = "%$barrier_free%";
    $types .= "s";
}

// Build the query
$where_clause = "";
if (!empty($where_clauses)) {
    $where_clause = "WHERE " . implode(" AND ", $where_clauses);
}

$sql = "SELECT * FROM bank_branches $where_clause ORDER BY id";
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
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
	<a href="create.php">Create</a>
	<a href="update.php">Update</a>
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
                <th>Latitude</th>
                <th>Longitude</th>
                <th>Service Hours</th>
                <th>Barrier Free Access</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['bank_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['branch_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['address']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['district']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['latitude']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['longitude']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['service_hours']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['barrier_free_access']) . "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='9'>No records found</td></tr>";
            }
            ?>
        </tbody>
    </table>

    <?php
	
	date_default_timezone_set('Asia/Hong_Kong');
	$date = date('d/m h:i');
	
	switch (date('N')) {
		case '1':
		$weekday = 'Monday';
		break;
		case '2':
		$weekday = 'Tuesday';
		break;
		case '3':
		$weekday = 'Wednesday';
		break;
		case '4':
		$weekday = 'Thursday';
		break;
		case '5':
		$weekday = 'Friday';
		break;
		case '6':
		$weekday = 'Saturday';
		break;
		case '7':
		$weekday = 'Sunday';
		break;
}
    echo "<p>Total Records: " . $result->num_rows . "</p>";
    echo "<p>Current Time: " . $date ." (". $weekday .") ". " (Server Time)</p>";

    // Clean up
    $stmt->close();
    $conn->close();
    ?>
</body>
</html>