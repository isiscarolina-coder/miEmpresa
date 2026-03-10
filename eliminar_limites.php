<?php
header("Content-Type: application/json; charset=UTF-8");

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
    die(json_encode(["status" => "error", "message" => "Fallo conexión: " . mysqli_connect_error()]));
}

$idAdmin = isset($_GET['idAdmin']) ? intval($_GET['idAdmin']) : 0;

if ($idAdmin > 0) {
    // Eliminamos los límites de los usuarios que pertenecen a este empresario
    $sql = "DELETE l FROM limite l 
            INNER JOIN usuario u ON l.idusuario = u.idusuario 
            WHERE u.idempresario = $idAdmin";
            
    if ($conexion->query($sql)) {
        echo json_encode(["status" => "success", "message" => "Límites eliminados"]);
    } else {
        echo json_encode(["status" => "error", "message" => $conexion->error]);
    }
}
$conexion->close();
?>

