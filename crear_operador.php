<?php
// 1. Desactivar la visualización de errores HTML
error_reporting(0);
ini_set('display_errors', 0);

// Permitir acceso desde cualquier origen (CORS) y configurar tipo JSON
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include 'conexion.php'; 

// Leer los datos que vienen de Android
$datos = json_decode(file_get_contents("php://input"), true);

if (!$datos) {
    echo json_encode(["status" => "error", "message" => "No se recibieron datos JSON"]);
    exit;
}

// Mapeo de variables según tu requerimiento
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

// Encriptar contraseña para seguridad
$passHash = password_hash($password, PASSWORD_DEFAULT);

// Preparar la consulta SQL
// operador = 1 (defecto), usdEstado = 1 (activo por defecto)
$sql = "INSERT INTO usuarios (usdNombre, usdUsuario, usdPassword, usdCorreo, usdTelefono, operador, usdEstado, idEmpresario) 
        VALUES (?, ?, ?, ?, ?, 1, 1, ?)";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("sssssi", $nombre, $usuario, $passHash, $correo, $telefono, $idAdmin);

if ($stmt->execute()) {
    echo json_encode([
        "status" => "success", 
        "message" => "Operador $nombre creado correctamente"
    ]);
} else {
    echo json_encode([
        "status" => "error", 
        "message" => "Error al insertar: " . $conn->error
    ]);
}

$stmt->close();
$conexion->close();
?>

