<?php
// ============================================================
//  api.php — Agulha e Linha - Catálogo de Produtos
//  Operações: list | save | update | delete
// ============================================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// ── CONFIG BANCO ──────────────────────────────────────────
define('DB_HOST',  '193.203.175.250');
define('DB_PORT',  3306);
define('DB_USER',  'u799109175_agulhaelinha');
define('DB_PASS',  'Q1k2v1y5@2025');
define('DB_NAME',  'u799109175_agulhaelinha');
define('DB_TABLE', 'tbl_produtos');

// ── CONEXÃO ───────────────────────────────────────────────
function getDB(): PDO {
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        DB_HOST, DB_PORT, DB_NAME);
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT            => 10,
    ]);
    return $pdo;
}

// ── ROTEAMENTO ────────────────────────────────────────────
$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = getDB();

    // ── LIST ──────────────────────────────────────────────
    if ($action === 'list' && $method === 'GET') {
        $stmt = $pdo->query(
            "SELECT id_produto, descricao, categoria, img_prod,
                    preco_original, preco_atual, desconto, observacao,
                    dt_registro
            FROM " . DB_TABLE . "
            ORDER BY preco_atual DESC"
        );
        echo json_encode(['success' => true, 'products' => $stmt->fetchAll()]);
        exit;
    }

    // ── SAVE ──────────────────────────────────────────────
    if ($action === 'save' && $method === 'POST') {

        $data = $_POST;

        $required = ['descricao', 'categoria', 'preco_original', 'preco_atual'];
        foreach ($required as $f) {
            if (empty($data[$f])) {
                http_response_code(422);
                echo json_encode(['success' => false, 'error' => "Campo obrigatório: $f"]);
                exit;
            }
        }

        // ── Upload da imagem ──────────────────────────────
        $img_prod = null;

        if (!empty($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
            $file     = $_FILES['imagem'];
            $maxSize  = 5 * 1024 * 1024;

            if ($file['size'] > $maxSize) {
                http_response_code(422);
                echo json_encode(['success' => false, 'error' => 'Imagem muito grande. Máximo 5 MB.']);
                exit;
            }

            $allowed  = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            $mimeType = mime_content_type($file['tmp_name']);

            if (!in_array($mimeType, $allowed)) {
                http_response_code(422);
                echo json_encode(['success' => false, 'error' => 'Formato inválido. Use JPG, PNG ou WEBP.']);
                exit;
            }

            $uploadDir = __DIR__ . '/uploads/produtos/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $ext      = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'jpg';
            $filename = uniqid('prod_', true) . '.' . strtolower($ext);
            $destPath = $uploadDir . $filename;

            if (!move_uploaded_file($file['tmp_name'], $destPath)) {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Falha ao salvar a imagem no servidor.']);
                exit;
            }

            $img_prod = 'uploads/produtos/' . $filename;
        }

        $stmt = $pdo->prepare(
            "INSERT INTO " . DB_TABLE . "
             (descricao, categoria, img_prod, preco_original, preco_atual, observacao)
             VALUES (:descricao, :categoria, :img_prod, :preco_original, :preco_atual, :observacao)"
        );
        $stmt->execute([
            ':descricao'      => trim($data['descricao']),
            ':categoria'      => $data['categoria'],
            ':img_prod'       => $img_prod,
            ':preco_original' => (float) $data['preco_original'],
            ':preco_atual'    => (float) $data['preco_atual'],
            ':observacao'     => $data['observacao'] ?? null,
        ]);

        echo json_encode([
            'success'  => true,
            'id'       => $pdo->lastInsertId(),
            'img_path' => $img_prod,
        ]);
        exit;
    }

    // ── UPDATE ────────────────────────────────────────────
    if ($action === 'update' && $method === 'POST') {

        $data = $_POST;

        $id = filter_var($data['id'] ?? 0, FILTER_VALIDATE_INT);
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID inválido']);
            exit;
        }

        $required = ['descricao', 'categoria', 'preco_original', 'preco_atual'];
        foreach ($required as $f) {
            if (empty($data[$f])) {
                http_response_code(422);
                echo json_encode(['success' => false, 'error' => "Campo obrigatório: $f"]);
                exit;
            }
        }

        // ── Upload da imagem (opcional no update) ─────────
        $img_prod  = null;
        $updateImg = false;

        if (!empty($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
            $file     = $_FILES['imagem'];
            $maxSize  = 5 * 1024 * 1024;

            if ($file['size'] > $maxSize) {
                http_response_code(422);
                echo json_encode(['success' => false, 'error' => 'Imagem muito grande. Máximo 5 MB.']);
                exit;
            }

            $allowed  = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            $mimeType = mime_content_type($file['tmp_name']);

            if (!in_array($mimeType, $allowed)) {
                http_response_code(422);
                echo json_encode(['success' => false, 'error' => 'Formato inválido. Use JPG, PNG ou WEBP.']);
                exit;
            }

            $uploadDir = __DIR__ . '/uploads/produtos/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $ext      = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'jpg';
            $filename = uniqid('prod_', true) . '.' . strtolower($ext);
            $destPath = $uploadDir . $filename;

            if (!move_uploaded_file($file['tmp_name'], $destPath)) {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Falha ao salvar a imagem no servidor.']);
                exit;
            }

            // Apaga imagem antiga para não acumular arquivos no servidor
            $stmtOld = $pdo->prepare("SELECT img_prod FROM " . DB_TABLE . " WHERE id_produto = :id");
            $stmtOld->execute([':id' => $id]);
            $oldRow = $stmtOld->fetch();
            if ($oldRow && $oldRow['img_prod']) {
                $oldFile = __DIR__ . '/' . $oldRow['img_prod'];
                if (file_exists($oldFile)) @unlink($oldFile);
            }

            $img_prod  = 'uploads/produtos/' . $filename;
            $updateImg = true;
        }

        // Monta SQL — só inclui img_prod se uma nova imagem foi enviada
        if ($updateImg) {
            $sql = "UPDATE " . DB_TABLE . "
                    SET descricao      = :descricao,
                        categoria      = :categoria,
                        preco_original = :preco_original,
                        preco_atual    = :preco_atual,
                        observacao     = :observacao,
                        img_prod       = :img_prod
                    WHERE id_produto   = :id";
        } else {
            $sql = "UPDATE " . DB_TABLE . "
                    SET descricao      = :descricao,
                        categoria      = :categoria,
                        preco_original = :preco_original,
                        preco_atual    = :preco_atual,
                        observacao     = :observacao
                    WHERE id_produto   = :id";
        }

        $params = [
            ':id'             => $id,
            ':descricao'      => trim($data['descricao']),
            ':categoria'      => $data['categoria'],
            ':preco_original' => (float) $data['preco_original'],
            ':preco_atual'    => (float) $data['preco_atual'],
            ':observacao'     => $data['observacao'] ?? null,
        ];
        if ($updateImg) $params[':img_prod'] = $img_prod;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        echo json_encode([
            'success'  => true,
            'img_path' => $img_prod,
        ]);
        exit;
    }

    // ── DELETE ────────────────────────────────────────────
    if ($action === 'delete' && $method === 'DELETE') {
        $id = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID inválido']);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM " . DB_TABLE . " WHERE id_produto = :id");
        $stmt->execute([':id' => $id]);

        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Produto não encontrado']);
            exit;
        }

        echo json_encode(['success' => true]);
        exit;
    }

    // ── ROTA NÃO ENCONTRADA ───────────────────────────────
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => "Ação '$action' não existe"]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Erro de banco de dados: ' . $e->getMessage()
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Erro interno: ' . $e->getMessage()
    ]);
}