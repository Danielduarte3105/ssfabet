<?php
require 'config.php';

// ================== CONFIGURE AQUI ==================
$admin_name     = "Admin";
$admin_email    = "admin@sfa.adv.br";
$admin_password = "Admin@1234";   // Mude para uma senha forte depois!
// ====================================================

$users = loadJson(FILE_USERS);

// Verifica se já existe admin
foreach ($users as $user) {
    if (strtolower($user['name']) === 'admin' || strpos($user['email'], 'admin') !== false) {
        die("<h3 style='color:red'>Usuário Admin já existe!</h3>");
    }
}

// Cria o admin
$users[] = [
    'id'          => count($users) + 1,
    'name'        => $admin_name,
    'email'       => $admin_email,
    'password'    => password_hash($admin_password, PASSWORD_DEFAULT),
    'total_points'=> 0
];

saveJson(FILE_USERS, $users);

echo "
    <div style='text-align:center; margin-top:50px; font-family:Arial'>
        <h2 style='color:green'>✅ Usuário Admin criado com sucesso!</h2>
        <hr>
        <p><strong>Nome:</strong> $admin_name</p>
        <p><strong>E-mail:</strong> $admin_email</p>
        <p><strong>Senha:</strong> $admin_password</p>
        <br>
        <a href='login.php' class='btn btn-primary btn-lg'>Ir para o Login</a>
    </div>
";
?>