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

// --- 2. CAPTURAR PARÁMETROS (Opcionales) ---
// Si no existen en $_GET, los manejamos como null o vacío
$fechaFiltro = $_GET['fecha'] ?? null;
$idTurnoFiltro = $_GET['idturno'] ?? null;

// --- 3. CONSTRUCCIÓN DE LA CONSULTA ---
$sql = "SELECT numeroGanadorcol, fecha, idturnos FROM numero WHERE 1=1";

// Si hay fecha, filtramos por ella
if (!empty($fechaFiltro)) {
    $sql .= " AND fecha = '" . $conexion->real_escape_string($fechaFiltro) . "'";
}

// Si hay ID de turno, filtramos por él
if (!empty($idTurnoFiltro)) {
    $sql .= " AND idturnos = " . intval($idTurnoFiltro);
}

// Ordenar por fecha y turno (descendente para ver lo más reciente)
$sql .= " ORDER BY fecha DESC, idturnos DESC";

$res = $conexion->query($sql);
$datos = [];

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $datos[] = $row;
    }
    
    echo json_encode([
        "status" => "success",
        "count"  => count($datos), // Añadimos un contador para control
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
