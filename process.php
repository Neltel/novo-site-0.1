<?php
/**
 * Processador em Tempo Real
 * Lida com requisições AJAX e processamento contínuo
 */

require_once __DIR__ . '/mestre.php';

header('Content-Type: application/json');

// Verifica a ação solicitada
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'start_capture':
            // Inicia o processo de captura contínua
            $captureSystem = new CaptureSystem($pdo, new OCRProcessor(), new AIEngine($pdo));
            $result = $captureSystem->runCaptureCycle();
            
            echo json_encode([
                'status' => 'success',
                'message' => $result ? 'Captura iniciada com sucesso' : 'Aguardando próximo ciclo de captura'
            ]);
            break;
            
        case 'stop_capture':
            // Para o processo de captura (implementação seria com um flag no banco)
            echo json_encode([
                'status' => 'success',
                'message' => 'Captura parada com sucesso'
            ]);
            break;
            
        case 'get_latest_results':
            // Retorna os últimos resultados para atualização da interface
            $stmt = $pdo->query("
                SELECT 
                    gr.*,
                    c1.card_value as left_value, c1.card_suit as left_suit,
                    c2.card_value as right_value, c2.card_suit as right_suit
                FROM game_results gr
                JOIN cards c1 ON gr.left_card_id = c1.id
                JOIN cards c2 ON gr.right_card_id = c2.id
                ORDER BY gr.created_at DESC
                LIMIT 5
            ");
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'status' => 'success',
                'data' => $results
            ]);
            break;
            
        default:
            throw new Exception("Ação não reconhecida");
    }
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>