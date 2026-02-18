<?php
/**
 * Logout Handler
 * Destroys session and redirects to login page
 */

require_once 'config/auth.php';

logoutUser();

header('Location: login.php?logged_out=1');
exit;
?>
