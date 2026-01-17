<?php
/**
 * ARCHIVO ÚNICO: CONEXIÓN + CREACIÓN DE OPERADOR
 * Optimizado para Koyeb y TiDB Cloud (SSL)
 */

// 1. Configuración de errores y cabeceras
error_reporting(0);
ini_set('display_errors', 0);
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

// 2. Parámetros de conexión (Se recomienda configurar en el panel de Koyeb como Env Vars)
$host = getenv('DB_HOST') ?: "gateway01.us-east-1.prod.aws.tidbcloud.com";
$user = getenv('DB_USER') ?: "4Asq3bxQtZ3iP3r.root";
$pass = getenv('DB_PASS') ?: "Kt7JQCCjn0CTWYAx";
$db   = getenv('DB_NAME') ?: "test";
$port = getenv('DB_PORT') ?: 4000;

// 3. Inicializar Conexión con SSL (Requerido por TiDB)
$conexion = mysqli_init();
$ca_cert = "/etc/ssl/certs/ca-certificates.crt"; // Ruta estándar en contenedores Debian/Koyeb
mysqli_ssl_set($conexion, NULL, NULL, $ca_cert, NULL, NULL);

// Conectar de forma silenciosa (@)
$resultado = @mysqli_real_connect($conexion, $host, $user, $pass, $db, $port, NULL, MYSQLI_CLIENT_SSL);

if (!$resultado) {
    echo json_encode([
        "status" => "error",
        "message" => "Fallo de conexión a Base de Datos: " . mysqli_connect_error()
    ]);
    exit;
}

mysqli_set_charset($conexion, "utf8");

// 4. Procesar la solicitud de Android
$datos = json_decode(file_get_contents("php://input"), true);

if (!$datos) {
    echo json_encode(["status" => "error", "message" => "No se recibieron datos JSON válidos"]);
    exit;
}

// Mapeo de variables recibidas desde CrearOperadorActivity.kt
$nombre    = $datos['usdNombre']    ?? '';
$usuario   = $datos['usdUsuario']   ?? '';
$password  = $datos['usdPassword']  ?? '';
$correo    = $datos['usdCorreo']    ?? '';
$telefono  = $datos['usdTelefono']  ?? '';
$idAdmin   = $datos['idEmpresario'] ?? 0;

// Validaciones básicas
if (empty($nombre) || empty($usuario) || empty($password)) {
    echo json_encode(["status" => "error", "message" => "Nombre, Usuario y Password son obligatorios"]);
    exit;
}

// Encriptar contraseña (genera una cadena de 60 caracteres)
$passHash = password_hash($password, PASSWORD_DEFAULT);

// 5. Inserción en la base de datos
// operador = 1 y usdEstado = 1 por defecto según requerimiento
$sql = "INSERT INTO usuarios (usdNombre, usdUsuario, usdPassword, usdCorreo, usdTelefono, operador, usdEstado, idEmpresario) 
        VALUES (?, ?, ?, ?, ?, 1, 1, ?)";

$stmt = $conexion->prepare($sql);

if ($stmt) {
    $stmt->bind_param("sssssi", $nombre, $usuario, $passHash, $correo, $telefono, $idAdmin);
    
    if ($stmt->execute()) {
        echo json_encode([
            "status" => "success",
            "message" => "Operador $nombre registrado correctamente"
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Error de ejecución SQL: " . $stmt->error
        ]);
    }
    $stmt->close();
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Error al preparar consulta: " . $conexion->error
    ]);
}

$conexion->close();
?>
