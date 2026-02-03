<?php
error_reporting(E_ALL); 
ini_set('display_errors', 0); 
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
    die(json_encode(["status" => "error", "message" => "Error de Conexión: " . mysqli_connect_error()]));
}

$json = file_get_contents('php://input');
$datos = json_decode($json, true);

if (!$datos) {
    die(json_encode(["status" => "error", "message" => "JSON Recibido Vacío"]));
}

$idUsuario = (int)$datos['idusuario'];
$fechaVenta = $datos['fecha_venta'];

// --- LOGICA DE TURNO ---
// Capturamos solo la hora actual en formato 24h (ej: 14:30:00)
$hora_actual = date('H:i:s'); 
$idTurno = 0;

// Consultamos los turnos
$sqlTurnos = "SELECT idturno, desde, hasta FROM turnos";
$resTurnos = $conexion->query($sqlTurnos);

while($t = $resTurnos->fetch_assoc()) {
    $desde = $t['desde'];
    $hasta = $t['hasta'];

    // Caso Normal: Turno en el mismo día (ej: 08:00 a 16:00)
    if ($desde <= $hasta) {
        if ($hora_actual >= $desde && $hora_actual <= $hasta) {
            $idTurno = $t['idturno'];
            break;
        }
    } 
    // Caso Especial: Turno que cruza la medianoche (ej: 22:00 a 06:00)
    else {
        if ($hora_actual >= $desde || $hora_actual <= $hasta) {
            $idTurno = $t['idturno'];
            break;
        }
    }
}

if ($idTurno == 0) {
    die(json_encode(["status" => "error", "message" => "No existe un turno activo para la hora: $hora_actual"]));
}

$respuestas = [];
// Asegúrate que en la tabla 'ventas', la columna sea 'Idturno' o 'idturno' (sensible a mayúsculas)
$stmt = $conexion->prepare("INSERT INTO ventas (idusuario, numVenta, monto, Idturno, fecha_venta) VALUES (?, ?, ?, ?, ?)");

if (!$stmt) {
    die(json_encode(["status" => "error", "message" => "Error en Prepare: " . $conexion->error]));
}

foreach ($datos['ventas'] as $v) {
    $num = $v['numero'];
    $mon = (int)$v['monto'];
    // "isiis" -> idUsuario(i), num(s), mon(i), idTurno(i), fechaVenta(s)
    $stmt->bind_param("isiis", $idUsuario, $num, $mon, $idTurno, $fechaVenta);
    $stmt->execute();
    
    $respuestas[] = [
        "codigo" => (string)$conexion->insert_id,
        "numero" => $num,
        "valor" => $mon,
        "turno_asignado" => $idTurno // Agregado para tu control
    ];
}

echo json_encode([
    "status" => "success", 
    "message" => "Ventas registradas con éxito", 
    "hora_procesada" => $hora_actual,
    "data" => $respuestas
]);

$stmt->close();
$conexion->close();
?>




