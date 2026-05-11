<?php
session_start();

define('DATA_DIR', __DIR__ . '/data/');
define('COMPS_DIR', DATA_DIR . 'comps/');

// Criar diretórios se não existirem
if (!is_dir(DATA_DIR)) mkdir(DATA_DIR, 0777, true);
if (!is_dir(COMPS_DIR)) mkdir(COMPS_DIR, 0777, true);

// Constantes (definidas no topo)
const FILE_USERS   = 'users.json';
const FILE_MATCHES = 'matches.json';
const FILE_BETS    = 'bets.json';

function loadJson($file) {
    $path = DATA_DIR . $file;
    if (!file_exists($path)) return [];
    $data = json_decode(file_get_contents($path), true);
    return is_array($data) ? $data : [];
}

function saveJson($file, $data) {
    $path = DATA_DIR . $file;
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// Funções para o chat
function loadMessages() {
    $messages = loadJson('conversas.json');
    return is_array($messages) ? $messages : [];
}

function saveMessage($message) {
    $messages = loadMessages();
    $messages[] = $message;
    saveJson('conversas.json', $messages);
}

// Funções para histórico de apostas (comunidade)
function loadAllBetsWithDetails() {
    $bets = loadJson(FILE_BETS);
    $matches = loadJson(FILE_MATCHES);
    $users = loadJson(FILE_USERS);
    
    $result = [];
    
    foreach ($bets as $bet) {
        $match = null;
        foreach ($matches as $m) {
            if ($m['id'] == $bet['match_id']) {
                $match = $m;
                break;
            }
        }
        
        $user = null;
        foreach ($users as $u) {
            if ($u['id'] == $bet['user_id']) {
                $user = $u;
                break;
            }
        }
        
        if (!$match || !$user) continue;
        
        $betItem = [
            'id' => $bet['id'] ?? uniqid(),
            'user_id' => $bet['user_id'],
            'user_name' => $user['name'],
            'match_id' => $bet['match_id'],
            'team1' => $match['team1'],
            'team2' => $match['team2'],
            'match_date' => $match['date'],
            'type' => $bet['type'],
            'prediction_winner' => $bet['prediction_winner'] ?? $bet['prediction'] ?? null,
            'prediction_score' => $bet['prediction_score'] ?? $bet['prediction'] ?? null,
            'created_at' => $bet['created_at'] ?? date('Y-m-d H:i:s')
        ];
        
        $result[] = $betItem;
    }
    
    // Ordenar por data mais recente
    usort($result, fn($a, $b) => strtotime($b['created_at']) <=> strtotime($a['created_at']));
    return $result;
}

// Função para salvar comprovante
function saveComprovante($file) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Erro no upload do arquivo'];
    }
    
    $extensao = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $nomeArquivo = time() . '_' . uniqid() . '.' . $extensao;
    $caminhoArquivo = COMPS_DIR . $nomeArquivo;
    
    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'message' => 'Tipo de arquivo não permitido. Use JPG, PNG ou PDF'];
    }
    
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['success' => false, 'message' => 'Arquivo muito grande. Máximo 5MB'];
    }
    
    if (move_uploaded_file($file['tmp_name'], $caminhoArquivo)) {
        return ['success' => true, 'path' => $nomeArquivo]; // Salva só o nome, não o caminho completo
    }
    
    return ['success' => false, 'message' => 'Erro ao salvar o arquivo'];
}

// Gera email automaticamente
function generateEmail($nome, $sobrenome) {
    $nomeClean = strtolower(trim($nome));
    $sobrenomeClean = strtolower(trim($sobrenome));
    
    $nomeClean = preg_replace('/[^a-z0-9]/', '', iconv('UTF-8', 'ASCII//TRANSLIT', $nomeClean));
    $sobrenomeClean = preg_replace('/[^a-z0-9]/', '', iconv('UTF-8', 'ASCII//TRANSLIT', $sobrenomeClean));
    
    if ($nomeClean && $sobrenomeClean) {
        return $nomeClean . '.' . $sobrenomeClean . '@sfa.adv.br';
    } elseif ($nomeClean) {
        return $nomeClean . '@sfa.adv.br';
    }
    return null;
}

// Cria novo usuário
function createUser($nome, $sobrenome, $email, $comprovanteNome) {
    $users = loadJson(FILE_USERS);
    
    // Verifica se email já existe
    foreach ($users as $user) {
        if (strtolower($user['email']) === strtolower($email)) {
            return ['success' => false, 'message' => 'Este e-mail já está cadastrado'];
        }
    }
    
    $newUser = [
        'id'          => count($users) + 1,
        'name'        => $nome . ' ' . $sobrenome,
        'nome'        => $nome,
        'sobrenome'   => $sobrenome,
        'email'       => strtolower($email),
        'password'    => password_hash('1234', PASSWORD_DEFAULT),
        'comprovante' => $comprovanteNome,           // Apenas o nome do arquivo
        'created_at'  => date('Y-m-d H:i:s'),
        'status'      => 'pending',
        'role'        => 'user'
    ];
    
    $users[] = $newUser;
    saveJson(FILE_USERS, $users);
    
    return ['success' => true, 'user' => $newUser];
}

// Outras funções (mantidas)
function isAdmin() { /* ... */ }
function getCurrentUser() { /* ... */ }
function updateUserStatus($userId, $status) { /* ... */ }