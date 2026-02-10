<?php
/**
 * Ferramenta de Calibração do Sistema
 * Permite definir as áreas de captura para as cartas
 */

require_once __DIR__ . '/../../mestre.php';

// Verifica se é uma solicitação POST (atualização de calibração)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Processa os dados de calibração
        $leftArea = [
            'x1' => (int)$_POST['left_x1'],
            'y1' => (int)$_POST['left_y1'],
            'x2' => (int)$_POST['left_x2'],
            'y2' => (int)$_POST['left_y2']
        ];
        
        $rightArea = [
            'x1' => (int)$_POST['right_x1'],
            'y1' => (int)$_POST['right_y1'],
            'x2' => (int)$_POST['right_x2'],
            'y2' => (int)$_POST['right_y2']
        ];
        
        // Valida as coordenadas
        foreach ([$leftArea, $rightArea] as $area) {
            if ($area['x1'] >= $area['x2'] || $area['y1'] >= $area['y2']) {
                throw new Exception("Coordenadas inválidas: x1 deve ser menor que x2, e y1 menor que y2");
            }
        }
        
        // Salva no banco de dados
        $pdo->beginTransaction();
        
        // Lado esquerdo
        $stmt = $pdo->prepare("
            INSERT INTO calibration (side, x1, y1, x2, y2)
            VALUES ('left', :x1, :y1, :x2, :y2)
            ON DUPLICATE KEY UPDATE
                x1 = VALUES(x1),
                y1 = VALUES(y1),
                x2 = VALUES(x2),
                y2 = VALUES(y2)
        ");
        $stmt->execute($leftArea);
        
        // Lado direito
        $stmt->execute(array_merge($rightArea, ['side' => 'right']));
        
        $pdo->commit();
        
        $successMessage = "Calibração atualizada com sucesso!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $errorMessage = "Erro ao atualizar calibração: " . $e->getMessage();
    }
}

// Obtém os dados atuais de calibração
$calibration = ['left' => [], 'right' => []];
$stmt = $pdo->query("SELECT * FROM calibration");
while ($row = $stmt->fetch()) {
    $calibration[$row['side']] = [
        'x1' => $row['x1'],
        'y1' => $row['y1'],
        'x2' => $row['x2'],
        'y2' => $row['y2']
    ];
}

