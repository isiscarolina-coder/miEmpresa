<?php
header("Content-Type: application/json; charset=UTF-8");

// --- 1. CONFIGURACIÓN DE CONEXIÓN (TiDB Cloud con SSL) ---
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

// --- 2. CAPTURAR PARÁMETROS DE FILTRO (GET) ---
// La app envía estos parámetros desde la URL
$fechaFiltro = isset($_GET['fecha']) ? $_GET['fecha'] : '';
$idTurnoFiltro = isset($_GET['idturno']) ? $_GET['idturno'] : '';

// --- 3. CONSTRUCCIÓN DE LA CONSULTA DINÁMICA ---
// Usamos INNER JOIN para traer el nombre del turno (Matutino, etc)
$sql = "SELECT r.numeroGanadorcol, r.fecha, r.idturnos
        FROM numero r
        WHERE 1=1";

// Si la fecha no está vacía, agregamos el filtro
if (!empty($fechaFiltro)) {
    // Aseguramos formato YYYY-MM-DD si es necesario
    $sql .= " AND r.fecha = '" . $conexion->real_escape_string($fechaFiltro) . "'";
}

// Si el ID de turno no está vacío, agregamos el filtro
if (!empty($idTurnoFiltro)) {
    $sql .= " AND r.idturnos = " . intval($idTurnoFiltro);
}

// Ordenar por fecha más reciente primero
$sql .= " ORDER BY r.fecha DESC, r.idturnos DESC";

$res = $conexion->query($sql);

$datos = [];

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $datos[] = $row;
    }
    
    echo json_encode([
        "status" => "success",
        "data" => $datos
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Error en la consulta: " . $conexion->error
    ]);
}

$conexion->close();
?>
