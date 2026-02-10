<?php
/**
 * Painel Administrativo
 * Exibe estatísticas e controles do sistema
 */

require_once __DIR__ . '/../mestre.php';

// Verifica se o usuário tem permissão para acessar
if (!in_array($_SERVER['REMOTE_ADDR'], ALLOWED_IPS) && !empty(ALLOWED_IPS)) {
    die("Acesso não autorizado.");
}

// Obtém estatísticas do sistema
$stats = [];
$queries = [
    'total_cards' => "SELECT COUNT(*) FROM cards",
    'today_cards' => "SELECT COUNT(*) FROM cards WHERE DATE(created_at) = CURDATE()",
    'total_games' => "SELECT COUNT(*) FROM game_results",
    'today_games' => "SELECT COUNT(*) FROM game_results WHERE DATE(created_at) = CURDATE()",
    'ai_accuracy' => "SELECT AVG(CASE WHEN prediction = winner THEN 1 ELSE 0 END) FROM game_results WHERE prediction != 'none'",
    'high_confidence_predictions' => "SELECT COUNT(*) FROM game_results WHERE confidence >= " . MIN_CONFIDENCE
];

foreach ($queries as $key => $query) {
    $stats[$key] = $pdo->query($query)->fetchColumn();
}

// Formata a precisão da IA como porcentagem
$stats['ai_accuracy'] = round($stats['ai_accuracy'] * 100, 2);

// Obtém os últimos resultados com previsões da IA
$recentPredictions = $pdo->query("
    SELECT 
        gr.*,
        c1.card_value as left_value, c1.card_suit as left_suit,
        c2.card_value as right_value, c2.card_suit as right_suit
    FROM game_results gr
    JOIN cards c1 ON gr.left_card_id = c1.id
    JOIN cards c2 ON gr.right_card_id = c2.id
    WHERE gr.prediction != 'none'
    ORDER BY gr.created_at DESC
    LIMIT 5
")->fetchAll();

// Obtém os padrões mais bem-sucedidos da IA
$topPatterns = $pdo->query("
    SELECT * FROM ai_learning
    WHERE times_used > 5
    ORDER BY success_rate DESC
    LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo</title>
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .stat-card {
            background: white;
            border-radius: 5px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-value {
            font-size: 2.5em;
            font-weight: bold;
            margin: 10px 0;
        }
        .stat-label {
            color: #666;
        }
        .section {
            margin-bottom: 40px;
        }
        .pattern-card {
            background: #f9f9f9;
            border-left: 4px solid #4CAF50;
            padding: 15px;
            margin-bottom: 10px;
        }
        .pattern-success {
            font-weight: bold;
            color: #4CAF50;
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>Painel Administrativo</h1>
            <nav class="admin-nav">
                <a href="dashboard.php" class="active">Dashboard</a>
                <a href="calibrate.php">Calibrar Sistema</a>
                <a href="settings.php">Configurações</a>
                <a href="../../index.php">Ver Site</a>
            </nav>
        </header>
        
        <main class="content">
            <section class="stats-section">
                <h2>Estatísticas do Sistema</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-value"><?= $stats['total_cards'] ?></div>
                        <div class="stat-label">Cartas Reconhecidas</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?= $stats['today_cards'] ?></div>
                        <div class="stat-label">Cartas Hoje</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?= $stats['total_games'] ?></div>
                        <div class="stat-label">Jogadas Analisadas</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?= $stats['today_games'] ?></div>
                        <div class="stat-label">Jogadas Hoje</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?= $stats['ai_accuracy'] ?>%</div>
                        <div class="stat-label">Precisão da IA</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?= $stats['high_confidence_predictions'] ?></div>
                        <div class="stat-label">Previsões Confiáveis</div>
                    </div>
                </div>
            </section>
            
            <div class="two-columns">
                <section class="section">
                    <h2>Últimas Previsões da IA</h2>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Jogada</th>
                                <th>Previsão</th>
                                <th>Confiança</th>
                                <th>Resultado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentPredictions as $pred): ?>
                            <tr>
                                <td>
                                    <?= $pred['left_value'] ?><?= $this->getSuitSymbol($pred['left_suit']) ?> 
                                    vs 
                                    <?= $pred['right_value'] ?><?= $this->getSuitSymbol($pred['right_suit']) ?>
                                </td>
                                <td><?= strtoupper($pred['prediction']) ?></td>
                                <td><?= round($pred['confidence'] * 100) ?>%</td>
                                <td class="<?= $pred['prediction'] == $pred['winner'] ? 'correct' : 'incorrect' ?>">
                                    <?= strtoupper($pred['winner']) ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </section>
                
                <section class="section">
                    <h2>Padrões Mais Eficazes</h2>
                    <?php foreach ($topPatterns as $pattern): ?>
                    <div class="pattern-card">
                        <div class="pattern-hash"><small><?= $pattern['pattern_hash'] ?></small></div>
                        <div class="pattern-success">Taxa de acerto: <?= round($pattern['success_rate'] * 100) ?>%</div>
                        <div>Usado <?= $pattern['times_used'] ?> vezes</div>
                        <div>Último uso: <?= date('d/m/Y H:i', strtotime($pattern['last_used'])) ?></div>
                    </div>
                    <?php endforeach; ?>
                </section>
            </div>
            
            <section class="section">
                <h2>Controles do Sistema</h2>
                <div class="controls-grid">
                    <button id="forceCapture" class="btn">Forçar Captura Agora</button>
                    <button id="rebuildAi" class="btn">Reconstruir Modelo de IA</button>
                    <button id="clearLogs" class="btn">Limpar Logs</button>
                    <button id="backupDb" class="btn">Backup do Banco</button>
                </div>
            </section>
        </main>
    </div>

    <script src="../../assets/js/scripts.js"></script>
    <script>
        // Controles do sistema
        document.getElementById('forceCapture').addEventListener('click', function() {
            fetch('../../process.php?action=force_capture')
                .then(response => response.json())
                .then(data => alert(data.message));
        });
        
        document.getElementById('rebuildAi').addEventListener('click', function() {
            if (confirm('Isso irá reconstruir o modelo de IA a partir dos dados atuais. Continuar?')) {
                fetch('../../process.php?action=rebuild_ai')
                    .then(response => response.json())
                    .then(data => alert(data.message));
            }
        });
        
        document.getElementById('clearLogs').addEventListener('click', function() {
            if (confirm('Tem certeza que deseja limpar todos os logs do sistema?')) {
                fetch('../../process.php?action=clear_logs')
                    .then(response => response.json())
                    .then(data => alert(data.message));
            }
        });
        
        document.getElementById('backupDb').addEventListener('click', function() {
            fetch('../../process.php?action=backup_db')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        window.location.href = data.download_url;
                    } else {
                        alert(data.message);
                    }
                });
        });
    </script>
</body>
</html>