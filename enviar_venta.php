<?php
// Impedir que errores crudos de PHP ensucien la salida JSON
error_reporting(0);
ini_set('display_errors', 0);

header("Content-Type: application/json; charset=UTF-8");

// 1. FORZAR HORA DE HONDURAS (Independiente de donde esté el teléfono)
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
    echo json_encode(["status" => "error", "message" => "Fallo conexión BD: " . mysqli_connect_error()]);
    exit;
}

mysqli_set_charset($conexion, "utf8");

// 2. Leer JSON de Android
$json = file_get_contents('php://input');
$datos = json_decode($json, true);

if (!$datos || !isset($datos['ventas'])) {
    echo json_encode(["status" => "error", "message" => "JSON vacío o inválido"]);
    exit;
}

$idUsuario = (int)$datos['idusuario'];
$fechaVentaString = $datos['fecha_venta']; 

// Capturamos la hora actual de HONDURAS
$hora_honduras = date('H:i:s'); 
$idTurno = 0;

// 3. Lógica de Turno (Consultando la tabla turnos)
$resTurnos = $conexion->query("SELECT idturnos, desde, hasta FROM turnos");
if ($resTurnos) {
    while($t = $resTurnos->fetch_assoc()) {
        $desde = $t['desde'];
        $hasta = $t['hasta'];

        if ($desde <= $hasta) {
            if ($hora_honduras >= $desde && $hora_honduras <= $hasta) {
                $idTurno = $t['idturnos'];
                break;
            }
        } else { // Caso turno medianoche
            if ($hora_honduras >= $desde || $hora_honduras <= $hasta) {
                $idTurno = $t['idturnos'];
                break;
            }
        }
    }
}

if ($idTurno == 0) {
    echo json_encode(["status" => "error", "message" => "No hay turno activo en Honduras para las: $hora_honduras"]);
    exit;
}

// 4. Inserción
$respuestas = [];
// IMPORTANTE: Verifica que la columna sea 'Idturno' (mayúscula) como pusiste en tu SQL
$stmt = $conexion->prepare("INSERT INTO ventas (idusuario, numVenta, monto, idturno, fecha_venta) VALUES (?, ?, ?, ?, ?)");

if (!$stmt) {
    echo json_encode(["status" => "error", "message" => "Error SQL: " . $conexion->error]);
    exit;
}

foreach ($datos['ventas'] as $v) {
    $num = $v['numero'];
    $mon = (int)$v['monto'];
    
    $stmt->bind_param("isiis", $idUsuario, $num, $mon, $idTurno, $fechaVentaString);
    
    if ($stmt->execute()) {
        $respuestas[] = [
            "codigo" => (string)$conexion->insert_id,
            "numero" => $num,
            "valor" => $mon
        ];
    }
}

echo json_encode([
    "status" => "success", 
    "message" => "Venta exitosa (Turno $idTurno)", 
    "data" => $respuestas
]);

$stmt->close();
$conexion->close();
?>





