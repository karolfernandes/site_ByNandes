<?php
// ======================================================================
// Produtos: listar (com ou sem categoria), cadastrar, editar, excluir
// Depende de: conexao.php (PDO $pdo)
// Tabelas usadas: Produtos, ImagemProduto, Produtos_has_ImagemProduto,
//                 Categorias, Produtos_has_Categorias, Vendas_has_Produtos
// ======================================================================

require_once __DIR__ . '/conexao.php';

// Config PDO
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

// ----------------------- Helpers --------------------------------------
function json_ok(array $data = [], int $code = 200) {
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['ok' => true] + $data, JSON_UNESCAPED_UNICODE);
  exit;
}
function json_err(string $msg, int $code = 400) {
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['ok' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE);
  exit;
}
function redirect_with(string $url, array $params = []): void {
  if ($params) {
    $qs  = http_build_query($params);
    $url .= (strpos($url,'?') === false ? '?' : '&') . $qs;
  }
  header("Location: $url"); exit;
}
function wants_json(): bool {
  // use isso para POST: se vier 'json=1' ou Accept: application/json
  if (isset($_REQUEST['json']) && $_REQUEST['json'] == '1') return true;
  $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
  return stripos($accept, 'application/json') !== false;
}
function readImageToBlob(?array $file): ?string {
  if (!$file || !isset($file['tmp_name'])) return null;
  if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) return null;
  $bin = @file_get_contents($file['tmp_name']);
  return $bin === false ? null : $bin;
}

// ----------------------- LISTAGEM (GET) --------------------------------
// ?listar=1 [&categoria_id=NN] [&categoria_slug=xxx]
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['listar'])) {
  try {
    $filtroCategoriaSQL = '';
    $params = [];

    if (!empty($_GET['categoria_id'])) {
      $filtroCategoriaSQL = "
        INNER JOIN Produtos_has_Categorias phc
                ON phc.Produtos_idProdutos = p.idProdutos
        INNER JOIN Categorias c
                ON c.idCategorias = phc.Categorias_idCategorias
        WHERE c.idCategorias = :catId
      ";
      $params[':catId'] = (int)$_GET['categoria_id'];
    } elseif (!empty($_GET['categoria_slug'])) {
      $filtroCategoriaSQL = "
        INNER JOIN Produtos_has_Categorias phc
                ON phc.Produtos_idProdutos = p.idProdutos
        INNER JOIN Categorias c
                ON c.idCategorias = phc.Categorias_idCategorias
        WHERE c.Categoriaurl = :slug
      ";
      $params[':slug'] = (string)$_GET['categoria_slug'];
    } else {
      $filtroCategoriaSQL = ""; // sem filtro
    }

    // subquery para pegar a PRIMEIRA imagem de cada produto
    $sql = "
      SELECT
        p.idProdutos,
        p.NomeProduto,
        p.Descricao,
        p.Quantidade,
        p.Preco,
        p.Preco_Promocional,
        p.Tamanho,
        p.Cor,
        p.Codigo,
        img.Foto AS Foto
      FROM Produtos p
      " . $filtroCategoriaSQL . "
      LEFT JOIN (
        SELECT
          phi.Produtos_idProdutos AS pid,
          MIN(phi.ImagemProduto_idImagemProduto) AS first_img_id
        FROM Produtos_has_ImagemProduto phi
        GROUP BY phi.Produtos_idProdutos
      ) firstimg ON firstimg.pid = p.idProdutos
      LEFT JOIN ImagemProduto img ON img.idImagemProduto = firstimg.first_img_id
      " . (empty($filtroCategoriaSQL) ? "" : "") . "
      ORDER BY p.NomeProduto
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $produtos = array_map(function($r){
      $dataUri = null;
      if (!empty($r['Foto'])) {
        $dataUri = 'data:image/jpeg;base64,' . base64_encode($r['Foto']); // ajuste mimetype se necessário
      }
      return [
        'id'               => (int)$r['idProdutos'],
        'nome'             => (string)$r['NomeProduto'],
        'descricao'        => (string)$r['Descricao'],
        'quantidade'       => (int)$r['Quantidade'],
        'preco'            => (float)$r['Preco'],
        'precoPromocional' => $r['Preco_Promocional'] !== null ? (float)$r['Preco_Promocional'] : null,
        'tamanho'          => $r['Tamanho'],
        'cor'              => $r['Cor'],
        'codigo'           => (int)$r['Codigo'],
        'imagem'           => $dataUri
      ];
    }, $rows);

    json_ok(['count' => count($produtos), 'produtos' => $produtos]);
  } catch (Throwable $e) {
    json_err('Erro ao listar produtos: ' . $e->getMessage(), 500);
  }
}

