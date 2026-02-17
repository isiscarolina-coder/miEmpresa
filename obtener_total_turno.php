<?php
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
$res = @mysqli_real_connect($conexion, $host, $user, $pass, $db, $port, NULL, MYSQLI_CLIENT_SSL);

if (!$res) {
    // Agregamos idturno 0 o null en caso de error
    die(json_encode(["total" => 0, "idturno" => 0])); 
}

$idOperador = isset($_GET['idusuario']) ? (int)$_GET['idusuario'] : 0;

if ($idOperador === 0) {
    echo json_encode(["total" => 0, "idturno" => 0]);
    exit;
}

$hora_actual = date('H:i:s');
$fecha_hoy = date('Y-m-d');

// 1. Identificar el Turno
$idTurno = 0;
$sqlT = "SELECT idturnos, desde, hasta FROM turnos";
$resT = $conexion->query($sqlT);

while($t = $resT->fetch_assoc()) {
    $desde = $t['desde'];
    $hasta = $t['hasta'];
    if ($desde <= $hasta) {
        if ($hora_actual >= $desde && $hora_actual <= $hasta) { 
            $idTurno = (int)$t['idturnos']; 
            break; 
        }
    } else {
        if ($hora_actual >= $desde || $hora_actual <= $hasta) { 
            $idTurno = (int)$t['idturnos']; 
            break; 
        }
    }
}

// 2. Si no hay turno identificado
if ($idTurno == 0) {
    echo json_encode(["total" => 0, "idturno" => 0]);
    exit;
}

// 3. Sumar el monto de las ventas de hoy para ese turno
$sqlV = "SELECT SUM(monto) as total FROM ventas WHERE idturno = $idTurno AND DATE(fecha_venta) = '$fecha_hoy' AND idusuario = $idOperador";
$resV = $conexion->query($sqlV);
$rowV = $resV->fetch_assoc();

$total = $rowV['total'] ? (float)$rowV['total'] : 0;

// RESPUESTA MODIFICADA
echo json_encode([
    "total" => $total,
    "idturno" => $idTurno
]);

$conexion->close();
?>
