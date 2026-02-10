<?php
/**
 * Funções Auxiliares Globais
 */

/**
 * Retorna o símbolo HTML para um naipe
 */
function getSuitSymbol($suit) {
    $symbols = [
        'hearts' => '♥',
        'diamonds' => '♦',
        'clubs' => '♣',
        'spades' => '♠'
    ];
    
    return $symbols[strtolower($suit)] ?? $suit;
}

/**
 * Valida se uma carta é válida
 */
function isValidCard($value, $suit) {
    $validValues = ['A', '2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K'];
    $validSuits = ['hearts', 'diamonds', 'clubs', 'spades'];
    
    return in_array($value, $validValues) && in_array($suit, $validSuits);
}

/**
 * Log de mensagens do sistema
 */
function logMessage($message, $level = 'info') {
    $logFile = LOGS_DIR . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    
    $formattedMessage = "[$timestamp] [$level] $message" . PHP_EOL;
    file_put_contents($logFile, $formattedMessage, FILE_APPEND);
}

/**
 * Converte valor da carta para peso numérico
 */
function cardValueToWeight($value) {
    $weights = [
        'A' => 14,
        'K' => 13,
        'Q' => 12,
        'J' => 11,
        '10' => 10,
        '9' => 9,
        '8' => 8,
        '7' => 7,
        '6' => 6,
        '5' => 5,
        '4' => 4,
        '3' => 3,
        '2' => 2
    ];
    
    return $weights[$value] ?? 0;
}

/**
 * Retorna a hora formatada para análise temporal
 */
function getTimeSlot($timestamp) {
    $hour = date('G', strtotime($timestamp));
    
    if ($hour >= 6 && $hour < 12) return 'morning';
    if ($hour >= 12 && $hour < 18) return 'afternoon';
    if ($hour >= 18 && $hour < 24) return 'evening';
    return 'night';
}