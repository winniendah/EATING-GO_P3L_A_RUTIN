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
$cafe = null;
$method = $_SERVER['REQUEST_METHOD'];
$idValue = $method === 'POST'
    ? ($_POST['id'] ?? '')
    : ($_GET['id'] ?? '');
$id = filter_var($idValue, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);

if ($id === false) {
    http_response_code(400);
    $errors[] = 'ID kafe tidak valid.';
} else {
    try {
        $statement = $pdo->prepare(
            'SELECT id, name, description, address, city, phone,
                    opening_time, closing_time, image_url
             FROM cafes
             WHERE id = :id'
        );
        $statement->execute(['id' => $id]);
        $cafe = $statement->fetch();
    } catch (PDOException $exception) {
        error_log('Cafe lookup for update failed: ' . $exception->getMessage());
        throw new RuntimeException('Data kafe tidak dapat dimuat.', 0, $exception);
    }

    if ($cafe === false) {
        $cafe = null;
        http_response_code(404);
        $errors[] = 'Data kafe tidak ditemukan.';
    }
}

$old = [
    'name' => (string) ($cafe['name'] ?? ''),
    'description' => (string) ($cafe['description'] ?? ''),
    'address' => (string) ($cafe['address'] ?? ''),
    'city' => (string) ($cafe['city'] ?? ''),
    'phone' => (string) ($cafe['phone'] ?? ''),
    'opening_time' => substr((string) ($cafe['opening_time'] ?? ''), 0, 5),
    'closing_time' => substr((string) ($cafe['closing_time'] ?? ''), 0, 5),
];
$currentImageUrl = (string) ($cafe['image_url'] ?? '');

