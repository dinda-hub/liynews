<?php
session_start();
session_unset();
session_destroy();

// Hapus cookie session jika ada
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

header("Location: ../public/index.php");
exit;
?>