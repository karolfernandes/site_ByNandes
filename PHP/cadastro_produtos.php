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
header("Location:  $url");
// fecha o script
exit;
}

function readImageToBlob(?array $file): ?string {
    if (!$file || !isset($file['tmp_name']) || $file['error']
    !== UPLOAD_ERR_OK) return null;
    $content = file_get_contents($file['tmp_name']);
    return $content === false ? null : $content;
}
try{

    // SE O METODO DE ENVIO FOR DIFERENTE DO POST
    if($_SERVER["REQUEST_METHOD"] !== "POST"){
        //VOLTAR À TELA DE CADASTRO E EXIBIR ERRO
        redirecWith("../paginas_lojista/cadastro_produtos_lojista.html",
           ["erro"=> "Metodo inválido"]);
    }
    // jogando os dados da dentro de váriaveis
    $NomeProduto = $_POST["nomecategoria"];
    $Descricao = $_POST[""];
    $Quantidade = (int)$_POST[""];
    $Preco = (double)$_POST[""];
    $Tamanho = $_POST[""];
    $Cor = $POST[""];
    $Codigo = (int)$_POST[""];
    $Preco_Promocional = (double)$_POST[""];
    $Produtos_idProdutos = 1;

     //criar variáveis das imagens
    $img1 = readImageToBlob($_FILES["imgproduto1"] ?? null);
    $img2 = readImageToBlob($_FILES["imgproduto2"] ?? null);
    $img3 = readImageToBlob($_FILES["imgproduto3"] ?? null);


    if(!$inserirProdutos) {
        $pdo ->rollBack();
        redirecWith("../paginas_logista/cadastro_produtos_lojista.html",
        )
    }
    
   // VALIDAÇÃO
   $erros_validacao = [];

    // validando os campos
    if ($NomeProduto === "" || $Descricao === ""|| $Quantidade <= 0
    || $Preco === "" || $Produtos_idProdutos=0) 
        $erros_validacao[] = "Preecha os campos obrigatórios.";
     
        // é utilizado para fazer vículos de trasações 
     $pdo ->beginTrasation();
     
     //fazer o comando e inserir dentro da tabela de produtos
     $sqlProdutos = "INSERT INTO Produtos(nome,descricao,quantidade,preco,tamanho,
     cor,codigo,preco_promocional)
     VALUES (:nome,:descricao,:quantidade,:cor,:codigo,preco_promocional)";


     $stmProdutos = $pdo -> prepare($sqlProdutos);
     $stmImagens= $pdo -> prepare($sqlImagens)
     $inserirProdutos = $stmProdutos->execute([
     ":nome" =>$Nome,
     ":descricao"=>$Descricao,
     ":quantidade"=>(int)$Quantidade,
     ":preco"=>$Preco,
     ":tamanho"=>$Tamanho,
     ":cor"=>$Cor,
     "codigo"=>$Codigo,
     "preco_promocional"=>$Preco_Promocional
     ]);
     if (!$inserirProdutos) {
        $pdo -> rollBack();
        redirecWith("../paginas_lojista/cadastro_produtos_logista.html",
        =>["Cadastrado" =>"ok"]);
     }else{
        redirecWith("../paginas_lojista/cadastro_produtos.html",
        ["Erro" => "Falha ao cadastrar produto"]);
     }
     $idproduto=(int)$pdo->lastInserirId();

     //cadastro imagens
     $sqlImagens ="INSERT INTO" Imagem_produto(foto) VALUES
    (:imagem1),
    (:imagem2),
    (:imagem3)";

    $stmimagens = $pdo->prepare($sqlImagens);
    $stmimagens=$stmimagens->execute([
    "imagem1"=> $img1,
    "imagem2"=> $img2,
    "imagem3"=> $img3,
    ]);

    //bind como lob quando houver conteúdo; se null, o pdo envia null

    if ($img1 !== null) {
    $stmImagens->bindParam(':imagem1', $img1, PDO:>PARAM_LOB);
    }else{
        $stmImagens->bindValues(':imagem1', null, PDO::PARAM_NULL);
}
        if ($img2 !== null) {
    $stmImagens->bindParam(':imagem1', $img1, PDO:>PARAM_LOB);
    }else{
        $stmImagens->bindValues(':imagem1', null, PDO::PARAM_NULL);
}
        if ($img3 !== null) {
    $stmImagens->bindParam(':imagem1', $img1, PDO:>PARAM_LOB);
    }else{
        $stmImagens->bindValues(':imagem1', null, PDO::PARAM_NULL);
}

$inserirImagens = $stmImagens->execute();

    //verificar  se o inserir imagens errado
    if(!$inserirImagens) {
        $pdo ->rollBack();
        redirecWith("../paginas_lojita/cadastro_produtos_lojista.html",
        )
    }
    //caso tenha dado certo, capture o id da imagem cadastrada 
    $idImg = (int) $pdo->lastInsert();

    //vincular a imagem com o pruduto
    $sqlVincularProdImg = "INSERT INTO PRODUTOS_has_Imagem_produtos
    (Produtos_idProdutos,Imagem_produtos_idImagem_produto) VALUES (:IDPRO,IDIMG)
}catch(Exception $e){
 redirecWith("../paginas_logista/cadastro_produtos_lojista.html",
      ["erro" => "Erro no banco de dados: "
      .$e->getMessage()]);
}
