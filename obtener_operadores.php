<?php
header("Content-Type: application/json; charset=UTF-8");
//error_reporting(0); // Desactivar errores visibles para no ensuciar el JSON

// DATOS DE CONEXIÓN EXPLÍCITOS
$host = "gateway01.us-east-1.prod.aws.tidbcloud.com";
$user = "4Asq3bxQtZ3iP3r.root";
$pass = "Kt7JQCCjn0CTWYAx";
$db   = "test";
$port = 4000;

// Inicializar conexión con SSL (Requerido por TiDB)
$conexion = mysqli_init();
$ca_cert = "/etc/ssl/certs/ca-certificates.crt";
mysqli_ssl_set($conexion, NULL, NULL, $ca_cert, NULL, NULL);

$resultado = @mysqli_real_connect($conexion, $host, $user, $pass, $db, $port, NULL, MYSQLI_CLIENT_SSL);

if (!$resultado) {
    echo json_encode(["status" => "error", "message" => "Fallo de conexión: " . mysqli_connect_error()]);
    exit;
}

mysqli_set_charset($conexion, "utf8");

// Consulta para obtener operadores
$sql = "SELECT idusuario, usdUsuario, usdPassword, usdEstado FROM usuarios WHERE operador = 1";
$res = $conexion->query($sql);

$operadores = array();

if ($res) {
    while($row = $res->fetch_assoc()) {
        $operadores[] = $row;
    }
    echo json_encode(["status" => "success", "data" => $operadores]);
} else {
    echo json_encode(["status" => "error", "message" => "Error en la consulta: " . $conexion->error]);
}

$conexion->close();
?>


