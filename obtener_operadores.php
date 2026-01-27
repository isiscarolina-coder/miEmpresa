<?php
/**
 * Endpoint: Obtener lista de operadores
 */

header("Content-Type: application/json; charset=UTF-8");

// --- Helper para respuesta JSON ---
function json_response(array $payload, int $code = 200): void {
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Establecer Zona Horaria (Honduras)
date_default_timezone_set('America/Tegucigalpa');

// --- Validar método ---
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(["status" => "error", "message" => "Método no permitido"], 405);
}

// --- Parámetros de conexión ---
$host = getenv('DB_HOST') ?: "gateway01.us-east-1.prod.aws.tidbcloud.com";
$user = getenv('DB_USER') ?: "4Asq3bxQtZ3iP3r.root";
$pass = getenv('DB_PASS') ?: "Kt7JQCCjn0CTWYAx";
$db   = getenv('DB_NAME') ?: "test";
$port = (int)(getenv('DB_PORT') ?: 4000);

// --- Conexión con SSL ---
$conexion = mysqli_init();
$ca_cert = "/etc/ssl/certs/ca-certificates.crt"; // Asegúrate que esta ruta sea correcta en tu server

mysqli_ssl_set($conexion, null, null, $ca_cert, null, null);

$connected = @mysqli_real_connect($conexion, $host, $user, $pass, $db, $port, null, MYSQLI_CLIENT_SSL);

if (!$connected) {
    json_response(["status" => "error", "message" => "Error de conexión: " . mysqli_connect_error()], 500);
}

mysqli_set_charset($conexion, "utf8");

// --- Capturar y Validar ID ---
$idAdmin = isset($_GET['idEmpresario']) ? (int)$_GET['idEmpresario'] : 0;

if ($idAdmin <= 0) {
    json_response(["status" => "error", "message" => "ID de administrador no proporcionado o inválido"], 400);
}

// --- Consulta usando Prepared Statements (Más seguro) ---
$sql = "SELECT idusuario, usdUsuario, usdEstado FROM usuario WHERE operador = 1 AND idEmpresario = ?";
$stmt = $conexion->prepare($sql);

if ($stmt) {
    $stmt->bind_param("i", $idAdmin);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $operadores = [];
    while ($row = $result->fetch_assoc()) {
        $operadores[] = [
            "idusuario"  => (int)$row['idusuario'],
            "usdUsuario" => $row['usdUsuario'],
            "usdEstado"  => (int)$row['usdEstado']
        ];
    }

    // Respuesta única de éxito
    json_response([
        "status" => "success", 
        "count"  => count($operadores), // Para que verifiques cuántos trae
        "data"   => $operadores
    ], 200);

} else {
    json_response(["status" => "error", "message" => "Error en la consulta: " . $conexion->error], 500);
}

$conexion->close();
?>









