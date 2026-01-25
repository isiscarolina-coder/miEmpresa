<?php
header("Content-Type: application/json; charset=UTF-8");
// Silenciar errores para no ensuciar el JSON

// DATOS DE CONEXIÓN
$host = "gateway01.us-east-1.prod.aws.tidbcloud.com";
$user = "4Asq3bxQtZ3iP3r.root";
$pass = "Kt7JQCCjn0CTWYAx";
$db   = "test";
$port = 4000;

// 1. FIRMA DE AUTENTICIDAD SSL
$conexion = mysqli_init();
$ca_cert = "/etc/ssl/certs/ca-certificates.crt"; // Ruta estándar en Docker/Koyeb

if (!file_exists($ca_cert)) {
    echo json_encode(["status" => "error", "message" => "Certificado SSL no encontrado en el servidor"]);
    exit;
}

mysqli_ssl_set($conexion, NULL, NULL, $ca_cert, NULL, NULL);

// 2. CONECTAR
$resultado = @mysqli_real_connect($conexion, $host, $user, $pass, $db, $port, NULL, MYSQLI_CLIENT_SSL);

if (!$resultado) {
    echo json_encode(["status" => "error", "message" => "Fallo de conexión a BD: " . mysqli_connect_error()]);
    exit;
}

mysqli_set_charset($conexion, "utf8");

// 3. CONSULTA (Ajustada a tu tabla 'usuario')
$sql = "SELECT idusuario, usdUsuario, usdEstado FROM usuario WHERE operador = 1";
$res = $conexion->query($sql);

$operadores = array();

if ($res) {
    while($row = $res->fetch_assoc()) {
        // Aseguramos que los tipos de datos sean correctos para Android
        $row['idusuario'] = (int)$row['idusuario'];
        $row['usdEstado'] = (int)$row['usdEstado'];
        $operadores[] = $row;
    }
    echo json_encode(["status" => "success", "data" => $operadores]);
} else {
    echo json_encode(["status" => "error", "message" => "Error en la consulta: " . $conexion->error]);
}

$conexion->close();
?>







