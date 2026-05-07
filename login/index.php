<?php

require_once('../db/conexion.php');
session_start();


$_SESSION = [];
session_unset();
session_destroy();


session_start();
session_regenerate_id(true);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $usuario = trim($_POST['usuario'] ?? '');
    $clave   = $_POST['clave'] ?? '';

    if ($usuario === '' || $clave === '') {
        $error = "Usuario o clave vacíos";
    } else {

        
        $clave_hash = hash('sha256', $clave);

        $stmt = $conexion->prepare("
            SELECT id, usuario, tipo
            FROM usuarios
            WHERE usuario = ?
              AND password = ?
            LIMIT 1
        ");

        if (!$stmt) {
            $error = "Error en la consulta.";
        } else {

            $stmt->bind_param("ss", $usuario, $clave_hash);
            $stmt->execute();
            $res = $stmt->get_result();

            if ($res && $res->num_rows === 1) {

                $row = $res->fetch_assoc();

                
                
                
                $_SESSION['usuario_id']     = (int)$row['id'];
                $_SESSION['usuario']        = $row['usuario'];
                $_SESSION['tipo']           = strtolower(trim($row['tipo']));
                $_SESSION['usuario_nombre'] = ucfirst($row['usuario']); 

                
                switch ($_SESSION['tipo']) {
                    case 'admin':
                        header("Location: ../admin/index.php");
                        exit;

                    case 'inventario':
                        header("Location: ../inventario/index.php");
                        exit;

                    case 'proceso':
                        header("Location: ../admin/buscar_codigo.php");
                        exit;

                    case 'empleado':
                        header("Location: ../admin/registrar_produccion.php");
                        exit;

                    default:
                        $error = "Rol no autorizado";
                        session_destroy();
                }

            } else {
                $error = "Usuario o clave incorrectos";
            }

            $stmt->close();
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Login PlastyPetco</title>
<link rel="icon" href="/beta/admin/plas.jpg" type="image/jpeg">
<style>
body {
    margin: 0;
    padding: 0;
    background: url("../admin/fondo.jpg") no-repeat center center fixed;
    background-size: cover;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    font-family: Arial;
}
.login-box {
    background: rgba(255,255,255,0.9);
    padding: 40px;
    width: 350px;
    border-radius: 15px;
    box-shadow: 0 0 15px rgba(0,0,0,0.25);
    text-align: center;
}
.logo-box {
    margin-bottom: 20px;
}
.logo-emoji {
    font-size: 70px;
}
.plastypet-title {
    margin: 10px 0 0 0;
    font-size: 28px;
    font-weight: bold;
}
.plastypet-title span.green { color: #0db10d; }
.plastypet-title span.blue { color: #007bff; }
.login-box input {
    width: 93%;
    padding: 12px;
    margin: 10px 0;
    border-radius: 8px;
    border: 1px solid #ccc;
    font-size: 15px;
}
.login-box button {
    width: 100%;
    padding: 12px;
    border: none;
    background: #007bff;
    color: white;
    font-size: 16px;
    border-radius: 8px;
    cursor: pointer;
}
.login-box button:hover {
    background: #0056b3;
}
.error {
    color: red;
    margin-bottom: 15px;
    font-weight: bold;
}
</style>
</head>
<body>

<div class="login-box">
    <div class="logo-box">
        <div class="logo-emoji">♻️</div>
        <h2 class="plastypet-title">
            <span class="green">Plasty</span><span class="blue">Petco</span>
        </h2>
    </div>

    <h1>Iniciar Sesión</h1>

    <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>

    <form method="POST">
        <input name="usuario" placeholder="Usuario" required>
        <input name="clave" type="password" placeholder="Clave" required>
        <button type="submit">Ingresar</button>
    </form>
</div>

</body>
</html>