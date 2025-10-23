<?php
require_once __DIR__ . "/conexao.php";

// Redirecionamento com parâmetros
function redirecWith($url, $params = []) {
    if (!empty($params)) {
        $qs = http_build_query($params);
        $sep = (strpos($url, '?') === false) ? '?' : '&';
        $url .= $sep . $qs;
    }
    header("Location: $url");
    exit;
}

// ================= LISTAGEM JSON =================
if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET["listar"])) {
    $tipo = $_GET["tipo"] ?? "frete"; // 'frete' ou 'pagamento'
    $formato = strtolower($_GET["format"] ?? "option");

    try {
        if ($tipo === "frete") {
            $sql = "SELECT idFrete AS id, Bairro AS bairro, Valor_frete AS valor,
                    Transportadora AS transportadora FROM Frete ORDER BY Bairro, Valor_frete";
            $stmt = $pdo->query($sql);
            $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($formato === "json") {
                $saida = array_map(function ($item) {
                    return [
                        "id" => (int)$item["id"],
                        "bairro" => $item["bairro"],
                        "valor" => (float)$item["valor"],
                        "transportadora" => $item["transportadora"],
                    ];
                }, $dados);

                header("Content-Type: application/json; charset=utf-8");
                echo json_encode(["ok" => true, "fretes" => $saida], JSON_UNESCAPED_UNICODE);
                exit;
            }
        } elseif ($tipo === "pagamento") {
            $sql = "SELECT idForma AS id, Nome AS nome FROM Formas_Pagamento ORDER BY Nome";
            $stmt = $pdo->query($sql);
            $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($formato === "json") {
                $saida = array_map(function ($item) {
                    return [
                        "id" => (int)$item["id"],
                        "nome" => $item["nome"],
                    ];
                }, $dados);

                header("Content-Type: application/json; charset=utf-8");
                echo json_encode(["ok" => true, "formas_pagamento" => $saida], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }

        // Formato HTML <option>
        header("Content-Type: text/html; charset=utf-8");
        foreach ($dados as $item) {
            if ($tipo === "frete") {
                $id = (int)$item["id"];
                $bairro = htmlspecialchars($item["bairro"], ENT_QUOTES, "UTF-8");
                $transp = !empty($item["transportadora"]) ? " (" . htmlspecialchars($item["transportadora"], ENT_QUOTES, "UTF-8") . ")" : "";
                $valor = number_format((float)$item["valor"], 2, ",", ".");
                echo "<option value='{$id}'>{$bairro}{$transp} - R$ {$valor}</option>\n";
            } else {
                $id = (int)$item["id"];
                $nome = htmlspecialchars($item["nome"], ENT_QUOTES, "UTF-8");
                echo "<option value='{$id}'>{$nome}</option>\n";
            }
        }
        exit;

    } catch (Throwable $e) {
        if ($formato === "json") {
            header("Content-Type: application/json; charset=utf-8", true, 500);
            echo json_encode(["ok" => false, "error" => "Erro ao listar dados", "detail" => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        } else {
            header("Content-Type: text/html; charset=utf-8", true, 500);
            echo "<option disabled>Erro ao carregar dados</option>";
        }
        exit;
    }
}

// ================= CADASTRO DE FRETE =================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["bairro"])) {
    try {
        $bairro = $_POST["bairro"] ?? '';
        $valor = (float)($_POST["valor"] ?? 0);
        $transportadora = $_POST["transportadora"] ?? '';

        if ($bairro === "" || $valor <= 0) {
            redirecWith("../paginas_lojista/frete_pagamento_lojista.html", ["erro" => "Preencha todos os campos obrigatórios"]);
        }

        $sql = "INSERT INTO Frete (Valor_frete, Bairro, Transportadora) VALUES (:valor, :bairro, :transportadora)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ":valor" => $valor,
            ":bairro" => $bairro,
            ":transportadora" => $transportadora
        ]);

        redirecWith("../paginas_lojista/frete_pagamento_lojista.html", ["cadastro" => "ok"]);

    } catch (Exception $e) {
        redirecWith("../paginas_lojista/frete_pagamento_lojista.html", ["erro" => "Erro no banco de dados: " . $e->getMessage()]);
    }
}

// ================= CADASTRO DE FORMAS DE PAGAMENTO =================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["nomepagamento"])) {
    try {
        $nome = trim($_POST["nomepagamento"] ?? '');
        if ($nome === "") {
            redirecWith("../paginas_lojista/frete_pagamento_lojista.html", ["erro" => "Informe o nome da forma de pagamento"]);
        }

        $sql = "INSERT INTO Formas_Pagamento (Nome) VALUES (:nome)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([":nome" => $nome]);

        redirecWith("../paginas_lojista/frete_pagamento_lojista.html", ["cadastro" => "ok"]);

    } catch (Exception $e) {
        redirecWith("../paginas_lojista/frete_pagamento_lojista.html", ["erro" => "Erro no banco de dados: " . $e->getMessage()]);
    }
}
?>
