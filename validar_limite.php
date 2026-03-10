<?php
header("Content-Type: application/json; charset=UTF-8");

// ... (Incluir tu código de conexión SSL aquí) ...

$idusuario = isset($_GET['idusuario']) ? intval($_GET['idusuario']) : 0;
$numero    = isset($_GET['numero']) ? $_GET['numero'] : '';
$monto_solicitado = isset($_GET['monto']) ? intval($_GET['monto']) : 0;

// 1. Obtener el turno activo actual
$sql_turno = "SELECT idturnos FROM turnos WHERE activo = 1 LIMIT 1";
$res_turno = $conexion->query($sql_turno);
$row_turno = $res_turno->fetch_assoc();
$idturno = $row_turno['idturnos'] ?? 0;

if ($idturno == 0) {
    die(json_encode(["status" => "error", "message" => "No hay turno activo"]));
}

// 2. Buscar si existe un límite para este usuario y número (o 'ALL')
$sql_limite = "SELECT cantidad FROM limite 
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
