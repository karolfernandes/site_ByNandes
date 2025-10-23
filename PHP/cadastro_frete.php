<?php

// Conectando este arquivo ao banco de dados
require_once __DIR__ ."/conexao.php";

// função para capturar os dados passados de uma página a outra
function redirecWith($url,$params=[]){
// verifica se os os paramentros não vieram vazios
 if(!empty($params)){
// separar os parametros em espaços diferentes
$qs= http_build_query($params);
$sep = (strpos($url,'?') === false) ? '?': '&';
$url .= $sep . $qs;
}
// joga a url para o cabeçalho no navegador
header("Location: $url");
// fecha o script
exit;
}







// ================= LISTAGEM JSON e OPTION ===============
if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET["listar"])) {
    try {
       $sqllistar = "SELECT idFrete AS id, Bairro AS bairro, Valor_frete AS valor,
        Transportadora AS transportadora FROM Frete ORDER BY Bairro, Valor_frete";


        $stmtlistar = $pdo->query($sqllistar);

        $listar = $stmtlistar->fetchAll(PDO::FETCH_ASSOC);

        var_dump($listar); // <--- Isso mostra exatamente o que foi retornado


        $formato = isset($_GET["format"]) ? strtolower($_GET["format"]) : "option";

        if ($formato === "json") {
           $saida = array_map(function ($item) {
    return [
        "id" => (int)$item["id"],
        "bairro" => $item["bairro"],
        "valor" => (float)$item["valor"],
        "transportadora" => $item["transportadora"],
    ];
    }, $listar);

    


            header("Content-Type: application/json; charset=utf-8");
            echo json_encode(["ok" => true, "fretes" => $saida], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Formato padrão (HTML <option>)
        header("Content-Type: text/html; charset=utf-8");
        foreach ($listar as $lista) {
            $id = (int)$lista["id"];
            $bairro = htmlspecialchars($lista["bairro"], ENT_QUOTES, "UTF-8");
            $transp = !empty($lista["transportadora"]) ? " (" . htmlspecialchars($lista["transportadora"], ENT_QUOTES, "UTF-8") . ")" : "";
            $valorFrete = number_format((float)$lista["valor"], 2, ",", ".");
            $label = "{$bairro}{$transp} - R$ {$valorFrete}";
            echo "<option value=\"{$id}\">{$label}</option>\n";
        }
        exit;

    } catch (Throwable $e) {
        if (isset($_GET["format"]) && strtolower($_GET["format"]) === "json") {
            header("Content-Type: application/json; charset=utf-8", true, 500);
            echo json_encode([
                "ok" => false,
                "error" => "Erro ao listar fretes",
                "detail" => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        } else {
            header("Content-Type: text/html; charset=utf-8", true, 500);
            echo "<option disabled>Erro ao carregar fretes</option>";
        }
        exit;
    }
}

// ================= CADASTRO DE FRETE =================

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        $bairro = $_POST["bairro"] ?? '';
        $valor = (double)($_POST["valor"] ?? 0);
        $transportadora = $_POST["transportadora"] ?? '';

        $erros_validacao = [];
        if ($bairro === "" || $valor === 0) {
            $erros_validacao[] = "Preencha todos os campos obrigatórios";
        }

        if (!empty($erros_validacao)) {
            redirecWith("../paginas_lojista/frete_pagamento_lojista.html", [
                "erro" => implode(", ", $erros_validacao)
            ]);
        }

        $sql = "INSERT INTO Frete (Valor_frete, Bairro, Transportadora)
                VALUES (:valor, :bairro, :transportadora)";
        $stmt = $pdo->prepare($sql);
        $inserir = $stmt->execute([
            ":valor" => $valor,
            ":bairro" => $bairro,
            ":transportadora" => $transportadora
        ]);

        if ($inserir) {
            redirecWith("../paginas_lojista/frete_pagamento_lojista.html", ["cadastro" => "ok"]);
        } else {
            redirecWith("../paginas_lojista/frete_pagamento_lojista.html", ["erro" => "Erro ao cadastrar no banco de dados"]);
        }

    } catch (Exception $e) {
        redirecWith("../paginas_lojista/frete_pagamento_lojista.html", [
            "erro" => "Erro no banco de dados: " . $e->getMessage()
        ]);
    }
}
?>