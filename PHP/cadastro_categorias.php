<?php
// Conectando este arquivo ao banco de dados
require_once __DIR__ . "/conexao.php";

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



  // códigos de listagem de dados
if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET["listar"])) {

   try{
   // comando de listagem de dados
   $sqllistar ="SELECT idCategorias AS id, Nome_categoria as nome FROM 
   Categorias ORDER BY nome";

   // Prepara o comando para ser executado
   $stmtlistar = $pdo->query($sqllistar);   
   //executa e captura os dados retornados e guarda em $lista
   $listar = $stmtlistar->fetchAll(PDO::FETCH_ASSOC);

   // verificação de formatos
    $formato = isset($_GET["format"]) ? strtolower($_GET["format"]) : "option";


    if ($formato === "json") {
      header("Content-Type: application/json; charset=utf-8");
      echo json_encode(["ok" => true, "categorias" => $listar], JSON_UNESCAPED_UNICODE);
      exit;
    }


   // RETORNO PADRÃO
    header('Content-Type: text/html; charset=utf-8');
    foreach ($listar as $lista) {
      $id   = (int)$lista["id"];
      $nome = htmlspecialchars($lista["nome"], ENT_QUOTES, "UTF-8");
      echo "<option value=\"{$id}\">{$nome}</option>\n";
    }
    exit;
  


   }catch (Throwable $e) {
    // Em caso de erro na listagem
    if (isset($_GET['format']) && strtolower($_GET['format']) === 'json') {
      header('Content-Type: application/json; charset=utf-8', true, 500);
      echo json_encode(['ok' => false, 'error' => 'Erro ao listar categorias',
       'detail' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    } else {
      header('Content-Type: text/html; charset=utf-8', true, 500);
      echo "<option disabled>Erro ao carregar categorias</option>";
    }
    exit;
  }


}


// códigos de cadastro
try{
// SE O METODO DE ENVIO FOR DIFERENTE DO POST
    if($_SERVER["REQUEST_METHOD"] !== "POST"){
        //VOLTAR À TELA DE CADASTRO E EXIBIR ERRO
        redirecWith("../paginas_logista/cadastro_produtos_lojista.html",
           ["erro"=> "Metodo inválido"
          ]);
    }


    // jogando os dados da dentro de váriaveis
    $nome = $_POST["nomecategoria"];
    $desconto = (double)$_POST["desconto"];

     // VALIDANDO OS CAMPOS
// criar uma váriavel para receber os erros de validação
    $erros_validacao=[];
    //se qualquer campo for vazio
    if($nome === "" ){
        $erros_validacao[]="Preencha todos os campos";
    }

    if (!empty($erros_validacao)) {
        redirecWith("../paginas_lojista/cadastro_produtos_lojista.html", [
            "erro" => implode(", ", $erros_validacao)
        ]);
    }


    /* Inserir a categoria no banco de dados */
    $sql ="INSERT INTO Categorias (Nome_categoria,desconto)
     Values (:Nome_categoria,:desconto)";
      $stmCategorias = $pdo->prepare( $sql);
      $inerirCategoria = $stmCategorias->execute([
            ":Nome_categoria" => $nome,
        ":desconto" => $desconto
    ]);

     // executando o comando no banco de dados
   
     /* Verificando se foi cadastrado no banco de dados */
     if(!$inserirVincularProdImg){
        redirecWith("../paginas_lojista/cadastro_produtos_lojista.html",
        ["cadastro" => "ok"]) ;


     }else{
        redirecWith("../paginas_lojista/cadastro_produtos_lojista.html",["erro" 
        =>"Erro ao cadastrar no banco de dados"]);
     }

}catch(Exception $e){
 redirecWith("../paginas_lojista/cadastro_produtos_lojista.html",
      ["erro" => "Erro no banco de dados: "
      .$e->getMessage()]);
}


    exit;



?>