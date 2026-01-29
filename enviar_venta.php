<?php
// enviar_venta.php
header("Content-Type: application/json; charset=UTF-8");

date_default_timezone_set('America/Tegucigalpa');

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

$json = file_get_contents('php://input');
$datos = json_decode($json, true);

if (!$datos || !isset($datos['ventas'])) {
    die(json_encode(["status" => "error", "message" => "Datos inválidos"]));
}

$idUsuario = (int)$datos['idusuario'];
$fechaVentaCompleta = $datos['fecha_venta']; 
$horaActual = date("H:i:s", strtotime($fechaVentaCompleta));

// Buscar turno
$idTurnoIdentificado = 0;
$resTurnos = $conexion->query("SELECT idturno, desde, hasta FROM turnos");
while($t = $resTurnos->fetch_assoc()) {
    if ($horaActual >= $t['desde'] && $horaActual <= $t['hasta']) {
        $idTurnoIdentificado = (int)$t['idturno'];
        break;
    }
}

if ($idTurnoIdentificado == 0) {
    die(json_encode(["status" => "error", "message" => "No hay turno activo para las $horaActual"]));
}

$respuestas = [];
// Validamos que el prepare no falle
$stmt = $conexion->prepare("INSERT INTO ventas (idusuario, numVenta, monto, Idturno, fecha_venta) VALUES (?, ?, ?, ?, ?)");

if ($stmt) {
    foreach ($datos['ventas'] as $venta) {
        $num = (string)$venta['numero'];
        $mon = (int)$venta['monto'];
        
        // "isiis" -> Int, String, Int, Int, String
        $stmt->bind_param("isiis", $idUsuario, $num, $mon, $idTurnoIdentificado, $fechaVentaCompleta);
        
        if ($stmt->execute()) {
            $respuestas[] = [
                "codigo" => (string)$conexion->insert_id,
                "numero" => $num,
                "valor" => $mon,
                "fecha" => $fechaVentaCompleta,
                "operador" => (string)$idUsuario
            ];
        }
    }
    $stmt->close();
}

echo json_encode([
    "status" => "success",
    "message" => "Venta registrada en Turno $idTurnoIdentificado",
    "data" => $respuestas
]);

$conexion->close();
?>


