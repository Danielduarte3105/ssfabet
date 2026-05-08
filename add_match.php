<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
    header('Location: admin.php?added=1');
    exit;
}
header('Location: admin.php');
?>