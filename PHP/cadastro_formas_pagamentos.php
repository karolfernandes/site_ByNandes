<?php
require_once __DIR__ . "/conexao.php";

header('Content-Type: application/json; charset=utf-8');

try {
    // LISTAGEM JSON
    if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET["listar"])) {
        $stmt = $pdo->query("SELECT idFrete AS id, Bairro AS bairro, Valor_frete AS valor, Transportadora AS transportadora FROM Frete ORDER BY Bairro, Valor_frete");
        $fretes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $saida = array_map(function($item) {
            return [
                "id" => (int)$item["id"],
                "bairro" => $item["bairro"],
                "valor" => (float)$item["valor"],
                "transportadora" => $item["transportadora"],
            ];
        }, $fretes);

        echo json_encode(["ok" => true, "fretes" => $saida], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // CADASTRO DE FRETE
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $bairro = $_POST["bairro"] ?? '';
        $valor = (float)($_POST["valor"] ?? 0);
        $transportadora = $_POST["transportadora"] ?? '';

        if ($bairro === "" || $valor <= 0) {
            echo json_encode(["ok" => false, "error" => "Preencha todos os campos obrigatórios"]);
            exit;
        }

        $sql = "INSERT INTO Frete (Valor_frete, Bairro, Transportadora) VALUES (:valor, :bairro, :transportadora)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ":valor" => $valor,
            ":bairro" => $bairro,
            ":transportadora" => $transportadora
        ]);

        echo json_encode(["ok" => true, "message" => "Frete cadastrado com sucesso"]);
        exit;
    }

    // Método inválido
    echo json_encode(["ok" => false, "error" => "Método inválido"]);
    exit;

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["ok" => false, "error" => "Erro no banco de dados", "detail" => $e->getMessage()]);
    exit;
}
?>
