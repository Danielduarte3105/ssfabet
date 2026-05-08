<?php
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Verifica se é Admin
$currentUser = null;
$users = loadJson(FILE_USERS);
foreach ($users as $u) {
    if ($u['id'] == $_SESSION['user_id']) {
        $currentUser = $u;
        break;
    }
}

if (!$currentUser || (strtolower($currentUser['name']) !== 'admin' && strpos(strtolower($currentUser['email']), 'admin') === false)) {
    die("<div class='container mt-5 text-center'><div class='alert alert-danger'>⛔ Acesso negado. Você não tem permissão para acessar esta área.</div><a href='index.php' class='btn btn-primary'>Voltar ao início</a></div>");
}

// ================== PROCESSAR NOVO JOGO ==================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_match') {
    $matches = loadJson(FILE_MATCHES);

    $matches[] = [
        'id'            => count($matches) + 1,
        'team1'         => trim($_POST['team1']),
        'team2'         => trim($_POST['team2']),
        'date'          => $_POST['date'],
        'result'        => null,
        'result_winner' => null
    ];

    saveJson(FILE_MATCHES, $matches);
    $success = "✅ Jogo adicionado com sucesso!";
}

// ================== PROCESSAR PLACAR ==================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['match_id'])) {
    $matches = loadJson(FILE_MATCHES);
    $matchId = (int)$_POST['match_id'];
    $golsHome = (int)$_POST['gols_home'];
    $golsAway = (int)$_POST['gols_away'];
    $placar = $golsHome . "-" . $golsAway;

    $result_winner = ($golsHome > $golsAway) ? 'home' : 
                     (($golsAway > $golsHome) ? 'away' : 'draw');

    foreach ($matches as &$match) {
        if ($match['id'] === $matchId) {
            $match['result'] = $placar;
            $match['result_winner'] = $result_winner;
            break;
        }
    }

    saveJson(FILE_MATCHES, $matches);
    $success = "🏆 Placar registrado com sucesso!";
}

