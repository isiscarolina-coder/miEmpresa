<?php
error_reporting(E_ALL); 
ini_set('display_errors', 0); // No ensucia el JSON, pero registra el error
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
    die(json_encode(["status" => "error", "message" => "Error de Conexión: " . mysqli_connect_error()]));
}

$json = file_get_contents('php://input');
$datos = json_decode($json, true);

if (!$datos) {
    die(json_encode(["status" => "error", "message" => "JSON Recibido Vacío"]));
}

$idUsuario = (int)$datos['idusuario'];
$fechaVenta = $datos['fecha_venta'];

// PRUEBA DE TURNO FIJO PARA VER SI INSERTA
$idTurno = 1; 

$respuestas = [];
$stmt = $conexion->prepare("INSERT INTO ventas (idusuario, numVenta, monto, Idturno, fecha_venta) VALUES (?, ?, ?, ?, ?)");

if (!$stmt) {
    die(json_encode(["status" => "error", "message" => "Error en Prepare: " . $conexion->error]));
}

foreach ($datos['ventas'] as $v) {
    $num = $v['numero'];
    $mon = (int)$v['monto'];
    $stmt->bind_param("isiis", $idUsuario, $num, $mon, $idTurno, $fechaVenta);
    $stmt->execute();
    
    $respuestas[] = [
        "codigo" => (string)$conexion->insert_id,
        "numero" => $num,
        "valor" => $mon
    ];
}

echo json_encode(["status" => "success", "message" => "Venta registrada", "data" => $respuestas]);
$conexion->close();
?>



