<?php
include 'conexion.php';
$input = json_decode(file_get_contents('php://input'), true);

$idUsuario = $input['id_usuario'];
$listaVentas = $input['ventas']; // Array de objetos {numero, monto}
$horaActual = date('H:i:s');
$fechaHoy = date('Y-m-d H:i:s');

// 1. Validar Turno
$sqlTurno = "SELECT id FROM turnos WHERE '$horaActual' BETWEEN hora_desde AND hora_hasta LIMIT 1";
$resTurno = $conn->query($sqlTurno);

if ($resTurno->num_rows == 0) {
    echo json_encode(array("status" => "error", "message" => "Fuera de horario de venta"));
    exit();
}

$filaTurno = $resTurno->fetch_assoc();
$idTurno = $filaTurno['id'];
$resultados = array();

// 2. Insertar Ventas
foreach ($listaVentas as $venta) {
    $num = $venta['numero'];
    $precio = $venta['monto'];
    
    // Insertamos primero para obtener el ID
    $stmt = $conn->prepare("INSERT INTO ventas (id_usuario, num_venta, monto, fecha_venta, id_turno) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("isdsi", $idUsuario, $num, $precio, $fechaHoy, $idTurno);
    
    if($stmt->execute()){
        $idVenta = $stmt->insert_id;
        $codigo = "S" . $idTurno . "-" . $idVenta;
        
        // Actualizamos con el código formateado
        $conn->query("UPDATE ventas SET codigo_generado='$codigo' WHERE id=$idVenta");
        
        $resultados[] = array(
            "codigo" => $codigo,
            "numero" => $num,
            "valor" => $precio,
            "fecha" => date("d M Y h:i:s A", strtotime($fechaHoy)),
            "operador" => "Operador" // Aquí deberías buscar el nombre real del user
        );
    }
}

echo json_encode(array("status" => "success", "data" => $resultados));
?>


