<?php
header('Content-Type: application/json');

// Configuración de la base de datos
$host = "gateway01.us-east-1.prod.aws.tidbcloud.com";
$user = "4Asq3bxQtZ3iP3r.root";
$pass = "Kt7JQCCjn0CTWYAx";
$db   = "test";
$port = 4000;

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Conexión fallida"]));
}

// Obtener el ID del empresario/admin desde la URL
$idEmpresario = isset($_GET['idEmpresario']) ? intval($_GET['idEmpresario']) : 0;

if ($idEmpresario <= 0) {
    echo json_encode(["status" => "error", "message" => "ID de empresario no válido"]);
    exit;
}

// Consulta para obtener las negociaciones y el nombre del usuario (operador)
// Ajusta los nombres de las tablas y campos según tu base de datos
$sql = "SELECT 
            n.idusuario, 
            u.usdUsuario as nombreOperador, 
            n.comision, 
            n.multiplicador, 
            n.fecha 
        FROM negociaciones n
        INNER JOIN usuarios u ON n.idusuario = u.idusuario
        WHERE u.id_empresario = ? 
        ORDER BY n.fecha DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $idEmpresario);
$stmt->execute();
$result = $stmt->get_result();

$data = [];

while ($row = $result->fetch_assoc()) {
    // Aseguramos que los tipos de datos coincidan con lo que espera Kotlin (Int para comision/multiplicador)
    $row['idusuario'] = intval($row['idusuario']);
    $row['comision'] = intval($row['comision']);
    $row['multiplicador'] = intval($row['multiplicador']);
    $data[] = $row;
}

if (count($data) > 0) {
    echo json_encode([
        "status" => "success",
        "data" => $data
    ]);
} else {
    echo json_encode([
        "status" => "success",
        "message" => "No hay negociaciones configuradas",
        "data" => []
    ]);
}

$stmt->close();
$conn->close();
?>
