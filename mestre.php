<?php
/**
 * ARQUIVO MESTRE - Configurações Centrais do Sistema
 * Todas as configurações globais e parâmetros do sistema estão aqui
 * Este é o ÚNICO arquivo que requer edição manual
 */

// 1. CONFIGURAÇÕES DO BANCO DE DADOS ==========================================
// Obtidas durante a instalação do XAMPP/MySQL ou do cPanel

/**
 * Endereço do servidor MySQL
 * - Local (XAMPP): geralmente 'localhost'
 * - Hospedagem: ver no cPanel > Banco de Dados > MySQL Databases
 */
define('DB_HOST', 'localhost');

/**
 * Usuário do banco de dados
 * - XAMPP: padrão é 'root'
 * - Hospedagem: criar usuário no cPanel > Banco de Dados > MySQL Databases
 */
define('DB_USER', 'root');

/**
 * Senha do banco de dados
 * - XAMPP: padrão é vazio ''
 * - Hospedagem: definir ao criar usuário MySQL
 */
define('DB_PASS', '');

/**
 * Nome do banco de dados
 * Criar no phpMyAdmin (XAMPP) ou cPanel > MySQL Databases
 * Exemplo: CREATE DATABASE card_system;
 */
define('DB_NAME', 'card_system');

// 2. CONFIGURAÇÕES DO GOOGLE CLOUD VISION API ================================
// Requer conta no Google Cloud Platform (https://cloud.google.com/)

/**
 * Caminho para o arquivo de credenciais JSON
 * - Criar projeto no Google Cloud Console
 * - Ativar a API Vision
 * - Criar credencial de "Conta de Serviço"
 * - Baixar o JSON e colocar na pasta do projeto
 */
define('GOOGLE_CREDENTIALS_PATH', __DIR__ . '/config/google-vision-credentials.json');

/**
 * ID do projeto no Google Cloud
 * Encontrado no Dashboard do Google Cloud Console
 */
define('GOOGLE_PROJECT_ID', 'sitefootocr');

// 3. CONFIGURAÇÕES DO SISTEMA DE CAPTURA =====================================

/**
 * URL do vídeo ao vivo para captura
 * Deve ser uma URL pública acessível
 */
define('VIDEO_SOURCE_URL', 'https://m.geralbet.bet.br/casino/game/1867331?btag=gasreqbujebxbtckmufazlziff&affid=428363');

/**
 * Intervalo de atualização do iframe (em milissegundos)
 * 600000 = 10 minutos
 */
define('REFRESH_INTERVAL', 600000);

/**
 * Intervalo de captura de frames (em segundos)
 * A cada quantos segundos o sistema tira um screenshot
 */
define('CAPTURE_INTERVAL', 30);

// 4. CONFIGURAÇÕES DA IA E ANÁLISE ===========================================

/**
 * Confiança mínima para exibir previsões (0 a 1)
 * 0.95 = 95% de confiança
 */
define('MIN_CONFIDENCE', 0.95);

/**
 * Número de resultados anteriores para análise
 * Quantas jogadas passadas a IA deve considerar
 */
define('ANALYSIS_WINDOW', 50);

/**
 * Limite de cartas no baralho (pode variar)
 */
define('MAX_CARDS_IN_DECK', 400);

// 5. DIRETÓRIOS DO SISTEMA ===================================================

/**
 * Pasta para armazenar screenshots capturados
 * Criar manualmente com permissões 755
 */
define('SCREENSHOTS_DIR', __DIR__ . '/storage/screenshots/');

/**
 * Pasta para armazenar dados de calibração
 */
define('CALIBRATION_DIR', __DIR__ . '/storage/calibration/');

/**
 * Pasta para logs do sistema
 */
define('LOGS_DIR', __DIR__ . '/storage/logs/');

// 6. CONFIGURAÇÕES DE SEGURANÇA ==============================================

/**
 * Chave secreta para sessões e tokens
 * Gerar uma string aleatória complexa
 */
define('SECURITY_KEY', 'sua-chave-secreta-complexa-aqui-123');

/**
 * IPs autorizados para acesso administrativo
 * Deixe vazio para permitir qualquer IP
 */
define('ALLOWED_IPS', ['127.0.0.1', '::1']);

// 7. INCLUSÃO DE DEPENDÊNCIAS ================================================
// Todas as bibliotecas necessárias para o sistema funcionar

require __DIR__ . '/vendor/autoload.php'; // Autoload do Composer
require __DIR__ . '/includes/database.php'; // Conexão com o banco
require __DIR__ . '/includes/ocr-processor.php'; // Processador OCR
require __DIR__ . '/includes/ai-engine.php'; // Motor de IA
require __DIR__ . '/includes/helpers.php'; // Funções auxiliares

// 8. INICIALIZAÇÃO DO SISTEMA ================================================

// Verifica se o sistema está instalado corretamente
if (!file_exists(GOOGLE_CREDENTIALS_PATH)) {
    die("Erro: Arquivo de credenciais do Google Vision não encontrado em " . GOOGLE_CREDENTIALS_PATH);
}

// Cria diretórios necessários se não existirem
$requiredDirs = [SCREENSHOTS_DIR, CALIBRATION_DIR, LOGS_DIR];
foreach ($requiredDirs as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Inicia a sessão segura
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => !empty($_SERVER['HTTPS']),
    'use_strict_mode' => true
]);

// Verificação de IP para área administrativa
if (!empty(ALLOWED_IPS) && !in_array($_SERVER['REMOTE_ADDR'], ALLOWED_IPS)) {
    die("Acesso não autorizado. Seu IP: " . $_SERVER['REMOTE_ADDR']);
}
?>