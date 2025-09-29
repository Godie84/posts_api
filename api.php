<?php
// ----------------------------------------------
// CONFIGURACIÓN INICIAL Y DEPENDENCIAS
// ----------------------------------------------

header('Content-Type: application/json'); // Todas las respuestas serán en formato JSON

require 'db.php'; // Conexión PDO en la variable $pdo

// Muestra errores (sólo en desarrollo)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ----------------------------------------------
// DETECCIÓN DEL MÉTODO Y DATOS ENVIADOS
// ----------------------------------------------

$method = $_SERVER['REQUEST_METHOD']; // GET, POST, PUT, DELETE
$input  = json_decode(file_get_contents('php://input'), true); // Captura JSON del body

// ----------------------------------------------
// RUTEO DE LA API SEGÚN EL MÉTODO
// ----------------------------------------------

$response = match ($method) {
    'GET'    => handleGet($pdo),
    'POST'   => handlePost($pdo, $input),
    'PUT'    => handlePut($pdo, $input),
    'DELETE' => handleDelete($pdo, $input),
    default  => ['error' => 'Método no soportado']
};

// ----------------------------------------------
// RESPUESTA UNIFICADA
// ----------------------------------------------

echo json_encode($response);


// ----------------------------------------------
// FUNCIONES DE LA API
// ----------------------------------------------

function handleGet(PDO $pdo): array
{
    try {
        $stmt = $pdo->query("SELECT * FROM posts");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        http_response_code(500);
        return ['error' => 'Error al obtener los posts: ' . $e->getMessage()];
    }
}

function handlePost(PDO $pdo, array $data): array
{
    // Validación básica
    if (empty($data['title']) || empty($data['content']) || empty($data['status'])) {
        http_response_code(400);
        return ['error' => 'Faltan campos requeridos (title, content, status)'];
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO posts (title, content, status) VALUES (:title, :content, :status)");
        $stmt->execute([
            ':title'   => $data['title'],
            ':content' => $data['content'],
            ':status'  => $data['status'],
        ]);
        http_response_code(201);
        return ['message' => 'Post creado exitosamente'];
    } catch (PDOException $e) {
        http_response_code(500);
        return ['error' => 'Error al crear el post: ' . $e->getMessage()];
    }
}

function handlePut(PDO $pdo, array $data): array
{
    if (empty($data['id']) || empty($data['title']) || empty($data['content']) || empty($data['status'])) {
        http_response_code(400);
        return ['error' => 'Faltan campos requeridos (id, title, content, status)'];
    }

    try {
        $stmt = $pdo->prepare("UPDATE posts SET title = :title, content = :content, status = :status WHERE id = :id");
        $stmt->execute([
            ':id'      => $data['id'],
            ':title'   => $data['title'],
            ':content' => $data['content'],
            ':status'  => $data['status'],
        ]);
        return ['message' => 'Post actualizado exitosamente'];
    } catch (PDOException $e) {
        http_response_code(500);
        return ['error' => 'Error al actualizar el post: ' . $e->getMessage()];
    }
}

function handleDelete(PDO $pdo, array $data): array
{
    if (empty($data['id'])) {
        http_response_code(400);
        return ['error' => 'Falta el campo id'];
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM posts WHERE id = :id");
        $stmt->execute([':id' => $data['id']]);
        return ['message' => 'Post eliminado exitosamente'];
    } catch (PDOException $e) {
        http_response_code(500);
        return ['error' => 'Error al eliminar el post: ' . $e->getMessage()];
    }
}
