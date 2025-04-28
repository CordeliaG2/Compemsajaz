<?php
$host = "localhost";
$user = "root";
$pass = "";        // contraseña vacía en XAMPP
$db   = "copemsa";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
  die("Conexión fallida: " . $conn->connect_error);
}
?>
