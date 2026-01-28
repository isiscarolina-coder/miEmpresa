<?php
header("Content-Type: application/json; charset=UTF-8");

$json_input = file_get_contents("php://input");
$datos = json_decode($json_input, true);

$usuario = isset($datos['usuario']) ? trim($datos['usuario']) : '';
$password = isset($datos['password']) ? $datos['password'] : '';

// --- Configuración de conexión ---
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

/**
 * Función de validación actualizada
 * Se agregó el parámetro $col_status para verificar si el usuario está activo
 */
function buscar_y_validar($conn, $tabla, $col_id, $col_user, $col_pass, $usuario, $password, $rol, $col_status = null) {
    // Si hay columna de estado, la incluimos en el SELECT
    $select_status = $col_status ? ", $col_status" : "";
    $sql = "SELECT $col_id, $col_user, $col_pass $select_status FROM $tabla WHERE $col_user = ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return null;

    mysqli_stmt_bind_param($stmt, "s", $usuario);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        // 1. Validar Contraseña (Hash o Texto Plano)
        if (password_verify($password, $row[$col_pass]) || $password === $row[$col_pass]) {
            
            // 2. Validar Estado (Si se proporcionó la columna)
            if ($col_status !== null && (int)$row[$col_status] === 0) {
                mysqli_stmt_close($stmt);
                return "disabled"; // Retornamos un flag específico
            }

            mysqli_stmt_close($stmt);
            return [
                'user_id'  => $row[$col_id],
                'username' => $row[$col_user],
                'rol'      => $rol
            ];
        }
    }
    mysqli_stmt_close($stmt);
    return null;
}

// --- EJECUTAR BÚSQUEDA ---
$usuario_data = null;

// 1. Intentar como EMPRESARIO (Asumimos que siempre están activos o no tienen esa columna)
$usuario_data = buscar_y_validar($conexion, 'empresario', 'idempresario', 'empUsuario', 'empPassword', $usuario, $password, 0);

// 2. Si no es empresario, intentar como USUARIO/OPERADOR (Aquí validamos usdEstado)
if ($usuario_data === null) {
    $usuario_data = buscar_y_validar(
        $conexion, 
        'usuario', 
        'idUsuario', 
        'usdUsuario', 
        'usdPassword', 
        $usuario, 
        $password, 
        1, 
        'usdEstado' // <--- Pasamos el nombre de la columna de estado
    );
}

// --- RESPUESTA FINAL ---

if ($usuario_data === "disabled") {
    // Caso: Usuario encontrado pero con usdEstado = 0
    echo json_encode([
        "status" => "error",
        "message" => "El usuario está deshabilitado. Contacte al administrador."
    ]);
} elseif ($usuario_data !== null) {
    // Caso: Login exitoso
    echo json_encode([
        "status" => "success", 
        "message" => "Login exitoso.",
        "user_id" => (string)$usuario_data['user_id'],
        "username" => $usuario_data['username'],
        "rol" => (string)$usuario_data['rol']
    ]);
} else {
    // Caso: No existe o contraseña mal escrita
    echo json_encode([
        "status" => "error", 
        "message" => "Credenciales inválidas."
    ]);
}

mysqli_close($conexion);
?>
