<?php
require_once __DIR__ . "/conexao.php";
header('Content-Type: application/json; charset=utf-8');

try {
    // ======================================================
    // LISTAR CUPONS
    // ======================================================
    if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET["listar"])) {
        $stmt = $pdo->query("
            SELECT 
                idCupomDesconto AS id,
                Cod_desconto AS codigo,
                Nome_desconto AS nome,
                Dias_validade AS data_validade,
                Quantidade AS quantidade
            FROM CupomDesconto
            ORDER BY idCupomDesconto DESC
        ");
        $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(["ok" => true, "cupons" => $dados], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ======================================================
    // CADASTRAR / ATUALIZAR / EXCLUIR CUPOM
    // ======================================================
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $acao = $_POST["acao"] ?? '';
        $id = $_POST["id"] ?? '';
        $nome = trim($_POST["nome"] ?? '');
        $codigo = trim($_POST["codigo"] ?? '');
        $data = trim($_POST["data_validade"] ?? '');
        $quantidade = trim($_POST["quantidade"] ?? '');

        // -------- CADASTRAR --------
        if ($acao === 'cadastrar') {
            if ($nome === '' || $codigo === '' || $data === '' || $quantidade === '') {
                echo json_encode(["ok" => false, "error" => "Preencha todos os campos obrigatórios."]);
                exit;
            }

            $sql = "INSERT INTO CupomDesconto 
                    (Cod_desconto, Dias_validade, Quantidade, Nome_desconto)
                    VALUES (:codigo, :data, :quantidade, :nome)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':codigo' => $codigo,
                ':data' => $data,
                ':quantidade' => $quantidade,
                ':nome' => $nome
            ]);

            echo json_encode(["ok" => true, "message" => "Cupom cadastrado com sucesso!"]);
            exit;
        }

        // -------- ATUALIZAR --------
        if ($acao === 'atualizar' && $id) {
            $sql = "UPDATE CupomDesconto 
                    SET Cod_desconto = :codigo, 
                        Dias_validade = :data, 
                        Quantidade = :quantidade, 
                        Nome_desconto = :nome
                    WHERE idCupomDesconto = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':codigo' => $codigo,
                ':data' => $data,
                ':quantidade' => $quantidade,
                ':nome' => $nome,
                ':id' => $id
            ]);

            echo json_encode(["ok" => true, "message" => "Cupom atualizado com sucesso!"]);
            exit;
        }

        // -------- EXCLUIR --------
        if ($acao === 'excluir' && $id) {
            $stmt = $pdo->prepare("DELETE FROM CupomDesconto WHERE idCupomDesconto = :id");
            $stmt->execute([':id' => $id]);

            echo json_encode(["ok" => true, "message" => "Cupom excluído com sucesso!"]);
            exit;
        }

        // -------- AÇÃO INVÁLIDA --------
        echo json_encode(["ok" => false, "error" => "Ação inválida ou ID ausente."]);
        exit;
    }

    // ======================================================
    // MÉTODO INVÁLIDO
    // ======================================================
    echo json_encode(["ok" => false, "error" => "Método inválido."]);
    exit;

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "ok" => false,
        "error" => "Erro no servidor.",
        "detalhe" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
