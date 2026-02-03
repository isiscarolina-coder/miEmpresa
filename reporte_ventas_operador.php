<?php
header("Content-Type: application/json; charset=UTF-8");
error_reporting(0);

$host = "gateway01.us-east-1.prod.aws.tidbcloud.com";
$user = "4Asq3bxQtZ3iP3r.root";
$pass = "Kt7JQCCjn0CTWYAx";
$db   = "test";
$port = 4000;

$conexion = mysqli_init();
$ca_cert = "/etc/ssl/certs/ca-certificates.crt";
mysqli_ssl_set($conexion, NULL, NULL, $ca_cert, NULL, NULL);
$res = @mysqli_real_connect($conexion, $host, $user, $pass, $db, $port, NULL, MYSQLI_CLIENT_SSL);

if (!$res) die(json_encode(["status" => "error", "message" => "Error conexión BD"]));

// Parámetros obligatorios para el operador
$idOperador = isset($_GET['idOperador']) ? (int)$_GET['idOperador'] : 0;
$idTurno = isset($_GET['idTurno']) ? (int)$_GET['idTurno'] : 0;
$fecha = isset($_GET['fecha']) ? $_GET['fecha'] : '';

if ($idOperador === 0 || $idTurno === 0 || empty($fecha)) {
    die(json_encode(["status" => "error", "message" => "Faltan parámetros de filtrado"]));
}

// Consulta directa y segura
$sql = "SELECT v.idventas, v.numVenta, v.monto, v.Idturno, t.turnos 
        FROM ventas v 
        INNER JOIN turnos t ON v.Idturno = t.idturnos 
        WHERE v.idusuario = $idOperador 
        AND v.Idturno = $idTurno 
        AND DATE(v.fecha_venta) = '$fecha' 
        ORDER BY v.idventas DESC";

$resultado = $conexion->query($sql);
$ventas = [];

while($row = $resultado->fetch_assoc()) {
    $ventas[] = [
        "idventas" => (int)$row['idventas'],
        "numVenta" => $row['numVenta'],
        "monto"    => (int)$row['monto'],
        "Idturno"  => (int)$row['Idturno'],
        "turnos"   => $row['turnos']
    ];
}

echo json_encode(["status" => "success", "data" => $ventas]);
$conexion->close();
?>
