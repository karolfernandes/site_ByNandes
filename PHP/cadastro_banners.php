<?php
require_once __DIR__ . "/conexao.php";

header('Content-Type: application/json');

try {
    $sql = "SELECT b.idBanner, b.Descricao, b.link, b.Data_validade, b.Imagem_banner, c.Nome as Categoria 
            FROM Banner b
            LEFT JOIN Categorias c ON b.Categorias_idCategorias = c.idCategorias
            ORDER BY b.idBanner DESC";
    $stmt = $pdo->query($sql);

    $banners = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $imgFile = __DIR__ . "/uploads/" . $row['Imagem_banner'];
        if (file_exists($imgFile)) {
            // Detectar tipo da imagem dinamicamente
            $ext = strtolower(pathinfo($imgFile, PATHINFO_EXTENSION));
            $mime = match($ext) {
                'jpg', 'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                default => 'application/octet-stream'
            };
            $imagemSrc = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($imgFile));
        } else {
            $imagemSrc = '';
        }

        $banners[] = [
            'id' => $row['idBanner'],
            'descricao' => $row['Descricao'],
            'link' => $row['link'],
            'validade' => $row['Data_validade'],
            'categoria' => $row['Categoria'] ?? '-',
            'imagem' => $imagemSrc
        ];
    }

    echo json_encode(['ok' => true, 'banners' => $banners]);

} catch (PDOException $e) {
    echo json_encode(['ok' => false, 'erro' => $e->getMessage()]);
}
