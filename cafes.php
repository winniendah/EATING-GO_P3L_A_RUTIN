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
</head>
<body>

<main>

    <h1>Daftar Kafe</h1>

    <a href="create_cafe.php">
        + Tambah Kafe
    </a>

    <?php if (isset($_GET['created']) && $_GET['created'] === '1'): ?>
        <p role="status">
            Data kafe berhasil ditambahkan!
        </p>
    <?php endif; ?>

    <?php if (isset($_GET['updated']) && $_GET['updated'] === '1'): ?>
        <p role="status">
            Data kafe berhasil diperbarui!
        </p>
    <?php endif; ?>

    <?php if ($cafes === []): ?>

        <p>Belum ada kafe aktif yang tersedia.</p>

    <?php else: ?>

        <section aria-label="Daftar kafe">

            <?php foreach ($cafes as $cafe): ?>

                <article>

                    <?php if (!empty($cafe['image_url'])): ?>
                        <img
                            src="<?= e($cafe['image_url']) ?>"
                            alt="Foto <?= e($cafe['name']) ?>"
                        >
                    <?php endif; ?>

                    <h2><?= e($cafe['name']) ?></h2>

                    <p><?= e($cafe['description']) ?></p>

                    <p>
                        <?= e($cafe['address']) ?>,
                        <?= e($cafe['city']) ?>
                    </p>

                    <p>
                        Jam buka:
                        <?= e(formatTime($cafe['opening_time'])) ?>
                        -
                        <?= e(formatTime($cafe['closing_time'])) ?>
                    </p>

                    <?php if (!empty($cafe['phone'])): ?>
                        <p>Telepon: <?= e($cafe['phone']) ?></p>
                    <?php endif; ?>

                </article>

            <?php endforeach; ?>

        </section>

    <?php endif; ?>

    <section aria-label="Perbarui data kafe">
        <h2>Perbarui Data Kafe</h2>

        <?php if ($editableCafes === []): ?>
            <p>Belum ada kafe yang dapat diperbarui.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($editableCafes as $editableCafe): ?>
                    <li>
                        <?= e($editableCafe['name']) ?>
                        <a href="update_cafe.php?id=<?= e((string) $editableCafe['id']) ?>">
                            Perbarui
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

</main>

</body>
</html>
