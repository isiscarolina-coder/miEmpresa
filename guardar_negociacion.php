<?php
header("Content-Type: application/json; charset=UTF-8");

// Configuración TiDB Cloud
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

// CAPTURAR DATOS DESDE $_POST (Compatible con el código Kotlin de arriba)
$idUsuario     = isset($_POST['idusuario']) ? intval($_POST['idusuario']) : 0;
$comision      = isset($_POST['comision']) ? intval($_POST['comision']) : 0;
$multiplicador = isset($_POST['multiplicador']) ? intval($_POST['multiplicador']) : 0;

if ($idUsuario <= 0) {
    die(json_encode(["status" => "error", "message" => "Datos incompletos"]));
}

// Actualizar o Insertar
$sql = "INSERT INTO negociaciones (idusuario, comision, multiplicador) 
        VALUES (?, ?, ?) 
        ON DUPLICATE KEY UPDATE comision = VALUES(comision), multiplicador = VALUES(multiplicador), fecha = CURRENT_TIMESTAMP";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("iii", $idUsuario, $comision, $multiplicador);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Guardado correctamente"]);
} else {
    echo json_encode(["status" => "error", "message" => $conexion->error]);
}

$stmt->close();
$conexion->close();
?>
