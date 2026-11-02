<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';

try {
    $statement = $pdo->prepare(
        'SELECT id, name, description, address, city, phone, opening_time, closing_time, image_url
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
                        <p><?= e($cafe['address']) ?>, <?= e($cafe['city']) ?></p>
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
    </main>
</body>
</html>