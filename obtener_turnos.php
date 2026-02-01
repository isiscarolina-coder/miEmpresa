<?php
/**
 * obtener_turnos.php
 * Retorna la lista de turnos para llenar el dropdown en Android
 */
header("Content-Type: application/json; charset=UTF-8");
//error_reporting(0); Configuración de conexión (TiDB Cloud)
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
    echo json_encode(["status" => "error", "message" => "Fallo conexión BD"]);
    exit;
}

mysqli_set_charset($conexion, "utf8");

// Consulta simple para obtener todos los turnos
$sql = "SELECT idturnos, turnos, desde, hasta FROM turnos ORDER BY idturnos ASC";
$res = $conexion->query($sql);

if ($res) {
    $data = array();
    while($row = $res->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode([
        "status" => "success",
        "data" => $data
    ]);
} else {
    echo json_encode([
        "status" => "error", 
        "message" => "Error en la consulta"
    ]);
}

$conexion->close();
?>
