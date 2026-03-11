<?php
header("Content-Type: application/json; charset=UTF-8");

// Configuración de conexión
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
    die(json_encode(["status" => "error", "message" => "Fallo conexión BD"]));
}

// Recibir parámetros
$fechaDesde  = isset($_GET['fechaDesde']) ? $_GET['fechaDesde'] : '';
$fechaHasta  = isset($_GET['fechaHasta']) ? $_GET['fechaHasta'] : '';
$idOperador  = isset($_GET['idOperador']) ? intval($_GET['idOperador']) : 0;
$idAdmin     = isset($_GET['idAdmin']) ? intval($_GET['idAdmin']) : 0;

// Capturamos la orientación y validamos
$orientacionInput = isset($_GET['orientacion']) ? strtoupper(trim($_GET['orientacion'])) : '';

// 1. Cláusula WHERE
$where = "WHERE v.fecha_venta BETWEEN '$fechaDesde' AND '$fechaHasta'";

if ($idOperador > 0) {
    $where .= " AND v.idusuario = $idOperador";
} else {
    $where .= " AND u.idempresario = $idAdmin";
}

// 2. Lógica de Ordenamiento: Solo si se recibe ASC o DESC
$orderBy = "";
if ($orientacionInput === 'ASC' || $orientacionInput === 'DESC') {
    $orderBy = " ORDER BY ventas_totales $orientacionInput";
}

// 3. Consulta Principal
// Agrupamos por FECHA, TURNO, OPERADOR y NÚMERO GANADOR
$sql = "SELECT 
    v.fecha_venta,
    v.idturno,
    t.idturnos as nombre_turno,
    v.idusuario,
    u.usdUsuario as nombre_operador,
    r.numeroGanadorcol as numero_ganador,
    SUM(v.monto) as ventas_totales,
    COALESCE(neg.comision, 0) as porcentaje_comision,
    -- Sumamos los premios basados en el multiplicador de la negociación
    SUM(CASE 
        WHEN v.numVenta = r.numeroGanadorcol THEN (v.monto * COALESCE(neg.multiplicador, 0)) 
        ELSE 0 
    END) as monto_ganador
FROM ventas v
INNER JOIN usuario u ON v.idusuario = u.idusuario
INNER JOIN turnos t ON v.idturno = t.idturnos
LEFT JOIN negociacion neg ON v.idusuario = neg.idusuario
LEFT JOIN numero r ON r.fecha = v.fecha_venta AND r.idturnos = v.idturno
$where
GROUP BY 
    v.fecha_venta,
    v.idturno, 
    t.idturnos, 
    v.idusuario,
    u.usdUsuario,
    r.numeroGanadorcol,
    neg.comision,
    neg.multiplicador
$orderBy";

$res = $conexion->query($sql);
$data = [];

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $ventas = floatval($row['ventas_totales']);
        $porcentaje = floatval($row['porcentaje_comision']);
        $ganado = floatval($row['monto_ganador']);
        
        $comision_monto = ($ventas * $porcentaje) / 100;
        $utilidad = $ventas - $comision_monto - $ganado;

        $data[] = [
            "fecha" => date("d/m/Y", strtotime($row['fecha_venta'])),
            "operador" => $row['nombre_operador'],
            "turno" => $row['nombre_turno'],
            "numero_ganador" => $row['numero_ganador'] ?? "N/A",
            "ventas_totales" => number_format($ventas, 2, '.', ''),
            "comision" => number_format($comision_monto, 2, '.', ''),
            "monto_ganador" => number_format($ganado, 2, '.', ''),
            "utilidad" => number_format($utilidad, 2, '.', '')
        ];
    }

    echo json_encode([
        "status" => "success",
        "orden_aplicado" => $orientacionInput ?: "ninguno (natural)",
        "data" => $data,
        "totales_globales" => calcularTotales($data)
    ]);
} else {
    echo json_encode(["status" => "error", "message" => $conexion->error]);
}

function calcularTotales($items) {
    $vt = 0; $cm = 0; $mg = 0; $ut = 0;
    foreach ($items as $i) {
        $vt += floatval($i['ventas_totales']);
        $cm += floatval($i['comision']);
        $mg += floatval($i['monto_ganador']);
        $ut += floatval($i['utilidad']);
    }
    return [
        "ventas_totales" => number_format($vt, 2, '.', ''),
        "comision" => number_format($cm, 2, '.', ''),
        "monto_ganador" => number_format($mg, 2, '.', ''),
        "ganancia_neta" => number_format($ut, 2, '.', '')
    ];
}

$conexion->close();
?>

