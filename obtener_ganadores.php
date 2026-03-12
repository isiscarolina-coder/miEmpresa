<?php
header("Content-Type: application/json; charset=UTF-8");

// --- 1. CONFIGURACIÓN DE CONEXIÓN ---
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

// --- 2. CAPTURAR PARÁMETROS DEL RANGO ---
$fDesde = $_POST['fechaDesde'] ?? null;
$tIni   = $_POST['idTurnoInicio'] ?? null;
$fHasta = $_POST['fechaHasta'] ?? null;
$tFin   = $_POST['idTurnoFin'] ?? null;

// --- 3. CONSTRUCCIÓN DE LA CONSULTA ---
$sql = "SELECT idnumeroGanador,
               numeroGanadorcol, 
               DATE_FORMAT(fecha, '%d/%m/%Y') AS fecha_formateada, 
               idturnos 
        FROM numero 
        WHERE 1=1";

// Filtro por Rango de Fechas
if (!empty($fDesde) && !empty($fHasta)) {
    $fDesde_esc = $conexion->real_escape_string($fDesde);
    $fHasta_esc = $conexion->real_escape_string($fHasta);
    
    // Filtramos el bloque principal de fechas
    $sql .= " AND fecha BETWEEN '$fDesde_esc' AND '$fHasta_esc'";
}

// Filtro por Rango de Turnos
// Nota: Esto asume que dentro de las fechas seleccionadas, 
// solo quieres los turnos comprendidos entre tIni y tFin.
if (!empty($tIni)) {
    $sql .= " AND idturnos >= " . intval($tIni);
}
if (!empty($tFin)) {
    $sql .= " AND idturnos <= " . intval($tFin);
}

$sql .= " ORDER BY fecha ASC, idturnos ASC"; 

// --- 4. EJECUCIÓN ---
$res = $conexion->query($sql);
$datos = [];

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $datos[] = $row;
    }
    
    echo json_encode([
        "status" => "success",
        "count"  => count($datos),
        "data"   => $datos
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Error en la consulta: " . $conexion->error
    ]);
}

$conexion->close();
?>
