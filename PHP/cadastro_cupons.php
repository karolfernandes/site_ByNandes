<?php
require_once __DIR__ . "/conexao.php";
header('Content-Type: application/json; charset=utf-8');

try {
    // LISTAGEM
    if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET["listar"])) {
        $stmt = $pdo->query("SELECT idCupomDesconto AS id, Nome_desconto AS nome
         FROM CupomDesconto ORDER BY idCupomDesconto");
        $formas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(["ok" => true, "cupons" => $formas], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // CADASTRO
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $nome = $_POST["CupomDesconto"] ?? '';

        if (trim($nome) === '') {
            echo json_encode(["ok" => false, "error" => "Preencha todos os campos obrigatórios"]);
            exit;
        }

        $sql = "INSERT INTO CupomDesconto (Nome_desconto) VALUES (:nome)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([":nome" => $nome]);

        echo json_encode(["ok" => true, "message" => "Cupom cadastrado com sucesso!"]);
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
