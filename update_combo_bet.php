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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método inválido']);
    exit;
}

// Verificar se todos os campos necessários existem
if (!isset($_POST['match_id']) || !isset($_POST['winner']) || !isset($_POST['score'])) {
    echo json_encode(['success' => false, 'message' => 'Dados incompletos']);
    exit;
}

$userId = $_SESSION['user_id'];
$matchId = (int)$_POST['match_id'];
$winner = $_POST['winner'];
$score = $_POST['score'];

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
    
    // Verificar se ainda pode editar (30 min antes)
    $matchTime = strtotime($match['date']);
    $currentTime = time();
    $timeDiff = $matchTime - $currentTime;
    
    if ($timeDiff <= 1800) {
        echo json_encode(['success' => false, 'message' => 'Não é mais possível editar! A partida começa em menos de 30 minutos.']);
        exit;
    }
    
    // Atualizar aposta
    $updated = false;
    foreach ($bets as &$bet) {
        if ($bet['user_id'] == $userId && $bet['match_id'] == $matchId && isset($bet['type']) && $bet['type'] === 'combo') {
            $bet['prediction_winner'] = $winner;
            $bet['prediction_score'] = $score;
            $bet['updated_at'] = date('Y-m-d H:i:s');
            $updated = true;
            break;
        }
    }
    
    if ($updated) {
        if (saveJson(FILE_BETS, $bets)) {
            echo json_encode(['success' => true, 'message' => 'Aposta atualizada com sucesso!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao salvar a aposta']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Aposta não encontrada']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
}
?>