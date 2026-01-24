<?php
// obtener_operadores.php
include 'conexion.php';
header("Content-Type: application/json; charset=UTF-8");

// Solo obtenemos usuarios que sean operadores (rol = 1)
$sql = "SELECT idusuario, usdUsuario, usdPassword, usdEstado FROM usuario";
$result = $conexion->query($sql);

$operadores = array();

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $operadores[] = $row;
    }
    echo json_encode(array("status" => "success", "data" => $operadores));
} else {
    echo json_encode(array("status" => "error", "message" => "No se encontraron operadores"));
}

$conexion->close();

?>
