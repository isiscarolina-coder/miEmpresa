<?php
// En Koyeb configurarás estas variables en el panel de control
$host = getenv('DB_HOST') ?: "gateway01.us-east-1.prod.aws.tidbcloud.com";
$user = getenv('DB_USER') ?: "4Asq3bxQtZ3iP3r.root";
$pass = getenv('DB_PASS') ?: "Kt7JQCCjn0CTWYAx";
$db   = getenv('DB_NAME') ?: "test";
$port = getenv('DB_PORT') ?: 4000;

$conexion = mysqli_init();
// Ruta del certificado para Linux (Koyeb/Docker)
$ca_cert = "/etc/ssl/certs/ca-certificates.crt";
mysqli_ssl_set($conexion, NULL, NULL, $ca_cert, NULL, NULL);

// Establecer conexión con TiDB Cloud
$resultado = mysqli_real_connect($conexion, $host, $user, $pass, $db, $port, NULL, MYSQLI_CLIENT_SSL);

if (!$resultado) {
    die(json_encode([
        "status" => "error", 
        "message" => "Error de conexión: " . mysqli_connect_error()
    ]));
}

mysqli_set_charset($conexion, "utf8");
?>


