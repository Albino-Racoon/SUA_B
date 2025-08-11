<?php
session_start();
require_once 'models/Profesor.php';

$profesor = new Profesor();

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Preveri, ali profesor obstaja
    if ($profesor->exists($id)) {
        if ($profesor->delete($id)) {
            header('Location: index.php?message=deleted');
        } else {
            header('Location: index.php?error=delete_failed');
        }
    } else {
        header('Location: index.php?error=not_found');
    }
} else {
    header('Location: index.php');
}

exit;
?>
