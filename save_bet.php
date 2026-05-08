<?php
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$userId = $_SESSION['user_id'];
$matchId = (int)$_POST['match_id'];
$type = $_POST['type'];

$bets = loadJson(FILE_BETS);

// Remover aposta anterior (permitir edição)
$bets = array_filter($bets, fn($b) => !($b['user_id'] == $userId && $b['match_id'] == $matchId));

if ($type === 'combined') {
    $bets[] = [
        'user_id'     => $userId,
        'match_id'    => $matchId,
        'type'        => 'combined',
        'winner_pred' => $_POST['winner_pred'],
        'score_pred'  => $_POST['score_pred'],
        'date'        => date('Y-m-d H:i:s')
    ];
}

saveJson(FILE_BETS, $bets);
header('Location: index.php?success=1');
exit;