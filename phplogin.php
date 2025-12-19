<?php
// login.php - Servicio de Inicio de Sesión Seguro

header("Content-Type: application/json; charset=UTF-8");

// --- Configuración de Base de Datos ---
$servername = "localhost";
$username_db = "adm";
$password_db = "1.lKnMvZ.1"; 
$dbname = "enoc"; 

// Crear conexión
$conn = new mysqli($servername, $username_db, $password_db, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die(json_encode([
        "status" => "error", 
        "message" => "Error de conexión a BD: " . $conn->connect_error
    ]));
}

// Obtener y validar datos POST
$usuario = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

if (empty($usuario) || empty($password)) {
    echo json_encode(["status" => "error", "message" => "Usuario y/o contraseña incompletos."]);
    $conn->close();
    exit();
}

// --- Lógica de Búsqueda y Validación ---
$usuario_data = null;
$password_hash_db = null;

// **Función auxiliar para buscar y validar en una tabla**
function buscar_y_validar($conn, $tabla, $col_user, $col_id, $col_nombre, $col_pass, $usuario, $password, $rol) {
    $sql = "SELECT $col_id, $col_nombre, $col_pass FROM $tabla WHERE $col_user = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {return null;}

    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        // 🚨 Validación Segura con password_verify()
        if (password_verify($password, $row[$col_pass])) {
            $stmt->close();
            return [
                'user_id' => $row[$col_id],
                'username' => $row[$col_nombre],
                'rol' => $rol
            ];
        }
    }
    $stmt->close();
    return null;
}

// 1. Buscar en la tabla 'administrador'
// Usando: idAdmin, admUsuario, admNombre, admPassword
$usuario_data1 = buscar_y_validar($conn, 
    'empresario', 'empUsuario', 'idempresario', 'empNombre', 'empPassword', 
    $usuario, $password, 0
);

// 2. Si no se encontró en administrador, buscar en la tabla 'usuario'
if ($usuario_data1 === null) {
    // Usando: idUsuario, usdUsuario, usdNombre, usdPassword
    $usuario_data1 = buscar_y_validar($conn, 
        'usuario', 'usdUsuario', 'idUsuario', 'usdNombre', 'usdPassword', 
        $usuario, $password, 1
    );
}

// --- Devolver Respuesta ---
if ($usuario_data1 !== null) {
    echo json_encode([
        "status" => "success", 
        "message" => "Login exitoso.",
        "user_id" => $usuario_data1['user_id'],
        "username" => $usuario_data1['username'],
        "rol" => $usuario_data1['rol']
    ]);
} else {
    // Mensaje genérico por seguridad
    echo json_encode(["status" => "error", "message" => "Credenciales inválidas o usuario no encontrado."]);
}

$conn->close();

