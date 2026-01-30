<?php
// enviar_venta.php
error_reporting(E_ALL); // Habilitamos temporalmente para ver el error real
ini_set('display_errors', 0); // No mostrar en HTML para no ensuciar el JSON

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
    echo json_encode(["status" => "error", "message" => "Fallo conexión BD " . mysqli_connect_error()]);
    exit;
}

$json = file_get_contents('php://input');
$datos = json_decode($json, true);

// VALIDACIÓN CRÍTICA
if (!$datos || !isset($datos['idusuario']) || !isset($datos['ventas'])) {
    echo json_encode(["status" => "error", "message" => "JSON mal formado o vacío"]);
    exit;
}

$idUsuario = $datos['idusuario'];
$fechaVenta = $datos['fecha_venta'];

// 1. OBTENER TURNO (Si falla aquí, la DB está vacía o el SELECT está mal)
$hora = date("H:i:s", strtotime($fechaVenta));
$idTurno = 0;
$resT = $conexion->query("SELECT idturno FROM turnos WHERE '$hora' BETWEEN desde AND hasta");
if ($rowT = $resT->fetch_assoc()) {
    $idTurno = $rowT['idturno'];
} else {
    echo json_encode(["status" => "error", "message" => "No hay turno para la hora $hora"]);
    exit;
}

// 2. INSERTAR
$respuestas = [];
// ASEGÚRATE QUE LA TABLA SE LLAME 'ventas'
$stmt = $conexion->prepare("INSERT INTO ventas (idusuario, numVenta, monto, Idturno, fecha_venta) VALUES (?, ?, ?, ?, ?)");

if (!$stmt) {
    echo json_encode(["status" => "error", "message" => "Error Prepare " . $conexion->error]);
    exit;
}

foreach ($datos['ventas'] as $v) {
    $num = $v['numero'];
    $mon = $v['monto'];
    $stmt->bind_param("isiis", $idUsuario, $num, $mon, $idTurno, $fechaVenta);
    if ($stmt->execute()) {
        $respuestas[] = [
            "codigo" => (string)$conexion->insert_id,
            "numero" => $num,
            "valor" => $mon,
            "fecha" => $fechaVenta,
            "operador" => $idUsuario
        ];
    }
}

echo json_encode(["status" => "success", "message" => "Venta registrada", "data" => $respuestas]);

$stmt->close();
$conexion->close();
?>


