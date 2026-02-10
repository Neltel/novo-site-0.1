<?php
/**
 * Sistema de Captura de Tela do Vídeo
 * Captura frames em intervalos regulares e processa com OCR
 */

class CaptureSystem {
    private $pdo;
    private $ocr;
    private $ai;
    private $lastCaptureTime = 0;
    
    public function __construct($pdo, $ocr, $ai) {
        $this->pdo = $pdo;
        $this->ocr = $ocr;
        $this->ai = $ai;
        
        // Cria diretório de screenshots se não existir
        if (!file_exists(SCREENSHOTS_DIR)) {
            mkdir(SCREENSHOTS_DIR, 0755, true);
        }
    }
    
    /**
     * Executa um ciclo completo de captura e processamento
     */
    public function runCaptureCycle() {
        // Verifica se é hora de capturar (baseado no intervalo definido)
        $currentTime = time();
        if (($currentTime - $this->lastCaptureTime) < CAPTURE_INTERVAL) {
            return false; // Ainda não é hora de capturar
        }
        
        // 1. Captura a tela do vídeo
        $screenshotPath = $this->captureVideoFrame();
        
        if (!$screenshotPath) {
            error_log("Erro ao capturar screenshot do vídeo");
            return false;
        }
        
        // 2. Processa a imagem com OCR
        $cards = $this->ocr->processImage($screenshotPath);
        
        // 3. Se ambas as cartas foram detectadas, processa a jogada
        if ($cards['left'] && $cards['right']) {
            $this->processGameRound($cards['left'], $cards['right']);
        }
        
        $this->lastCaptureTime = $currentTime;
        return true;
    }
    
    /**
     * Captura um frame do vídeo ao vivo
     * @return string|false Caminho do screenshot ou false em caso de erro
     */
    private function captureVideoFrame() {
        // Usando o PhantomJS para capturar a página (instalação necessária)
        $tempJs = tempnam(sys_get_temp_dir(), 'capture_') . '.js';
        $outputFile = SCREENSHOTS_DIR . 'capture_' . time() . '.jpg';
        
        // Cria um script PhantomJS para capturar a página
        $jsCode = <<<JS
var page = require('webpage').create();
page.viewportSize = { width: 1280, height: 720 };
page.open('{$videoUrl}', function(status) {
    if (status !== 'success') {
        console.log('Unable to load the address!');
        phantom.exit(1);
    } else {
        window.setTimeout(function() {
            page.render('{$outputFile}');
            phantom.exit();
        }, 2000); // Espera 2 segundos para carregar
    }
});
JS;
        
        file_put_contents($tempJs, $jsCode);
        
        // Executa o PhantomJS
        exec("phantomjs {$tempJs}", $output, $returnVar);
        unlink($tempJs);
        
        return ($returnVar === 0 && file_exists($outputFile)) ? $outputFile : false;
    }
    
    /**
     * Processa uma rodada completa do jogo
     */
    private function processGameRound($leftCard, $rightCard) {
        // 1. Salva as cartas no banco de dados
        $leftCardId = $this->saveCardToDatabase($leftCard, 'left');
        $rightCardId = $this->saveCardToDatabase($rightCard, 'right');
        
        // 2. Determina o vencedor real da rodada
        $winner = $this->determineWinner($leftCard, $rightCard);
        
        // 3. Obtém a previsão da IA
        $prediction = $this->ai->predictOutcome($leftCard, $rightCard);
        
        // 4. Salva o resultado no banco de dados
        $this->saveGameResult($leftCardId, $rightCardId, $winner, $prediction);
        
        // 5. Atualiza o aprendizado da IA
        $this->ai->updateLearning($leftCard, $rightCard, $winner, $prediction);
        
        return true;
    }
    
    /**
     * Salva uma carta reconhecida no banco de dados
     */
    private function saveCardToDatabase($cardData, $position) {
        $stmt = $this->pdo->prepare("
            INSERT INTO cards (
                card_value, 
                card_suit, 
                position, 
                confidence
            ) VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $cardData['card'],
            $cardData['suit'],
            $position,
            $cardData['confidence']
        ]);
        
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Determina o vencedor de uma rodada
     */
    private function determineWinner($leftCard, $rightCard) {
        $cardValues = [
            'A' => 14, 'K' => 13, 'Q' => 12, 'J' => 11,
            '10' => 10, '9' => 9, '8' => 8, '7' => 7,
            '6' => 6, '5' => 5, '4' => 4, '3' => 3, '2' => 2
        ];
        
        $leftValue = $cardValues[$leftCard['card']] ?? 0;
        $rightValue = $cardValues[$rightCard['card']] ?? 0;
        
        if ($leftValue > $rightValue) return 'left';
        if ($rightValue > $leftValue) return 'right';
        return 'draw';
    }
    
    /**
     * Salva o resultado de uma jogada no banco de dados
     */
    private function saveGameResult($leftCardId, $rightCardId, $winner, $prediction) {
        $stmt = $this->pdo->prepare("
            INSERT INTO game_results (
                left_card_id,
                right_card_id,
                winner,
                prediction,
                analysis_time,
                confidence
            ) VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $leftCardId,
            $rightCardId,
            $winner,
            $prediction['prediction'],
            $prediction['analysis_time'],
            $prediction['confidence']
        ]);
    }
}
?>