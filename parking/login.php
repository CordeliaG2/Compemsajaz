<?php
session_start();
require 'db_config.php';

$debug     = isset($_GET['debug']) && $_GET['debug'] == '1';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo    = trim($_POST['correo'] ?? '');
    $passInput = trim($_POST['password'] ?? '');

    if ($correo === '' || $passInput === '') {
        $error_msg = 'Por favor ingresa correo y contraseña.';
    } else {
        $sql = "SELECT password FROM usuarios WHERE correo = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param('s', $correo);
            if ($stmt->execute()) {
                $stmt->store_result();
                if ($stmt->num_rows === 1) {
                    $stmt->bind_result($hash_db);
                    $stmt->fetch();

                    $hash_input = hash('sha256', $passInput);

                    if ($debug) {
                        echo "<pre>DEBUG:\nEntrada sin hash: [$passInput]\nHash(entrada)    : [$hash_input]\nDB               : [$hash_db]</pre>";
                    }

                    if ($hash_input === $hash_db) {
                        $_SESSION['usuario'] = $correo;
                        header('Location: mapa.html');
                        exit();
                    } else {
                        $error_msg = 'Correo o contraseña incorrectos.';
                    }
                } else {
                    $error_msg = 'Correo no encontrado.';
                }
            } else {
                $error_msg = 'Error al ejecutar consulta: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            $error_msg = 'Error en la preparación: ' . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Inicio de Sesión - Copemsa</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="d-flex justify-content-center align-items-center vh-100 bg-light">
  <div class="card p-4 shadow-lg" style="width: 400px;">
    <h3 class="text-center mb-4">Iniciar Sesión</h3>

    <form method="POST" action="login.php<?= $debug ? '?debug=1' : '' ?>">
      <div class="mb-3">
        <label for="correo" class="form-label">Correo</label>
        <input type="email" name="correo" id="correo" class="form-control"
               value="<?= htmlspecialchars($_POST['correo'] ?? '') ?>" required />
      </div>
      <div class="mb-3">
        <label for="contrasena" class="form-label">Contraseña</label>
        <input type="password" name="password" id="contrasena" class="form-control" required />
      </div>
      <button type="submit" class="btn btn-primary w-100">Iniciar Sesión</button>
    </form>

    <div class="mt-3 text-center">
        <p>¿No tienes cuenta?</p>
        <a href="registro.php" class="btn btn-success">Registrarse</a>
    </div>

    <?php if ($error_msg): ?>
      <div class="text-center mt-3 text-danger">
        <?= htmlspecialchars($error_msg) ?>
      </div>
    <?php endif; ?>

    <div class="mt-3 text-center">
      <a href="index.html" class="text-decoration-none">Regresar al inicio</a>
    </div>
  </div>
</body>
</html>
