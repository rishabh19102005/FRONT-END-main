<?php
// Include auth functions
require_once 'auth.php';

// Logout the user
logoutUser();

// Redirect to login page
header("Location: login.php");
exit();
?> 