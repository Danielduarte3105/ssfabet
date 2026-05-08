<?php
require 'config.php';

$novosUsuarios = [
    "osvaldo marmo",
    "andreza fernandes",
    "andresa domenico",
    "mariana romanin",
    "marcos antonio",
    "sergio moura",
    "sergio prates",
    "mateus estevam",
    "orion frança",
    "lucas renault",
    "jairo gusmao",
    "ricardo rocha",
    "nicolas suporte"
];

$users = loadJson(FILE_USERS);

// Pegar o próximo ID
$nextId = empty($users) ? 1 : max(array_column($users, 'id')) + 1;

$adicionados = 0;
$jaExistentes = 0;

foreach ($novosUsuarios as $nomeCompleto) {
    $nome = trim($nomeCompleto);
    $nomeLower = strtolower($nome);
    
    // Criar email: osvaldo.marmo@sfa.adv.br
    $email = str_replace(' ', '.', $nomeLower) . '@sfa.adv.br';

    // Verificar se usuário já existe (por email)
    $existe = false;
    foreach ($users as $u) {
        if (strtolower($u['email']) === $email) {
            $existe = true;
            break;
        }
    }

    if ($existe) {
        $jaExistentes++;
        continue;
    }

    $users[] = [
        'id'           => $nextId++,
        'name'         => ucwords($nome),           // Nome formatado
        'email'        => $email,
        'password'     => password_hash('1234', PASSWORD_DEFAULT),
        'total_points' => 0
    ];

    $adicionados++;
}

saveJson(FILE_USERS, $users);

echo "<h2 style='color:green; text-align:center; margin-top:50px;'>✅ Usuários adicionados com sucesso!</h2>";
echo "<div style='text-align:center; font-family:Arial; max-width:800px; margin:30px auto;'>";
echo "<p><strong>Usuários adicionados:</strong> $adicionados</p>";
echo "<p><strong>Já existentes (ignorados):</strong> $jaExistentes</p>";
echo "<hr>";
echo "<p><strong>Senha padrão:</strong> <code>1234</code> para todos</p>";
echo "<br>";
echo "<a href='login.php' class='btn btn-primary btn-lg'>Ir para Login</a>";
echo "</div>";
?>