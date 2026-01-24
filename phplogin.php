<?php
// Evitar que cualquier error de PHP se imprima y rompa el JSON
//error_reporting(0);
//ini_set('display_errors', 0);

header("Content-Type: application/json; charset=UTF-8");

// Leer el JSON que viene de Android
$json_input = file_get_contents("php://input");
$datos = json_decode($json_input, true);

// Extraer las variables del JSON
$usuario = isset($datos['usuario']) ? trim($datos['usuario']) : '';
$password = isset($datos['password']) ? $datos['password'] : '';

// --- 1. Conexión (Mantén tu lógica de SSL) ---
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

// --- 2. LEER JSON (Esto corrige el error raro) ---
$json_input = file_get_contents("php://input");
$datos = json_decode($json_input, true);

$usuario = isset($datos['usuario']) ? trim($datos['usuario']) : '';
$password = isset($datos['password']) ? $datos['password'] : '';

if (empty($usuario) || empty($password)) {
    echo json_encode(["status" => "error", "message" => "Datos incompletos."]);
    mysqli_close($conexion);
    exit();
}

// --- 3. Función auxiliar (Igual a la tuya pero con password_verify) ---
function buscar_y_validar($conn, $tabla, $col_user, $col_id, $col_nombre, $col_pass, $usuario, $password, $rol) {
    $sql = "SELECT $col_id, $col_nombre, $col_pass FROM $tabla WHERE $col_user = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return null;

    mysqli_stmt_bind_param($stmt, "s", $usuario);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        // Importante: password_verify es para contraseñas hasheadas
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

// ... Resto de tu lógica de roles (empresario y luego usuario) ...

// --- 5. Respuesta Final ---
if ($usuario_data !== null) {
    echo json_encode([
        "status" => "success", 
        "message" => "Login exitoso.",
        "user_id" => (string)$usuario_data['user_id'],
        "username" => $usuario_data['username'],
        "rol" => $usuario_data['rol']
    ]);
} else {
    echo json_encode(["status" => "error", "message" => "Credenciales inválidas."]);
}
mysqli_close($conexion);
?>


