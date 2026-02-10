<?php
/**
 * Motor de IA para Análise e Previsão de Jogadas
 * Implementa aprendizado automático baseado em padrões
 */

class AIEngine {
    private $pdo;
    private $currentDeck = [];
    private $patternWeights = [];
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->loadCurrentDeck();
        $this->loadPatternWeights();
    }
    
    /**
     * Carrega o estado atual do baralho
     * Conta quantas cartas de cada tipo já foram jogadas
     */
    private function loadCurrentDeck() {
        // Considera apenas as últimas 400 cartas (tamanho máximo do baralho)
        $stmt = $this->pdo->query("
            SELECT card_value, card_suit, COUNT(*) as count 
            FROM cards 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)
            GROUP BY card_value, card_suit
            ORDER BY created_at DESC
            LIMIT 400
        ");
        
        $this->currentDeck = [];
        while ($row = $stmt->fetch()) {
            $this->currentDeck[$row['card_value']][$row['card_suit']] = $row['count'];
        }
    }
    
    /**
     * Carrega os pesos dos padrões aprendidos
     * Padrões com maior taxa de acerto têm mais peso
     */
    private function loadPatternWeights() {
        $stmt = $this->pdo->query("
            SELECT pattern_hash, success_rate 
            FROM ai_learning 
            WHERE times_used > 5
            ORDER BY success_rate DESC, times_used DESC
            LIMIT 100
        ");
        
        $this->patternWeights = [];
        while ($row = $stmt->fetch()) {
            $this->patternWeights[$row['pattern_hash']] = $row['success_rate'];
        }
    }
    
    /**
     * Analisa os últimos resultados para identificar padrões
     * @param int $limit Quantidade de jogadas anteriores a considerar
     * @return array Padrões identificados
     */
    public function analyzePatterns($limit = 50) {
        $stmt = $this->pdo->prepare("
            SELECT 
                gr.winner,
                c1.card_value as left_value, c1.card_suit as left_suit,
                c2.card_value as right_value, c2.card_suit as right_suit,
                HOUR(gr.created_at) as hour,
                MINUTE(gr.created_at) as minute,
                DAYOFWEEK(gr.created_at) as day_of_week
            FROM game_results gr
            JOIN cards c1 ON gr.left_card_id = c1.id
            JOIN cards c2 ON gr.right_card_id = c2.id
            ORDER BY gr.created_at DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $patterns = [];
        $results = $stmt->fetchAll();
        
        // Agrupa resultados por padrões específicos
        foreach ($results as $result) {
            // Padrão 1: Combinação específica de cartas
            $patternKey = "cards:{$result['left_value']}-{$result['left_suit']}_vs_{$result['right_value']}-{$result['right_suit']}";
            $patterns[$patternKey][$result['winner']] = ($patterns[$patternKey][$result['winner']] ?? 0) + 1;
            
            // Padrão 2: Horário específico
            $timePattern = "time:{$result['hour']}:{$result['minute']}";
            $patterns[$timePattern][$result['winner']] = ($patterns[$timePattern][$result['winner']] ?? 0) + 1;
            
            // Padrão 3: Dia da semana
            $dayPattern = "day:{$result['day_of_week']}";
            $patterns[$dayPattern][$result['winner']] = ($patterns[$dayPattern][$result['winner']] ?? 0) + 1;
            
            // Padrão 4: Cartas do mesmo naipe
            if ($result['left_suit'] == $result['right_suit']) {
                $sameSuitPattern = "same_suit:{$result['left_suit']}";
                $patterns[$sameSuitPattern][$result['winner']] = ($patterns[$sameSuitPattern][$result['winner']] ?? 0) + 1;
            }
        }
        
        return $patterns;
    }
    
    /**
     * Faz uma previsão para a próxima jogada
     * @param array $leftCard Carta do lado esquerdo
     * @param array $rightCard Carta do lado direito
     * @return array Previsão com confiança e explicação
     */
    public function predictOutcome($leftCard, $rightCard) {
        $startTime = microtime(true);
        $currentHour = date('G');
        $currentMinute = date('i');
        $dayOfWeek = date('N');
        
        // 1. Análise básica baseada no valor das cartas
        $basicPrediction = $this->basicCardAnalysis($leftCard, $rightCard);
        
        // 2. Análise de padrões temporais
        $timePatterns = $this->timePatternAnalysis($currentHour, $currentMinute, $dayOfWeek);
        
        // 3. Análise de sequência de cartas
        $sequenceAnalysis = $this->sequenceAnalysis($leftCard, $rightCard);
        
        // 4. Análise de cartas restantes no baralho
        $deckAnalysis = $this->deckAnalysis($leftCard, $rightCard);
        
        // Combina todas as análises
        $combined = $this->combinePredictions([
            $basicPrediction,
            $timePatterns,
            $sequenceAnalysis,
            $deckAnalysis
        ]);
        
        // Calcula tempo de análise
        $analysisTime = round((microtime(true) - $startTime) * 1000, 2);
        
        return [
            'prediction' => $combined['prediction'],
            'confidence' => $combined['confidence'],
            'analysis_time' => $analysisTime,
            'explanations' => [
                'basic_analysis' => $basicPrediction,
                'time_patterns' => $timePatterns,
                'sequence_analysis' => $sequenceAnalysis,
                'deck_analysis' => $deckAnalysis
            ]
        ];
    }
    
    /**
     * Análise básica baseada no valor das cartas
     */
    private function basicCardAnalysis($leftCard, $rightCard) {
        $cardValues = [
            'A' => 14, 'K' => 13, 'Q' => 12, 'J' => 11,
            '10' => 10, '9' => 9, '8' => 8, '7' => 7,
            '6' => 6, '5' => 5, '4' => 4, '3' => 3, '2' => 2
        ];
        
        $leftValue = $cardValues[$leftCard['value']] ?? 0;
        $rightValue = $cardValues[$rightCard['value']] ?? 0;
        
        if ($leftValue > $rightValue) {
            return ['prediction' => 'left', 'confidence' => 0.7];
        } elseif ($rightValue > $leftValue) {
            return ['prediction' => 'right', 'confidence' => 0.7];
        } else {
            return ['prediction' => 'draw', 'confidence' => 0.5];
        }
    }
    
    /**
     * Análise de padrões temporais
     */
    private function timePatternAnalysis($hour, $minute, $dayOfWeek) {
        // Consulta padrões específicos para este horário
        $timePatternKey = "time:{$hour}:{$minute}";
        $dayPatternKey = "day:{$dayOfWeek}";
        
        $timeConfidence = $this->patternWeights[$timePatternKey] ?? 0;
        $dayConfidence = $this->patternWeights[$dayPatternKey] ?? 0;
        
        // Se não houver padrão forte, retorna confiança baixa
        if ($timeConfidence < 0.6 && $dayConfidence < 0.6) {
            return ['prediction' => 'none', 'confidence' => 0];
        }
        
        // Combina os dois padrões temporais
        $combinedConfidence = ($timeConfidence * 0.6) + ($dayConfidence * 0.4);
        
        // Aqui precisaríamos consultar qual a previsão associada a esses padrões
        // Implementação simplificada:
        return [
            'prediction' => $timeConfidence > $dayConfidence ? 'left' : 'right',
            'confidence' => $combinedConfidence * 0.8 // Reduz um pouco a confiança
        ];
    }
    
    /**
     * Combina múltiplas previsões em um resultado final
     */
    private function combinePredictions($predictions) {
        $votes = ['left' => 0, 'right' => 0, 'draw' => 0];
        $totalConfidence = 0;
        
        foreach ($predictions as $pred) {
            if ($pred['prediction'] != 'none' && $pred['confidence'] > 0.3) {
                $votes[$pred['prediction']] += $pred['confidence'];
                $totalConfidence += $pred['confidence'];
            }
        }
        
        if ($totalConfidence == 0) {
            return ['prediction' => 'none', 'confidence' => 0];
        }
        
        // Normaliza os votos
        $leftScore = $votes['left'] / $totalConfidence;
        $rightScore = $votes['right'] / $totalConfidence;
        $drawScore = $votes['draw'] / $totalConfidence;
        
        // Determina o vencedor
        $maxScore = max($leftScore, $rightScore, $drawScore);
        
        if ($maxScore < 0.5) {
            return ['prediction' => 'none', 'confidence' => $maxScore];
        }
        
        $finalPrediction = array_search($maxScore, [
            'left' => $leftScore,
            'right' => $rightScore,
            'draw' => $drawScore
        ]);
        
        return [
            'prediction' => $finalPrediction,
            'confidence' => $maxScore
        ];
    }
    
    /**
     * Atualiza o aprendizado da IA com um novo resultado
     */
    public function updateLearning($leftCard, $rightCard, $actualWinner, $prediction) {
        // Identifica os padrões relevantes para esta jogada
        $patterns = $this->identifyRelevantPatterns($leftCard, $rightCard);
        
        // Para cada padrão, atualiza seu registro de aprendizado
        foreach ($patterns as $patternHash => $patternData) {
            $this->updatePattern($patternHash, $patternData, $actualWinner, $prediction);
        }
        
        // Atualiza o baralho atual
        $this->updateDeckState($leftCard, $rightCard);
    }
    
    /**
     * Identifica padrões relevantes para uma jogada específica
     */
    private function identifyRelevantPatterns($leftCard, $rightCard) {
        $hour = date('G');
        $minute = date('i');
        $dayOfWeek = date('N');
        
        return [
            md5("cards:{$leftCard['value']}-{$leftCard['suit']}_vs_{$rightCard['value']}-{$rightCard['suit']}") => [
                'type' => 'card_combination',
                'parameters' => json_encode([
                    'left_card' => $leftCard,
                    'right_card' => $rightCard
                ])
            ],
            md5("time:{$hour}:{$minute}") => [
                'type' => 'time_pattern',
                'parameters' => json_encode([
                    'hour' => $hour,
                    'minute' => $minute
                ])
            ],
            md5("day:{$dayOfWeek}") => [
                'type' => 'day_pattern',
                'parameters' => json_encode([
                    'day_of_week' => $dayOfWeek
                ])
            ]
        ];
    }
    
    /**
     * Atualiza um padrão específico no banco de dados
     */
    private function updatePattern($patternHash, $patternData, $actualWinner, $prediction) {
        // Verifica se o padrão já existe
        $stmt = $this->pdo->prepare("
            SELECT id, success_rate, times_used 
            FROM ai_learning 
            WHERE pattern_hash = ?
        ");
        $stmt->execute([$patternHash]);
        $existing = $stmt->fetch();
        
        $wasCorrect = ($prediction['prediction'] == $actualWinner) ? 1 : 0;
        $newConfidence = $prediction['confidence'];
        
        if ($existing) {
            // Atualiza padrão existente
            $newTimesUsed = $existing['times_used'] + 1;
            $newSuccessRate = (($existing['success_rate'] * $existing['times_used']) + $wasCorrect) / $newTimesUsed;
            
            $update = $this->pdo->prepare("
                UPDATE ai_learning 
                SET 
                    success_rate = ?,
                    times_used = ?,
                    last_used = NOW()
                WHERE id = ?
            ");
            $update->execute([$newSuccessRate, $newTimesUsed, $existing['id']]);
        } else {
            // Insere novo padrão
            $insert = $this->pdo->prepare("
                INSERT INTO ai_learning (
                    pattern_hash,
                    parameters,
                    success_rate,
                    last_used,
                    times_used
                ) VALUES (?, ?, ?, NOW(), 1)
            ");
            $insert->execute([
                $patternHash,
                $patternData['parameters'],
                $wasCorrect
            ]);
        }
    }
}
?>