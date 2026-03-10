<?php
header("Content-Type: application/json; charset=UTF-8");

// Configuración de conexión
$host = "gateway01.us-east-1.prod.aws.tidbcloud.com";
$user = "4Asq3bxQtZ3iP3r.root";
$pass = "Kt7JQCCjn0CTWYAx";
$db   = "test";
$port = 4000;

$conexion = mysqli_init();
// Asegúrate de que esta ruta sea correcta en el entorno de Koyeb
$ca_cert = "/etc/ssl/certs/ca-certificates.crt"; 
mysqli_ssl_set($conexion, NULL, NULL, $ca_cert, NULL, NULL);

$resultado = @mysqli_real_connect($conexion, $host, $user, $pass, $db, $port, NULL, MYSQLI_CLIENT_SSL);

if (!$resultado) {
    die(json_encode(["status" => "error", "message" => "Fallo conexión: " . mysqli_connect_error()]));
}

// Captura de datos con validación simple
$idusuario = isset($_GET['idusuario']) ? (int)$_GET['idusuario'] : 0;
$numero    = isset($_GET['numero']) ? $_GET['numero'] : '';
$cantidad  = isset($_GET['cantidad']) ? (int)$_GET['cantidad'] : 0;
$fecha     = date("Y-m-d");

// Verificación de datos recibidos
if ($idusuario > 0 && $cantidad > 0 && !empty($numero)) {
    
    // Usamos Sentencias Preparadas para evitar Inyección SQL
    $stmt = $conexion->prepare("INSERT INTO limite (idusuario, numero, cantLimited, fecha) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isis", $idusuario, $numero, $cantidad, $fecha);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Límite guardado"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Error al insertar: " . $stmt->error]);
    }
    
    $stmt->close();
} else {
    // Agregamos detalle para saber qué falló exactamente durante el debug
    echo json_encode([
        "status" => "error", 
        "message" => "Datos incompletos",
        "debug" => [
            "idusuario" => $idusuario,
            "cantidad" => $cantidad,
            "numero" => $numero
        ]
    ]);
}

$conexion->close();
?>