// ----------------------- ROTAS (POST) ----------------------------------
// acao=cadastrar|editar|excluir
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $acao = $_POST['acao'] ?? '';

  // ---------- Cadastrar ----------
  if ($acao === 'cadastrar') {
    try {
      $NomeProduto       = trim($_POST['nomeproduto'] ?? '');
      $Descricao         = trim($_POST['descricao'] ?? '');
      $Quantidade        = (int)($_POST['quantidade'] ?? 0);
      $Preco             = (float)($_POST['preco'] ?? 0);
      $Tamanho           = $_POST['tamanho'] ?? null;
      $Cor               = $_POST['cor'] ?? null;
      $Codigo            = (int)($_POST['codigo'] ?? 0);
      $PrecoPromocional  = isset($_POST['precopromocional']) && $_POST['precopromocional'] !== ''
                           ? (float)$_POST['precopromocional'] : null;

      $catIds = []; // opcional: vincular categorias já no cadastro
      if (!empty($_POST['categorias'])) {
        // aceite "1,2,3" ou array
        $catIds = is_array($_POST['categorias'])
          ? array_map('intval', $_POST['categorias'])
          : array_map('intval', preg_split('/[,\s]+/', $_POST['categorias']));
      }

      $img1 = readImageToBlob($_FILES['imgproduto1'] ?? null);
      $img2 = readImageToBlob($_FILES['imgproduto2'] ?? null);
      $img3 = readImageToBlob($_FILES['imgproduto3'] ?? null);

      // Validação básica
      $erros = [];
      if ($NomeProduto === '' || $Descricao === '') $erros[] = 'Nome e descrição são obrigatórios.';
      if ($Quantidade <= 0) $erros[] = 'Quantidade deve ser maior que zero.';
      if ($Preco <= 0) $erros[] = 'Preço deve ser maior que zero.';
      if ($erros) {
        if (wants_json()) json_err(implode(' ', $erros), 422);
        redirect_with('../paginas_lojista/cadastro_produtos_lojista.html', ['erro' => implode(', ', $erros)]);
      }

      $pdo->beginTransaction();

      $sql = "INSERT INTO Produtos
        (NomeProduto, Descricao, Quantidade, Preco, Tamanho, Cor, Codigo, Preco_Promocional)
        VALUES (:n,:d,:q,:p,:t,:c,:cod,:pp)";
      $st = $pdo->prepare($sql);
      $ok = $st->execute([
        ':n' => $NomeProduto, ':d' => $Descricao, ':q' => $Quantidade, ':p' => $Preco,
        ':t' => $Tamanho, ':c' => $Cor, ':cod' => $Codigo, ':pp' => $PrecoPromocional
      ]);
      if (!$ok) throw new RuntimeException('Falha ao inserir produto');

      $idProduto = (int)$pdo->lastInsertId();

      // categorias (opcional)
      if ($catIds) {
        $stC = $pdo->prepare("INSERT INTO Produtos_has_Categorias (Produtos_idProdutos, Categorias_idCategorias) VALUES (:p,:c)");
        foreach ($catIds as $cid) {
          if ($cid > 0) $stC->execute([':p'=>$idProduto, ':c'=>$cid]);
        }
      }

      // imagens (opcional)
      $imgs = array_filter([$img1, $img2, $img3], fn($x) => $x !== null);
      if ($imgs) {
        $stImg = $pdo->prepare("INSERT INTO ImagemProduto (Foto) VALUES (:foto)");
        $stLink = $pdo->prepare("INSERT INTO Produtos_has_ImagemProduto (Produtos_idProdutos, ImagemProduto_idImagemProduto) VALUES (:p,:i)");
        foreach ($imgs as $blob) {
          $stImg->bindValue(':foto', $blob, PDO::PARAM_LOB);
          $stImg->execute();
          $imgId = (int)$pdo->lastInsertId();
          $stLink->execute([':p'=>$idProduto, ':i'=>$imgId]);
        }
      }

      $pdo->commit();
      if (wants_json()) json_ok(['id'=>$idProduto, 'msg'=>'Produto cadastrado']);
      redirect_with('../paginas_lojista/cadastro_produtos_lojista.html', ['cadastro'=>'ok']);

    } catch (Throwable $e) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      if (wants_json()) json_err('Erro ao cadastrar: ' . $e->getMessage(), 500);
      redirect_with('../paginas_lojista/cadastro_produtos_lojista.html', ['erro'=>'Erro: '.$e->getMessage()]);
    }
  }

  // ---------- Editar ----------
  if ($acao === 'editar') {
    try {
      $id = (int)($_POST['id'] ?? 0);
      if ($id <= 0) {
        if (wants_json()) json_err('ID inválido', 422);
        redirect_with('../paginas_lojista/cadastro_produtos_lojista.html', ['erro'=>'ID inválido']);
      }

      // Campos (se vierem vazios, mantemos o existente? aqui vamos exigir valores)
      $NomeProduto       = trim($_POST['nomeproduto'] ?? '');
      $Descricao         = trim($_POST['descricao'] ?? '');
      $Quantidade        = (int)($_POST['quantidade'] ?? 0);
      $Preco             = (float)($_POST['preco'] ?? 0);
      $Tamanho           = $_POST['tamanho'] ?? null;
      $Cor               = $_POST['cor'] ?? null;
      $Codigo            = (int)($_POST['codigo'] ?? 0);
      $PrecoPromocional  = isset($_POST['precopromocional']) && $_POST['precopromocional'] !== ''
                           ? (float)$_POST['precopromocional'] : null;

      $catIds = [];
      if (isset($_POST['categorias'])) {
        $catIds = is_array($_POST['categorias'])
          ? array_map('intval', $_POST['categorias'])
          : array_map('intval', preg_split('/[,\s]+/', $_POST['categorias']));
      }

      // novas imagens (opcional)
      $img1 = readImageToBlob($_FILES['imgproduto1'] ?? null);
      $img2 = readImageToBlob($_FILES['imgproduto2'] ?? null);
      $img3 = readImageToBlob($_FILES['imgproduto3'] ?? null);

      $erros = [];
      if ($NomeProduto === '' || $Descricao === '') $erros[] = 'Nome e descrição são obrigatórios.';
      if ($Quantidade <= 0) $erros[] = 'Quantidade deve ser maior que zero.';
      if ($Preco <= 0) $erros[] = 'Preço deve ser maior que zero.';
      if ($erros) {
        if (wants_json()) json_err(implode(' ', $erros), 422);
        redirect_with('../paginas_lojista/cadastro_produtos_lojista.html', ['erro'=>implode(', ',$erros)]);
      }

      $pdo->beginTransaction();

      $sql = "UPDATE Produtos
              SET NomeProduto=:n, Descricao=:d, Quantidade=:q, Preco=:p,
                  Tamanho=:t, Cor=:c, Codigo=:cod, Preco_Promocional=:pp
              WHERE idProdutos=:id";
      $st = $pdo->prepare($sql);
      $st->execute([
        ':n'=>$NomeProduto, ':d'=>$Descricao, ':q'=>$Quantidade, ':p'=>$Preco,
        ':t'=>$Tamanho, ':c'=>$Cor, ':cod'=>$Codigo, ':pp'=>$PrecoPromocional,
        ':id'=>$id
      ]);

      // Atualiza categorias (se enviadas)
      if ($catIds !== []) {
        // zera e re-insere
        $pdo->prepare("DELETE FROM Produtos_has_Categorias WHERE Produtos_idProdutos=:p")
            ->execute([':p'=>$id]);
        $stC = $pdo->prepare("INSERT INTO Produtos_has_Categorias (Produtos_idProdutos, Categorias_idCategorias) VALUES (:p,:c)");
        foreach ($catIds as $cid) {
          if ($cid > 0) $stC->execute([':p'=>$id, ':c'=>$cid]);
        }
      }

      // adiciona novas imagens (não remove antigas)
      $imgs = array_filter([$img1, $img2, $img3], fn($x)=>$x!==null);
      if ($imgs) {
        $stImg  = $pdo->prepare("INSERT INTO ImagemProduto (Foto) VALUES (:foto)");
        $stLink = $pdo->prepare("INSERT INTO Produtos_has_ImagemProduto (Produtos_idProdutos, ImagemProduto_idImagemProduto) VALUES (:p,:i)");
        foreach ($imgs as $blob) {
          $stImg->bindValue(':foto', $blob, PDO::PARAM_LOB);
          $stImg->execute();
          $imgId = (int)$pdo->lastInsertId();
          $stLink->execute([':p'=>$id, ':i'=>$imgId]);
        }
      }

      $pdo->commit();
      if (wants_json()) json_ok(['id'=>$id, 'msg'=>'Produto atualizado']);
      redirect_with('../paginas_lojista/cadastro_produtos_lojista.html', ['edicao'=>'ok']);

    } catch (Throwable $e) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      if (wants_json()) json_err('Erro ao editar: '.$e->getMessage(), 500);
      redirect_with('../paginas_lojista/cadastro_produtos_lojista.html', ['erro'=>'Erro: '.$e->getMessage()]);
    }
  }

  // ---------- Excluir ----------
  if ($acao === 'excluir') {
    try {
      $id = (int)($_POST['id'] ?? 0);
      if ($id <= 0) {
        if (wants_json()) json_err('ID inválido', 422);
        redirect_with('../paginas_lojista/cadastro_produtos_lojista.html', ['erro'=>'ID inválido']);
      }

      $pdo->beginTransaction();

      // Descobre imagens ligadas a este produto
      $imgIds = $pdo->prepare("SELECT ImagemProduto_idImagemProduto AS imgId FROM Produtos_has_ImagemProduto WHERE Produtos_idProdutos=:p");
      $imgIds->execute([':p'=>$id]);
      $imgs = array_column($imgIds->fetchAll(), 'imgId');

      // Remove relações
      $pdo->prepare("DELETE FROM Vendas_has_Produtos WHERE Produtos_idProdutos=:p")->execute([':p'=>$id]);
      $pdo->prepare("DELETE FROM Produtos_has_Categorias WHERE Produtos_idProdutos=:p")->execute([':p'=>$id]);
      $pdo->prepare("DELETE FROM Produtos_has_ImagemProduto WHERE Produtos_idProdutos=:p")->execute([':p'=>$id]);

      // Exclui produto
      $pdo->prepare("DELETE FROM Produtos WHERE idProdutos=:p")->execute([':p'=>$id]);

      // Limpa imagens órfãs (aquelas que não têm mais vínculo)
      if ($imgs) {
        $stCheck = $pdo->prepare("SELECT COUNT(*) FROM Produtos_has_ImagemProduto WHERE ImagemProduto_idImagemProduto=:i");
        $stDel   = $pdo->prepare("DELETE FROM ImagemProduto WHERE idImagemProduto=:i");
        foreach ($imgs as $iid) {
          $stCheck->execute([':i'=>$iid]);
          if ((int)$stCheck->fetchColumn() === 0) {
            $stDel->execute([':i'=>$iid]);
          }
        }
      }

      $pdo->commit();
      if (wants_json()) json_ok(['id'=>$id, 'msg'=>'Produto excluído']);
      redirect_with('../paginas_lojista/cadastro_produtos_lojista.html', ['excluir'=>'ok']);

    } catch (Throwable $e) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      if (wants_json()) json_err('Erro ao excluir: '.$e->getMessage(), 500);
      redirect_with('../paginas_lojista/cadastro_produtos_lojista.html', ['erro'=>'Erro: '.$e->getMessage()]);
    }
  }

  // Se chegou aqui, acao inválida
  if (wants_json()) json_err('Ação inválida', 400);
  redirect_with('../paginas_lojista/cadastro_produtos_lojista.html', ['erro'=>'Ação inválida']);
}

// Se não bateu em nenhuma rota:
http_response_code(405);
echo "Método não permitido.";
