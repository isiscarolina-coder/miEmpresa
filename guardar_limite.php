<?php
header("Content-Type: application/json; charset=UTF-8");

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
    die(json_encode(["status" => "error", "message" => "Fallo conexión: " . mysqli_connect_error()]));
}

// Captura de datos
$idusuario = isset($_GET['idusuario']) ? (int)$_GET['idusuario'] : 0;
$numero    = isset($_GET['numero']) ? $_GET['numero'] : '';
$cantidad  = isset($_GET['cantidad']) ? (int)$_GET['cantidad'] : 0;
$fecha     = date("Y-m-d");

if ($idusuario > 0 && $cantidad > 0 && !empty($numero)) {
    
    /**
     * Usamos ON DUPLICATE KEY UPDATE:
     * Si el par (idusuario, numero) ya existe, actualiza 'cantLimited' y 'fecha'.
     * Si no existe, inserta el nuevo registro.
     */
    $sql = "INSERT INTO limite (idusuario, numero, cantLimited, fecha) 
            VALUES (?, ?, ?, ?) 
            ON DUPLICATE KEY UPDATE cantLimited = VALUES(cantLimited), fecha = VALUES(fecha)";

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("isis", $idusuario, $numero, $cantidad, $fecha);

    if ($stmt->execute()) {
        // affected_rows será 1 si fue insertado, o 2 si fue actualizado
        $accion = ($conexion->affected_rows == 2) ? "actualizado" : "guardado";
        echo json_encode(["status" => "success", "message" => "Límite $accion correctamente"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Error en DB: " . $stmt->error]);
    }
    
    $stmt->close();
} else {
    echo json_encode([
        "status" => "error", 
        "message" => "Datos incompletos",
        "debug" => ["idusuario" => $idusuario, "cantidad" => $cantidad, "numero" => $numero]
    ]);
}

$conexion->close();
?>
