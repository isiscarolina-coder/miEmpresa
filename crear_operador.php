<?php
/**
 * Endpoint único: conexión + creación de operador
 * Optimizado para Koyeb/TiDB Cloud con SSL y Docker
 */

//declare(strict_types=1);

// --- Configuración global ---
///error_reporting(0);
//ini_set('display_errors', '0');
//error_reporting(E_ALL);
//ini_set('display_errors', '1');

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// --- Helper para respuesta JSON ---
function json_response(array $payload, int $code = 200): void {
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// --- Validar método ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response([
        "status" => "error",
        "message" => "Método no permitido. Usa POST."
    ], 405);
}

// --- Leer JSON ---
$raw = file_get_contents("php://input");
$datos = json_decode($raw, true);

if (!is_array($datos)) {
    json_response([
        "status" => "error",
        "message" => "No se recibieron datos JSON válidos"
    ], 400);
}

// --- Mapear variables ---
$nombre   = trim((string)($datos['usdNombre']    ?? ''));
$usuario  = trim((string)($datos['usdUsuario']   ?? ''));
$password = (string)($datos['usdPassword']       ?? '');
$correo   = trim((string)($datos['usdCorreo']    ?? ''));
$telefono = trim((string)($datos['usdTelefono']  ?? ''));
$idAdmin  = (int)($datos['idEmpresario']         ?? 0);

// --- Validaciones básicas ---
if ($nombre === '' || $usuario === '' || $password === '') {
    json_response([
        "status" => "error",
        "message" => "Nombre, Usuario y Password son obligatorios"
    ], 422);
}

// Validación opcional de correo
if ($correo !== '' && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    json_response([
        "status" => "error",
        "message" => "Correo no válido"
    ], 422);
}

// Validación opcional de longitud mínima de password
if (strlen($password) < 6) {
    json_response([
        "status" => "error",
        "message" => "La contraseña debe tener al menos 6 caracteres"
    ], 422);
}

// --- Hash de contraseña ---
$passHash = password_hash($password, PASSWORD_DEFAULT);
if ($passHash === false) {
    json_response([
        "status" => "error",
        "message" => "No se pudo encriptar la contraseña"
    ], 500);
}

// --- Parámetros de conexión (usar Env Vars en Docker/Koyeb) ---
$host = getenv('DB_HOST') ?: "gateway01.us-east-1.prod.aws.tidbcloud.com";
$user = getenv('DB_USER') ?: "usuario.root";
$pass = getenv('DB_PASS') ?: "password_seguro";
$db   = getenv('DB_NAME') ?: "test";
$port = (int)(getenv('DB_PORT') ?: 4000);

// --- Inicializar conexión con SSL (TiDB requiere CA) ---
$conexion = mysqli_init();
if ($conexion === false) {
    json_response([
        "status" => "error",
        "message" => "No se pudo inicializar la conexión MySQLi"
    ], 500);
}

// Ruta estándar de CA en Debian/Koyeb/Docker (con ca-certificates instalado)
$ca_cert = "/etc/ssl/certs/ca-certificates.crt";
if (!file_exists($ca_cert)) {
    json_response([
        "status" => "error",
        "message" => "Certificado SSL no encontrado en $ca_cert"
    ], 500);
}

mysqli_ssl_set($conexion, null, null, $ca_cert, null, null);

// Conectar (silencioso con @)
$connected = @mysqli_real_connect(
    $conexion,
    $host,
    $user,
    $pass,
    $db,
    $port,
    null,
    MYSQLI_CLIENT_SSL
);

if (!$connected) {
    json_response([
        "status" => "error",
        "message" => "Fallo de conexión a Base de Datos: " . mysqli_connect_error()
    ], 500);
}

mysqli_set_charset($conexion, "utf8");

// --- Verificar duplicados opcional (usuario único) ---
$checkSql = "SELECT 1 FROM usuarios WHERE usdUsuario = ? LIMIT 1";
$checkStmt = $conexion->prepare($checkSql);
if ($checkStmt === false) {
    json_response([
        "status" => "error",
        "message" => "Error al preparar verificación: " . $conexion->error
    ], 500);
}
$checkStmt->bind_param("s", $usuario);
if (!$checkStmt->execute()) {
    $checkStmt->close();
    json_response([
        "status" => "error",
        "message" => "Error al ejecutar verificación: " . $checkStmt->error
    ], 500);
}
$checkStmt->store_result();
if ($checkStmt->num_rows > 0) {
    $checkStmt->close();
    $conexion->close();
    json_response([
        "status" => "error",
        "message" => "El usuario ya existe"
    ], 409);
}
$checkStmt->close();

// --- Inserción ---
$sql = "INSERT INTO usuarios 
        (usdNombre, usdUsuario, usdPassword, usdCorreo, usdTelefono, operador, usdEstado, idEmpresario)
        VALUES (?, ?, ?, ?, ?, 1, 1, ?)";

$stmt = $conexion->prepare($sql);
if ($stmt === false) {
    $conexion->close();
    json_response([
        "status" => "error",
        "message" => "Error al preparar consulta: " . $conexion->error
    ], 500);
}

$stmt->bind_param("sssssi", $nombre, $usuario, $passHash, $correo, $telefono, $idAdmin);

if (!$stmt->execute()) {
    $err = $stmt->error;
    $stmt->close();
    $conexion->close();
    json_response([
        "status" => "error",
        "message" => "Error de ejecución SQL: " . $err
    ], 500);
}

// Opcional: devolver el ID insertado
$insertId = $stmt->insert_id;
$stmt->close();
$conexion->close();

json_response([
    "status" => "success",
    "message" => "Operador {$nombre} registrado correctamente",
    "data" => [
        "id" => $insertId,
        "usuario" => $usuario
    ]
], 201);
