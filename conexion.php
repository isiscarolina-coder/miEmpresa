   <?php

// Credenciales (Prioriza las de Koyeb)
$host = getenv('DB_HOST') ?: "gateway01.us-east-1.prod.aws.tidbcloud.com";
$user = getenv('DB_USER') ?: "4Asq3bxQtZ3iP3r.root";
$pass = getenv('DB_PASS') ?: "Kt7JQCCjn0CTWYAx";
$db   = getenv('DB_NAME') ?: "test";
$port = getenv('DB_PORT') ?: 4000;

$conexion = mysqli_init();

// ESTA RUTA ES LA QUE FUNCIONA EN KOYEB/DOCKER
$ca_cert = "/etc/ssl/certs/ca-certificates.crt";

if (!file_exists($ca_cert)) {
    header('Content-Type: application/json');
    die(json_encode(["status" => "error", "message" => "Certificado SSL no encontrado en $ca_cert"]));
}

mysqli_ssl_set($conexion, NULL, NULL, $ca_cert, NULL, NULL);

// Intentar conectar
$resultado = @mysqli_real_connect($conexion, $host, $user, $pass, $db, $port, NULL, MYSQLI_CLIENT_SSL);

if (!$resultado) {
    header('Content-Type: application/json');
    die(json_encode([
        "status" => "error", 
        "message" => "Fallo de conexión TiDB: " . mysqli_connect_error()
    ]));
}

mysqli_set_charset($conexion, "utf8");

// No cerrar la etiqueta de PHP si es un archivo puramente lógico (evita espacios accidentales)


