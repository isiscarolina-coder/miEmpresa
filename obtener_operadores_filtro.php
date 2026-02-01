<?php
/**
 * obtener_operadores_filtro.php
 * Usado exclusivamente para llenar el dropdown de filtros en el reporte de administrador.
 */
header("Content-Type: application/json; charset=UTF-8");

// Configuración de conexión (TiDB Cloud)
$host = "gateway01.us-east-1.prod.aws.tidbcloud.com";
$user = "4Asq3bxQtZ3iP3r.root";
$pass = "Kt7JQCCjn0CTWYAx";
$db   = "test";
$port = 4000;

$conexion = mysqli_init();
$ca_cert = "/etc/ssl/certs/ca-certificates.crt";
mysqli_ssl_set($conexion, NULL, NULL, $ca_cert, NULL, NULL);

$res = @mysqli_real_connect($conexion, $host, $user, $pass, $db, $port, NULL, MYSQLI_CLIENT_SSL);

if (!$res) {
    echo json_encode(["status" => "error", "message" => "Error conexión BD"]);
    exit;
}

mysqli_set_charset($conexion, "utf8");

// Capturar el ID del administrador desde la URL
$idAdmin = isset($_GET['idEmpresario']) ? (int)$_GET['idEmpresario'] : 0;

if ($idAdmin === 0) {
    echo json_encode(["status" => "error", "message" => "ID de administrador no válido"]);
    exit;
}

// Consulta: Obtenemos solo los operadores creados por este administrador
$sql = "SELECT idusuario, usdUsuario FROM usuario WHERE operador = 1 AND idEmpresario = $idAdmin ORDER BY usdUsuario ASC";
$resultado = $conexion->query($sql);

if ($resultado) {
    $operadores = array();
    while($row = $resultado->fetch_assoc()) {
        $operadores[] = [
            "idusuario"  => $row['idusuario'],
            "usdUsuario" => $row['usdUsuario']
        ];
    }
    echo json_encode(["status" => "success", "data" => $operadores]);
} else {
    echo json_encode(["status" => "error", "message" => $conexion->error]);
}

$conexion->close();
?>
