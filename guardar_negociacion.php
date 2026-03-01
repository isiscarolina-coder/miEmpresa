<?php
header("Content-Type: application/json; charset=UTF-8");

// Configuración de conexión (TiDB Cloud con SSL)
$host = "gateway01.us-east-1.prod.aws.tidbcloud.com";
$user = "4Asq3bxQtZ3iP3r.root";
$pass = "Kt7JQCCjn0CTWYAx";
$db   = "test";
$port = 4000;

$conexion = mysqli_init();
$ca_cert = "/etc/ssl/certs/ca-certificates.crt";
mysqli_ssl_set($conexion, NULL, NULL, $ca_cert, NULL, NULL);
$resultado = @mysqli_real_connect($conexion, $host, $user, $pass, $db, $port, NULL, MYSQLI_CLIENT_SSL);

if (!$resultado) {
    die(json_encode(["status" => "error", "message" => "Fallo conexión BD"]));
}

// Tu App de Android envía los datos por POST (x-www-form-urlencoded)
// idusuario, comision, multiplicador
$idUsuario     = isset($_POST['idusuario']) ? intval($_POST['idusuario']) : 0;
$comision      = isset($_POST['comision']) ? intval($_POST['comision']) : 0;
$multiplicador = isset($_POST['multiplicador']) ? intval($_POST['multiplicador']) : 0;

if ($idUsuario <= 0) {
    die(json_encode(["status" => "error", "message" => "ID de usuario no válido"]));
}

// Usamos REPLACE INTO o INSERT ... ON DUPLICATE KEY UPDATE 
// para que si ya existe una negociación para ese usuario, se actualice.
$sql = "INSERT INTO negociaciones (idusuario, comision, multiplicador) 
        VALUES (?, ?, ?) 
        ON DUPLICATE KEY UPDATE comision = VALUES(comision), multiplicador = VALUES(multiplicador), fecha = CURRENT_TIMESTAMP";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("iii", $idUsuario, $comision, $multiplicador);

if ($stmt->execute()) {
    echo json_encode([
        "status" => "success", 
        "message" => "Negociación guardada correctamente"
    ]);
} else {
    echo json_encode([
        "status" => "error", 
        "message" => "Error al ejecutar: " . $stmt->error
    ]);
}

$stmt->close();
$conexion->close();
?>
