<?php
/**
 * Página Principal do Sistema
 * Exibe o vídeo ao vivo, resultados recentes e previsões da IA
 */

require_once __DIR__ . '/mestre.php';

// Inicializa os componentes principais
$ocr = new OCRProcessor();
$ai = new AIEngine($pdo);
$captureSystem = new CaptureSystem($pdo, $ocr, $ai);

// Executa captura se estiver ativada
if (isset($_GET['capture'])) {
    $captureSystem->runCaptureCycle();
}

// Obtém os últimos resultados
$stmt = $this->pdo->query("
    SELECT 
        gr.*,
        c1.card_value as left_value, c1.card_suit as left_suit,
        c2.card_value as right_value, c2.card_suit as right_suit
    FROM game_results gr
    JOIN cards c1 ON gr.left_card_id = c1.id
    JOIN cards c2 ON gr.right_card_id = c2.id
    ORDER BY gr.created_at DESC
    LIMIT 10
");
$recentResults = $stmt->fetchAll();

// Obtém a última previsão da IA
$lastPrediction = $this->pdo->query("
    SELECT * FROM game_results 
    WHERE prediction != 'none'
    ORDER BY created_at DESC
    LIMIT 1
")->fetch();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Análise de Cartas</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>Sistema de Análise de Cartas</h1>
            <div class="controls">
                <button id="startCapture">Iniciar Captura</button>
                <button id="stopCapture">Parar Captura</button>
                <a href="admin/calibrate.php" class="btn">Calibrar Sistema</a>
            </div>
        </header>
        
        <main class="content">
            <section class="video-section">
                <h2>Vídeo ao Vivo</h2>
                <div class="video-container">
                    <iframe id="liveVideo" src="<?= htmlspecialchars(VIDEO_SOURCE_URL) ?>" 
                            frameborder="0" allowfullscreen></iframe>
                </div>
                <div class="video-controls">
                    <p>O iframe será atualizado automaticamente a cada 10 minutos</p>
                </div>
            </section>
            
            <section class="results-section">
                <h2>Últimos Resultados</h2>
                <div class="results-grid">
                    <?php foreach ($recentResults as $result): ?>
                    <div class="result-card <?= $result['winner'] ?>">
                        <div class="left-card">
                            <span class="card-value"><?= $result['left_value'] ?></span>
                            <span class="card-suit"><?= $this->getSuitSymbol($result['left_suit']) ?></span>
                        </div>
                        <div class="vs">VS</div>
                        <div class="right-card">
                            <span class="card-value"><?= $result['right_value'] ?></span>
                            <span class="card-suit"><?= $this->getSuitSymbol($result['right_suit']) ?></span>
                        </div>
                        <div class="winner-badge">
                            <?= strtoupper($result['winner']) ?>
                        </div>
                        <?php if ($result['prediction'] != 'none'): ?>
                        <div class="prediction <?= $result['prediction'] == $result['winner'] ? 'correct' : 'incorrect' ?>">
                            IA: <?= strtoupper($result['prediction']) ?> 
                            (<?= round($result['confidence'] * 100) ?>%)
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
            
            <?php if ($lastPrediction): ?>
            <section class="prediction-section">
                <h2>Última Previsão da IA</h2>
                <div class="prediction-card <?= $lastPrediction['confidence'] >= MIN_CONFIDENCE ? 'high-confidence' : 'low-confidence' ?>">
                    <div class="prediction-header">
                        <h3>Próxima Jogada</h3>
                        <span class="confidence"><?= round($lastPrediction['confidence'] * 100) ?>% de Confiança</span>
                    </div>
                    <div class="prediction-body">
                        <p>A IA prevê que o lado <strong><?= strtoupper($lastPrediction['prediction']) ?></strong> vencerá a próxima jogada.</p>
                        <?php if ($lastPrediction['confidence'] >= MIN_CONFIDENCE): ?>
                        <p class="recommendation">RECOMENDAÇÃO: APOSTAR NO <?= strtoupper($lastPrediction['prediction']) ?></p>
                        <?php else: ?>
                        <p class="no-recommendation">A IA não tem confiança suficiente para recomendar uma aposta.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
            <?php endif; ?>
        </main>
        
        <footer class="footer">
            <p>Sistema de Análise de Cartas &copy; <?= date('Y') ?></p>
            <p>Última atualização: <?= date('H:i:s') ?></p>
        </footer>
    </div>

    <script src="assets/js/scripts.js"></script>
    <script>
        // Atualiza o iframe a cada 10 minutos
        setInterval(function() {
            var iframe = document.getElementById('liveVideo');
            iframe.src = iframe.src; // Recarrega o iframe
        }, <?= REFRESH_INTERVAL ?>);
        
        // Controles de captura
        document.getElementById('startCapture').addEventListener('click', function() {
            fetch('process.php?action=start_capture')
                .then(response => response.json())
                .then(data => alert(data.message));
        });
        
        document.getElementById('stopCapture').addEventListener('click', function() {
            fetch('process.php?action=stop_capture')
                .then(response => response.json())
                .then(data => alert(data.message));
        });
    </script>
</body>
</html>