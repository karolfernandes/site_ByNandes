<?php
require_once __DIR__ . '/conexao.php';

header('Content-Type: application/json; charset=utf-8');
mysqli_report(MYSQLI_REPORT_OFF); // desativa exceções automáticas

$response = ['ok' => false];

/*  =====================LISTAGEM================================== */
/* ===================== LISTAGEM ===================== */
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['listar'])) {
    header('Content-Type: application/json; charset=utf-8');

    try {
        $sql = "SELECT b.idBanner, b.Descricao, b.link, b.Data_validade,
                       b.Imagem_banner, c.Nome_categoria
                FROM Banner b
                LEFT JOIN Categorias c ON b.Categorias_idCategorias = c.idCategorias
                ORDER BY b.idBanner DESC";

        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $banners = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Converte imagem binária para Base64
        foreach ($banners as &$b) {
            if (!empty($b['Imagem_banner'])) {
                $b['Imagem_banner'] = base64_encode($b['Imagem_banner']);
            }
        }

        echo json_encode(['ok' => true, 'banners' => $banners], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ----------------- FUNÇÃO PARA CADASTRAR -----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Campos do formulário
        $descricao = $_POST['descricao'] ?? '';
        $link = $_POST['link'] ?? '';
        $data_validade = $_POST['data'] ?? null;
        $categoria = $_POST['categoria'] ?? null;

        // Upload da imagem
        if (!isset($_FILES['img']) || $_FILES['img']['error'] != 0) {
            throw new Exception('Selecione uma imagem válida.');
        }

        $imgData = file_get_contents($_FILES['img']['tmp_name']);

        // Inserção no banco
        $sql = "INSERT INTO Banner (Descricao, link, Data_validade, Categorias_idCategorias, Imagem_banner)
                VALUES (:descricao, :link, :data_validade, :categoria, :img)";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':descricao', $descricao);
        $stmt->bindParam(':link', $link);
        $stmt->bindParam(':data_validade', $data_validade);
        $stmt->bindParam(':categoria', $categoria ?: null, PDO::PARAM_INT);
        $stmt->bindParam(':img', $imgData, PDO::PARAM_LOB);
        $stmt->execute();

        echo json_encode(['ok' => true]);
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ----------------- FUNÇÃO PARA EXCLUIR -----------------
if (isset($_GET['excluir'])) {
    try {
        $id = intval($_GET['excluir']);
        $stmt = $conn->prepare("DELETE FROM Banner WHERE idBanner = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        echo json_encode(['ok' => true]);
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}