<?php
session_start();

function require_admin() {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
<<<<<<< HEAD
        header("Location: ../frontend/src/pages/login.php"); 
        exit();
    }
}
?>
=======
        header("Location: /UNISTAY_UIUX/frontend/public/login.php"); 
        exit();
    }
}
?>
>>>>>>> UI/UX
