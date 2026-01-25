<?php
/**
 * Endpoint: Obtener lista de operadores
 * Estructura robusta compatible con Android y TiDB Cloud
 */

header("Content-Type: application/json; charset=UTF-8");

// --- Helper para respuesta JSON ---
function json_response(array $payload, int $code = 200): void {
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// --- Validar método ---
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response([
        "status" => "error",
        "message" => "Método no permitido. Usa GET."
    ], 405);
}

// --- Parámetros de conexión ---
$host = getenv('DB_HOST') ?: "gateway01.us-east-1.prod.aws.tidbcloud.com";
$user = getenv('DB_USER') ?: "4Asq3bxQtZ3iP3r.root";
$pass = getenv('DB_PASS') ?: "Kt7JQCCjn0CTWYAx";
$db   = getenv('DB_NAME') ?: "test";
$port = (int)(getenv('DB_PORT') ?: 4000);

// --- Inicializar conexión con SSL ---
$conexion = mysqli_init();
if ($conexion === false) {
    json_response([
        "status" => "error",
        "message" => "No se pudo inicializar la conexión MySQLi"
    ], 500);
}

$ca_cert = "/etc/ssl/certs/ca-certificates.crt";
if (!file_exists($ca_cert)) {
    json_response([
        "status" => "error",
        "message" => "Certificado SSL no encontrado"
    ], 500);
}

mysqli_ssl_set($conexion, null, null, $ca_cert, null, null);

// Conectar
$connected = @mysqli_real_connect($conexion, $host, $user, $pass, $db, $port, null, MYSQLI_CLIENT_SSL);

if (!$connected) {
    json_response([
        "status" => "error",
        "message" => "Fallo de conexión a BD: " . mysqli_connect_error()
    ], 500);
}

mysqli_set_charset($conexion, "utf8");

// --- Consulta ---
// Usamos "usuario" como en tu otro archivo
$sql = "SELECT idusuario, usdUsuario, usdEstado FROM usuario WHERE operador = 1";
$res = $conexion->query($sql);

if ($res) {
    $operadores = array();
    while($row = $res->fetch_assoc()) {
        // Casteo de tipos para asegurar compatibilidad con Android
        $operadores[] = [
            "idusuario"  => (int)$row['idusuario'],
            "usdUsuario" => $row['usdUsuario'],
            "usdEstado"  => (int)$row['usdEstado']
        ];
    }
    
    // Si no hay datos, enviamos éxito pero con lista vacía
    json_response([
        "status" => "success",
        "data" => $operadores
    ], 200);

} else {
    json_response([
        "status" => "error",
        "message" => "Error en la consulta: " . $conexion->error
    ], 500);
}

$conexion->close();
?>







