<?php
/**
 * API do Sistema de Análise de Cartas
 * Fornece dados para integração com outros sistemas
 */

require_once __DIR__ . '/mestre.php';

header('Content-Type: application/json');

// Verifica a chave de API
$apiKey = $_GET['api_key'] ?? $_SERVER['HTTP_X_API_KEY'] ?? '';
if ($apiKey !== SECURITY_KEY) {
    http_response_code(401);
    die(json_encode(['status' => 'error', 'message' => 'API key inválida']));
}

// Determina o endpoint
$endpoint = $_GET['endpoint'] ?? '';
$response = [];

try {
    switch ($endpoint) {
        case 'latest_results':
            $limit = min((int)($_GET['limit'] ?? 10), 50);
            
            $stmt = $pdo->prepare("
                SELECT 
                    gr.*,
                    c1.card_value as left_value, c1.card_suit as left_suit,
                    c2.card_value as right_value, c2.card_suit as right_suit
                FROM game_results gr
                JOIN cards c1 ON gr.left_card_id = c1.id
                JOIN cards c2 ON gr.right_card_id = c2.id
                ORDER BY gr.created_at DESC
                LIMIT :limit
            ");
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $response = [
                'status' => 'success',
                'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ];
            break;
            
        case 'current_prediction':
            $stmt = $pdo->query("
                SELECT * FROM game_results 
                WHERE prediction != 'none'
                ORDER BY created_at DESC
                LIMIT 1
            ");
            $prediction = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $response = [
                'status' => 'success',
                'data' => $prediction ?: null
            ];
            break;
            
        case 'ai_stats':
            $stats = [
                'total_predictions' => $pdo->query("SELECT COUNT(*) FROM game_results WHERE prediction != 'none'")->fetchColumn(),
                'accuracy' => $pdo->query("SELECT AVG(CASE WHEN prediction = winner THEN 1 ELSE 0 END) FROM game_results WHERE prediction != 'none'")->fetchColumn(),
                'high_confidence_predictions' => $pdo->query("SELECT COUNT(*) FROM game_results WHERE confidence >= " . MIN_CONFIDENCE)->fetchColumn(),
                'learned_patterns' => $pdo->query("SELECT COUNT(*) FROM ai_learning")->fetchColumn()
            ];
            
            $response = [
                'status' => 'success',
                'data' => $stats
            ];
            break;
            
        default:
            throw new Exception("Endpoint não reconhecido");
    }
} catch (Exception $e) {
    $response = [
        'status' => 'error',
        'message' => $e->getMessage()
    ];
}

echo json_encode($response);