// Valores padrão se não houver calibração
if (empty($calibration['left'])) {
    $calibration['left'] = ['x1' => 100, 'y1' => 100, 'x2' => 300, 'y2' => 400];
}
if (empty($calibration['right'])) {
    $calibration['right'] = ['x1' => 500, 'y1' => 100, 'x2' => 700, 'y2' => 400];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calibrar Sistema</title>
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <style>
        .calibration-area {
            position: relative;
            margin: 20px 0;
        }
        #videoPreview {
            border: 2px solid #333;
            max-width: 100%;
        }
        .calibration-box {
            position: absolute;
            border: 2px dashed red;
            background-color: rgba(255, 0, 0, 0.1);
        }
        .calibration-form {
            margin-top: 20px;
            padding: 20px;
            background: #f5f5f5;
            border-radius: 5px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: inline-block;
            width: 80px;
            font-weight: bold;
        }
        input[type="number"] {
            width: 80px;
            padding: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>Calibrar Sistema de Captura</h1>
            <a href="../dashboard.php" class="btn">Voltar ao Painel</a>
        </header>
        
        <main class="content">
            <?php if (isset($successMessage)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($successMessage) ?></div>
            <?php elseif (isset($errorMessage)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($errorMessage) ?></div>
            <?php endif; ?>
            
            <section class="instructions">
                <h2>Instruções de Calibração</h2>
                <ol>
                    <li>Ajuste as coordenadas abaixo para definir as áreas de captura</li>
                    <li>As áreas devem cobrir exatamente onde as cartas aparecem no vídeo</li>
                    <li>Use a visualização ao lado para ajudar no posicionamento</li>
                    <li>Clique em "Salvar Calibração" quando estiver satisfeito</li>
                </ol>
            </section>
            
            <div class="calibration-container">
                <div class="calibration-area">
                    <iframe id="videoPreview" src="<?= htmlspecialchars(VIDEO_SOURCE_URL) ?>" 
                            frameborder="0" allowfullscreen></iframe>
                    <div id="leftBox" class="calibration-box" 
                         style="left: <?= $calibration['left']['x1'] ?>px; 
                                top: <?= $calibration['left']['y1'] ?>px;
                                width: <?= $calibration['left']['x2'] - $calibration['left']['x1'] ?>px;
                                height: <?= $calibration['left']['y2'] - $calibration['left']['y1'] ?>px;">
                        <div class="box-label">Esquerda</div>
                    </div>
                    <div id="rightBox" class="calibration-box" 
                         style="left: <?= $calibration['right']['x1'] ?>px; 
                                top: <?= $calibration['right']['y1'] ?>px;
                                width: <?= $calibration['right']['x2'] - $calibration['right']['x1'] ?>px;
                                height: <?= $calibration['right']['y2'] - $calibration['right']['y1'] ?>px;">
                        <div class="box-label">Direita</div>
                    </div>
                </div>
                
                <form method="POST" class="calibration-form">
                    <h3>Coordenadas de Calibração</h3>
                    
                    <fieldset>
                        <legend>Área Esquerda</legend>
                        <div class="form-group">
                            <label for="left_x1">X1:</label>
                            <input type="number" id="left_x1" name="left_x1" value="<?= $calibration['left']['x1'] ?>" min="0">
                            
                            <label for="left_y1">Y1:</label>
                            <input type="number" id="left_y1" name="left_y1" value="<?= $calibration['left']['y1'] ?>" min="0">
                        </div>
                        <div class="form-group">
                            <label for="left_x2">X2:</label>
                            <input type="number" id="left_x2" name="left_x2" value="<?= $calibration['left']['x2'] ?>" min="0">
                            
                            <label for="left_y2">Y2:</label>
                            <input type="number" id="left_y2" name="left_y2" value="<?= $calibration['left']['y2'] ?>" min="0">
                        </div>
                    </fieldset>
                    
                    <fieldset>
                        <legend>Área Direita</legend>
                        <div class="form-group">
                            <label for="right_x1">X1:</label>
                            <input type="number" id="right_x1" name="right_x1" value="<?= $calibration['right']['x1'] ?>" min="0">
                            
                            <label for="right_y1">Y1:</label>
                            <input type="number" id="right_y1" name="right_y1" value="<?= $calibration['right']['y1'] ?>" min="0">
                        </div>
                        <div class="form-group">
                            <label for="right_x2">X2:</label>
                            <input type="number" id="right_x2" name="right_x2" value="<?= $calibration['right']['x2'] ?>" min="0">
                            
                            <label for="right_y2">Y2:</label>
                            <input type="number" id="right_y2" name="right_y2" value="<?= $calibration['right']['y2'] ?>" min="0">
                        </div>
                    </fieldset>
                    
                    <button type="submit" class="btn btn-primary">Salvar Calibração</button>
                    <button type="button" id="testCapture" class="btn">Testar Captura</button>
                </form>
            </div>
            
            <section class="current-calibration">
                <h2>Dados de Calibração Atuais</h2>
                <div class="calibration-data">
                    <h3>Área Esquerda:</h3>
                    <pre><?= print_r($calibration['left'], true) ?></pre>
                    
                    <h3>Área Direita:</h3>
                    <pre><?= print_r($calibration['right'], true) ?></pre>
                </div>
            </section>
        </main>
    </div>

    <script src="../../assets/js/scripts.js"></script>
    <script>
        // Atualiza as caixas de calibração quando os valores mudam
        document.querySelectorAll('input[type="number"]').forEach(input => {
            input.addEventListener('change', updateBoxes);
        });
        
        function updateBoxes() {
            // Atualiza caixa esquerda
            document.getElementById('leftBox').style.left = document.getElementById('left_x1').value + 'px';
            document.getElementById('leftBox').style.top = document.getElementById('left_y1').value + 'px';
            document.getElementById('leftBox').style.width = (document.getElementById('left_x2').value - document.getElementById('left_x1').value) + 'px';
            document.getElementById('leftBox').style.height = (document.getElementById('left_y2').value - document.getElementById('left_y1').value) + 'px';
            
            // Atualiza caixa direita
            document.getElementById('rightBox').style.left = document.getElementById('right_x1').value + 'px';
            document.getElementById('rightBox').style.top = document.getElementById('right_y1').value + 'px';
            document.getElementById('rightBox').style.width = (document.getElementById('right_x2').value - document.getElementById('right_x1').value) + 'px';
            document.getElementById('rightBox').style.height = (document.getElementById('right_y2').value - document.getElementById('right_y1').value) + 'px';
        }
        
        // Testa a captura com as configurações atuais
        document.getElementById('testCapture').addEventListener('click', function() {
            fetch('../../process.php?action=test_capture', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    left: {
                        x1: document.getElementById('left_x1').value,
                        y1: document.getElementById('left_y1').value,
                        x2: document.getElementById('left_x2').value,
                        y2: document.getElementById('left_y2').value
                    },
                    right: {
                        x1: document.getElementById('right_x1').value,
                        y1: document.getElementById('right_y1').value,
                        x2: document.getElementById('right_x2').value,
                        y2: document.getElementById('right_y2').value
                    }
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert('Teste realizado com sucesso!\n' + 
                          'Carta esquerda: ' + (data.left_card || 'Não detectada') + '\n' +
                          'Carta direita: ' + (data.right_card || 'Não detectada'));
                } else {
                    alert('Erro no teste: ' + data.message);
                }
            });
        });
    </script>
</body>
</html>