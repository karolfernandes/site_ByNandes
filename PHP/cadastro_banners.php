<?php
// cadastro_banners.php
require_once __DIR__ . '/conexao.php';

/* ---------------------- UTIL ---------------------- */
function redirect_with(string $url, array $params = []): void {
  if ($params) {
    $qs  = http_build_query($params);
    $url .= (strpos($url, '?') === false ? '?' : '&') . $qs;
  }
  header("Location: $url");
  exit;
}

function read_image_to_blob(?array $file): ?string {
  if (!$file || !isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) return null;
  $bin = file_get_contents($file['tmp_name']);
  return $bin === false ? null : $bin;
}

const PAGE_OK   = '../PAGINAS_LOjISTA/promocoes_lojista.html';
const JSON_FLAGS = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

/* ===================== LISTAGEM (GET ?listar=1) ===================== */
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['listar'])) {
  header('Content-Type: application/json; charset=utf-8');

  try {
    $sql = "
      SELECT
        b.idBanner                 AS id,
        b.Imagem_banner           AS imagem,
        b.Data_validade           AS data_validade,
        b.Descricao               AS descricao,
        b.link                    AS link,
        b.Categorias_idCategorias AS categoria_id,
        c.Nome_categoria                    AS categoria_nome
      FROM Banner b
      LEFT JOIN Categorias c
        ON c.idCategorias = b.Categorias_idCategorias
      ORDER BY b.idBanner DESC
    ";

    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $banners = array_map(function ($r) {
      return [
        'id'             => (int)$r['id'],
        'descricao'      => $r['descricao'],
        'data_validade'  => $r['data_validade'],
        'link'           => ($r['link'] ?? '') !== '' ? $r['link'] : null,
        'categoria_id'   => isset($r['categoria_id']) ? (int)$r['categoria_id'] : null,
        'categoria_nome' => $r['categoria_nome'] ?? null,
        // Devolve a imagem em base64 (frontend decide se usa data URL)
        'imagem'         => !empty($r['imagem']) ? base64_encode($r['imagem']) : null,
      ];
    }, $rows);

    echo json_encode(['ok' => true, 'count' => count($banners), 'banners' => $banners], JSON_FLAGS);
    exit;

  } catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Erro ao listar banners', 'detail' => $e->getMessage()], JSON_FLAGS);
    exit;
  }
}

