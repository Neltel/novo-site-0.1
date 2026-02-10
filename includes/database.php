<?php
/**
 * Sistema de Banco de Dados
 * Conexão centralizada usando as configurações do mestre.php
 */

// Verifica se as constantes estão definidas
if (!defined('DB_HOST') || !defined('DB_USER') || !defined('DB_NAME')) {
    die("Erro: Configurações de banco de dados não encontradas. Verifique mestre.php");
}

try {
    // Cria conexão PDO com o MySQL
    // DSN (Data Source Name) define o tipo de banco, host e nome do banco
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    
    // Opções para a conexão PDO
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Lança exceções em erros
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Retorna arrays associativos
        PDO::ATTR_EMULATE_PREPARES   => false, // Usa prepared statements nativos
    ];
    
    // Instancia o objeto PDO (PHP Data Object)
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    
    // Define o timezone para as datas no banco
    $pdo->exec("SET time_zone = '-03:00';");
    
} catch (PDOException $e) {
    // Em caso de erro, registra no log e exibe mensagem amigável
    error_log("Erro de conexão com o banco: " . $e->getMessage());
    die("Erro ao conectar com o banco de dados. Por favor, tente novamente mais tarde.");
}

/**
 * Função para criar as tabelas necessárias
 * Executada apenas uma vez durante a instalação
 */
function createDatabaseTables($pdo) {
    $queries = [
        // Tabela de cartas reconhecidas
        "CREATE TABLE IF NOT EXISTS cards (
            id INT AUTO_INCREMENT PRIMARY KEY,
            card_value VARCHAR(5) NOT NULL,   // Valor da carta (A, 2, 3, ..., K)
            card_suit VARCHAR(10) NOT NULL,   // Naipe (hearts, diamonds, clubs, spades)
            position ENUM('left', 'right') NOT NULL, // Posição na tela
            confidence FLOAT NOT NULL,        // Confiança do OCR (0-1)
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_card_identity (card_value, card_suit),
            INDEX idx_created_at (created_at)
        ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
        
        // Tabela de resultados das jogadas
        "CREATE TABLE IF NOT EXISTS game_results (
            id INT AUTO_INCREMENT PRIMARY KEY,
            left_card_id INT NOT NULL,       // ID da carta esquerda
            right_card_id INT NOT NULL,       // ID da carta direita
            winner ENUM('left', 'right', 'draw') NOT NULL, // Vencedor
            prediction ENUM('left', 'right', 'draw', 'none') DEFAULT 'none', // Previsão da IA
            analysis_time FLOAT NOT NULL,     // Tempo de análise em ms
            confidence FLOAT DEFAULT NULL,    // Confiança da previsão
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (left_card_id) REFERENCES cards(id),
            FOREIGN KEY (right_card_id) REFERENCES cards(id),
            INDEX idx_created_at (created_at),
            INDEX idx_winner (winner))
        ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
        
        // Tabela de aprendizado da IA
        "CREATE TABLE IF NOT EXISTS ai_learning (
            id INT AUTO_INCREMENT PRIMARY KEY,
            pattern_hash VARCHAR(64) NOT NULL,   // Hash do padrão identificado
            parameters TEXT NOT NULL,           // Parâmetros do padrão (JSON)
            success_rate FLOAT NOT NULL,         // Taxa de acerto (0-1)
            last_used DATETIME NOT NULL,         // Quando foi usado pela última vez
            times_used INT DEFAULT 1,            // Quantas vezes foi usado
            INDEX idx_pattern_hash (pattern_hash),
            INDEX idx_success_rate (success_rate))
        ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
        
        // Tabela de calibração
        "CREATE TABLE IF NOT EXISTS calibration (
            id INT AUTO_INCREMENT PRIMARY KEY,
            side ENUM('left', 'right') NOT NULL, // Lado calibrado
            x1 INT NOT NULL,                     // Coordenada X superior esquerda
            y1 INT NOT NULL,                     // Coordenada Y superior esquerda
            x2 INT NOT NULL,                     // Coordenada X inferior direita
            y2 INT NOT NULL,                     // Coordenada Y inferior direita
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uk_side (side))
        ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
    ];
    
    // Executa cada query
    foreach ($queries as $query) {
        try {
            $pdo->exec($query);
        } catch (PDOException $e) {
            error_log("Erro ao criar tabela: " . $e->getMessage());
            die("Erro durante a instalação do banco de dados.");
        }
    }
}

// Verifica se as tabelas existem e cria se necessário
$tables = $pdo->query("SHOW TABLES LIKE 'cards'")->fetch();
if (!$tables) {
    createDatabaseTables($pdo);
}
?>