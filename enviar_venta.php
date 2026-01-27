<?php
header("Content-Type: application/json; charset=UTF-8");
error_reporting(0);

// 1. Configurar Zona Horaria (Ajusta según tu país, ej: 'America/Caracas')
date_default_timezone_set('America/Tegucigalpa');

// 2. Conexión a TiDB Cloud (SSL Requerido)
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

// 3. Leer JSON de Android
$json = file_get_contents('php://input');
$datos = json_decode($json, true);

if (!$datos || !isset($datos['ventas'])) {
    die(json_encode(["status" => "error", "message" => "Datos inválidos"]));
}

$idUsuario = $datos['idusuario'];
$fechaVentaCompleta = $datos['fecha_venta']; // Ej: "2026-01-25 15:30:00"

// --- LOGICA DE TURNO ROBUSTA EN PHP ---
// Extraemos solo la hora de la fecha enviada
$horaActual = date("H:i:s", strtotime($fechaVentaCompleta));

$idTurnoIdentificado = 0;

// Consultamos los turnos directamente de la base de datos
$sqlTurnos = "SELECT idturno, desde, hasta FROM turnos";
$resTurnos = $conexion->query($sqlTurnos);

while($t = $resTurnos->fetch_assoc()) {
    // Validamos si la hora recibida está entre el rango del turno
    if ($horaActual >= $t['desde'] && $horaActual <= $t['hasta']) {
        $idTurnoIdentificado = $t['idturno'];
        break;
    }
}

if ($idTurnoIdentificado == 0) {
    die(json_encode(["status" => "error", "message" => "No existe un turno activo para la hora: $horaActual"]));
}

// 4. Inserción de Ventas
$exitos = 0;
$respuestas = [];
$stmt = $conexion->prepare("INSERT INTO ventas (idusuario, numVenta, monto, Idturno, fecha_venta) VALUES (?, ?, ?, ?, ?)");

foreach ($datos['ventas'] as $venta) {
    $num = $venta['numero'];
    $mon = $venta['monto'];
    $stmt->bind_param("isiis", $idUsuario, $num, $mon, $idTurnoIdentificado, $fechaVentaCompleta);
    
    if ($stmt->execute()) {
        $exitos++;
        $respuestas[] = [
            "codigo" => $conexion->insert_id,
            "numero" => $num,
            "valor" => (double)$mon,
            "fecha" => $fechaVentaCompleta,
            "operador" => $idUsuario
        ];
    }
}

echo json_encode([
    "status" => "success",
    "message" => "Venta registrada en Turno $idTurnoIdentificado",
    "data" => $respuestas
]);

$stmt->close();
$conexion->close();
?>

