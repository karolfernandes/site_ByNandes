<?php
require_once __DIR__ . "/conexao.php"; // arquivo de conexão

// Função para redirecionar com parâmetros
function redirecWith($url, $params = []) {
    if (!empty($params)) {
        $qs = http_build_query($params);
        $sep = (strpos($url, '?') === false) ? '?' : '&';
        $url .= $sep . $qs;
    }
    header("Location: $url");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Receber os dados do formulário
    $codigo = trim($_POST['codigo'] ?? '');
    $desconto = trim($_POST['desconto'] ?? '');
    $validade = trim($_POST['validade'] ?? '');
    $categoria = $_POST['categoria_id'] ?? null;
    $status = $_POST['status'] ?? 1; // padrão: ativo

    // Validação de campos obrigatórios
    if (empty($codigo) || empty($desconto) || empty($validade)) {
        redirecWith("../paginas_lojista/cupons.html", ["erro" => "Preencha todos os campos obrigatórios"]);
    }

    // Inserir no banco de dados
    try {
        $sql = "INSERT INTO Cupons (Codigo, Desconto, Data_validade, Categorias_idCategorias, Status) 
                VALUES (:codigo, :desconto, :validade, :categoria, :status)";
        $stmt = $pdo->prepare($sql);
        $sucesso = $stmt->execute([
            ":codigo" => $codigo,
            ":desconto" => $desconto,
            ":validade" => $validade,
            ":categoria" => $categoria,
            ":status" => $status
        ]);

        if ($sucesso) {
            redirecWith("../paginas_lojista/cupons.html", ["cadastro" => "ok"]);
        } else {
            $errorInfo = $stmt->errorInfo();
            redirecWith("../paginas_lojista/cupons.html", ["erro" => "Erro ao cadastrar: " . $errorInfo[2]]);
        }

    } catch (PDOException $e) {
        redirecWith("../paginas_lojista/cupons.html", ["erro" => "Erro: " . $e->getMessage()]);
    }
} else {
    // Acesso direto via GET
    redirecWith("../paginas_lojista/cupons.html");
}
?>
