<?php
require 'config.php';
session_start();

// Garantir que não haja saída antes do JSON
ob_clean();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

if (!isset($_GET['match_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID da partida não fornecido']);
    exit;
}

$userId = $_SESSION['user_id'];
$matchId = (int)$_GET['match_id'];

try {
    $bets = loadJson(FILE_BETS);
    
    foreach ($bets as $bet) {
        if ($bet['user_id'] == $userId && $bet['match_id'] == $matchId && isset($bet['type']) && $bet['type'] === 'combo') {
            echo json_encode(['success' => true, 'bet' => $bet]);
            exit;
        }
    }
    
    echo json_encode(['success' => false, 'bet' => null, 'message' => 'Nenhuma aposta encontrada']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro ao carregar dados: ' . $e->getMessage()]);
}
?>