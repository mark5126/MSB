<?php
// dashboard.php - Redirects users to the admin dashboard.

// The existing admin dashboard is located in index.php.
// This redirects users who access dashboard.php to index.php.
header('Location: index.php');

// Stops the execution of the current PHP script
// to prevent any additional code from running after the redirect.
exit();
?>
