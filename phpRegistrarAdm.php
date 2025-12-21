<?php
// 1. Configuración de conexión (Mantenemos tu lógica SSL)
$host = getenv('DB_HOST') ?: 'gateway01.us-east-1.prod.aws.tidbcloud.com'; 
$user = getenv('DB_USER') ?: '4Asq3bxQtZ3iP3r.root';
$pass = getenv('DB_PASS') ?: 'Kt7JQCCjn0CTWYAx';
$db   = getenv('DB_NAME') ?: 'test';
$port = getenv('DB_PORT') ?: 4000;

$conexion = mysqli_init();

$ca_cert = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') 
            ? NULL 
            : "/etc/ssl/certs/ca-certificates.crt";

mysqli_ssl_set($conexion, NULL, NULL, $ca_cert, NULL, NULL);

$resultado = mysqli_real_connect($conexion, $host, $user, $pass, $db, $port, NULL, MYSQLI_CLIENT_SSL);

if (!$resultado) {
    die("Error de conexión: " . mysqli_connect_error());
}

// 2. Recepción de datos del formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $nombre   = $_POST['admNombre'];
    $usuario  = $_POST['admUsuario'];
    $password = $_POST['password']; 
    $correo   = $_POST['admCorreo'];
    $telefono = $_POST['admTelefono'];

    // --- NUEVA VALIDACIÓN: Verificar si el usuario ya existe ---
    $sqlCheck = "SELECT empUsuario FROM empresario WHERE empUsuario = ?";
    $stmtCheck = mysqli_prepare($conexion, $sqlCheck);
    mysqli_stmt_bind_param($stmtCheck, "s", $usuario);
    mysqli_stmt_execute($stmtCheck);
    mysqli_stmt_store_result($stmtCheck);

    if (mysqli_stmt_num_rows($stmtCheck) > 0) {
        // El usuario ya existe en la base de datos
        echo "<h2 style='color:red;'>Error: El usuario '$usuario' ya está registrado.</h2>";
        echo "<p>Por favor, elige un nombre de usuario diferente.</p>";
        echo "<a href='javascript:history.back()'>Volver al formulario</a>";
        
        mysqli_stmt_close($stmtCheck);
    } else {
        // El usuario no existe, procedemos a insertar
        mysqli_stmt_close($stmtCheck);

        // 3. Preparar la sentencia SQL de inserción
        $sql = "INSERT INTO empresario (empNombre, empUsuario, empPassword, empCorreo, empTelefono) 
                VALUES (?, ?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($conexion, $sql);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "sssss", $nombre, $usuario, $password, $correo, $telefono);
            
            if (mysqli_stmt_execute($stmt)) {
                echo "<h2 style='color:green;'>¡Registro exitoso!</h2>";
                echo "<p>El empresario $nombre ha sido guardado correctamente.</p>";
                echo "<a href='newAdminUsuario.html'>Registrar otro usuario</a>";
            } else {
                echo "Error al insertar: " . mysqli_stmt_error($stmt);
            }
            
            mysqli_stmt_close($stmt);
        } else {
            echo "Error en la preparación: " . mysqli_error($conexion);
        }
    }
}

mysqli_close($conexion);
?>