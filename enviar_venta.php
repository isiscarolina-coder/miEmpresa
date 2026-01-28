<?php
// enviar_venta.php
header("Content-Type: application/json; charset=UTF-8");

// 1. Configurar Zona Horaria
date_default_timezone_set('America/Tegucigalpa');

// 2. Conexión a TiDB Cloud
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
    echo json_encode(["status" => "error", "message" => "Fallo conexión BD: " . mysqli_connect_error()]);
    exit;
}

mysqli_set_charset($conexion, "utf8");

// 3. Leer JSON de Android
$json = file_get_contents('php://input');
$datos = json_decode($json, true);

if (!$datos || !isset($datos['ventas'])) {
    echo json_encode(["status" => "error", "message" => "Datos inválidos o vacíos"]);
    exit;
}

$idUsuario = $datos['idusuario'];
$fechaVentaCompleta = $datos['fecha_venta']; 
$horaActual = date("H:i:s", strtotime($fechaVentaCompleta));

// 4. Lógica de Turno
$idTurnoIdentificado = 0;
$sqlTurnos = "SELECT idturno, desde, hasta FROM turnos";
$resTurnos = $conexion->query($sqlTurnos);

while($t = $resTurnos->fetch_assoc()) {
    if ($horaActual >= $t['desde'] && $horaActual <= $t['hasta']) {
        $idTurnoIdentificado = $t['idturno'];
        break;
    }
}

if ($idTurnoIdentificado == 0) {
    echo json_encode(["status" => "error", "message" => "No existe turno activo para: $horaActual"]);
    exit;
}

// 5. Inserción de Ventas
$respuestas = [];
// ASEGÚRATE QUE LOS NOMBRES DE COLUMNAS EN TU DB SEAN: idusuario, numVenta, monto, Idturno, fecha_venta
$stmt = $conexion->prepare("INSERT INTO ventas (idusuario, numVenta, monto, Idturno, fecha_venta) VALUES (?, ?, ?, ?, ?)");

if (!$stmt) {
    echo json_encode(["status" => "error", "message" => "Error en Prepare: " . $conexion->error]);
    exit;
}

foreach ($datos['ventas'] as $venta) {
    $num = $venta['numero'];
    $mon = $venta['monto'];
    
    // "isiis" -> idusuario(i), numVenta(s), monto(i), Idturno(i), fecha_venta(s)
    $stmt->bind_param("isiis", $idUsuario, $num, $mon, $idTurnoIdentificado, $fechaVentaCompleta);
    
    if ($stmt->execute()) {
        $respuestas[] = [
            "codigo" => (string)$conexion->insert_id,
            "numero" => $num,
            "valor" => (int)$mon,
            "fecha" => $fechaVentaCompleta,
            "operador" => (int)$idUsuario
        ];
    }
}

// 6. Respuesta Final (Única salida de texto)
echo json_encode([
    "status" => "success",
    "message" => "Venta registrada en Turno $idTurnoIdentificado",
    "data" => $respuestas
]);

$stmt->close();
$conexion->close();
?>


