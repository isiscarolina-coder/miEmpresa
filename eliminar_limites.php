<?php
header("Content-Type: application/json; charset=UTF-8");
// ... (misma conexión SSL anterior) ...

$idAdmin = isset($_GET['idAdmin']) ? intval($_GET['idAdmin']) : 0;

if ($idAdmin > 0) {
    // Eliminamos los límites de los usuarios que pertenecen a este empresario
    $sql = "DELETE l FROM limite l 
            INNER JOIN usuario u ON l.idusuario = u.idusuario 
            WHERE u.idempresario = $idAdmin";
            
    if ($conexion->query($sql)) {
        echo json_encode(["status" => "success", "message" => "Límites eliminados"]);
    } else {
        echo json_encode(["status" => "error", "message" => $conexion->error]);
    }
}
$conexion->close();
?>

