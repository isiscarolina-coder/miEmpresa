<?php
header("Content-Type: application/json; charset=UTF-8");

// --- 1. CONFIGURACIÓN DE CONEXIÓN ---
$host = "gateway01.us-east-1.prod.aws.tidbcloud.com";
$user = "4Asq3bxQtZ3iP3r.root";
$pass = "Kt7JQCCjn0CTWYAx";
$db   = "test";
$port = 4000;

$conexion = mysqli_init();
// Asegúrate de que esta ruta sea correcta en tu servidor Koyeb
$ca_cert = "/etc/ssl/certs/ca-certificates.crt"; 
mysqli_ssl_set($conexion, NULL, NULL, $ca_cert, NULL, NULL);

$resultado = @mysqli_real_connect($conexion, $host, $user, $pass, $db, $port, NULL, MYSQLI_CLIENT_SSL);

if (!$resultado) {
    die(json_encode(["status" => "error", "message" => "Fallo conexión BD: " . mysqli_connect_error()]));
}

// --- 2. CAPTURA DE DATOS (Cambiado a $_GET para que funcione con tu URL) ---
$numero  = $_GET['numero'] ?? null;
$idturno = $_GET['idturno'] ?? null;
// Si envías la fecha en la URL, la usamos; si no, usamos la de hoy.
$fecha   = $_GET['fecha'] ?? date("Y-m-d"); 

// Validación básica
if (empty($numero) || empty($idturno)) {
    die(json_encode(["status" => "error", "message" => "Datos incompletos (numero o idturno vacíos)"]));
}

// --- 3. VERIFICAR SI YA EXISTE EL REGISTRO ---
$sqlCheck = "SELECT idnumeroGanador FROM numero WHERE fecha = ? AND idturnos = ? LIMIT 1";
$stmtCheck = $conexion->prepare($sqlCheck);
$stmtCheck->bind_param("si", $fecha, $idturno);
$stmtCheck->execute();
$resCheck = $stmtCheck->get_result();

if ($resCheck->num_rows > 0) {
    echo json_encode(["status" => "error", "message" => "Ya existe un número para este turno hoy"]);
    $stmtCheck->close();
    $conexion->close();
    exit;
}
$stmtCheck->close();

// --- 4. INSERTAR ---
$sqlInsert = "INSERT INTO numero (numeroGanadorcol, idturnos, fecha) VALUES (?, ?, ?)";
$stmtInsert = $conexion->prepare($sqlInsert);
$stmtInsert->bind_param("iis", $numero, $idturno, $fecha);

if ($stmtInsert->execute()) {
    echo json_encode(["status" => "success", "message" => "Registrado con éxito"]);
} else {
    echo json_encode(["status" => "error", "message" => "Error al insertar: " . $stmtInsert->error]);
}

$stmtInsert->close();
$conexion->close();
?>
