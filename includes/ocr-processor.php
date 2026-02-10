<?php
/**
 * Processador OCR para Reconhecimento de Cartas
 * Utiliza a API Google Cloud Vision
 */

// Verifica se as configurações necessárias estão definidas
if (!defined('GOOGLE_CREDENTIALS_PATH') || !defined('GOOGLE_PROJECT_ID')) {
    die("Erro: Configurações do Google Vision não encontradas. Verifique mestre.php");
}

// Carrega a biblioteca do Google Cloud Vision
require_once __DIR__ . '/../vendor/autoload.php';

use Google\Cloud\Vision\V1\ImageAnnotatorClient;
use Google\Cloud\Vision\V1\Feature\Type;

class OCRProcessor {
    private $client;
    private $calibrationData;
    
    public function __construct() {
        // Inicializa o cliente do Google Vision
        // Usa o arquivo de credenciais JSON definido em mestre.php
        putenv('GOOGLE_APPLICATION_CREDENTIALS=' . GOOGLE_CREDENTIALS_PATH);
        $this->client = new ImageAnnotatorClient([
            'projectId' => GOOGLE_PROJECT_ID
        ]);
        
        // Carrega dados de calibração
        $this->loadCalibration();
    }
    
    /**
     * Carrega os dados de calibração do banco de dados
     * Define as áreas onde as cartas aparecem na tela
     */
    private function loadCalibration() {
        global $pdo;
        
        try {
            $stmt = $pdo->query("SELECT * FROM calibration");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $this->calibrationData = [
                'left' => [],
                'right' => []
            ];
            
            foreach ($data as $row) {
                $this->calibrationData[$row['side']] = [
                    'x1' => $row['x1'],
                    'y1' => $row['y1'],
                    'x2' => $row['x2'],
                    'y2' => $row['y2']
                ];
            }
            
        } catch (PDOException $e) {
            error_log("Erro ao carregar calibração: " . $e->getMessage());
            die("Erro no sistema de calibração.");
        }
    }
    
    /**
     * Processa uma imagem para reconhecer cartas
     * @param string $imagePath Caminho da imagem
     * @return array Resultados do reconhecimento
     */
    public function processImage($imagePath) {
        // Verifica se o arquivo de imagem existe
        if (!file_exists($imagePath)) {
            throw new Exception("Arquivo de imagem não encontrado: " . $imagePath);
        }
        
        $results = [
            'left' => null,
            'right' => null,
            'timestamp' => time()
        ];
        
        // Processa cada lado (esquerdo e direito)
        foreach (['left', 'right'] as $side) {
            if (empty($this->calibrationData[$side])) {
                continue; // Pula se não houver calibração
            }
            
            // Recorta a área de interesse baseado na calibração
            $croppedImage = $this->cropImage(
                $imagePath,
                $this->calibrationData[$side]['x1'],
                $this->calibrationData[$side]['y1'],
                $this->calibrationData[$side]['x2'],
                $this->calibrationData[$side]['y2']
            );
            
            // Salva temporariamente a imagem recortada
            $tempPath = sys_get_temp_dir() . '/card_' . $side . '_' . time() . '.jpg';
            imagejpeg($croppedImage, $tempPath, 90);
            imagedestroy($croppedImage);
            
            // Chama a API do Google Vision
            $image = file_get_contents($tempPath);
            $response = $this->client->textDetection($image);
            $annotations = $response->getTextAnnotations();
            
            if ($annotations) {
                // Pega o primeiro resultado (maior confiança)
                $text = $annotations[0]->getDescription();
                $vertices = $annotations[0]->getBoundingPoly()->getVertices();
                
                // Processa o texto para identificar carta e naipe
                $cardInfo = $this->parseCardText($text);
                
                if ($cardInfo) {
                    $results[$side] = [
                        'card' => $cardInfo['value'],
                        'suit' => $cardInfo['suit'],
                        'confidence' => $this->calculateConfidence($vertices, $text),
                        'raw_text' => $text
                    ];
                }
            }
            
            // Remove a imagem temporária
            unlink($tempPath);
        }
        
        return $results;
    }
    
    /**
     * Recorta uma área específica da imagem
     * @param string $imagePath Caminho da imagem original
     * @param int $x1 Coordenada X superior esquerda
     * @param int $y1 Coordenada Y superior esquerda
     * @param int $x2 Coordenada X inferior direita
     * @param int $y2 Coordenada Y inferior direita
     * @return resource Imagem recortada
     */
    private function cropImage($imagePath, $x1, $y1, $x2, $y2) {
        $original = imagecreatefromjpeg($imagePath);
        $width = $x2 - $x1;
        $height = $y2 - $y1;
        
        $cropped = imagecreatetruecolor($width, $height);
        imagecopy($cropped, $original, 0, 0, $x1, $y1, $width, $height);
        
        return $cropped;
    }
    
    /**
     * Interpreta o texto reconhecido para identificar carta e naipe
     * @param string $text Texto reconhecido pelo OCR
     * @return array|null Array com 'value' e 'suit' ou null se inválido
     */
    private function parseCardText($text) {
        // Padrões para valores e naipes
        $values = [
            'A' => ['A', 'ÁS', 'ACE'],
            '2' => ['2', 'DOIS'],
            '3' => ['3', 'TRÊS'],
            // ... todos os valores até K
            'K' => ['K', 'REI', 'KING']
        ];
        
        $suits = [
            'hearts' => ['♥', 'COPAS', 'HEARTS'],
            'diamonds' => ['♦', 'OUROS', 'DIAMONDS'],
            'clubs' => ['♣', 'PAUS', 'CLUBS'],
            'spades' => ['♠', 'ESPADAS', 'SPADES']
        ];
        
        $text = trim($text);
        $lines = explode("\n", $text);
        
        // Tenta encontrar valor e naipe
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Verifica valor da carta
            foreach ($values as $value => $patterns) {
                foreach ($patterns as $pattern) {
                    if (stripos($line, $pattern) !== false) {
                        $cardValue = $value;
                        break 2;
                    }
                }
            }
        }
        
        // Verifica naipe da carta
        foreach ($lines as $line) {
            $line = trim($line);
            
            foreach ($suits as $suit => $patterns) {
                foreach ($patterns as $pattern) {
                    if (stripos($line, $pattern) !== false) {
                        $cardSuit = $suit;
                        break 2;
                    }
                }
            }
        }
        
        return (isset($cardValue) && isset($cardSuit)) 
            ? ['value' => $cardValue, 'suit' => $suit] 
            : null;
    }
    
    /**
     * Calcula a confiança do reconhecimento baseado no tamanho do bounding box
     * @param array $vertices Vértices do bounding box do texto
     * @param string $text Texto reconhecido
     * @return float Confiança (0-1)
     */
    private function calculateConfidence($vertices, $text) {
        // Lógica para calcular confiança baseada no tamanho do texto e bounding box
        // Implementação simplificada - pode ser melhorada
        $expectedSize = 100; // Tamanho esperado para texto de carta
        $width = abs($vertices[1]->getX() - $vertices[0]->getX());
        $height = abs($vertices[2]->getY() - $vertices[0]->getY());
        $sizeScore = min(1, ($width * $height) / $expectedSize);
        
        $textLength = strlen($text);
        $lengthScore = min(1, $textLength / 10); // Texto típico tem ~10 chars
        
        return ($sizeScore * 0.6) + ($lengthScore * 0.4);
    }
    
    public function __destruct() {
        // Fecha a conexão com o Google Vision
        if ($this->client) {
            $this->client->close();
        }
    }
}
?>