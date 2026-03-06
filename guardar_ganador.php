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

// --- 2. CAPTURA DE DATOS ---
$numero  = $_POST['numero'] ?? null;
$idturno = $_POST['idturno'] ?? null;
$fecha   = date("Y-m-d");

// Validación básica de que los datos no estén vacíos
if (empty($numero) || empty($idturno)) {
    die(json_encode(["status" => "error", "message" => "Datos incompletos"]));
}

// --- 3. VERIFICAR SI YA EXISTE EL REGISTRO ---
$sqlCheck = "SELECT id FROM numero WHERE fecha = ? AND idturnos = ? LIMIT 1";
$stmtCheck = $conexion->prepare($sqlCheck);
$stmtCheck->bind_param("si", $fecha, $idturno);
$stmtCheck->execute();
$resCheck = $stmtCheck->get_result();

if ($resCheck->num_rows > 0) {
    // Si entra aquí, es porque ya hay un registro para esa fecha y ese turno
    echo json_encode(["status" => "error", "message" => "Ya existe un número registrado para este turno el día de hoy"]);
    $stmtCheck->close();
    $conexion->close();
    exit;
}
$stmtCheck->close();

// --- 4. INSERTAR SI NO EXISTE ---
$sqlInsert = "INSERT INTO numero (numeroGanadorcol, idturnos, fecha) VALUES (?, ?, ?)";
$stmtInsert = $conexion->prepare($sqlInsert);
$stmtInsert->bind_param("iis", $numero, $idturno, $fecha);

if ($stmtInsert->execute()) {
    echo json_encode(["status" => "success", "message" => "Número ganador registrado con éxito"]);
} else {
    echo json_encode(["status" => "error", "message" => "Error al registrar: " . $conexion->error]);
}

$stmtInsert->close();
$conexion->close();
?>
