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

// Leer el JSON enviado desde Android/Browser
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Validar que los datos obligatorios existan
if (!isset($data['idusuario']) || !isset($data['usdUsuario'])) {
    echo json_encode(["status" => "error", "message" => "Datos incompletos"]);
    exit;
}

$idusuario = $data['idusuario'];
$nuevoUsuario = $data['usdUsuario'];
$nuevoPassword = isset($data['usdPassword']) && !empty($data['usdPassword']) ? $data['usdPassword'] : null;

try {
    // 1. Verificar si el nombre de usuario ya existe en otro registro
    $stmtCheck = $conn->prepare("SELECT idusuario FROM usuario WHERE usdUsuario = ? AND idusuario != ?");
    $stmtCheck->bind_param("si", $nuevoUsuario, $idusuario); // 's' para usuario, 'i' para idusuario
    $stmtCheck->execute();
    $resultCheck = $stmtCheck->get_result();

    if ($resultCheck->num_rows > 0) {
        echo json_encode(["status" => "error", "message" => "El nombre de usuario ya está en uso"]);
        exit;
    }

    // 2. Preparar la consulta de actualización
    if ($nuevoPassword !== null) {
        // Encriptamos la contraseña para usdPassword
        $hashedPassword = password_hash($nuevoPassword, PASSWORD_DEFAULT);
        
        // El orden en el SQL es: usdUsuario(1), usdPassword(2), usdPassV(3), idusuario(4)
        $stmt = $conn->prepare("UPDATE usuario SET usdUsuario = ?, usdPassword = ?, usdPassV = ? WHERE idusuario = ?");
        
        // CORRECCIÓN: Definimos tipos 'ssss' o 'sssi' dependiendo de tu DB. Asumiré 'sssi' (idusuario como entero)
        // Estructura: nuevoUsuario (s), hashedPassword (s), nuevoPassword visible (s), idusuario (i)
        $stmt->bind_param("sssi", $nuevoUsuario, $hashedPassword, $nuevoPassword, $idusuario);
    } else {
        // Si no hay password, solo actualizamos el nombre de usuario
        $stmt = $conn->prepare("UPDATE usuario SET usdUsuario = ? WHERE idusuario = ?");
        $stmt->bind_param("si", $nuevoUsuario, $idusuario);
    }

    if ($stmt->execute()) {
        // Modificado para ser más precisos con las filas afectadas
        if ($stmt->affected_rows > 0) {
            echo json_encode(["status" => "success", "message" => "Usuario actualizado correctamente"]);
        } else {
            // affected_rows puede ser 0 si mandaste los mismos datos que ya estaban guardados
            echo json_encode(["status" => "success", "message" => "No se realizaron cambios (los datos ya eran idénticos)"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Error al ejecutar la actualización"]);
    }

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}

$conn->close();
?>
