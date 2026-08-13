<?php
require_once __DIR__ . '/../config.php';
session_start();

function require_admin_login(): void {
    if (empty($_SESSION['admin_logged_in'])) {
        header('Location: login.php');
        exit;
    }
}