$matches = loadJson(FILE_MATCHES);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin SSFABET - Painel de Controle</title>
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
        .admin-container {
            position: relative;
            z-index: 2;
            padding: 2rem 1rem;
        }

        /* Header do Admin */
        .admin-header {
            background: rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 1.5rem 2rem;
            margin-bottom: 2rem;
            transition: all 0.3s ease;
        }

        .admin-header:hover {
            background: rgba(0, 0, 0, 0.4);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
        }

        .admin-title {
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
        }

        .admin-title i {
            margin-right: 15px;
            color: #ffd700;
        }

        .admin-badge {
            background: linear-gradient(135deg, #ffd700, #ffed4e);
            color: #333;
            padding: 0.3rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        /* Cards de formulário */
        .form-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            margin-bottom: 2rem;
            border: none;
            transition: transform 0.3s ease;
        }

        .form-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
        }

        .form-card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 1.2rem 1.5rem;
            color: white;
        }

        .form-card-header h5 {
            margin: 0;
            font-weight: 600;
        }

        .form-card-header i {
            margin-right: 10px;
        }

        .form-card-body {
            padding: 1.5rem;
        }

        /* Campos de formulário estilizados */
        .form-group-custom {
            margin-bottom: 1rem;
        }

        .form-group-custom label {
            color: #333;
            font-weight: 500;
            margin-bottom: 0.5rem;
            display: block;
        }

        .form-group-custom label i {
            margin-right: 8px;
            color: #667eea;
        }

        .form-control-custom {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            transition: all 0.3s ease;
            font-size: 1rem;
        }

        .form-control-custom:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        /* Botões estilizados */
        .btn-primary-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 12px;
            padding: 0.8rem 1.5rem;
            font-weight: 600;
            color: white;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .btn-success-custom {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            border-radius: 12px;
            padding: 0.8rem 1.5rem;
            font-weight: 600;
            color: white;
            transition: all 0.3s ease;
        }

        .btn-success-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(40, 167, 69, 0.3);
        }

        .btn-secondary-custom {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border: none;
            border-radius: 12px;
            padding: 0.6rem 1.2rem;
            color: white;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-secondary-custom:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
            color: white;
        }

        /* Game cards na admin */
        .admin-game-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            height: 100%;
            animation: fadeInUp 0.5s ease forwards;
        }

        .admin-game-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        }

        .game-card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 1rem;
            color: white;
        }

        .game-card-header h5 {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .game-card-header small {
            opacity: 0.9;
            font-size: 0.85rem;
        }

        .game-card-body {
            padding: 1.2rem;
        }

        .result-badge {
            background: linear-gradient(135deg, #28a745, #20c997);
            border-radius: 12px;
            padding: 0.8rem;
            text-align: center;
            margin-bottom: 1rem;
        }

        .result-badge strong {
            display: block;
            font-size: 1.1rem;
            margin-bottom: 0.3rem;
        }

        /* Score selector para admin */
        .score-selector-admin {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
        }

        .score-select {
            width: 80px;
            border-radius: 10px;
            border: 2px solid #e0e0e0;
            padding: 0.5rem;
            text-align: center;
            font-size: 1rem;
            font-weight: 600;
        }

        /* Alertas personalizados */
        .alert-custom {
            border-radius: 15px;
            border: none;
            padding: 1rem;
            margin-bottom: 1.5rem;
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

        .alert-success-custom {
            background: linear-gradient(135deg, #d4fc79 0%, #96e6a1 100%);
            color: #1e5c2e;
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
            .admin-container {
                padding: 1rem;
            }
            
            .admin-header {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }
            
            .admin-title {
                font-size: 1.5rem;
            }
            
            .score-selector-admin {
                flex-direction: column;
            }
            
            .score-select {
                width: 100%;
            }
            
            .btn-success-custom {
                width: 100%;
                margin-top: 0.5rem;
            }
        }

        /* Estatísticas */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 1rem;
            text-align: center;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #667eea;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }
    </style>
</head>
<body>
    <!-- Fundo animado com partículas -->
    <div class="background-animation" id="particles"></div>

    <div class="admin-container">
        <div class="container">
            <!-- Header do Admin -->
            <div class="admin-header d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h1 class="admin-title">
                        <i class="fas fa-crown"></i> 
                        Painel do Administrador
                    </h1>
                    <p class="mt-2 mb-0">
                        <i class="fas fa-user-shield"></i> Olá, <strong><?= htmlspecialchars($currentUser['name']) ?></strong>
                        <span class="admin-badge ms-2">
                            <i class="fas fa-check-circle"></i> Admin
                        </span>
                    </p>
                </div>
                <a href="index.php" class="btn-secondary-custom">
                    <i class="fas fa-arrow-left"></i> Voltar para Apostas
                </a>
            </div>

            <?php if (isset($success)): ?>
                <div class="alert-custom alert-success-custom">
                    <i class="fas fa-check-circle"></i> <?= $success ?>
                </div>
            <?php endif; ?>

            <!-- Estatísticas Rápidas -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?= count($matches) ?></div>
                    <div class="stat-label">
                        <i class="fas fa-futbol"></i> Total de Jogos
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">
                        <?= count(array_filter($matches, function($m) { return !empty($m['result']); })) ?>
                    </div>
                    <div class="stat-label">
                        <i class="fas fa-check-circle"></i> Jogos Finalizados
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">
                        <?= count(array_filter($matches, function($m) { return empty($m['result']); })) ?>
                    </div>
                    <div class="stat-label">
                        <i class="fas fa-clock"></i> Jogos Pendentes
                    </div>
                </div>
            </div>

            <!-- Formulário para Adicionar Novo Jogo -->
            <div class="form-card">
                <div class="form-card-header">
                    <h5>
                        <i class="fas fa-plus-circle"></i> 
                        Adicionar Novo Jogo
                    </h5>
                </div>
                <div class="form-card-body">
                    <form method="POST" id="addMatchForm">
                        <input type="hidden" name="action" value="add_match">
                        <div class="row">
                            <div class="col-md-5 mb-3">
                                <div class="form-group-custom">
                                    <label>
                                        <i class="fas fa-home"></i> Time da Casa
                                    </label>
                                    <input type="text" name="team1" class="form-control-custom" 
                                           placeholder="Ex: Palmeiras" required>
                                </div>
                            </div>
                            <div class="col-md-5 mb-3">
                                <div class="form-group-custom">
                                    <label>
                                        <i class="fas fa-building"></i> Time Visitante
                                    </label>
                                    <input type="text" name="team2" class="form-control-custom" 
                                           placeholder="Ex: Flamengo" required>
                                </div>
                            </div>
                            <div class="col-md-2 mb-3">
                                <div class="form-group-custom">
                                    <label>
                                        <i class="fas fa-calendar-alt"></i> Data e Hora
                                    </label>
                                    <input type="datetime-local" name="date" class="form-control-custom" required>
                                </div>
                            </div>
                        </div>
                        <div class="text-end">
                            <button type="submit" class="btn-primary-custom" id="addMatchBtn">
                                <i class="fas fa-save"></i> Adicionar Jogo
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Lista de Jogos -->
            <h4 class="mb-4">
                <i class="fas fa-list"></i> 
                Jogos Cadastrados
                <span class="badge bg-primary ms-2"><?= count($matches) ?> no total</span>
            </h4>

            <?php if (empty($matches)): ?>
                <div class="alert-custom alert-success-custom">
                    <i class="fas fa-info-circle"></i> Nenhum jogo cadastrado ainda. 
                    Utilize o formulário acima para adicionar jogos.
                </div>
            <?php endif; ?>

            <div class="row">
                <?php foreach ($matches as $match): ?>
                    <div class="col-lg-6 mb-4">
                        <div class="admin-game-card">
                            <div class="game-card-header">
                                <h5>
                                    <i class="fas fa-futbol"></i> 
                                    <?= htmlspecialchars($match['team1']) ?> 
                                    <strong>VS</strong> 
                                    <?= htmlspecialchars($match['team2']) ?>
                                </h5>
                                <small>
                                    <i class="far fa-calendar-alt"></i> 
                                    <?= date('d/m/Y H:i', strtotime($match['date'])) ?>
                                </small>
                            </div>
                            <div class="game-card-body">
                                <?php if (!empty($match['result'])): ?>
                                    <div class="result-badge">
                                        <strong>
                                            <i class="fas fa-flag-checkered"></i> 
                                            Resultado Final
                                        </strong>
                                        <div class="fs-3 fw-bold mt-2">
                                            <?= htmlspecialchars($match['team1']) ?> 
                                            <?= $match['result'] ?> 
                                            <?= htmlspecialchars($match['team2']) ?>
                                        </div>
                                        <small class="mt-2 d-block">
                                            Vencedor: <?= $match['result_winner'] === 'home' ? $match['team1'] : 
                                                         ($match['result_winner'] === 'away' ? $match['team2'] : 'Empate') ?>
                                        </small>
                                    </div>
                                <?php else: ?>
                                    <form method="POST" id="scoreForm_<?= $match['id'] ?>">
                                        <input type="hidden" name="match_id" value="<?= $match['id'] ?>">
                                        <div class="score-selector-admin">
                                            <div class="text-center">
                                                <label class="fw-bold mb-2"><?= htmlspecialchars($match['team1']) ?></label>
                                                <select name="gols_home" class="score-select" required>
                                                    <?php for($i = 0; $i <= 19; $i++): ?>
                                                        <option value="<?= $i ?>"><?= $i ?></option>
                                                    <?php endfor; ?>
                                                </select>
                                            </div>
                                            <div class="text-center fs-3 fw-bold">×</div>
                                            <div class="text-center">
                                                <label class="fw-bold mb-2"><?= htmlspecialchars($match['team2']) ?></label>
                                                <select name="gols_away" class="score-select" required>
                                                    <?php for($i = 0; $i <= 19; $i++): ?>
                                                        <option value="<?= $i ?>"><?= $i ?></option>
                                                    <?php endfor; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="text-center mt-3">
                                            <button type="submit" class="btn-success-custom" 
                                                    onclick="return confirm('⚠️ Tem certeza que deseja registrar este placar? A ação é irreversível!')">
                                                <i class="fas fa-save"></i> Registrar Placar
                                            </button>
                                        </div>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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
        
        // Animação de entrada para os cards
        document.addEventListener('DOMContentLoaded', function() {
            createParticles();
            
            // Adicionar delay de animação aos cards
            const cards = document.querySelectorAll('.admin-game-card');
            cards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.05}s`;
            });
            
            // Efeito de loading no formulário de adicionar jogo
            const addMatchForm = document.getElementById('addMatchForm');
            const addMatchBtn = document.getElementById('addMatchBtn');
            
            if (addMatchForm) {
                addMatchForm.addEventListener('submit', function(e) {
                    addMatchBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adicionando...';
                    addMatchBtn.disabled = true;
                });
            }
            
            // Efeito de loading nos formulários de placar
            const scoreForms = document.querySelectorAll('form[id^="scoreForm_"]');
            scoreForms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const submitBtn = this.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Registrando...';
                        submitBtn.disabled = true;
                    }
                });
            });
        });
        
        // Animação suave de hover nos cards
        const statCards = document.querySelectorAll('.stat-card');
        statCards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
            });
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
    </script>
</body>
</html>