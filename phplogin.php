<?php
header("Content-Type: application/json; charset=UTF-8");

// --- 1. Configuración de Base de Datos (Variables de Entorno) ---
$host = getenv('DB_HOST');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');
$db   = getenv('DB_NAME');
$port = getenv('DB_PORT') ?: 4000;

$conexion = mysqli_init();
// Ruta del certificado para Linux (Koyeb/Docker)
$ca_cert = "/etc/ssl/certs/ca-certificates.crt";
mysqli_ssl_set($conexion, NULL, NULL, $ca_cert, NULL, NULL);

// Establecer conexión con TiDB Cloud
$resultado = @mysqli_real_connect($conexion, $host, $user, $pass, $db, $port, NULL, MYSQLI_CLIENT_SSL);

if (!$resultado) {
    die(json_encode([
        "status" => "error", 
        "message" => "Error de conexión: " . mysqli_connect_error()
    ]));
}

// --- 2. Obtener y validar datos POST ---
$usuario = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

if (empty($usuario) || empty($password)) {
    echo json_encode(["status" => "error", "message" => "Usuario y/o contraseña incompletos."]);
    mysqli_close($conexion);
    exit();
}

// --- 3. Función auxiliar para buscar y validar ---
function buscar_y_validar($conn, $tabla, $col_user, $col_id, $col_nombre, $col_pass, $usuario, $password, $rol) {
    $sql = "SELECT $col_id, $col_nombre, $col_pass FROM $tabla WHERE $col_user = ?";
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!$stmt) return null;

    mysqli_stmt_bind_param($stmt, "s", $usuario);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        // NOTA: Si al registrar usaste password_hash(), usa password_verify.
        // Si guardaste el texto plano (no recomendado), usa: if ($password === $row[$col_pass])
        if (password_verify($password, $row[$col_pass]) || $password === $row[$col_pass]) {
            mysqli_stmt_close($stmt);
            return [
                'user_id'  => $row[$col_id],
                'username' => $row[$col_nombre],
                'rol'      => $rol
            ];
        }
    }
    mysqli_stmt_close($stmt);
    return null;
}

// --- 4. Lógica de Roles ---

// Intentar primero en la tabla 'empresario' (Rol 0)
$usuario_data = buscar_y_validar($conexion, 
    'empresario', 'empUsuario', 'idempresario', 'empNombre', 'empPassword', 
    $usuario, $password, 0
);

// Si no se encontró, buscar en la tabla 'usuario' (Rol 1)
if ($usuario_data === null) {
    $usuario_data = buscar_y_validar($conexion, 
        'usuario', 'usdUsuario', 'idUsuario', 'usdNombre', 'usdPassword', 
        $usuario, $password, 1
    );
}

// --- 5. Respuesta JSON ---
if ($usuario_data !== null) {
    echo json_encode([
        "status" => "success", 
        "message" => "Login exitoso.",
        "user_id" => $usuario_data['user_id'],
        "username" => $usuario_data['username'],
        "rol" => $usuario_data['rol']
    ]);
} else {
    echo json_encode(["status" => "error", "message" => "Credenciales inválidas."]);
}

mysqli_close($conexion);
?>


