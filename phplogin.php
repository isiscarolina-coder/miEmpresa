<?php
header("Content-Type: application/json; charset=UTF-8");

// 1. Obtener datos del JSON
$json_input = file_get_contents("php://input");
$datos = json_decode($json_input, true);

$usuario = isset($datos['usuario']) ? trim($datos['usuario']) : '';
$password = isset($datos['password']) ? $datos['password'] : '';

// 2. Conexión a TiDB
$host = getenv('DB_HOST');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');
$db   = getenv('DB_NAME');
$port = getenv('DB_PORT') ?: 4000;

$conexion = mysqli_init();
$ca_cert = "/etc/ssl/certs/ca-certificates.crt";
mysqli_ssl_set($conexion, NULL, NULL, $ca_cert, NULL, NULL);
$resultado = @mysqli_real_connect($conexion, $host, $user, $pass, $db, $port, NULL, MYSQLI_CLIENT_SSL);

if (!$resultado) {
    echo json_encode(["status" => "error", "message" => "Error de conexión"]);
    exit;
}

if (empty($usuario) || empty($password)) {
    echo json_encode(["status" => "error", "message" => "Datos incompletos."]);
    mysqli_close($conexion);
    exit();
}

// 3. Función de validación (Asegúrate de que los nombres de columnas sean correctos)
function buscar_y_validar($conn, $tabla, $col_user, $col_id, $col_nombre, $col_pass, $usuario, $password, $rol) {
    $sql = "SELECT $col_id, $col_nombre, $col_pass FROM $tabla WHERE $col_user = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return null;

    mysqli_stmt_bind_param($stmt, "s", $usuario);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        // Verifica con hash o texto plano
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

// --- 4. LA PARTE QUE FALTABA: EJECUTAR LA BÚSQUEDA ---
$usuario_data = null;

// Intentar primero como EMPRESARIO (Rol 0)
$usuario_data = buscar_y_validar($conexion, 
    'empresario', 'empUsuario', 'idempresario', 'empNombre', 'empPassword', 
    $usuario, $password, 0
);

// Si no es empresario, intentar como USUARIO/OPERADOR (Rol 1)
if ($usuario_data === null) {
    $usuario_data = buscar_y_validar($conexion, 
        'usuario', 'usdUsuario', 'idUsuario', 'usdNombre', 'usdPassword', 
        $usuario, $password, 1
    );
}

// 5. Respuesta Final
if ($usuario_data !== null) {
    echo json_encode([
        "status" => "success", 
        "message" => "Login exitoso.",
        "user_id" => (string)$usuario_data['user_id'],
        "username" => $usuario_data['username'],
        "rol" => (string)$usuario_data['rol']
    ]);
} else {
    echo json_encode([
        "status" => "error", 
        "message" => "Credenciales inválidas."
    ]);
}

mysqli_close($conexion);
?>

