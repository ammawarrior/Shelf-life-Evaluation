<?php
// Enable error reporting for MySQLi (useful for debugging)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Prevent constant redefinition
if (!defined('DB_SERVER')) {
    define('DB_SERVER', 'localhost');
}
if (!defined('DB_USERNAME')) {
    define('DB_USERNAME', 'root');
}
if (!defined('DB_PASSWORD')) {
    define('DB_PASSWORD', '');
}
if (!defined('DB_NAME')) {
    define('DB_NAME', 'sensory');
}

try {
    // Create connection
    $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

    // Set character set to UTF-8 (for better compatibility)
    $conn->set_charset("utf8mb4");

} catch (Exception $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
