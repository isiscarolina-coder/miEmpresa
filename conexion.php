<?php
// 1. Evitar que PHP imprima errores o warnings en la respuesta (vital para Android)
error_reporting(0);
ini_set('display_errors', 0);

// 2. Definir variables (Prioriza las de entorno de Koyeb)
$host = getenv('DB_HOST') ?: "gateway01.us-east-1.prod.aws.tidbcloud.com";
$user = getenv('DB_USER') ?: "4Asq3bxQtZ3iP3r.root";
$pass = getenv('DB_PASS') ?: "Kt7JQCCjn0CTWYAx";
$db   = getenv('DB_NAME') ?: "test";
$port = getenv('DB_PORT') ?: 4000;

// 3. Inicializar conexión con SSL
$conexion = mysqli_init();

// Ruta del certificado para Linux (Koyeb/Docker usa habitualmente esta ruta)
$ca_cert = "/etc/ssl/certs/ca-certificates.crt";
mysqli_ssl_set($conexion, NULL, NULL, $ca_cert, NULL, NULL);

// 4. Intentar la conexión de forma "silenciosa" (@)
$resultado = @mysqli_real_connect($conexion, $host, $user, $pass, $db, $port, NULL, MYSQLI_CLIENT_SSL);

// 5. Validar si la conexión falló
if (!$resultado) {
    // Forzamos el header JSON antes de morir para que Android no vea texto plano
    header('Content-Type: application/json; charset=utf-8');
    die(json_encode([
        "status" => "error", 
        "message" => "Error de conexión a la base de datos: " . mysqli_connect_error()
    ]));
}

mysqli_set_charset($conexion, "utf8");

// No cerrar la etiqueta de PHP si es un archivo puramente lógico (evita espacios accidentales)


