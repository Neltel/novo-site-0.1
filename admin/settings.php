<?php
/**
 * Página de Configurações do Sistema
 */

require_once __DIR__ . '/../../mestre.php';

// Verifica permissão
if (!in_array($_SERVER['REMOTE_ADDR'], ALLOWED_IPS) && !empty(ALLOWED_IPS)) {
    die("Acesso não autorizado.");
}

// Processa o formulário de configurações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validações básicas
        $newSettings = [
            'capture_interval' => max(10, min((int)$_POST['capture_interval'], 300)),
            'min_confidence' => max(0.5, min((float)$_POST['min_confidence'], 1.0)),
            'analysis_window' => max(10, min((int)$_POST['analysis_window'], 200))
        ];
        
        // Salva em um arquivo de configuração ou banco de dados
        file_put_contents(CALIBRATION_DIR . 'system_settings.json', json_encode($newSettings));
        
        $successMessage = "Configurações atualizadas com sucesso!";
    } catch (Exception $e) {
        $errorMessage = "Erro ao salvar configurações: " . $e->getMessage();
    }
}

// Carrega configurações atuais
$settingsFile = CALIBRATION_DIR . 'system_settings.json';
$currentSettings = [
    'capture_interval' => CAPTURE_INTERVAL,
    'min_confidence' => MIN_CONFIDENCE,
    'analysis_window' => ANALYSIS_WINDOW
];

if (file_exists($settingsFile)) {
    $currentSettings = array_merge($currentSettings, json_decode(file_get_contents($settingsFile), true));
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações do Sistema</title>
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <style>
        .settings-form {
            max-width: 600px;
            margin: 0 auto;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="number"], select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .form-actions {
            margin-top: 30px;
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>Configurações do Sistema</h1>
            <nav class="admin-nav">
                <a href="dashboard.php">Dashboard</a>
                <a href="calibrate.php">Calibrar</a>
                <a href="settings.php" class="active">Configurações</a>
                <a href="../../index.php">Ver Site</a>
            </nav>
        </header>
        
        <main class="content">
            <?php if (isset($successMessage)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($successMessage) ?></div>
            <?php elseif (isset($errorMessage)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($errorMessage) ?></div>
            <?php endif; ?>
            
            <form method="POST" class="settings-form">
                <div class="form-group">
                    <label for="capture_interval">Intervalo de Captura (segundos)</label>
                    <input type="number" id="capture_interval" name="capture_interval" 
                           value="<?= $currentSettings['capture_interval'] ?>" min="10" max="300">
                    <p class="help-text">Tempo entre cada captura de tela do vídeo</p>
                </div>
                
                <div class="form-group">
                    <label for="min_confidence">Confiança Mínima para Previsão (0.5-1.0)</label>
                    <input type="number" id="min_confidence" name="min_confidence" step="0.01"
                           value="<?= $currentSettings['min_confidence'] ?>" min="0.5" max="1.0">
                    <p class="help-text">Apenas exibe previsões com esta confiança mínima</p>
                </div>
                
                <div class="form-group">
                    <label for="analysis_window">Janela de Análise (jogadas)</label>
                    <input type="number" id="analysis_window" name="analysis_window" 
                           value="<?= $currentSettings['analysis_window'] ?>" min="10" max="200">
                    <p class="help-text">Quantas jogadas anteriores a IA deve considerar</p>
                </div>
                
                <div class="form-group">
                    <label for="video_url">URL do Vídeo</label>
                    <input type="text" id="video_url" name="video_url" 
                           value="<?= htmlspecialchars(VIDEO_SOURCE_URL) ?>" disabled>
                    <p class="help-text">Altere no arquivo mestre.php</p>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Salvar Configurações</button>
                    <button type="reset" class="btn">Restaurar Padrões</button>
                </div>
            </form>
            
            <section class="danger-zone">
                <h2>Zona de Perigo</h2>
                <div class="danger-actions">
                    <button id="resetAi" class="btn btn-danger">Reiniciar Aprendizado da IA</button>
                    <button id="clearDatabase" class="btn btn-danger">Limpar Banco de Dados</button>
                </div>
            </section>
        </main>
    </div>

    <script src="../../assets/js/scripts.js"></script>
    <script>
        document.getElementById('resetAi').addEventListener('click', function() {
            if (confirm('Isso irá resetar todo o aprendizado da IA. Continuar?')) {
                fetch('../../process.php?action=reset_ai')
                    .then(response => response.json())
                    .then(data => {
                        alert(data.message);
                        if (data.status === 'success') {
                            location.reload();
                        }
                    });
            }
        });
        
        document.getElementById('clearDatabase').addEventListener('click', function() {
            if (confirm('ISSO APAGARÁ TODOS OS DADOS! TEM CERTEZA?')) {
                const password = prompt('Digite a senha de administração:');
                if (password) {
                    fetch('../../process.php?action=clear_db', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ password: password })
                    })
                    .then(response => response.json())
                    .then(data => {
                        alert(data.message);
                        if (data.status === 'success') {
                            location.reload();
                        }
                    });
                }
            }
        });
    </script>
</body>
</html>