<?php
header("Content-Type: application/json; charset=UTF-8");

$host = "gateway01.us-east-1.prod.aws.tidbcloud.com";
$user = "4Asq3bxQtZ3iP3r.root";
$pass = "Kt7JQCCjn0CTWYAx";
$db   = "test";
$port = 4000;

// 1. Inicializar el objeto
$conexion = mysqli_init();
if (!$conexion) {
    die(json_encode(["status" => "error", "message" => "Fallo al inicializar mysqli"]));
}

// 2. Configurar SSL
$ca_cert = "/etc/ssl/certs/ca-certificates.crt"; 
mysqli_ssl_set($conexion, NULL, NULL, $ca_cert, NULL, NULL);

// 3. Intentar la conexión (eliminamos el @ para poder ver errores si fallara)
$resultado = mysqli_real_connect($conexion, $host, $user, $pass, $db, $port, NULL, MYSQLI_CLIENT_SSL);

if (!$resultado) {
    die(json_encode([
        "status" => "error", 
        "message" => "Fallo conexión: " . mysqli_connect_error()
    ]));
}

// 4. Lógica de eliminación
$idAdmin = isset($_GET['idAdmin']) ? intval($_GET['idAdmin']) : 0;

if ($idAdmin > 0) {
    // IMPORTANTE: TiDB y MySQL a veces requieren una sintaxis específica para DELETE con JOIN
    // Usamos sentencias preparadas para evitar errores de inicialización y seguridad
    $sql = "DELETE l FROM limite l 
            INNER JOIN usuario u ON l.idusuario = u.idusuario 
            WHERE u.idempresario = ?";
            
    if ($stmt = $conexion->prepare($sql)) {
        $stmt->bind_param("i", $idAdmin);
        
        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Límites eliminados"]);
        } else {
            echo json_encode(["status" => "error", "message" => $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(["status" => "error", "message" => "Error en la consulta: " . $conexion->error]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "ID de Admin no proporcionado"]);
}

$conexion->close();
?>
