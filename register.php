<?php
require 'config.php';

$success = false;
$error   = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $nome      = trim($_POST['nome'] ?? '');
    $sobrenome = trim($_POST['sobrenome'] ?? '');
    $email     = trim($_POST['email'] ?? '');

    if (empty($nome) || empty($sobrenome)) {
        $error = "Nome e sobrenome são obrigatórios!";
    } elseif (empty($email)) {
        $error = "E-mail é obrigatório!";
    } elseif (!isset($_FILES['comprovante']) || $_FILES['comprovante']['error'] !== UPLOAD_ERR_OK) {
        $error = "Por favor, envie o comprovante de pagamento!";
    } else {
        $uploadResult = saveComprovante($_FILES['comprovante']);
        
        if ($uploadResult['success']) {
            $comprovanteNome = $uploadResult['path'];
            
            $createResult = createUser($nome, $sobrenome, $email, $comprovanteNome);
            
            if ($createResult['success']) {
                $success = true;
            } else {
                $error = $createResult['message'];
                // Remove comprovante se falhar o cadastro
                if (file_exists(COMPS_DIR . $comprovanteNome)) {
                    unlink(COMPS_DIR . $comprovanteNome);
                }
            }
        } else {
            $error = $uploadResult['message'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro SSFABET - Ativação de Conta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
        .register-container {
            position: relative;
            z-index: 2;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }

        /* Card principal */
        .register-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 30px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            max-width: 1000px;
            width: 100%;
            animation: slideUp 0.5s ease;
        }

        .register-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 30px 70px rgba(0, 0, 0, 0.4);
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Cabeçalho */
        .card-header-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem;
            text-align: center;
            color: white;
        }

        .logo-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1);
            }
        }

        .card-header-custom h3 {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .card-header-custom p {
            opacity: 0.9;
            margin: 0;
        }

        /* Corpo do card */
        .card-body-custom {
            padding: 2rem;
        }

        /* QR Code Section */
        .qr-section {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            border-radius: 20px;
            padding: 1.5rem;
            text-align: center;
            margin-bottom: 2rem;
        }

        .qr-code {
            background: white;
            padding: 1rem;
            border-radius: 15px;
            display: inline-block;
            margin-bottom: 1rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .qr-code:hover {
            transform: scale(1.05);
        }

        .qr-code img {
            width: 200px;
            height: 200px;
            object-fit: contain;
        }

        .qr-section h5 {
            color: #333;
            margin-bottom: 0.5rem;
        }

        .qr-section p {
            color: #666;
            font-size: 0.9rem;
        }

        .pix-copy {
            background: rgba(102, 126, 234, 0.1);
            border-radius: 10px;
            padding: 0.5rem;
            margin-top: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .pix-copy:hover {
            background: rgba(102, 126, 234, 0.2);
        }

        /* Formulário */
        .form-group-custom {
            margin-bottom: 1.5rem;
        }

        .form-group-custom label {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
            display: block;
        }

        .form-group-custom label i {
            margin-right: 8px;
            color: #667eea;
        }

        .input-group-custom {
            position: relative;
        }

        .input-group-custom i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #667eea;
            z-index: 1;
        }

        .input-group-custom input {
            width: 100%;
            padding: 0.8rem 1rem 0.8rem 2.8rem;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .input-group-custom input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .input-group-custom input:disabled {
            background: #f5f5f5;
            color: #666;
        }

        /* Upload de arquivo */
        .file-upload {
            position: relative;
            width: 100%;
        }

        .file-upload input[type="file"] {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .file-upload-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 0.8rem;
            border: 2px dashed #667eea;
            border-radius: 12px;
            background: rgba(102, 126, 234, 0.05);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .file-upload-label:hover {
            background: rgba(102, 126, 234, 0.1);
            border-color: #764ba2;
        }

        .file-upload-label i {
            font-size: 1.2rem;
            color: #667eea;
        }

        .file-name {
            margin-top: 0.5rem;
            font-size: 0.85rem;
            color: #666;
            text-align: center;
        }

        /* Botão de cadastro */
        .btn-register {
            width: 100%;
            padding: 0.8rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        /* Link para login */
        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            color: #666;
        }

        .login-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .login-link a:hover {
            color: #764ba2;
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .register-card {
                margin: 1rem;
            }
            
            .card-header-custom {
                padding: 1.5rem;
            }
            
            .card-header-custom h3 {
                font-size: 1.5rem;
            }
            
            .card-body-custom {
                padding: 1.5rem;
            }
            
            .qr-code img {
                width: 150px;
                height: 150px;
            }
        }

        /* Info de senha */
        .password-info {
            background: rgba(102, 126, 234, 0.1);
            border-radius: 10px;
            padding: 0.8rem;
            margin-top: 1rem;
            font-size: 0.85rem;
        }

        .password-info i {
            color: #28a745;
        }
    </style>
</head>
<body>
    <!-- Fundo animado com partículas -->
    <div class="background-animation" id="particles"></div>

    <div class="register-container">
        <div class="register-card">
            <div class="card-header-custom">
                <div class="logo-icon">
                    <i class="fas fa-qrcode"></i>
                </div>
                <h3>Ative sua Conta</h3>
                <p>Faça o pagamento e envie o comprovante para ativar seu acesso</p>
            </div>
            
            <div class="card-body-custom">
                <div class="row">
                    <div class="col-md-5">
                        <div class="qr-section">
                            <h5>
                                <i class="fas fa-qrcode"></i> QR Code PIX
                            </h5>
                            <div class="qr-code" onclick="zoomQRCode()">
                                <img src="src/imgs/payment.jpeg" alt="QR Code PIX" id="qrCodeImg" onerror="this.src='https://via.placeholder.com/200?text=QR+Code'">
                            </div>
                            <p>Escaneie o QR Code com seu banco</p>
                            <!-- <div class="pix-copy" onclick="copyPix()">
                                <i class="fas fa-copy"></i> Copiar código PIX
                                <small class="d-block text-muted" id="pixKey">00020126360014BR.GOV.BCB.PIX0114contato@sfa.adv.br520400005303986540510.005802BR5913SSFABET6008BRASILIA62070503***6304E2A3</small>
                            </div> -->
                        </div>
                    </div>
                    
                    <div class="col-md-7">
                        <form method="POST" enctype="multipart/form-data" id="registerForm">
                            <div class="form-group-custom">
                                <label>
                                    <i class="fas fa-user"></i> Nome
                                </label>
                                <div class="input-group-custom">
                                    <i class="fas fa-user"></i>
                                    <input type="text" name="nome" id="nome" 
                                           placeholder="Seu nome" required autocomplete="off">
                                </div>
                            </div>
                            
                            <div class="form-group-custom">
                                <label>
                                    <i class="fas fa-user-tie"></i> Sobrenome
                                </label>
                                <div class="input-group-custom">
                                    <i class="fas fa-user-tie"></i>
                                    <input type="text" name="sobrenome" id="sobrenome" 
                                           placeholder="Seu sobrenome" required autocomplete="off">
                                </div>
                            </div>
                            
                            <div class="form-group-custom">
                                <label>
                                    <i class="fas fa-envelope"></i> E-mail
                                </label>
                                <div class="input-group-custom">
                                    <i class="fas fa-envelope"></i>
                                    <input type="email" name="email" id="email" 
                                           placeholder="seu.email@sfa.adv.br" required readonly 
                                           style="background: #f5f5f5;">
                                </div>
                                <small class="text-muted">
                                    <i class="fas fa-info-circle"></i> O e-mail é gerado automaticamente
                                </small>
                            </div>
                            
                            <div class="form-group-custom">
                                <label>
                                    <i class="fas fa-upload"></i> Comprovante de Pagamento
                                </label>
                                <div class="file-upload">
                                    <input type="file" name="comprovante" id="comprovante" 
                                           accept="image/*,.pdf" required>
                                    <div class="file-upload-label" onclick="document.getElementById('comprovante').click()">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                        <span>Clique para enviar o comprovante</span>
                                    </div>
                                    <div class="file-name" id="fileName"></div>
                                </div>
                                <small class="text-muted">
                                    <i class="fas fa-info-circle"></i> Formatos aceitos: JPG, PNG, PDF (máx. 5MB)
                                </small>
                            </div>
                            
                            <div class="password-info">
                                <i class="fas fa-lock"></i> 
                                <strong>Senha padrão:</strong> 1234 (você poderá alterar após o primeiro acesso)
                            </div>
                            
                            <button type="submit" name="register" class="btn-register mt-3" id="registerBtn">
                                <i class="fas fa-check-circle"></i> Confirmar Cadastro
                            </button>
                        </form>
                        
                        <div class="login-link">
                            Já possui conta? <a href="login.php">
                                <i class="fas fa-sign-in-alt"></i> Faça login
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script>
        // Geração de email automático (mantido)
        function generateEmail() {
            const nome = document.getElementById('nome').value.trim();
            const sobrenome = document.getElementById('sobrenome').value.trim();
            
            if (nome || sobrenome) {
                const nomeClean = nome.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[^a-z0-9]/g, '');
                const sobrenomeClean = sobrenome.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[^a-z0-9]/g, '');
                
                let email = nomeClean;
                if (sobrenomeClean) email += '.' + sobrenomeClean;
                email += '@sfa.adv.br';
                
                document.getElementById('email').value = email;
            }
        }

        document.getElementById('nome').addEventListener('input', generateEmail);
        document.getElementById('sobrenome').addEventListener('input', generateEmail);

        // Preview do nome do arquivo
        document.getElementById('comprovante').addEventListener('change', function(e) {
            const fileNameSpan = document.getElementById('fileName');
            if (e.target.files[0]) {
                fileNameSpan.innerHTML = `<i class="fas fa-check-circle text-success"></i> ${e.target.files[0].name}`;
            }
        });

        // SweetAlert de feedback
        <?php if ($success): ?>
            Swal.fire({
                icon: 'success',
                title: 'Cadastro Realizado!',
                text: 'Seu cadastro foi realizado com sucesso! Aguarde a aprovação.',
                timer: 2500,
                showConfirmButton: false
            }).then(() => {
                window.location.href = 'login.php';
            });
        <?php elseif ($error): ?>
            Swal.fire({
                icon: 'error',
                title: 'Erro!',
                text: '<?= addslashes($error) ?>',
                confirmButtonColor: '#667eea'
            });
        <?php endif; ?>
    </script>
</body>
</html>