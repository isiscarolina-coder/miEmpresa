<?php
header("Content-Type: application/json; charset=UTF-8");

// Configuración de conexión (TiDB Cloud con SSL)
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
$fechaDesde = isset($_GET['fechaDesde']) ? $_GET['fechaDesde'] : ''; // Formato YYYY-MM-DD
$fechaHasta = isset($_GET['fechaHasta']) ? $_GET['fechaHasta'] : '';
$idTurno1   = isset($_GET['idTurno1']) ? intval($_GET['idTurno1']) : 0;
$idTurno2   = isset($_GET['idTurno2']) ? intval($_GET['idTurno2']) : 0;
$idOperador = isset($_GET['idOperador']) ? intval($_GET['idOperador']) : 0;
$idAdmin    = isset($_GET['idAdmin']) ? intval($_GET['idAdmin']) : 0;

// 1. Construir la cláusula WHERE básica
$where = "WHERE v.fecha_venta BETWEEN '$fechaDesde' AND '$fechaHasta'";
$where .= " AND v.idturno BETWEEN $idTurno1 AND $idTurno2";

// 2. Filtrar por operador si se proporciona, si no, mostrar todos del admin
if ($idOperador > 0) {
    $where .= " AND v.idusuario = $idOperador";
} else {
    // Si no hay operador, filtramos por los que pertenecen a este administrador
    $where .= " AND u.idempresario = $idAdmin";
}

// 3. Consulta Principal
// Agrupamos por fecha, turno y usuario para obtener los totales por "bloque"
$sql = "SELECT 
    v.fecha_venta,
    v.idturno,
    t.idturnos as nombre_turno,
    v.idusuario,
    u.usdUsuario as nombre_operador,
    SUM(v.monto) as ventas_totales,
    r.numeroGanadorcol as numero_ganador,
    COALESCE(neg.comision, 0) as porcentaje_comision,
    -- Calculamos el monto ganado
    SUM(CASE 
    -- Forzamos a que ambos sean tratados como texto o número para evitar fallos de formato
    WHEN CAST(v.numVenta AS UNSIGNED) = CAST(r.numeroGanadorcol AS UNSIGNED) 
    THEN (v.monto * COALESCE(neg.multiplicador, 0)) 
    ELSE 0 END) as monto_ganador
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
    ORDER BY v.fecha_venta, v.idturno ASC";

$res = $conexion->query($sql);
$data = [];

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $ventas = floatval($row['ventas_totales']);
        $porcentaje = floatval($row['porcentaje_comision']);
        $ganado = floatval($row['monto_ganador']);
        
        // Calculamos la comisión monetaria
        $comision_monto = ($ventas * $porcentaje) / 100;
        
        // Calculamos Pérdida o Ganancia (Ventas - Comisión - Premios Pagados)
        $utilidad = $ventas - $comision_monto - $ganado;

        $data[] = [
            "numero_ganador" => $row['numero_ganador'] ?? "N/A",
            "turno" => $row['nombre_turno'],
            "fecha" => date("d/m/Y", strtotime($row['fecha_venta'])), // Formato solicitado
            "operador" => $row['nombre_operador'],
            "ventas_totales" => number_format($ventas, 2, '.', ''),
            "comision" => number_format($comision_monto, 2, '.', ''),
            "monto_ganador" => number_format($ganado, 2, '.', ''),
            "utilidad" => number_format($utilidad, 2, '.', '')
        ];
    }

    echo json_encode([
        "status" => "success",
        "data" => $data,
        "totales_globales" => calcularTotales($data)
    ]);
} else {
    echo json_encode(["status" => "error", "message" => $conexion->error]);
}

// Función auxiliar para los campos de arriba (Ventas Totales, Comisión, etc)
function calcularTotales($items) {
    $vt = 0; $cm = 0; $mg = 0; $ut = 0;
    foreach ($items as $i) {
        $vt += $i['ventas_totales'];
        $cm += $i['comision'];
        $mg += $i['monto_ganador'];
        $ut += $i['utilidad'];
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
