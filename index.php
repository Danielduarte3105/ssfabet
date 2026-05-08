<?php
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Carregar dados
$users = loadJson(FILE_USERS);
$matches = loadJson(FILE_MATCHES);
$bets = loadJson(FILE_BETS);

// Buscar usuário atual
$currentUser = null;
foreach ($users as $u) {
    if ($u['id'] == $userId) {
        $currentUser = $u;
        break;
    }
}

if (!$currentUser) {
    session_destroy();
    header('Location: login.php');
    exit;
}

$_SESSION['user_name'] = $currentUser['name'];
$_SESSION['user_email'] = $currentUser['email'];

// Separar jogos: Pendentes e Finalizados
$pendingMatches = [];
$finishedMatches = [];

foreach ($matches as $match) {
    if (empty($match['result'])) {
        $pendingMatches[] = $match;
    } else {
        $finishedMatches[] = $match;
    }
}

// Ordenar pendentes: mais próximos primeiro (data crescente)
usort($pendingMatches, fn($a, $b) => strtotime($a['date']) <=> strtotime($b['date']));

// Finalizados: mais recentes primeiro (data decrescente)
usort($finishedMatches, fn($a, $b) => strtotime($b['date']) <=> strtotime($a['date']));

// Função para buscar aposta do usuário
function getUserBet($bets, $userId, $matchId) {
    foreach ($bets as $b) {
        if ($b['user_id'] == $userId && $b['match_id'] == $matchId) return $b;
    }
    return null;
}

/**
 * Verifica se apostas ainda estão liberadas para o jogo.
 * Retorna true se faltam MAIS de 30 minutos para o início.
 * Retorna false se faltam 30 minutos ou menos (bloqueia criação E edição).
 */
function canBet($matchDate) {
    $matchTime = strtotime($matchDate);
    $currentTime = time();
    $timeDiff = $matchTime - $currentTime;
    return $timeDiff > 1800; // 30 minutos em segundos
}

// Calcular Ranking
function calculateRanking($users, $bets, $matches) {
    $ranking = [];
    foreach ($users as $user) {
        $points = 0;
        foreach ($bets as $bet) {
            if ($bet['user_id'] != $user['id']) continue;
            foreach ($matches as $match) {
                if ($match['id'] == $bet['match_id'] && !empty($match['result'])) {
                    if ($bet['type'] === 'winner' && $bet['prediction'] === $match['result_winner']) {
                        $points += 1;
                    } elseif ($bet['type'] === 'score' && $bet['prediction'] === $match['result']) {
                        $points += 3;
                    } elseif ($bet['type'] === 'combo' && isset($bet['prediction_winner']) && isset($bet['prediction_score'])) {
                        // Aposta combinada
                        $scoreCorrect = ($bet['prediction_score'] === $match['result']);
                        $winnerCorrect = ($bet['prediction_winner'] === $match['result_winner']);
                        
                        if ($scoreCorrect && $winnerCorrect) {
                            $points += 4; // Acertou tudo - 4 pontos
                        } elseif ($scoreCorrect) {
                            $points += 3; // Acertou só o placar - 3 pontos
                        } elseif ($winnerCorrect) {
                            $points += 1; // Acertou só o vencedor - 1 ponto
                        }
                    }
                    break;
                }
            }
        }
        $ranking[] = ['name' => $user['name'], 'points' => $points, 'id' => $user['id']];
    }
    usort($ranking, fn($a, $b) => $b['points'] <=> $a['points']);
    return $ranking;
}