if ($method === 'POST' && $cafe !== null) {
    foreach ($old as $field => $value) {
        $old[$field] = trim((string) ($_POST[$field] ?? ''));
    }

    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], (string) $_POST['csrf_token'])
    ) {
        $errors[] = 'Permintaan tidak valid. Silakan muat ulang halaman.';
    }

    if ($old['name'] === '') {
        $errors[] = 'Nama kafe wajib diisi.';
    } elseif (mb_strlen($old['name']) > 150) {
        $errors[] = 'Nama kafe maksimal 150 karakter.';
    }

    if ($old['address'] === '') {
        $errors[] = 'Alamat kafe wajib diisi.';
    } elseif (mb_strlen($old['address']) > 255) {
        $errors[] = 'Alamat maksimal 255 karakter.';
    }

    if ($old['city'] === '') {
        $errors[] = 'Kota wajib diisi.';
    } elseif (mb_strlen($old['city']) > 100) {
        $errors[] = 'Nama kota maksimal 100 karakter.';
    }

    foreach (['opening_time' => 'Jam buka', 'closing_time' => 'Jam tutup'] as $field => $label) {
        if ($old[$field] === '') {
            $errors[] = $label . ' wajib diisi.';
        } elseif (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/D', $old[$field])) {
            $errors[] = $label . ' tidak valid.';
        }
    }

    if (
        preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/D', $old['opening_time']) &&
        preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/D', $old['closing_time']) &&
        $old['opening_time'] >= $old['closing_time']
    ) {
        $errors[] = 'Jam tutup harus lebih besar dari jam buka.';
    }

    if ($old['phone'] !== '' && mb_strlen($old['phone']) > 20) {
        $errors[] = 'Nomor telepon maksimal 20 karakter.';
    }

    $finalImagePath = $currentImageUrl !== '' ? $currentImageUrl : null;

    // Cek jika pengguna memilih untuk menghapus foto saat ini
    if (!empty($_POST['remove_image']) && $_POST['remove_image'] === '1') {
        if (
            $currentImageUrl !== '' &&
            str_starts_with($currentImageUrl, 'assets/uploads/cafes/') &&
            file_exists(__DIR__ . '/' . $currentImageUrl)
        ) {
            unlink(__DIR__ . '/' . $currentImageUrl);
        }
        $finalImagePath = null;
    }

    // Cek jika pengguna mengunggah foto baru
    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['image'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Gagal mengunggah foto kafe.';
        } elseif ($file['size'] > 2 * 1024 * 1024) {
            $errors[] = 'Ukuran foto maksimal 2MB.';
        } else {
            $allowedMimeTypes = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp',
                'image/gif'  => 'gif',
            ];

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($file['tmp_name']);

            if (!isset($allowedMimeTypes[$mimeType])) {
                $errors[] = 'Format foto tidak valid. Gunakan format JPG, PNG, WEBP, atau GIF.';
            } else {
                $extension = $allowedMimeTypes[$mimeType];
                $fileName = 'cafe_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
                $uploadDir = __DIR__ . '/assets/uploads/cafes/';

                if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
                    $errors[] = 'Gagal membuat folder penyimpanan foto.';
                } elseif (!move_uploaded_file($file['tmp_name'], $uploadDir . $fileName)) {
                    $errors[] = 'Gagal menyimpan foto ke server.';
                } else {
                    // Hapus foto lama jika tersimpan secara lokal di folder uploads
                    if (
                        $currentImageUrl !== '' &&
                        str_starts_with($currentImageUrl, 'assets/uploads/cafes/') &&
                        file_exists(__DIR__ . '/' . $currentImageUrl)
                    ) {
                        unlink(__DIR__ . '/' . $currentImageUrl);
                    }

                    $finalImagePath = 'assets/uploads/cafes/' . $fileName;
                }
            }
        }
    }

    if ($errors === []) {
        try {
            $statement = $pdo->prepare(
                'UPDATE cafes
                 SET name = :name,
                     description = :description,
                     address = :address,
                     city = :city,
                     phone = :phone,
                     opening_time = :opening_time,
                     closing_time = :closing_time,
                     image_url = :image_url
                 WHERE id = :id'
            );
            $statement->execute([
                'name' => $old['name'],
                'description' => $old['description'] !== ''
                    ? $old['description']
                    : null,
                'address' => $old['address'],
                'city' => $old['city'],
                'phone' => $old['phone'] !== ''
                    ? $old['phone']
                    : null,
                'opening_time' => $old['opening_time'],
                'closing_time' => $old['closing_time'],
                'image_url' => $finalImagePath,
                'id' => $id,
            ]);

            header('Location: cafes.php?updated=1');
            exit;
        } catch (PDOException $exception) {
            error_log('Update cafe failed: ' . $exception->getMessage());
            $errors[] = 'Data kafe gagal diperbarui. Silakan coba kembali.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perbarui Kafe - Eating Go</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<main class="container">
    <div class="form-card">
        <h1 class="page-title" style="margin-bottom: 0.5rem;">Perbarui Data Kafe</h1>
        <p class="page-subtitle" style="margin-bottom: 1.5rem;">Ubah informasi detail kafe yang dipilih.</p>

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

        <?php if ($cafe !== null): ?>
            <form action="update_cafe.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?= e((string) $id) ?>">
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e($_SESSION['csrf_token']) ?>"
                >

                <div class="form-group">
                    <label for="name">Nama Kafe *</label>
                    <input type="text" id="name" name="name" maxlength="150" value="<?= e($old['name']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="description">Deskripsi Kafe</label>
                    <textarea id="description" name="description" rows="3"><?= e($old['description']) ?></textarea>
                </div>

                <div class="form-group">
                    <label for="address">Alamat Kafe *</label>
                    <textarea id="address" name="address" maxlength="255" rows="2" required><?= e($old['address']) ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="city">Kota *</label>
                        <input type="text" id="city" name="city" maxlength="100" value="<?= e($old['city']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="phone">Nomor Telepon</label>
                        <input type="tel" id="phone" name="phone" maxlength="20" value="<?= e($old['phone']) ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="opening_time">Jam Buka *</label>
                        <input type="time" id="opening_time" name="opening_time" value="<?= e($old['opening_time']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="closing_time">Jam Tutup *</label>
                        <input type="time" id="closing_time" name="closing_time" value="<?= e($old['closing_time']) ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="image">Foto Kafe</label>
                    <?php if (!empty($currentImageUrl)): ?>
                        <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 0.75rem;">
                            <img
                                src="<?= e($currentImageUrl) ?>"
                                alt="Foto saat ini"
                                style="width: 90px; height: 90px; object-fit: cover; border-radius: var(--radius-sm); border: 1px solid var(--border-color);"
                            >
                            <div>
                                <span style="font-size: 0.85rem; color: var(--text-muted); display: block; margin-bottom: 0.35rem;">Foto saat ini</span>
                                <label style="font-size: 0.85rem; color: var(--danger); cursor: pointer; display: flex; align-items: center; gap: 0.35rem;">
                                    <input type="checkbox" name="remove_image" value="1">
                                    Hapus foto saat ini
                                </label>
                            </div>
                        </div>
                    <?php endif; ?>
                    <input
                        type="file"
                        id="image"
                        name="image"
                        accept="image/jpeg,image/png,image/webp,image/gif"
                    >
                    <small style="display: block; margin-top: 0.35rem; color: var(--text-muted); font-size: 0.85rem;">
                        Pilih file baru jika ingin mengganti foto (JPG, PNG, WEBP, GIF, Maksimal 2MB).
                    </small>
                </div>

                <p style="font-size: 0.85rem; color: var(--text-muted);">* Wajib diisi.</p>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    <a href="cafes.php" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        <?php else: ?>
            <div class="empty-state">
                <p>Data kafe tidak dapat ditampilkan karena tidak ditemukan atau ID tidak valid.</p>
                <a href="cafes.php" class="btn btn-primary">Kembali ke Daftar Kafe</a>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

</body>
</html>
