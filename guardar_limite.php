<?php
header("Content-Type: application/json; charset=UTF-8");

$host = "gateway01.us-east-1.prod.aws.tidbcloud.com";$user = "4Asq3bxQtZ3iP3r.root";
$pass = "Kt7JQCCjn0CTWYAx";
$db   = "test";
$port = 4000;

$conexion = mysqli_init();
$ca_cert = "/etc/ssl/certs/ca-certificates.crt"; 
mysqli_ssl_set($conexion, NULL, NULL, $ca_cert, NULL, NULL);
$resultado = @mysqli_real_connect($conexion, $host, $user, $pass, $db, $port, NULL, MYSQLI_CLIENT_SSL);

if (!$resultado) die(json_encode(["status" => "error", "message" => "Fallo conexión"]));

// Recibir por POST
$idusuario = isset($_POST['idusuario']) ? intval($_POST['idusuario']) : 0;
$numero    = isset($_POST['numero']) ? $_POST['numero'] : ''; // 'ALL' o el número (05)
$cantidad  = isset($_POST['cantidad']) ? intval($_POST['cantidad']) : 0;
$fecha     = date("Y-m-d");

if ($idusuario > 0 && $cantidad > 0) {
    $sql = "INSERT INTO limite (idusuario, numero, cantidad, fecha) VALUES ($idusuario, '$numero', $cantidad, '$fecha')";
    if ($conexion->query($sql)) {
        echo json_encode(["status" => "success", "message" => "Límite guardado"]);
    } else {
        echo json_encode(["status" => "error", "message" => $conexion->error]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Datos incompletos"]);
}

$conexion->close();
?>