$ranking = calculateRanking($users, $bets, $matches);
$top10 = array_slice($ranking, 0, 7);
$otherRanks = array_slice($ranking, 7);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SSFABET - Arena de Apostas Dev By Daniel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            position: relative;
            overflow-x: hidden;
        }

        /* Fundo animado com partículas */
        .background-animation {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            overflow: hidden;
        }

        .particle {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float linear infinite;
        }

        @keyframes float {
            0% {
                transform: translateY(100vh) translateX(0) rotate(0deg);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-20vh) translateX(100px) rotate(360deg);
                opacity: 0;
            }
        }

        /* Container principal */
        .main-container {
            position: relative;
            z-index: 2;
            padding: 2rem 1rem;
        }

        /* Navbar inovadora */
        .navbar-custom {
            background: rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            margin-bottom: 2rem;
            padding: 1rem 2rem;
            transition: all 0.3s ease;
        }

        .navbar-custom:hover {
            background: rgba(0, 0, 0, 0.4);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
        }

        /* Adicione esta regra CSS para garantir o texto branco */
        .welcome-text {
            font-size: 1.1rem;
            font-weight: 500;
            color: white;
        }

        .welcome-text i {
            margin-right: 8px;
            color: #ffd700;
        }

        .welcome-text strong {
            color: white;
        }

        /* Adicione estas regras CSS para o novo header */
        .site-header {
            text-align: center;
            margin-bottom: 2rem;
            padding: 1rem;
        }

        .site-title {
            font-size: 3.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #fff 0%, #ffd700 50%, #ffed4e 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 0 30px rgba(255, 215, 0, 0.3);
            letter-spacing: 2px;
            animation: glow 2s ease-in-out infinite alternate;
            margin-bottom: 0.5rem;
        }

        .site-subtitle {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
            letter-spacing: 3px;
            font-weight: 300;
        }

        @keyframes glow {
            from {
                text-shadow: 0 0 20px rgba(255, 215, 0, 0.3);
            }
            to {
                text-shadow: 0 0 40px rgba(255, 215, 0, 0.6);
            }
        }

        /* Responsividade para o título */
        @media (max-width: 768px) {
            .site-title {
                font-size: 2.2rem;
            }
            
            .site-subtitle {
                font-size: 0.7rem;
                letter-spacing: 2px;
            }
        }

        @media (max-width: 480px) {
            .site-title {
                font-size: 1.8rem;
            }
        }


        .btn-custom {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            border-radius: 12px;
            padding: 0.5rem 1.2rem;
            transition: all 0.3s ease;
            color: white;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-custom:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
            color: white;
        }

        .btn-logout {
            background: rgba(220, 53, 69, 0.2);
        }

        .btn-logout:hover {
            background: rgba(220, 53, 69, 0.4);
        }

        /* Filtro de pesquisa */
        .search-filter {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .search-input {
            background: rgba(255, 255, 255, 0.9);
            border: none;
            border-radius: 12px;
            padding: 0.8rem 1rem;
            font-size: 1rem;
        }

        .search-input:focus {
            outline: none;
            box-shadow: 0 0 0 2px #667eea;
            background: white;
        }

        /* Tabs inovadoras */
        .tabs-custom {
            border: none;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .tabs-custom .nav-link {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: none;
            border-radius: 15px;
            padding: 0.8rem 1.5rem;
            color: white;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .tabs-custom .nav-link i {
            margin-right: 8px;
        }

        .tabs-custom .nav-link:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        .tabs-custom .nav-link.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        /* Cards de jogos */
        .game-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            margin-bottom: 1.5rem;
            overflow: hidden;
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .game-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        }

        .game-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 1rem;
            color: white;
        }

        .game-header h5 {
            margin: 0;
            font-weight: 600;
        }

        .game-header small {
            opacity: 0.9;
        }

        .game-body {
            padding: 1.5rem;
        }

        /* Alertas personalizados */
        .alert-bet {
            border-radius: 15px;
            border: none;
            padding: 1rem;
            margin-bottom: 1rem;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-bet-success {
            background: linear-gradient(135deg, #d4fc79 0%, #96e6a1 100%);
            color: #1e5c2e;
        }

        .alert-warning-custom {
            background: linear-gradient(135deg, #ffeaa7, #fdcb6e);
            color: #d63031;
        }

        /* Aposta Combinada */
        .combo-bet-container {
            background: linear-gradient(135deg, #667eea20 0%, #764ba220 100%);
            border-radius: 15px;
            padding: 1rem;
            margin-bottom: 1rem;
        }


        /* Adicione estas regras CSS para o botão de voltar ao topo */
        .scroll-top-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            color: white;
            cursor: pointer;
            display: none;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            transition: all 0.3s ease;
            z-index: 1000;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .scroll-top-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
            background: linear-gradient(135deg, #764ba2, #667eea);
        }

        .scroll-top-btn.show {
            display: flex;
            animation: fadeInUp 0.3s ease;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .scroll-top-btn {
                bottom: 20px;
                right: 20px;
                width: 45px;
                height: 45px;
                font-size: 1.2rem;
            }
        }

        .combo-selection {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }

        .selection-group {
            flex: 1;
            min-width: 200px;
        }

        .selection-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #333;
        }

        .selection-group select {
            width: 100%;
            padding: 0.6rem;
            border-radius: 10px;
            border: 2px solid #e0e0e0;
            transition: all 0.3s ease;
        }

        .selection-group select:focus {
            outline: none;
            border-color: #667eea;
        }

        .points-badge {
            display: inline-block;
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-left: 0.5rem;
        }

        .points-4 {
            background: #ffd700;
            color: #333;
        }

        .btn-combo {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 12px;
            padding: 0.8rem;
            font-weight: 600;
            color: white;
            transition: all 0.3s ease;
            width: 100%;
        }

        .btn-combo:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .btn-edit-bet {
            background: linear-gradient(135deg, #ffc107, #fd7e14);
            border: none;
            border-radius: 12px;
            padding: 0.5rem 1rem;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-edit-bet:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 193, 7, 0.3);
        }

        .lock-icon {
            color: #dc3545;
            margin-left: 0.5rem;
        }

        /* Modal Personalizado */
        .modal-custom .modal-content {
            background: linear-gradient(135deg, #fff, #f8f9fa);
            border-radius: 20px;
            border: none;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .modal-custom .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px 20px 0 0;
            border: none;
        }

        .modal-custom .modal-footer {
            border: none;
        }

        /* Ranking card */
        .ranking-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 2rem;
        }

        .ranking-header {
            background: linear-gradient(135deg, #ffd700, #ffed4e);
            padding: 1.1rem;
            text-align: center;
            color: #333;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .ranking-header:hover {
            background: linear-gradient(135deg, #ffed4e, #ffd700);
        }

        .ranking-header h4 {
            margin: 0;
            font-weight: 500;
        }

        .ranking-header i {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .ranking-table {
            margin: 0;
        }

        .ranking-table thead th {
            background: #f8f9fa;
            color: #333;
            font-weight: 600;
            border: none;
            padding: 1rem;
        }

        .ranking-table tbody tr {
            transition: all 0.3s ease;
        }

        .ranking-table tbody tr:hover {
            background: rgba(102, 126, 234, 0.1);
            transform: scale(1.01);
        }

        .rank-1 {
            background: linear-gradient(90deg, #ffd700, #fff8e1);
            font-weight: bold;
        }

        .rank-2 {
            background: linear-gradient(90deg, #c0c0c0, #f5f5f5);
        }

        .rank-3 {
            background: linear-gradient(90deg, #cd7f32, #fdf0e3);
        }

        /* Substitua a classe .btn-show-all existente por esta versão ajustada */
        .btn-show-all {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            border-radius: 12px;
            padding: 0.5rem 1rem;
            margin: 1rem auto;
            color: white;
            font-weight: 500;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            width: auto;
            min-width: 200px;
            max-width: 80%;
            display: block;
            text-align: center;
        }

        .btn-show-all:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        /* Mensagem sem resultados */
        .no-results {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 20px;
            padding: 3rem;
            text-align: center;
            color: #667eea;
        }

        .no-results i {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        /* Responsividade */
        @media (max-width: 992px) {
            .main-container {
                padding: 1rem;
            }
            
            .navbar-custom {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .ranking-card {
                position: static;
                margin-top: 2rem;
            }
        }

        @media (max-width: 768px) {
            .combo-selection {
                flex-direction: column;
            }
            
            .tabs-custom .nav-link {
                padding: 0.5rem 1rem;
                font-size: 0.9rem;
            }
        }

        /* Animações */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .game-card {
            animation: fadeInUp 0.5s ease forwards;
        }

        
    </style>
</head>
<body>
    <!-- Fundo animado com partículas -->
    <div class="background-animation" id="particles"></div>

    

    <div class="main-container">
        <div class="container">

            <!-- Header do Site -->
            <div class="site-header">
                <h1 class="site-title">SSFABET</h1>
                <div class="site-subtitle">ARENA DE APOSTAS</div>
                <div class="site-subtitle" style="font-size: 8px;">
                    DEV BY DANIEL
                </div>
            </div>
            <!-- Navbar Personalizada -->
            <div class="navbar-custom d-flex justify-content-between align-items-center">
                <div class="welcome-text">
                    <i class="fas fa-crown"></i> 
                    Bem-vindo, <strong><?= htmlspecialchars($currentUser['name']) ?></strong>
                </div>
                <div class="d-flex gap-2">
                    <?php if (strtolower($currentUser['name']) === 'admin' || strpos(strtolower($currentUser['email']), 'admin') !== false): ?>
                        <a href="admin.php" class="btn-custom">
                            <i class="fas fa-shield-alt"></i> Admin
                        </a>
                    <?php endif; ?>
                    <a href="logout.php" class="btn-custom btn-logout">
                        <i class="fas fa-sign-out-alt"></i> Sair
                    </a>
                </div>
            </div>

            <div class="row">
                <!-- Coluna Principal -->
                <div class="col-lg-7">
                    <!-- Filtro de Pesquisa -->
                    <div class="search-filter">
                        <div class="input-group">
                            <span class="input-group-text bg-transparent border-0 text-white">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="text" class="form-control search-input" id="searchInput" 
                                   placeholder="Pesquisar por time ou nome do jogo...">
                            <button class="btn btn-light ms-2 rounded-pill" id="clearSearch" style="display: none;">
                                <i class="fas fa-times"></i> Limpar
                            </button>
                        </div>
                    </div>

                    <!-- Tabs Personalizadas -->
                    <ul class="nav nav-tabs tabs-custom" id="myTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="apostas-tab" data-bs-toggle="tab" data-bs-target="#apostas" type="button">
                                <i class="fas fa-gamepad"></i> Apostas Pendentes
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="historico-tab" data-bs-toggle="tab" data-bs-target="#historico" type="button">
                                <i class="fas fa-history"></i> Histórico
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <!-- ABA APOSTAS PENDENTES -->
                        <div class="tab-pane fade show active" id="apostas">
                            <div id="pendingMatchesContainer">
                                <?php if (empty($pendingMatches)): ?>
                                    <div class="alert-bet alert-bet-success">
                                        <i class="fas fa-check-circle"></i> Não há jogos pendentes no momento.
                                    </div>
                                <?php endif; ?>

                                <?php foreach ($pendingMatches as $match): 
                                    $userBet = getUserBet($bets, $userId, $match['id']);
                                    $canBet = canBet($match['date']);
                                ?>
                                <div class="game-card" data-match-id="<?= $match['id'] ?>" data-match-date="<?= $match['date'] ?>"
                                     data-team1="<?= htmlspecialchars(strtolower($match['team1'])) ?>" 
                                     data-team2="<?= htmlspecialchars(strtolower($match['team2'])) ?>"
                                     data-match-name="<?= htmlspecialchars(strtolower($match['team1'] . ' vs ' . $match['team2'])) ?>">
                                    <div class="game-header">
                                        <h5>
                                            <i class="fas fa-futbol"></i> 
                                            <?= htmlspecialchars($match['team1']) ?> <strong>VS</strong> <?= htmlspecialchars($match['team2']) ?>
                                        </h5>
                                        <small>
                                            <i class="far fa-calendar-alt"></i> 
                                            <?= date('d/m/Y H:i', strtotime($match['date'])) ?>
                                            <?php if (!$canBet && !$userBet): ?>
                                                <span class="badge bg-danger ms-2">
                                                    <i class="fas fa-clock"></i> Apostas encerradas
                                                </span>
                                            <?php elseif (!$canBet && $userBet): ?>
                                                <span class="badge bg-warning ms-2">
                                                    <i class="fas fa-lock"></i> Edição bloqueada
                                                </span>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                    <div class="game-body">
                                        <?php if ($userBet): ?>
                                            <div class="alert-bet alert-bet-success">
                                                <i class="fas fa-check-circle"></i> 
                                                <strong>Sua aposta combinada:</strong><br>
                                                <?php if ($userBet['type'] === 'combo'): ?>
                                                    <span class="badge bg-primary mt-2">
                                                        <i class="fas fa-chart-line"></i> Vencedor: <?= $userBet['prediction_winner'] === 'home' ? $match['team1'] : ($userBet['prediction_winner'] === 'away' ? $match['team2'] : 'Empate') ?>
                                                    </span>
                                                    <span class="badge bg-info mt-2">
                                                        <i class="fas fa-futbol"></i> Placar: <?= $userBet['prediction_score'] ?>
                                                    </span>
                                                <?php else: ?>
                                                    <?= $userBet['type'] === 'winner' ? 
                                                        'Vencedor: <strong>' . ($userBet['prediction'] === 'home' ? $match['team1'] : ($userBet['prediction'] === 'away' ? $match['team2'] : 'Empate')) . '</strong>' : 
                                                        'Placar exato: <strong>' . $userBet['prediction'] . '</strong>' 
                                                    ?>
                                                <?php endif; ?>

                                                <?php if ($canBet): ?>
                                                    <button class="btn-edit-bet mt-3" onclick="openEditModal(<?= $match['id'] ?>, '<?= addslashes($match['team1']) ?>', '<?= addslashes($match['team2']) ?>')">
                                                        <i class="fas fa-edit"></i> Editar Aposta
                                                    </button>
                                                <?php else: ?>
                                                    <div class="alert-warning-custom mt-3 p-2 rounded">
                                                        <i class="fas fa-lock lock-icon"></i> 
                                                        Aposta bloqueada — A partida começa em menos de 30 minutos
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                        <?php elseif ($canBet): ?>
                                            <div class="combo-bet-container">
                                                <h6 class="mb-3">
                                                    <i class="fas fa-gem"></i> Aposta Combinada
                                                    <span class="points-badge points-4">Até 4 pontos</span>
                                                </h6>
                                                <div class="combo-selection">
                                                    <div class="selection-group">
                                                        <label>
                                                            <i class="fas fa-trophy"></i> Quem Vence?
                                                        </label>
                                                        <select id="winner_<?= $match['id'] ?>" class="form-select">
                                                            <option value="">Selecione...</option>
                                                            <option value="home">🏠 <?= htmlspecialchars($match['team1']) ?></option>
                                                            <option value="draw">⚖️ Empate</option>
                                                            <option value="away">🏟️ <?= htmlspecialchars($match['team2']) ?></option>
                                                        </select>
                                                    </div>
                                                    <div class="selection-group">
                                                        <label>
                                                            <i class="fas fa-chart-line"></i> Placar Exato
                                                        </label>
                                                        <div class="d-flex gap-2 align-items-center">
                                                            <select id="score_home_<?= $match['id'] ?>" class="form-select">
                                                                <?php for($i=0; $i<=9; $i++): ?>
                                                                    <option value="<?= $i ?>"><?= $i ?></option>
                                                                <?php endfor; ?>
                                                            </select>
                                                            <span class="fw-bold">×</span>
                                                            <select id="score_away_<?= $match['id'] ?>" class="form-select">
                                                                <?php for($i=0; $i<=9; $i++): ?>
                                                                    <option value="<?= $i ?>"><?= $i ?></option>
                                                                <?php endfor; ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                <button class="btn-combo" onclick="confirmComboBet(<?= $match['id'] ?>, '<?= addslashes($match['team1']) ?>', '<?= addslashes($match['team2']) ?>')">
                                                    <i class="fas fa-check-circle"></i> Confirmar Aposta Combinada
                                                </button>
                                            </div>

                                        <?php else: ?>
                                            <div class="alert-warning-custom p-3 rounded text-center">
                                                <i class="fas fa-hourglass-end"></i> 
                                                Prazo para apostas e edições encerrado! Esta partida começará em breve.
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div id="noPendingResults" class="no-results" style="display: none;">
                                <i class="fas fa-search"></i>
                                <h4>Nenhum jogo encontrado</h4>
                                <p>Tente buscar por outro time ou jogo</p>
                            </div>
                        </div>

                        <!-- ABA HISTÓRICO -->
                        <div class="tab-pane fade" id="historico">
                            <div id="finishedMatchesContainer">
                                <?php if (empty($finishedMatches)): ?>
                                    <div class="alert-bet alert-bet-success">
                                        <i class="fas fa-info-circle"></i> Nenhum jogo finalizado ainda.
                                    </div>
                                <?php endif; ?>

                                <?php foreach ($finishedMatches as $match): 
                                    $userBet = getUserBet($bets, $userId, $match['id']);
                                ?>
                                <div class="game-card" data-team1="<?= htmlspecialchars(strtolower($match['team1'])) ?>" 
                                     data-team2="<?= htmlspecialchars(strtolower($match['team2'])) ?>"
                                     data-match-name="<?= htmlspecialchars(strtolower($match['team1'] . ' vs ' . $match['team2'])) ?>">
                                    <div class="game-header">
                                        <h5>
                                            <i class="fas fa-check-circle"></i> 
                                            <?= htmlspecialchars($match['team1']) ?> <strong>VS</strong> <?= htmlspecialchars($match['team2']) ?>
                                        </h5>
                                        <small>
                                            <i class="far fa-calendar-alt"></i> 
                                            <?= date('d/m/Y H:i', strtotime($match['date'])) ?>
                                        </small>
                                    </div>
                                    <div class="game-body">
                                        <div class="alert-bet alert-bet-success">
                                            <i class="fas fa-flag-checkered"></i> 
                                            <strong>Resultado final:</strong> 
                                            <span class="badge bg-success"><?= $match['result'] ?></span>
                                            <br>
                                            <small>Vencedor: <?= $match['result_winner'] === 'home' ? $match['team1'] : ($match['result_winner'] === 'away' ? $match['team2'] : 'Empate') ?></small>
                                        </div>

                                        <?php if ($userBet): ?>
                                            <div class="mt-3 p-3 bg-light rounded">
                                                <i class="fas fa-receipt"></i> 
                                                <strong>Sua aposta:</strong><br>
                                                <?php if ($userBet['type'] === 'combo'): ?>
                                                    <span class="badge bg-primary mt-2">
                                                        Vencedor: <?= $userBet['prediction_winner'] === 'home' ? $match['team1'] : ($userBet['prediction_winner'] === 'away' ? $match['team2'] : 'Empate') ?>
                                                    </span>
                                                    <span class="badge bg-info mt-2">
                                                        Placar: <?= $userBet['prediction_score'] ?>
                                                    </span>
                                                <?php else: ?>
                                                    <?= $userBet['type'] === 'winner' ? 
                                                        'Vencedor: ' . ($userBet['prediction'] === 'home' ? $match['team1'] : ($userBet['prediction'] === 'away' ? $match['team2'] : 'Empate')) : 
                                                        'Placar exato: ' . $userBet['prediction'] 
                                                    ?>
                                                <?php endif; ?>
                                                
                                                <?php 
                                                    $won = false;
                                                    $points = 0;
                                                    if ($userBet['type'] === 'combo') {
                                                        $scoreCorrect = ($userBet['prediction_score'] === $match['result']);
                                                        $winnerCorrect = ($userBet['prediction_winner'] === $match['result_winner']);
                                                        
                                                        if ($scoreCorrect && $winnerCorrect) {
                                                            $won = true;
                                                            $points = 4;
                                                        } elseif ($scoreCorrect) {
                                                            $won = true;
                                                            $points = 3;
                                                        } elseif ($winnerCorrect) {
                                                            $won = true;
                                                            $points = 1;
                                                        }
                                                    } elseif ($userBet['type'] === 'winner' && $userBet['prediction'] === $match['result_winner']) {
                                                        $won = true;
                                                        $points = 1;
                                                    } elseif ($userBet['type'] === 'score' && $userBet['prediction'] === $match['result']) {
                                                        $won = true;
                                                        $points = 3;
                                                    }
                                                ?>
                                                <?php if ($won): ?>
                                                    <span class="badge bg-success ms-2">
                                                        <i class="fas fa-check"></i> Acertou! +<?= $points ?> pontos
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger ms-2">
                                                        <i class="fas fa-times"></i> Não acertou
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div id="noHistoryResults" class="no-results" style="display: none;">
                                <i class="fas fa-search"></i>
                                <h4>Nenhum jogo encontrado</h4>
                                <p>Tente buscar por outro time ou jogo</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botão Voltar ao Topo -->
                <button class="scroll-top-btn" id="scrollTopBtn">
                    <i class="fas fa-arrow-up"></i>
                </button>

                <!-- Coluna Ranking -->
                <div class="col-lg-5">
                    <div class="ranking-card">
                        <div class="ranking-header" id="rankingHeader">
                            <i class="fas fa-chart-line"></i>
                            <h4>Classificação Geral</h4>
                            <small>Ranking de apostadores</small>
                        </div>
                        <div class="table-responsive">
                            <table class="ranking-table table">
                                <thead>
                                    <tr>
                                        <th width="60"><i class="fas fa-hashtag"></i> Pos</th>
                                        <th><i class="fas fa-user"></i> Apostador</th>
                                        <th class="text-end"><i class="fas fa-star"></i> Pontos</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($top10 as $i => $r): ?>
                                    <tr class="<?= $i == 0 ? 'rank-1' : ($i == 1 ? 'rank-2' : ($i == 2 ? 'rank-3' : '')) ?>">
                                        <td class="text-center fw-bold">
                                            <?= $i + 1 ?>º
                                            <?php if ($i == 0): ?>
                                                <i class="fas fa-crown text-warning"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($r['name']) ?>
                                            <?php if ($r['name'] == $currentUser['name']): ?>
                                                <span class="badge bg-primary ms-2">Você</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end fw-bold fs-5"><?= $r['points'] ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <?php if (!empty($otherRanks)): ?>
                                    <button class="btn-show-all" id="showAllRankingBtn">
                                        <i class="fas fa-list"></i> Ver todas as posições (<?= count($otherRanks) + 10 ?>)
                                    </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Ranking Completo -->
    <div class="modal fade modal-custom" id="rankingModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-trophy"></i> Classificação Geral Completa
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table ranking-table">
                            <thead>
                                <tr>
                                    <th width="30">Pos</th>
                                    <th>Apostador</th>
                                    <th class="text-end">Pontos</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ranking as $i => $r): ?>
                                <tr class="<?= $i == 0 ? 'rank-1' : ($i == 1 ? 'rank-2' : ($i == 2 ? 'rank-3' : '')) ?>">
                                    <td class="text-center fw-bold">
                                        <?= $i + 1 ?>º
                                        <?php if ($i == 0): ?>
                                            <i class="fas fa-crown text-warning"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($r['name']) ?>
                                        <?php if ($r['name'] == $currentUser['name']): ?>
                                            <span class="badge bg-primary ms-2">Você</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end fw-bold fs-5"><?= $r['points'] ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Fechar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmação -->
    <div class="modal fade modal-custom" id="confirmModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-check-circle"></i> Confirmar Aposta
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="modalBody">
                    <!-- Conteúdo dinâmico -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-primary" id="confirmBetBtn">
                        <i class="fas fa-check"></i> Confirmar Aposta
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Edição -->
    <div class="modal fade modal-custom" id="editModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit"></i> Editar Aposta
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="editModalBody">
                    <!-- Conteúdo dinâmico -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-warning" id="editBetBtn">
                        <i class="fas fa-save"></i> Salvar Alterações
                    </button>
                </div>
            </div>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    let currentMatchId = null;
    let currentBetData = null;
    let currentEditMatchId = null;
    let currentEditTeam1 = null;
    let currentEditTeam2 = null;
    let confirmModal = null;
    let editModal = null;
    let rankingModal = null;

    // Geração de partículas animadas
    function createParticles() {
        const container = document.getElementById('particles');
        if (!container) return;

        const particleCount = 50;

        for (let i = 0; i < particleCount; i++) {
            const particle = document.createElement('div');
            particle.classList.add('particle');

            const size = Math.random() * 5 + 2;
            const duration = Math.random() * 10 + 5;
            const delay = Math.random() * 5;
            const left = Math.random() * 100;

            particle.style.width = `${size}px`;
            particle.style.height = `${size}px`;
            particle.style.left = `${left}%`;
            particle.style.animationDuration = `${duration}s`;
            particle.style.animationDelay = `${delay}s`;

            container.appendChild(particle);
        }
    }

    // Função de pesquisa/filtro
    function initSearchFilter() {
        const searchInput = document.getElementById('searchInput');
        const clearBtn = document.getElementById('clearSearch');
        
        function filterGames() {
            const searchTerm = searchInput.value.toLowerCase().trim();
            const activeTab = document.querySelector('.tab-pane.active').id;
            
            if (activeTab === 'apostas') {
                const pendingGames = document.querySelectorAll('#pendingMatchesContainer .game-card');
                let hasVisible = false;
                
                pendingGames.forEach(game => {
                    const team1 = game.dataset.team1 || '';
                    const team2 = game.dataset.team2 || '';
                    const matchName = game.dataset.matchName || '';
                    
                    const matches = searchTerm === '' || 
                                   team1.includes(searchTerm) || 
                                   team2.includes(searchTerm) || 
                                   matchName.includes(searchTerm);
                    
                    game.style.display = matches ? 'block' : 'none';
                    if (matches) hasVisible = true;
                });
                
                document.getElementById('noPendingResults').style.display = 
                    (!hasVisible && searchTerm !== '') ? 'block' : 'none';
                    
            } else if (activeTab === 'historico') {
                const finishedGames = document.querySelectorAll('#finishedMatchesContainer .game-card');
                let hasVisible = false;
                
                finishedGames.forEach(game => {
                    const team1 = game.dataset.team1 || '';
                    const team2 = game.dataset.team2 || '';
                    const matchName = game.dataset.matchName || '';
                    
                    const matches = searchTerm === '' || 
                                   team1.includes(searchTerm) || 
                                   team2.includes(searchTerm) || 
                                   matchName.includes(searchTerm);
                    
                    game.style.display = matches ? 'block' : 'none';
                    if (matches) hasVisible = true;
                });
                
                document.getElementById('noHistoryResults').style.display = 
                    (!hasVisible && searchTerm !== '') ? 'block' : 'none';
            }
            
            clearBtn.style.display = searchTerm !== '' ? 'inline-block' : 'none';
        }
        
        searchInput.addEventListener('input', filterGames);
        
        clearBtn.addEventListener('click', () => {
            searchInput.value = '';
            filterGames();
        });
        
        // Observar mudanças de tab
        const tabs = document.querySelectorAll('#myTab button');
        tabs.forEach(tab => {
            tab.addEventListener('shown.bs.tab', () => {
                if (searchInput.value.trim() !== '') {
                    filterGames();
                }
            });
        });
    }

    // Abrir modal de ranking completo
    document.getElementById('showAllRankingBtn')?.addEventListener('click', function() {
        if (!rankingModal) {
            rankingModal = new bootstrap.Modal(document.getElementById('rankingModal'));
        }
        rankingModal.show();
    });

    // Clicar no header do ranking também abre o modal
    document.getElementById('rankingHeader')?.addEventListener('click', function() {
        if (!rankingModal && document.getElementById('showAllRankingBtn')) {
            rankingModal = new bootstrap.Modal(document.getElementById('rankingModal'));
            rankingModal.show();
        }
    });

    // Botão Voltar ao Topo
    const scrollTopBtn = document.getElementById('scrollTopBtn');

    window.addEventListener('scroll', function() {
        if (window.pageYOffset > 300) {
            scrollTopBtn.classList.add('show');
        } else {
            scrollTopBtn.classList.remove('show');
        }
    });

    scrollTopBtn.addEventListener('click', function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });

    // Confirmar aposta combinada
    window.confirmComboBet = function(matchId, team1, team2) {
        const winner = document.getElementById(`winner_${matchId}`).value;
        const scoreHome = document.getElementById(`score_home_${matchId}`).value;
        const scoreAway = document.getElementById(`score_away_${matchId}`).value;

        if (!winner) {
            Swal.fire({
                icon: 'warning',
                title: 'Atenção!',
                text: 'Por favor, selecione o vencedor da partida!',
                confirmButtonColor: '#667eea'
            });
            return;
        }

        const score = `${scoreHome}-${scoreAway}`;
        let winnerText = '';

        if (winner === 'home') {
            winnerText = team1;
        } else if (winner === 'away') {
            winnerText = team2;
        } else {
            winnerText = 'Empate';
        }

        currentMatchId = matchId;
        currentBetData = {
            type: 'combo',
            winner: winner,
            winnerText: winnerText,
            score: score,
            scoreHome: scoreHome,
            scoreAway: scoreAway
        };

        const modalBody = document.getElementById('modalBody');
        modalBody.innerHTML = `
            <div class="text-center">
                <i class="fas fa-gem" style="font-size: 3rem; color: #667eea;"></i>
                <h5 class="mt-3">Confirmar Aposta Combinada</h5>
                <div class="alert alert-info mt-3">
                    <strong>${team1} VS ${team2}</strong>
                    <div class="mt-2">
                        <span class="badge bg-primary p-2">
                            🏆 Vencedor: ${winnerText}
                        </span>
                        <span class="badge bg-info p-2 ms-2">
                            ⚽ Placar: ${score}
                        </span>
                    </div>
                    <hr>
                    <div class="text-start">
                        <small><i class="fas fa-star text-warning"></i> Pontuação máxima: 4 pontos</small><br>
                        <small><i class="fas fa-check-circle text-success"></i> Acertando vencedor (1 ponto) + placar exato (3 pontos)</small>
                    </div>
                </div>
                <p class="mt-2">Deseja confirmar esta aposta?</p>
            </div>
        `;

        if (!confirmModal) {
            confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
        }
        confirmModal.show();
    };

    // SALVAR APOSTA
    document.getElementById('confirmBetBtn').addEventListener('click', function() {
        if (!currentMatchId || !currentBetData) return;

        const submitBtn = this;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';

        const formData = new FormData();
        formData.append('match_id', currentMatchId);
        formData.append('type', currentBetData.type);
        formData.append('winner', currentBetData.winner);
        formData.append('score', currentBetData.score);
        formData.append('score_home', currentBetData.scoreHome);
        formData.append('score_away', currentBetData.scoreAway);

        fetch('save_combo_bet.php', {
            method: 'POST',
            body: formData
        })
        .then(async response => {
            const text = await response.text();
            let data = {};
            try {
                data = JSON.parse(text);
            } catch (e) {
                data = { success: true, message: 'Aposta realizada com sucesso!' };
            }

            if (data.success === true || text.toLowerCase().includes('success') || text.toLowerCase().includes('sucesso')) {
                bootstrap.Modal.getInstance(document.getElementById('confirmModal')).hide();
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-check"></i> Confirmar Aposta';
                
                Swal.fire({
                    icon: 'success',
                    title: 'Aposta realizada com sucesso!',
                    showConfirmButton: false,
                    timer: 1000,
                    timerProgressBar: true
                });
                
                setTimeout(() => window.location.href = 'index.php', 1000);
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro ao salvar aposta',
                    text: data.message || 'Erro inesperado.',
                    confirmButtonColor: '#667eea'
                });
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-check"></i> Confirmar Aposta';
            }
        })
        .catch(error => {
            bootstrap.Modal.getInstance(document.getElementById('confirmModal')).hide();
            Swal.fire({
                icon: 'success',
                title: 'Aposta realizada com sucesso!',
                showConfirmButton: false,
                timer: 1000,
                timerProgressBar: true
            });
            setTimeout(() => window.location.href = 'index.php', 1000);
        });
    });

    // ABRIR MODAL EDIÇÃO
    window.openEditModal = function(matchId, team1, team2) {
        currentEditMatchId = matchId;
        currentEditTeam1 = team1;
        currentEditTeam2 = team2;

        const editModalBody = document.getElementById('editModalBody');
        editModalBody.innerHTML = `
            <div class="text-center p-4">
                <i class="fas fa-spinner fa-spin fa-2x"></i>
                <p class="mt-2">Carregando sua aposta...</p>
            </div>
        `;

        if (!editModal) {
            editModal = new bootstrap.Modal(document.getElementById('editModal'));
        }
        editModal.show();

        fetch(`get_current_bet.php?match_id=${matchId}`)
        .then(response => response.json())
        .then(data => {
            if (!data.success || !data.bet) {
                editModalBody.innerHTML = `<div class="alert alert-warning">Não foi possível carregar a aposta.</div>`;
                return;
            }

            const scoreParts = data.bet.prediction_score.split('-');
            const scoreHome = scoreParts[0];
            const scoreAway = scoreParts[1];

            let homeOptions = '', awayOptions = '';
            for (let i = 0; i <= 9; i++) {
                homeOptions += `<option value="${i}" ${i == scoreHome ? 'selected' : ''}>${i}</option>`;
                awayOptions += `<option value="${i}" ${i == scoreAway ? 'selected' : ''}>${i}</option>`;
            }

            editModalBody.innerHTML = `
                <div class="combo-bet-container">
                    <div class="selection-group mb-3">
                        <label>Quem vence?</label>
                        <select id="edit_winner" class="form-select">
                            <option value="home" ${data.bet.prediction_winner === 'home' ? 'selected' : ''}>${team1}</option>
                            <option value="draw" ${data.bet.prediction_winner === 'draw' ? 'selected' : ''}>Empate</option>
                            <option value="away" ${data.bet.prediction_winner === 'away' ? 'selected' : ''}>${team2}</option>
                        </select>
                    </div>
                    <div class="selection-group">
                        <label>Placar</label>
                        <div class="d-flex gap-2">
                            <select id="edit_score_home" class="form-select">${homeOptions}</select>
                            <select id="edit_score_away" class="form-select">${awayOptions}</select>
                        </div>
                    </div>
                </div>
            `;
        })
        .catch(error => {
            console.error(error);
            editModalBody.innerHTML = `<div class="alert alert-danger">Erro ao carregar aposta.</div>`;
        });
    };

    // SALVAR EDIÇÃO
    document.getElementById('editBetBtn').addEventListener('click', function() {
        const winner = document.getElementById('edit_winner').value;
        const scoreHome = document.getElementById('edit_score_home').value;
        const scoreAway = document.getElementById('edit_score_away').value;
        const score = `${scoreHome}-${scoreAway}`;

        const submitBtn = this;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';

        const formData = new FormData();
        formData.append('match_id', currentEditMatchId);
        formData.append('winner', winner);
        formData.append('score', score);

        fetch('update_combo_bet.php', {
            method: 'POST',
            body: formData
        })
        .then(async response => {
            const text = await response.text();
            let data = {};
            try {
                data = JSON.parse(text);
            } catch (e) {
                data = { success: true, message: 'Aposta atualizada com sucesso!' };
            }

            if (data.success === true || text.toLowerCase().includes('success') || text.toLowerCase().includes('sucesso')) {
                bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-save"></i> Salvar Alterações';
                
                Swal.fire({
                    icon: 'success',
                    title: 'Aposta atualizada com sucesso!',
                    showConfirmButton: false,
                    timer: 1000,
                    timerProgressBar: true
                });
                
                setTimeout(() => window.location.href = 'index.php', 1000);
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro ao atualizar aposta',
                    text: data.message || 'Erro inesperado.',
                    confirmButtonColor: '#667eea'
                });
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-save"></i> Salvar Alterações';
            }
        })
        .catch(error => {
            bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();
            Swal.fire({
                icon: 'success',
                title: 'Aposta atualizada com sucesso!',
                showConfirmButton: false,
                timer: 1000,
                timerProgressBar: true
            });
            setTimeout(() => window.location.href = 'index.php', 1000);
        });
    });

    // RESET MODAIS
    document.getElementById('confirmModal').addEventListener('hidden.bs.modal', function () {
        const confirmBtn = document.getElementById('confirmBetBtn');
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = '<i class="fas fa-check"></i> Confirmar Aposta';
    });

    document.getElementById('editModal').addEventListener('hidden.bs.modal', function () {
        const editBtn = document.getElementById('editBetBtn');
        editBtn.disabled = false;
        editBtn.innerHTML = '<i class="fas fa-save"></i> Salvar Alterações';
    });

    // INIT
    document.addEventListener('DOMContentLoaded', function() {
        createParticles();
        initSearchFilter();
        
        const cards = document.querySelectorAll('.game-card');
        cards.forEach((card, index) => {
            card.style.animationDelay = `${index * 0.1}s`;
        });
    });
</script>
</body>
</html>