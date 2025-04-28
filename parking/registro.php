<?php
// Iniciar sesión
session_start();

// Si se envió el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Conexión a la base de datos
    $conexion = new mysqli("localhost", "root", "", "copemsa");

    if ($conexion->connect_error) {
        die("Error de conexión: " . $conexion->connect_error);
    }

    // Recibir datos del formulario
    $nombre = $conexion->real_escape_string($_POST["nombre"]);
    $correo = $conexion->real_escape_string($_POST["correo"]);
    $contrasena = $conexion->real_escape_string($_POST["contrasena"]);

    // Hashear la contraseña con SHA-256 para ser compatible con tu login.php
    $contrasena_hash = hash('sha256', $contrasena);

    // Verificar si el correo ya está registrado
    $verificar = $conexion->query("SELECT * FROM usuarios WHERE correo='$correo'");

    if ($verificar->num_rows > 0) {
        $_SESSION["error"] = "El correo ya está registrado.";
    } else {
        // Insertar nuevo usuario
        $sql = "INSERT INTO usuarios (nombre, correo, password) VALUES ('$nombre', '$correo', '$contrasena_hash')";

        if ($conexion->query($sql) === TRUE) {
            $_SESSION["success"] = "Registro exitoso. Redirigiendo al login...";
            $_SESSION["redirect"] = true;
            header("Location: registro.php");
            exit();
        } else {
            $_SESSION["error"] = "Error al registrar: " . $conexion->error;
        }
    }

    $conexion->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Registro de Usuario - Copemsa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="d-flex justify-content-center align-items-center vh-100 bg-light">

    <div class="card p-4 shadow-lg" style="width: 400px;">
        <h3 class="text-center mb-4">Crear Cuenta</h3>

        <?php if (isset($_SESSION["error"])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION["error"]; unset($_SESSION["error"]); ?></div>
        <?php endif; ?>

        <?php if (isset($_SESSION["success"])): ?>
            <div class="alert alert-success"><?php echo $_SESSION["success"]; unset($_SESSION["success"]); ?></div>
            <?php if (isset($_SESSION["redirect"])): ?>
                <script>
                    setTimeout(function() {
                        window.location.href = "login.php";
                    }, 3000);
                </script>
                <?php unset($_SESSION["redirect"]); ?>
            <?php endif; ?>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label for="nombre" class="form-label">Nombre Completo</label>
                <input type="text" class="form-control" id="nombre" name="nombre" required />
            </div>

            <div class="mb-3">
                <label for="correo" class="form-label">Correo Electrónico</label>
                <input type="email" class="form-control" id="correo" name="correo" required />
            </div>

            <div class="mb-3">
                <label for="contrasena" class="form-label">Contraseña</label>
                <input type="password" class="form-control" id="contrasena" name="contrasena" required />
            </div>

            <button type="submit" class="btn btn-success w-100">Registrarse</button>
        </form>

        <div class="mt-3 text-center">
            <a href="login.php" class="text-decoration-none">¿Ya tienes cuenta? Inicia sesión</a>
        </div>
    </div>

</body>
</html>
