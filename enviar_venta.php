<?php
// 1. Impedir que errores de PHP ensucien la salida JSON
header("Content-Type: application/json; charset=UTF-8");

// 2. Configurar Zona Horaria
date_default_timezone_set('America/Tegucigalpa');

// 3. Conexión a TiDB Cloud
$host = "gateway01.us-east-1.prod.aws.tidbcloud.com";
$user = "4Asq3bxQtZ3iP3r.root";
$pass = "Kt7JQCCjn0CTWYAx";
$db   = "test";
$port = 4000;

$conexion = mysqli_init();
$ca_cert = "/etc/ssl/certs/ca-certificates.crt";

// Verificar si el certificado existe antes de intentar usarlo
if (!file_exists($ca_cert)) {
    die(json_encode(["status" => "error", "message" => "Certificado SSL no encontrado en el servidor"]));
}

mysqli_ssl_set($conexion, NULL, NULL, $ca_cert, NULL, NULL);

// Conexión real con supresión de warnings (@)
$resultado = @mysqli_real_connect($conexion, $host, $user, $pass, $db, $port, NULL, MYSQLI_CLIENT_SSL);

if (!$resultado) {
    die(json_encode(["status" => "error", "message" => "Error de conexión: " . mysqli_connect_error()]));
}

// IMPORTANTE: Establecer charset para evitar errores de caracteres
mysqli_set_charset($conexion, "utf8");

// 4. Leer JSON de Android
$json = file_get_contents('php://input');
$datos = json_decode($json, true);

if (!$datos || !isset($datos['ventas'])) {
    die(json_encode(["status" => "error", "message" => "No se recibieron datos o el formato es incorrecto"]));
}

$idUsuario = $datos['idusuario'];
$fechaVentaCompleta = $datos['fecha_venta']; 
$horaActual = date("H:i:s", strtotime($fechaVentaCompleta));

// 5. Lógica de Turno
$idTurnoIdentificado = 0;
$sqlTurnos = "SELECT idturno, desde, hasta FROM turnos";
$resTurnos = $conexion->query($sqlTurnos);

if ($resTurnos) {
    while($t = $resTurnos->fetch_assoc()) {
        if ($horaActual >= $t['desde'] && $horaActual <= $t['hasta']) {
            $idTurnoIdentificado = $t['idturno'];
            break;
        }
    }
}

if ($idTurnoIdentificado == 0) {
    die(json_encode(["status" => "error", "message" => "No existe un turno activo para la hora: $horaActual"]));
}

// 6. Inserción de Ventas (Usando Prepared Statement)
$respuestas = [];
// Corregir nombres de columnas según tu DB (idusuario, numVenta, monto, Idturno, fecha_venta)
$stmt = $conexion->prepare("INSERT INTO ventas (idusuario, numVenta, monto, Idturno, fecha_venta) VALUES (?, ?, ?, ?, ?)");

if ($stmt) {
    foreach ($datos['ventas'] as $venta) {
        $num = $venta['numero'];
        $mon = (int)$venta['monto'];
        
        $stmt->bind_param("isiis", $idUsuario, $num, $mon, $idTurnoIdentificado, $fechaVentaCompleta);
        
        if ($stmt->execute()) {
            $respuestas[] = [
                "codigo" => (string)$conexion->insert_id,
                "numero" => $num,
                "valor"  => $mon,
                "fecha"  => $fechaVentaCompleta,
                "operador" => (string)$idUsuario
            ];
        }
    }
    $stmt->close();
}

// 7. Respuesta Final
echo json_encode([
    "status" => "success",
    "message" => "Venta registrada en Turno $idTurnoIdentificado",
    "data" => $respuestas
]);

$conexion->close();
?>



