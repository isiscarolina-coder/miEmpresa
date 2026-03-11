<?php
header("Content-Type: application/json; charset=UTF-8");

$host = "gateway01.us-east-1.prod.aws.tidbcloud.com";
$user = "4Asq3bxQtZ3iP3r.root";
$pass = "Kt7JQCCjn0CTWYAx";
$db    = "test";
$port = 4000;

$conexion = mysqli_init();
$ca_cert = "/etc/ssl/certs/ca-certificates.crt"; 
mysqli_ssl_set($conexion, NULL, NULL, $ca_cert, NULL, NULL);

$resultado = @mysqli_real_connect($conexion, $host, $user, $pass, $db, $port, NULL, MYSQLI_CLIENT_SSL);

if (!$resultado) {
    die(json_encode(["status" => "error", "message" => "Fallo conexión: " . mysqli_connect_error()]));
}

// Captura de datos
$idusuario = isset($_POST['idusuario']) ? (int)$_POST['idusuario'] : 0;
$numero    = isset($_POST['numero']) ? $_POST['numero'] : '';
$cantidad  = isset($_POST['cantidad']) ? (int)$_POST['cantidad'] : 0;
$fecha     = date("Y-m-d");

// Validamos que el ID de usuario y la cantidad sean coherentes
if ($idusuario > 0 && $cantidad > 0 && !empty($numero)) {
    
    $sql = "INSERT INTO limite (idusuario, numero, cantLimited, fecha) 
            VALUES (?, ?, ?, ?) 
            ON DUPLICATE KEY UPDATE cantLimited = VALUES(cantLimited), fecha = VALUES(fecha)";

    $stmt = $conexion->prepare($sql);

    // --- CASO ESPECIAL: Si el número es "-1" ---
    if ($numero === "-1") {
        for ($i = 0; $i <= 99; $i++) {
            // Formateamos a dos dígitos: 00, 01, 02... 99
            $num_formateado = str_pad($i, 2, "0", STR_PAD_LEFT);
            $stmt->bind_param("isis", $idusuario, $num_formateado, $cantidad, $fecha);
            $stmt->execute();
        }
        echo json_encode(["status" => "success", "message" => "Límite de $cantidad aplicado a los números del 00 al 99"]);
    } 
    // --- CASO NORMAL: Un solo número ---
    else {
        $stmt->bind_param("isis", $idusuario, $numero, $cantidad, $fecha);
        if ($stmt->execute()) {
            $accion = ($conexion->affected_rows == 2) ? "actualizado" : "guardado";
            echo json_encode(["status" => "success", "message" => "Límite para el número $numero $accion correctamente"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Error en DB: " . $stmt->error]);
        }
    }
    
    $stmt->close();
} else {
    echo json_encode([
        "status" => "error", 
        "message" => "Datos incompletos o cantidad inválida",
        "debug" => ["idusuario" => $idusuario, "numero" => $numero, "cantidad" => $cantidad]
    ]);
}

$conexion->close();
?>
