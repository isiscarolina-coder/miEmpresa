<?php
header("Content-Type: application/json; charset=UTF-8");

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

$datos = json_decode(file_get_contents("php://input"), true);
$usuario = $datos['usdUsuario'];
$nuevoEstado = $datos['usdEstado']; // Recibe 0 o 1

$sql = "UPDATE usuario SET usdEstado = ? WHERE usdUsuario = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("is", $nuevoEstado, $usuario);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Estado actualizado"]);
} else {
    echo json_encode(["status" => "error", "message" => $conexion->error]);
}

$stmt->close();
$conexion->close();
?>