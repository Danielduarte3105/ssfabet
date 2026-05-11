<?php
require 'config.php';

header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';

if ($action === 'get_messages') {
    $messages = loadMessages();
    // Ordenar por data
    usort($messages, fn($a, $b) => strtotime($a['created_at'] ?? '') <=> strtotime($b['created_at'] ?? ''));
    
    echo json_encode([
        'success' => true,
        'messages' => array_slice($messages, -50) // últimas 50 mensagens
    ]);
    exit;
}

if ($action === 'send_message') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Não autenticado']);
        exit;
    }
    
    $messageText = trim($_POST['message'] ?? '');
    if (empty($messageText)) {
        echo json_encode(['success' => false, 'message' => 'Mensagem vazia']);
        exit;
    }
    
    $message = [
        'id' => uniqid(),
        'user_id' => $_SESSION['user_id'],
        'user_name' => $_SESSION['user_name'] ?? 'Usuário',
        'message' => $messageText,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    saveMessage($message);
    
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'get_bets_history') {
    $allBets = loadAllBetsWithDetails();
    echo json_encode([
        'success' => true,
        'bets' => $allBets
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Ação inválida']);