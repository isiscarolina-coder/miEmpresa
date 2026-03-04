<?php
header('Content-Type: application/json');

$host = "gateway01.us-east-1.prod.aws.tidbcloud.com";
$user = "4Asq3bxQtZ3iP3r.root";
$pass = "Kt7JQCCjn0CTWYAx";
$db   = "test";
$port = 4000;

// 1. Inicializar el objeto mysqli sin conectar
$conn = mysqli_init();

if (!$conn) {
    die(json_encode(["status" => "error", "message" => "Fallo al inicializar mysqli"]));
}

// 2. Forzar el uso de SSL. 
// Para TiDB Cloud Serverless, basta con pasar los parámetros vacíos para activar el cifrado.
$conn->ssl_set(NULL, NULL, NULL, NULL, NULL);

// 3. Establecer la conexión usando el flag MYSQLI_CLIENT_SSL
$connected = $conn->real_connect($host, $user, $pass, $db, $port, NULL, MYSQLI_CLIENT_SSL);

if (!$connected) {
    echo json_encode(["status" => "error", "message" => "Conexión fallida: " . $conn->connect_error]);
    exit;
}

// --- El resto de tu lógica permanece igual ---

$idEmpresario = isset($_GET['idEmpresario']) ? intval($_GET['idEmpresario']) : 0;

if ($idEmpresario <= 0) {
    echo json_encode(["status" => "error", "message" => "ID de empresario no válido"]);
    exit;
}

$sql = "SELECT 
            n.idusuario, 
            u.usdUsuario as nombreOperador, 
            n.comision, 
            n.multiplicador 
        FROM negociacion n
        INNER JOIN usuario u ON n.idusuario = u.idusuario
        WHERE u.idempresario = ?";

$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("i", $idEmpresario);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $row['idusuario'] = intval($row['idusuario']);
        $row['comision'] = intval($row['comision']);
        $row['multiplicador'] = intval($row['multiplicador']);
        $data[] = $row;
    }

    echo json_encode([
        "status" => "success",
        "data" => $data,
        "message" => empty($data) ? "No hay negociaciones configuradas" : ""
    ]);

    $stmt->close();
} else {
    echo json_encode(["status" => "error", "message" => "Error en SQL: " . $conn->error]);
}

$conn->close();
?>
