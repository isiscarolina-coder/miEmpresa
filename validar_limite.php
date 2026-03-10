<?php
header("Content-Type: application/json; charset=UTF-8");

// 1. FORZAR HORA DE HONDURAS (Independiente de donde esté el teléfono)
date_default_timezone_set('America/Tegucigalpa');

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

$idusuario = isset($_GET['idusuario']) ? intval($_GET['idusuario']) : 0;
$numero    = isset($_GET['numero']) ? $_GET['numero'] : '';
$monto_solicitado = isset($_GET['monto']) ? intval($_GET['monto']) : 0;
$hora_honduras = date('H:i:s'); 
$idTurno = 0;

// 1. Obtener el turno activo actual
$resTurnos = $conexion->query("SELECT idturnos, desde, hasta FROM turnos");
if ($resTurnos) {
    while($t = $resTurnos->fetch_assoc()) {
        $desde = $t['desde'];
        $hasta = $t['hasta'];

        if ($desde <= $hasta) {
            if ($hora_honduras >= $desde && $hora_honduras <= $hasta) {
                $idTurno = $t['idturnos'];
                break;
            }
        } else { // Caso turno medianoche
            if ($hora_honduras >= $desde || $hora_honduras <= $hasta) {
                $idTurno = $t['idturnos'];
                break;
            }
        }
    }
}

if ($idTurno == 0) {
    echo json_encode(["status" => "error", "message" => "No hay turno activo en Honduras para las: $hora_honduras"]);
    exit;
}

// 2. Buscar si existe un límite para este usuario y número (o 'ALL')
$sql_limite = "SELECT cantLimited FROM limite 
               WHERE idusuario = $idusuario 
               AND (numero = '$numero' OR numero = 'ALL') 
               ORDER BY (numero = '$numero') DESC LIMIT 1";
$res_limite = $conexion->query($sql_limite);

if ($res_limite->num_rows > 0) {
    $row_limite = $res_limite->fetch_assoc();
    $cantLimite = $row_limite['cantidad'];

    // 3. Sumar lo que ya se ha vendido de ese número en este turno y día
    $fecha_hoy = date("Y-m-d");
    $sql_ventas = "SELECT SUM(monto) as total_vendido FROM ventas 
                   WHERE idusuario = $idusuario 
                   AND numVenta = '$numero' 
                   AND idturno = $idturno 
                   AND fecha_venta = '$fecha_hoy'";
    $res_ventas = $conexion->query($sql_ventas);
    $row_ventas = $res_ventas->fetch_assoc();
    $total_en_db = intval($row_ventas['total_vendido'] ?? 0);

    // 4. Validación Final
    if (($total_en_db + $monto_solicitado) > $cantLimite) {
        echo json_encode(["status" => "error", "message" => "Valor sobregirado. Límite: $cantLimite"]);
    } else {
        echo json_encode(["status" => "success", "message" => "Monto permitido"]);
    }
} else {
    // Si no hay límite establecido, se permite cualquier monto
    echo json_encode(["status" => "success", "message" => "Sin restricciones"]);
}

$conexion->close();
?>








