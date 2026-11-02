<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/config/database.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

$errors = [];
$method = $_SERVER['REQUEST_METHOD'];
$idValue = $method === 'POST'
    ? ($_POST['id'] ?? '')
    : ($_GET['id'] ?? '');

$id = filter_var($idValue, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);

if ($id === false) {
    header('Location: cafes.php');
    exit;
}

try {
    $statement = $pdo->prepare(
        'SELECT id, name, city, address
         FROM cafes
         WHERE id = :id'
    );
    $statement->execute(['id' => $id]);
    $cafe = $statement->fetch();
} catch (PDOException $exception) {
    error_log('Lookup cafe for deletion failed: ' . $exception->getMessage());
    throw new RuntimeException('Data kafe tidak dapat dimuat.', 0, $exception);
}

if ($cafe === false) {
    header('Location: cafes.php');
    exit;
}

if ($method === 'POST') {
    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], (string) $_POST['csrf_token'])
    ) {
        $errors[] = 'Permintaan tidak valid. Silakan muat ulang halaman.';
    } else {
        try {
            // Soft delete sesuai dengan kolom enum status di schema database
            $deleteStmt = $pdo->prepare(
                'UPDATE cafes
                 SET status = :status
                 WHERE id = :id'
            );
            $deleteStmt->execute([
                'status' => 'nonaktif',
                'id' => $id,
            ]);

            header('Location: cafes.php?deleted=1');
            exit;
        } catch (PDOException $exception) {
            error_log('Delete cafe failed: ' . $exception->getMessage());
            $errors[] = 'Gagal menghapus data kafe. Silakan coba kembali.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hapus Kafe - Eating Go</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<main class="container">
    <div class="form-card">
        <h1 class="page-title" style="color: var(--danger); margin-bottom: 0.5rem;">Konfirmasi Hapus Kafe</h1>
        <p class="page-subtitle" style="margin-bottom: 1.5rem;">
            Apakah Anda yakin ingin menghapus kafe <strong><?= e($cafe['name']) ?></strong> (<?= e($cafe['city']) ?>)?
        </p>

        <?php if ($errors !== []): ?>
            <div class="alert alert-danger" role="alert">
                <strong>Terjadi kesalahan:</strong>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div style="background-color: var(--primary-light); padding: 1.25rem; border-radius: var(--radius-md); margin-bottom: 1.5rem;">
            <p><strong>Nama:</strong> <?= e($cafe['name']) ?></p>
            <p><strong>Alamat:</strong> <?= e($cafe['address']) ?>, <?= e($cafe['city']) ?></p>
            <p style="margin-top: 0.5rem; font-size: 0.88rem; color: var(--text-muted);">
                <em>Catatan: Kafe ini akan dinonaktifkan dari daftar publik.</em>
            </p>
        </div>

        <form action="delete_cafe.php" method="POST">
            <input type="hidden" name="id" value="<?= e((string) $id) ?>">
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">

            <div class="form-actions" style="margin-top: 0;">
                <button type="submit" class="btn btn-danger">
                    🗑️ Ya, Hapus Kafe
                </button>
                <a href="cafes.php" class="btn btn-secondary">
                    Batal
                </a>
            </div>
        </form>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

</body>
</html>

