<?php
require 'config.php';
session_start();

// Garantir que não haja saída antes do JSON
if (ob_get_length()) {
    ob_clean();
}
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método inválido']);
    exit;
}

// Verificar se todos os campos necessários existem
if (!isset($_POST['match_id']) || !isset($_POST['type']) || !isset($_POST['winner']) || !isset($_POST['score'])) {
    echo json_encode(['success' => false, 'message' => 'Dados incompletos']);
    exit;
}

$userId = $_SESSION['user_id'];
$matchId = (int)$_POST['match_id'];
$type = $_POST['type'];
$winner = $_POST['winner'];
$score = $_POST['score'];
$scoreHome = isset($_POST['score_home']) ? (int)$_POST['score_home'] : 0;
$scoreAway = isset($_POST['score_away']) ? (int)$_POST['score_away'] : 0;

try {
    // Carregar dados
    $matches = loadJson(FILE_MATCHES);
    $bets = loadJson(FILE_BETS);
    
    // Verificar se o jogo existe
    $match = null;
    foreach ($matches as $m) {
        if ($m['id'] === $matchId) {
            $match = $m;
            break;
        }
    }
    
    if (!$match) {
        echo json_encode(['success' => false, 'message' => 'Jogo não encontrado']);
        exit;
    }
    
    // Verificar se já passou do prazo (30 min antes)
    $matchTime = strtotime($match['date']);
    $currentTime = time();
    $timeDiff = $matchTime - $currentTime;
    
    if ($timeDiff <= 1800) {
        echo json_encode(['success' => false, 'message' => 'Prazo para apostas encerrado! (30 minutos antes do jogo)']);
        exit;
    }
    
    // Verificar se já existe aposta
    foreach ($bets as $bet) {
        if ($bet['user_id'] == $userId && $bet['match_id'] == $matchId) {
            echo json_encode(['success' => false, 'message' => 'Você já possui uma aposta para este jogo. Use a função de editar.']);
            exit;
        }
    }
    
    // Criar nova aposta combinada
    $newId = count($bets) > 0 ? max(array_column($bets, 'id')) + 1 : 1;
    
    $newBet = [
        'id' => $newId,
        'user_id' => $userId,
        'match_id' => $matchId,
        'type' => 'combo',
        'prediction_winner' => $winner,
        'prediction_score' => $score,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    $bets[] = $newBet;
    
    // Salvar com verificação de erro
    if (saveJson(FILE_BETS, $bets)) {
        echo json_encode(['success' => true, 'message' => 'Aposta realizada com sucesso!'], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao salvar a aposta'], JSON_UNESCAPED_UNICODE);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
exit;
?>