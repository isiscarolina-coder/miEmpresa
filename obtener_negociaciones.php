<?php
header('Content-Type: application/json');

// Configuración de la base de datos
$host = "gateway01.us-east-1.prod.aws.tidbcloud.com";
$user = "4Asq3bxQtZ3iP3r.root";
$pass = "Kt7JQCCjn0CTWYAx";
$db   = "test";
$port = 4000;

// Agregado el puerto a la conexión para TiDB
$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Conexión fallida: " . $conn->connect_error]);
    exit;
}

// Obtener el ID del empresario
$idEmpresario = isset($_GET['idEmpresario']) ? intval($_GET['idEmpresario']) : 0;

if ($idEmpresario <= 0) {
    echo json_encode(["status" => "error", "message" => "ID de empresario no válido"]);
    exit;
}

// Consulta SQL corregida (sin espacios raros)
$sql = "SELECT 
            n.idusuario, 
            u.usdUsuario as nombreOperador, 
            n.comision, 
            n.multiplicador 
        FROM negociacion n
        INNER JOIN usuario u ON n.idusuario = u.idusuario
        WHERE u.idempresario = ?";

if ($stmt = $conn->prepare($sql)) {
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
    echo json_encode(["status" => "error", "message" => "Error en la consulta: " . $conn->error]);
}

$conn->close();
?>
