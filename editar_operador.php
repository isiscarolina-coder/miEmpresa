<?php
header('Content-Type: application/json; charset=utf-8');

// Configuración de la base de datos
$host = "gateway01.us-east-1.prod.aws.tidbcloud.com";
$user = "4Asq3bxQtZ3iP3r.root";
$pass = "Kt7JQCCjn0CTWYAx";
$db   = "test";
$port = 4000;


$conn = mysqli_init();
$ca_cert = "/etc/ssl/certs/ca-certificates.crt";
mysqli_ssl_set($conn, NULL, NULL, $ca_cert, NULL, NULL);
$res = @mysqli_real_connect($conn, $host, $user, $pass, $db, $port, NULL, MYSQLI_CLIENT_SSL);

if (!$res) die(json_encode(["status" => "error", "message" => "Error conexión"]));

if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Fallo de conexión"]));
}

// Leer el JSON enviado desde Android
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Validar que los datos obligatorios existan
if (!isset($data['idusuario']) || !isset($data['usdUsuario'])) {
    echo json_encode(["status" => "error", "message" => "Datos incompletos"]);
    exit;
}

$idusuario = $data['idusuario'];
$nuevoUsuario = $data['usdUsuario'];
$nuevoPassword = isset($data['usdPassword']) ? $data['usdPassword'] : null;
$PasswordReal = isset($data['usdPassword']) ? $data['usdPassword'] : null;

try {
    // 1. Verificar si el nombre de usuario ya existe en otro registro (evitar duplicados)
    $stmtCheck = $conn->prepare("SELECT idusuario FROM usuario WHERE usdUsuario = ? AND idusuario != ?");
    $stmtCheck->bind_param("si", $nuevoUsuario, $idusuario);
    $stmtCheck->execute();
    $resultCheck = $stmtCheck->get_result();

    if ($resultCheck->num_rows > 0) {
        echo json_encode(["status" => "error", "message" => "El nombre de usuario ya está en uso"]);
        exit;
    }

    // 2. Preparar la consulta de actualización según si hay password nuevo o no
    if ($nuevoPassword !== null && !empty($nuevoPassword)) {
        // Si hay password, lo encriptamos (RECOMENDADO)
        $hashedPassword = password_hash($nuevoPassword, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("UPDATE usuario SET usdUsuario = ?, usdPassword = ?, usdPassV = ? WHERE idusuario = ?");
        $stmt->bind_param("ssis", $nuevoUsuario, $hashedPassword, $idusuario, $PasswordReal);
    } else {
        // Si no hay password, solo actualizamos el nombre de usuario
        $stmt = $conn->prepare("UPDATE usuario SET usdUsuario = ? WHERE idusuario = ?");
        $stmt->bind_param("si", $nuevoUsuario, $idusuario);
    }

    if ($stmt->execute()) {
        if ($stmt->affected_rows >= 0) { // >= 0 por si el usuario guarda sin cambiar nada
            echo json_encode(["status" => "success", "message" => "Usuario actualizado correctamente"]);
        } else {
            echo json_encode(["status" => "error", "message" => "No se realizaron cambios"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Error al ejecutar la actualización"]);
    }

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}

$conn->close();
?>
