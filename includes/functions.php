<?php
// Basic functions needed by the application

function connect_db() {
    // Database connection function
    $host = 'localhost';
    $db = 'bakersfieldesports';
    $user = 'root';
    $pass = '';
    
    try {
        $conn = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch(PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}

function sanitize_input($data) {
    // Basic input sanitization
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function redirect($url) {
    // Simple redirect function
    header("Location: $url");
    exit();
}
