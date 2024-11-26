
<?php
// logout.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
// Enable error reporting for debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'connect.php'; // Includes session_start()

session_unset(); // Unset all session variables
session_destroy(); // Destroy the session
header("Location: ../login/index.php"); // Update this line to use the correct relative path
exit();
?>