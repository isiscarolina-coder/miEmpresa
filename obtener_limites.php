<?php
header("Content-Type: application/json; charset=UTF-8");

// Configuración de conexión (TiDB Cloud con SSL)
$host = "gateway01.us-east-1.prod.aws.tidbcloud.com";
$user = "4Asq3bxQtZ3iP3r.root";
$pass = "Kt7JQCCjn0CTWYAx";
$db   = "test";
$port = 4000;

$conexion = mysqli_init();
// Ruta del certificado SSL (Asegúrate de que sea la correcta en tu servidor Koyeb/Hosting)
$ca_cert = "/etc/ssl/certs/ca-certificates.crt"; 
mysqli_ssl_set($conexion, NULL, NULL, $ca_cert, NULL, NULL);

$resultado = @mysqli_real_connect($conexion, $host, $user, $pass, $db, $port, NULL, MYSQLI_CLIENT_SSL);

if (!$resultado) {
    die(json_encode(["status" => "error", "message" => "Fallo conexión BD"]));
}

// Recibir el ID del Administrador (Empresario)
$idAdmin = isset($_GET['idAdmin']) ? intval($_GET['idAdmin']) : 0;

if ($idAdmin <= 0) {
    echo json_encode(["status" => "error", "message" => "ID de administrador no válido"]);
    exit();
}

/**
 * Consulta:
 * Seleccionamos los datos de la tabla 'limite' (l)
 * Hacemos un INNER JOIN con 'usuario' (u) para obtener el nombre del operador (usdUsuario)
 * Filtramos por idempresario para que el admin solo vea los límites de sus propios operadores
 */
$sql = "SELECT 
            l.idlimite, 
            l.idusuario, 
            u.usdUsuario, 
            l.numero, 
            l.cantLimited, 
            l.fecha 
        FROM limite l 
        INNER JOIN usuario u ON l.idusuario = u.idusuario 
        WHERE u.idempresario = $idAdmin 
        ORDER BY l.fecha DESC, l.idlimite DESC";

$res = $conexion->query($sql);
$data = [];

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $data[] = [
            "idlimite"   => intval($row['idlimite']),
            "idusuario"  => intval($row['idusuario']),
            "usdUsuario" => $row['usdUsuario'], // Nombre del operador
            "numero"     => $row['numero'],     // Número limitado
            "cantidad"   => intval($row['cantLimited']), // Monto límite
            "fecha"      => date("d M Y", strtotime($row['fecha'])) // Formato: 02 Mar 2026
        ];
    }

    echo json_encode([
        "status" => "success", 
        "data"   => $data
    ]);
} else {
    echo json_encode([
        "status"  => "error", 
        "message" => "Error en la consulta: " . $conexion->error
    ]);
}

$conexion->close();
?>
