<?php
// Start or resume the current session so it can be destroyed
session_start();
// Destroy all data associated with the current session and log the user out
session_destroy();
// Redirect the user to the homepage after logging out
header('Location: index.php');
// Ensure no further code is executed after the redirect
exit();
?>
