<?php
header("Content-Type: application/json; charset=UTF-8");

// --- 1. CONFIGURACIÓN DE CONEXIÓN (Mantenemos tu configuración actual) ---
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
    die(json_encode(["status" => "error", "message" => "Fallo conexión BD: " . mysqli_connect_error()]));
}

// --- 2. CAPTURA DE DATOS JSON (Desde Android envías un JSON por POST) ---
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Extraemos las variables según los nombres que pusiste en el Kotlin (JSONObject().apply)
$idGanador = $data['idnumeroGanador'] ?? null;
$numero    = $data['numeroGanadorcol'] ?? null;
$fecha     = $data['fecha'] ?? null;
$idturno   = $data['idturnos'] ?? null;

// Validación básica
if (empty($idGanador) || empty($numero) || empty($fecha) || empty($idturno)) {
    die(json_encode(["status" => "error", "message" => "Datos incompletos para actualizar"]));
}

// --- 3. VERIFICAR DUPLICADOS (Opcional) ---
// Evita que al editar cambies el registro a una fecha/turno que ya tenga un número asignado
$sqlCheck = "SELECT idnumeroGanador FROM numero WHERE fecha = ? AND idturnos = ? AND idnumeroGanador != ? LIMIT 1";
$stmtCheck = $conexion->prepare($sqlCheck);
$stmtCheck->bind_param("sii", $fecha, $idturno, $idGanador);
$stmtCheck->execute();
$resCheck = $stmtCheck->get_result();

if ($resCheck->num_rows > 0) {
    echo json_encode(["status" => "error", "message" => "Ya existe otro registro con esta fecha y turno"]);
    $stmtCheck->close();
    $conexion->close();
    exit;
}
$stmtCheck->close();

// --- 4. ACTUALIZAR (UPDATE) ---
$sqlUpdate = "UPDATE numero SET numeroGanadorcol = ?, idturnos = ?, fecha = ? WHERE idnumeroGanador = ?";
$stmtUpdate = $conexion->prepare($sqlUpdate);

// "iisi" -> numero(int), idturno(int), fecha(string), idGanador(int)
$stmtUpdate->bind_param("iisi", $numero, $idturno, $fecha, $idGanador);

if ($stmtUpdate->execute()) {
    // Verificamos si realmente se cambió algo o si los datos eran los mismos
    if ($stmtUpdate->affected_rows >= 0) {
        echo json_encode(["status" => "success", "message" => "Actualizado con éxito"]);
    } else {
        echo json_encode(["status" => "error", "message" => "No se realizaron cambios"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Error al actualizar: " . $stmtUpdate->error]);
}

$stmtUpdate->close();
$conexion->close();
?>