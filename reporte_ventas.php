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
$res = @mysqli_real_connect($conexion, $host, $user, $pass, $db, $port, NULL, MYSQLI_CLIENT_SSL);

if (!$res) die(json_encode(["status" => "error", "message" => "Error conexión"]));

// Capturar filtros
$idAdmin = $_GET['idEmpresario'];
$idOperador = $_GET['idOperador']; // Puede ser "ALL" o un ID
$idTurno = $_GET['idTurno'];
$fecha = $_GET['fecha']; // Formato YYYY-MM-DD

// Construir consulta dinámica
$where = "WHERE v.Idturno = $idTurno AND DATE(v.fecha_venta) = '$fecha' ";

if ($idOperador !== "ALL") {
    $where .= " AND v.idusuario = $idOperador";
} else {
    // Si es ALL, filtramos por los usuarios que pertenecen a este administrador
    $where .= " AND v.idusuario IN (SELECT idusuario FROM usuario WHERE idEmpresario = $idAdmin)";
}

$sql = "SELECT v.idventas, v.numVenta, v.monto, v.Idturno, t.turnos 
        FROM ventas v 
        INNER JOIN turnos t ON v.Idturno = t.idturnos 
        $where ORDER BY v.fecha_venta DESC";

$resultado = $conexion->query($sql);
$ventas = [];

while($row = $resultado->fetch_assoc()) {
    $ventas[] = $row;
}

echo json_encode(["status" => "success", "data" => $ventas]);
$conexion->close();
?>
