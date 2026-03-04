<?php
header('Content-Type: application/json');

$host = "gateway01.us-east-1.prod.aws.tidbcloud.com";
$user = "4Asq3bxQtZ3iP3r.root";
$pass = "Kt7JQCCjn0CTWYAx";
$db   = "test";
$port = 4000;

// 1. Inicializar mysqli sin conectar todavía
$conn = mysqli_init();

// 2. Configurar SSL (TiDB requiere que este flag esté activo)
// No necesitas certificados locales para el Tier Serverless, solo activar el modo SSL
$conn->ssl_set(NULL, NULL, NULL, NULL, NULL);

// 3. Realizar la conexión con el flag MYSQLI_CLIENT_SSL
$success = $conn->real_connect($host, $user, $pass, $db, $port, NULL, MYSQLI_CLIENT_SSL);

if (!$success) {
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
