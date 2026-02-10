<?php

// Include necessary files
require_once 'auth.php'; // Authentication handling
require_once 'routes/admin.php';
require_once 'routes/tecnico.php';
require_once 'routes/cliente.php';
require_once 'routes/api.php';

// Start session for tracking user login state
session_start();

// Get the request URI and method
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Authentication check function
function isAuthenticated() {
    return isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;
}

// Routing logic
switch ($requestUri) {
    case '/admin':
        if (isAuthenticated()) {
            require 'routes/admin.php';
        } else {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
        }
        break;
    case '/tecnico':
        if (isAuthenticated()) {
            require 'routes/tecnico.php';
        } else {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
        }
        break;
    case '/cliente':
        if (isAuthenticated()) {
            require 'routes/cliente.php';
        } else {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
        }
        break;
    case '/api':
        require 'routes/api.php'; // API routes can be public or require authentication
        break;
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Route not found']);
        break;
} 
?>