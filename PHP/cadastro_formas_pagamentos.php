<?php
require_once __DIR__ . "/conexao.php";
header('Content-Type: application/json; charset=utf-8');

try {
    // LISTAGEM
    if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET["listar"])) {
        $stmt = $pdo->query("SELECT idFormaPagamento AS id, NomePagamento AS nome FROM FormaPagamento ORDER BY idFormaPagamento");
        $formas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(["ok" => true, "formas_pagamento" => $formas], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // CADASTRO
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $nome = $_POST["nomepagamento"] ?? '';

        if (trim($nome) === '') {
            echo json_encode(["ok" => false, "error" => "Preencha todos os campos obrigatórios"]);
            exit;
        }

        $sql = "INSERT INTO FormaPagamento (NomePagamento) VALUES (:nome)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([":nome" => $nome]);

        echo json_encode(["ok" => true, "message" => "Forma de pagamento cadastrada com sucesso!"]);
        exit;
    }

    echo json_encode(["ok" => false, "error" => "Método inválido"]);
    exit;

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["ok" => false, "error" => "Erro no banco de dados", "detail" => $e->getMessage()]);
    exit;
}
?>
