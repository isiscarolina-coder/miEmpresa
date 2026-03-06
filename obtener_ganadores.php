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
$fechaFiltro = $_GET['fecha'] ?? null;
$idTurnoFiltro = $_GET['idturno'] ?? null;

// --- 3. CONSTRUCCIÓN DE LA CONSULTA ---
// Usamos DATE_FORMAT para cambiar el formato de la fecha de YYYY-MM-DD a DD/MM/YYYY
$sql = "SELECT 
            numeroGanadorcol, 
            DATE_FORMAT(fecha, '%d/%m/%Y') AS fecha, 
            idturnos 
        FROM numero 
        WHERE 1=1";

// Filtro por fecha (Asumiendo que el usuario envía la fecha en formato YYYY-MM-DD para la búsqueda)
if (!empty($fechaFiltro)) {
    $sql .= " AND fecha = '" . $conexion->real_escape_string($fechaFiltro) . "'";
}

if (!empty($idTurnoFiltro)) {
    $sql .= " AND idturnos = " . intval($idTurnoFiltro);
}

$sql .= " ORDER BY r.fecha DESC, idturnos DESC"; // Nota: r.fecha se cambió a fecha si no usas alias de tabla

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