/* ===================== ATUALIZAÇÃO (POST acao=atualizar) ===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['acao'] ?? '') === 'atualizar')) {
  try {
    $id        = (int)($_POST['id'] ?? 0);
    $descricao = trim($_POST['descricao'] ?? '');
    $dataVal   = trim($_POST['data'] ?? '');
    $link      = trim($_POST['link'] ?? '');
    $categoria = $_POST['categoriab'] ?? null;
    $categoria = ($categoria === '' || $categoria === null) ? null : (int)$categoria;

    if ($id <= 0) {
      redirect_with(PAGE_OK, ['erro_banner' => 'ID inválido para edição.']);
    }

    // Lê (se houver) nova imagem
    $imgBlob = read_image_to_blob($_FILES['foto'] ?? null);

    // Validações
    $erros = [];
    if ($descricao === '') { $erros[] = 'Informe a descrição.'; }
    elseif (mb_strlen($descricao) > 45) { $erros[] = 'Descrição deve ter no máximo 45 caracteres.'; }

    $dt = DateTime::createFromFormat('Y-m-d', $dataVal);
    if (!($dt && $dt->format('Y-m-d') === $dataVal)) { $erros[] = 'Data de validade inválida (use YYYY-MM-DD).'; }

    if ($link !== '' && mb_strlen($link) > 45) { $erros[] = 'Link deve ter no máximo 45 caracteres.'; }

    if ($erros) {
      redirect_with(PAGE_OK, ['erro_banner' => implode(' ', $erros)]);
    }

    // Monta UPDATE dinâmico (atualiza Imagem_banner só se uma nova foi enviada)
    $setSql = "Descricao = :desc, Data_validade = :dt, link = :lnk, Categorias_idCategorias = :cat";
    if ($imgBlob !== null) {
      $setSql = "Imagem_banner = :img, " . $setSql;
    }

    $sql = "UPDATE Banner SET $setSql WHERE idBanner = :id";
    $st  = $pdo->prepare($sql);

    if ($imgBlob !== null) {
      $st->bindValue(':img', $imgBlob, PDO::PARAM_LOB);
    }

    $st->bindValue(':desc', $descricao, PDO::PARAM_STR);
    $st->bindValue(':dt',   $dataVal,   PDO::PARAM_STR);

    if ($link === '') {
      $st->bindValue(':lnk', null, PDO::PARAM_NULL);
    } else {
      $st->bindValue(':lnk', $link, PDO::PARAM_STR);
    }

    if ($categoria === null) {
      $st->bindValue(':cat', null, PDO::PARAM_NULL);
    } else {
      $st->bindValue(':cat', $categoria, PDO::PARAM_INT);
    }

    $st->bindValue(':id', $id, PDO::PARAM_INT);
    $st->execute();

    redirect_with(PAGE_OK, ['editar_banner' => 'ok']);

  } catch (Throwable $e) {
    redirect_with(PAGE_OK, ['erro_banner' => 'Erro ao editar: ' . $e->getMessage()]);
  }
}

/* ===================== EXCLUSÃO (POST acao=excluir) ===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['acao'] ?? '') === 'excluir')) {
  try {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
      redirect_with(PAGE_OK, ['erro_banner' => 'ID inválido para exclusão.']);
    }

    $st = $pdo->prepare("DELETE FROM Banner WHERE idBanner = :id");
    $st->bindValue(':id', $id, PDO::PARAM_INT);
    $st->execute();

    redirect_with(PAGE_OK, ['excluir_banner' => 'ok']);

  } catch (Throwable $e) {
    redirect_with(PAGE_OK, ['erro_banner' => 'Erro ao excluir: ' . $e->getMessage()]);
  }
}

/* ===================== CADASTRO (POST padrão) ===================== */
try {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_with(PAGE_OK, ['erro_banner' => 'Método inválido.']);
  }

  // Se chegou aqui sem acao=atualizar/excluir, entendemos como CADASTRAR
  $descricao = trim($_POST['descricao'] ?? '');
  $dataVal   = trim($_POST['data'] ?? '');
  $link      = trim($_POST['link'] ?? '');
  $categoria = (int)$_POST['categoria'] ;
 

  $imgBlob   = read_image_to_blob($_FILES['foto'] ?? null);

  $erros = [];
  if ($descricao === '') { $erros[] = 'Informe a descrição.'; }
  elseif (mb_strlen($descricao) > 45) { $erros[] = 'Descrição deve ter no máximo 45 caracteres.'; }

  $dt = DateTime::createFromFormat('Y-m-d', $dataVal);
  if (!($dt && $dt->format('Y-m-d') === $dataVal)) { $erros[] = 'Data de validade inválida (use YYYY-MM-DD).'; }

  if ($link !== '' && mb_strlen($link) > 45) { $erros[] = 'Link deve ter no máximo 45 caracteres.'; }

  if ($imgBlob === null) { $erros[] = 'Envie a imagem do banner.'; }

  if ($erros) {
    redirect_with(PAGE_OK, ['erro_banner' => implode(' ', $erros)]);
  }

  $sql = "INSERT INTO Banner (Imagem_banner, Data_validade, Descricao, link, Categorias_idCategorias)
          VALUES (:img, :dt, :desc, :lnk, :cat)";
  $st  = $pdo->prepare($sql);

  $st->bindValue(':img',  $imgBlob, PDO::PARAM_LOB);
  $st->bindValue(':dt',   $dataVal, PDO::PARAM_STR);
  $st->bindValue(':desc', $descricao, PDO::PARAM_STR);

  $link === ''
    ? $st->bindValue(':lnk', null, PDO::PARAM_NULL)
    : $st->bindValue(':lnk', $link, PDO::PARAM_STR);

  $categoria >0
    ? $st->bindValue(':cat', null, PDO::PARAM_NULL)
    : $st->bindValue(':cat', $categoria, PDO::PARAM_INT);

  $st->execute();

  redirect_with(PAGE_OK, ['cadastro_banner' => 'ok']);

} catch (Throwable $e) {
  redirect_with(PAGE_OK, ['erro_banner' => 'Erro no banco de dados: ' . $e->getMessage()]);
}
