 <?php
// Conectando ao banco de dados
require_once __DIR__ . "/conexao.php";

// Função para redirecionar com parâmetros
function redirecWith($url, $params = [])
{
    if (!empty($params)) {
        $qs = http_build_query($params);
        $sep = (strpos($url, '?') === false) ? '?' : '&';
        $url .= $sep . $qs;
    }
    header("Location: $url");
    exit;
}



// -------------------------
// -------------------------
// LISTAGEM DE PRODUTOS
if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET["listar"])) {
  try {
    // comando SQL para listar todos os produtos
    $sqlListar = "SELECT 
                    idProdutos,
                    NomeProduto,
                    Descricao,
                    Quantidade,
                    Preco,
                    Preco_Promocional,
                    Tamanho,
                    Cor,
                    Codigo
                  FROM Produtos
                  ORDER BY NomeProduto";

    $stmtListar = $pdo->query($sqlListar);
    $listarprodutos = $stmtListar->fetchAll(PDO::FETCH_ASSOC);

    $produtos= array_map(function ($resposta) {
        return[
            "idProdutos" =>(int) $resposta["idProdutos"],
            "NomeProduto" =>["NomeProduto"],
            //convertendo a imagem
            "imagem" => !empty($resposta["imagem"]) ? base64_encode($resposta["imagem"]) : null
        ];
        }, $rows);
    
   echo json_encode(
      ['ok'=>true,'count'=>count($produtos),'categorias'=>$produtos],
      JSON_UNESCAPED_UNICODE // mantém acentos corretamente
    );

    

  }catch (Throwable $e){
    header("Content-Type: text/html; charset=utf-8", true, 500);
    echo "<tr><td colspan='9' class='text-danger text-center'>Erro ao carregar produtos: " . $e->getMessage() . "</td></tr>";
    
  }
exit;

}


$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
// Função para ler imagem como blob
function readImageToBlob(?array $file): ?string {
  if (!$file || !isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) return null;
  $content = file_get_contents($file['tmp_name']);
  return $content === false ? null : $content;
}




try {
    // Verifica se o método é POST
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        redirecWith("", [
            "erro" => "Método inválido"
        ]);
    }

    // Jogando os dados do formulário em variáveis
    $NomeProduto = $_POST["nomeproduto"] ?? '';
    $Descricao = $_POST["descricao"] ?? '';
    $Quantidade = (int)$_POST["quantidade"];
    $Preco =(double)$_POST["preco"] ;
    $Tamanho = $_POST["tamanho"] ?? '';
    $Cor = $_POST["cor"] ?? '';
    $Codigo = (int)$_POST["codigo"] ;
    $Preco_Promocional = (double)$_POST["precopromocional"];
  

    // Criar variáveis das imagens
    $img1 = readImageToBlob($_FILES["imgproduto1"] ?? null);
    $img2 = readImageToBlob($_FILES["imgproduto2"] ?? null);
    $img3 = readImageToBlob($_FILES["imgproduto3"] ?? null);

    // Validação
    $erros_validacao = [];

    if (
        $NomeProduto === "" || $Descricao === "" || $Quantidade <= 0 ||
        $Preco <= 0 ) {
        $erros_validacao[] = "Preencha os campos obrigatórios.";
    }

    if (!empty($erros_validacao)) {
        redirecWith("../paginas_lojista/cadastro_produtos_lojista.html", [
            "erro" => implode(", ", $erros_validacao)
        ]);
    }

    // Início da transação
    $pdo->beginTransaction();

    // Inserir na tabela de produtos
    $sqlProdutos = "INSERT INTO Produtos (NomeProduto, descricao, quantidade, preco, tamanho, cor, codigo, preco_promocional)
                    VALUES (:nome, :descricao, :quantidade, :preco, :tamanho, :cor, :codigo, :preco_promocional)";

    $stmProdutos = $pdo->prepare($sqlProdutos);

    $inserirProdutos = $stmProdutos->execute([
        ":nome" => $NomeProduto,
        ":descricao" => $Descricao,
        ":quantidade" => $Quantidade,
        ":preco" => $Preco,
        ":tamanho" => $Tamanho,
        ":cor" => $Cor,
        ":codigo" => $Codigo,
        ":preco_promocional" => $Preco_Promocional
    ]);

    if (!$inserirProdutos) {
        $pdo->rollBack();
        redirecWith("../paginas_lojista/cadastro_produtos_lojista.html", [
            "erro" => "Erro ao cadastrar produto"
        ]);
    }

    $idProduto = (int)$pdo->lastInsertId();

    // Cadastro das imagens
    $sqlImagens = "INSERT INTO Imagemproduto (Foto) VALUES (:imagem1), (:imagem2), (:imagem3)";
    $stmImagens = $pdo->prepare($sqlImagens);

    $stmImagens->bindValue(':imagem1', $img1, $img1 !== null ? PDO::PARAM_LOB : PDO::PARAM_NULL);
    $stmImagens->bindValue(':imagem2', $img2, $img2 !== null ? PDO::PARAM_LOB : PDO::PARAM_NULL);
    $stmImagens->bindValue(':imagem3', $img3, $img3 !== null ? PDO::PARAM_LOB : PDO::PARAM_NULL);

    $inserirImagens = $stmImagens->execute();

    if (!$inserirImagens) {
        $pdo->rollBack();
        redirecWith("../paginas_lojista/cadastro_produtos_lojista.html", [
            "erro" => "Falha ao cadastrar imagens"
        ]);
    }

    $idImg = (int)$pdo->lastInsertId();

    // Vincular produto com imagem
    $sqlImagens = "INSERT INTO  Produtos_has_ImagemProduto (Produtos_idProdutos, ImagemProduto_idImagemProduto)
                           VALUES (:idpro, :idimg)";

    $stmImagens = $pdo->prepare($sqlImagens);

    $inserirImagens = $stmImagens->execute([
        ":idpro" => $idProduto,
        ":idimg" => $idImg
    ]);

    if (!$inserirImagens) {
        $pdo->rollBack();
        redirecWith("../paginas_lojista/cadastro_produtos_lojista.html", [
            "erro" => "Falha ao vincular produto com imagem"
        ]);
    } else {
        $pdo->commit();
        redirecWith("../paginas_lojista/cadastro_produtos_lojista.html", [
            "cadastro" => "ok"
        ]);
    }
} catch (Exception $e) {
    redirecWith("../paginas_lojista/cadastro_produtos_lojista.html", [
        "erro" => "Erro no banco de dados: " . $e->getMessage()
    ]);
}
?>
