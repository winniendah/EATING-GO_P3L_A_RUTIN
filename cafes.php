<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';

try {
    $statement = $pdo->prepare(
        'SELECT id, name, description, address, city, phone,
                opening_time, closing_time, image_url
         FROM cafes
         WHERE status = :status
         ORDER BY name ASC'
    );

    $statement->execute(['status' => 'aktif']);
    $cafes = $statement->fetchAll();

} catch (PDOException $exception) {
    error_log('Cafe list query failed: ' . $exception->getMessage());
    throw new RuntimeException('Data kafe tidak dapat dimuat.', 0, $exception);
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function formatTime(?string $time): string
{
    if ($time === null || $time === '') {
        return '-';
    }

    return date('H:i', strtotime($time));
}

session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

try {
    $editableCafeStatement = $pdo->prepare(
        'SELECT id, name
         FROM cafes
         WHERE status = :status
         ORDER BY name ASC'
    );
    $editableCafeStatement->execute(['status' => 'aktif']);
    $editableCafes = $editableCafeStatement->fetchAll();
} catch (PDOException $exception) {
    error_log('Editable cafe list query failed: ' . $exception->getMessage());
    throw new RuntimeException('Data kafe untuk diperbarui tidak dapat dimuat.', 0, $exception);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Kafe - Eating Go</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<main class="container">

    <div class="page-header">
        <div>
            <h1 class="page-title">Daftar Kafe</h1>
            <p class="page-subtitle">Jelajahi dan temukan tempat nongkrong terbaik di Eating Go</p>
        </div>
        <div>
            <a href="create_cafe.php" class="btn btn-primary">
                + Tambah Kafe
            </a>
        </div>
    </div>

    <?php if (isset($_GET['created']) && $_GET['created'] === '1'): ?>
        <div class="alert alert-success" role="status">
            ✓ Data kafe berhasil ditambahkan!
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['updated']) && $_GET['updated'] === '1'): ?>
        <div class="alert alert-success" role="status">
            ✓ Data kafe berhasil diperbarui!
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['deleted']) && $_GET['deleted'] === '1'): ?>
        <div class="alert alert-success" role="status">
            ✓ Data kafe berhasil dihapus!
        </div>
    <?php endif; ?>

    <?php if ($cafes === []): ?>

        <div class="empty-state">
            <p>Belum ada kafe aktif yang tersedia saat ini.</p>
            <a href="create_cafe.php" class="btn btn-primary">+ Tambah Kafe Pertama</a>
        </div>

    <?php else: ?>

        <section class="cafe-grid" aria-label="Daftar kafe">

            <?php foreach ($cafes as $cafe): ?>

                <article class="cafe-card">

                    <div class="cafe-image-wrapper">
                        <?php if (!empty($cafe['image_url'])): ?>
                            <img
                                src="<?= e($cafe['image_url']) ?>"
                                alt="Foto <?= e($cafe['name']) ?>"
                                class="cafe-image"
                                loading="lazy"
                            >
                        <?php else: ?>
                            <div class="cafe-image-placeholder">
                                ☕
                                <span>No Image</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="cafe-body">
                        <h2 class="cafe-title"><?= e($cafe['name']) ?></h2>

                        <p class="cafe-desc">
                            <?= e($cafe['description'] ?: 'Tidak ada deskripsi kafe.') ?>
                        </p>

                        <div class="cafe-info">
                            <div class="cafe-info-item">
                                <span class="icon">📍</span>
                                <span><?= e($cafe['address']) ?>, <?= e($cafe['city']) ?></span>
                            </div>
                            <div class="cafe-info-item">
                                <span class="icon">🕒</span>
                                <span><?= e(formatTime($cafe['opening_time'])) ?> - <?= e(formatTime($cafe['closing_time'])) ?></span>
                            </div>
                            <?php if (!empty($cafe['phone'])): ?>
                                <div class="cafe-info-item">
                                    <span class="icon">📞</span>
                                    <span><?= e($cafe['phone']) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="cafe-actions">
                            <a href="update_cafe.php?id=<?= e((string) $cafe['id']) ?>" class="btn btn-outline btn-sm">
                                ✏️ Perbarui
                            </a>

                            <form action="delete_cafe.php" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus kafe <?= e(addslashes($cafe['name'])) ?>?');">
                                <input type="hidden" name="id" value="<?= e((string) $cafe['id']) ?>">
                                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                                <button type="submit" class="btn btn-danger btn-sm">
                                    🗑️ Hapus
                                </button>
                            </form>
                        </div>
                    </div>

                </article>

            <?php endforeach; ?>

        </section>

    <?php endif; ?>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

</body>
</html>
