<?php
$conexion = new mysqli(
    "turntable.proxy.rlwy.net", 
    "root",
    "VXJyGiWHXHiZEYagQMHAOgNXZRTtSeSA",        
    "railway",
    59111                           
);

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$conexion->set_charset("utf8mb4");

// 1. Verificar qué bases de datos existen
echo "<h3>Bases de datos disponibles:</h3>";
$bases = $conexion->query("SHOW DATABASES");
while($row = $bases->fetch_array()) {
    echo "📁 " . $row[0] . "<br>";
}

// 2. Verificar tablas en 'railway'
echo "<h3>Tablas en la base 'railway':</h3>";
$tablas = $conexion->query("SHOW TABLES");
if ($tablas->num_rows > 0) {
    while($row = $tablas->fetch_array()) {
        echo "📋 " . $row[0] . "<br>";
    }
} else {
    echo "⚠️ No hay tablas en 'railway'<br>";
}

// 3. Buscar tabla 'usuarios' en todas las bases
echo "<h3>Buscando tabla 'usuarios':</h3>";
$buscar = $conexion->query("
    SELECT TABLE_SCHEMA, TABLE_NAME 
    FROM information_schema.TABLES 
    WHERE TABLE_NAME = 'usuarios'
");
if ($buscar->num_rows > 0) {
    while($row = $buscar->fetch_array()) {
        echo "✅ Tabla 'usuarios' encontrada en: " . $row[0] . "<br>";
        
        // Intentar ver los datos
        $conexion->select_db($row[0]);
        $datos = $conexion->query("SELECT * FROM usuarios LIMIT 5");
        if ($datos && $datos->num_rows > 0) {
            echo "📊 Datos encontrados: " . $datos->num_rows . " usuarios<br>";
        } else {
            echo "⚠️ La tabla 'usuarios' está vacía<br>";
        }
    }
} else {
    echo "❌ No se encontró la tabla 'usuarios' en ninguna base de datos<br>";
}

// 4. Probar inicio de sesión con credenciales de ejemplo
echo "<h3>Prueba de inicio de sesión:</h3>";
if (isset($_POST['test_login'])) {
    $test_user = $_POST['username'];
    $test_pass = $_POST['password'];
    
    // Ajusta esta consulta según la estructura de tu tabla usuarios
    $sql = "SELECT * FROM usuarios WHERE nombre_usuario = ? OR email = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ss", $test_user, $test_user);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if ($resultado->num_rows > 0) {
        echo "✅ Usuario encontrado<br>";
    } else {
        echo "❌ Usuario NO encontrado<br>";
    }
}

$conexion->close();
?>

<!-- Formulario de prueba -->
<form method="POST">
    Usuario: <input type="text" name="username" required><br>
    Contraseña: <input type="password" name="password" required><br>
    <button type="submit" name="test_login">Probar inicio de sesión</button>
</form>