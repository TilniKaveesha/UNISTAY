<?php
// Authentication functions for the UniStay application
declare(strict_types=1);

if (session_status()!== PHP_SESSION_ACTIVE){
    session_start();

}
// Check if the user is logged in
function requireRole(string $requiredRole): void {
    
    if (!isset($_SESSION['user_id'])){// User is not logged in
        header('Location: /login.php');
        exit;// Redirect to login page
    }
// Check if the user has the required role
    if (($_SESSION['role']?? '')!==$requiredRole){// User does not have the required role
        http_Response_code(403);
        exit('Access Denied: You do not have permission to access this page.');
    }
}
?>