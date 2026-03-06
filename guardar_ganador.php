<?php
header("Content-Type: application/json");
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
$numero  = $_POST['numero'];
$idturno = $_POST['idturno'];
$fecha   = date("Y-m-d");

$sql = "INSERT INTO resultados (numero, idturno, fecha, id_admin) 
        VALUES (?, ?, ?, ?) 
        ON DUPLICATE KEY UPDATE numero = VALUES(numero)";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("sisi", $numero, $idturno, $fecha, $idadmin);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Número ganador registrado"]);
} else {
    echo json_encode(["status" => "error", "message" => $conexion->error]);
}
?>
