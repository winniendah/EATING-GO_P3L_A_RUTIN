<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/config/database.php';

// Membuat token keamanan form
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

$errors = [];
$success = false;

$old = [
    'name' => '',
    'description' => '',
    'address' => '',
    'city' => '',
    'phone' => '',
    'opening_time' => '',
    'closing_time' => '',
    'image_url' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($old as $field => $value) {
        $old[$field] = trim((string) ($_POST[$field] ?? ''));
    }

    // Validasi CSRF
    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], (string) $_POST['csrf_token'])
    ) {
        $errors[] = 'Permintaan tidak valid. Silakan muat ulang halaman.';
    }

    // Validasi input wajib
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

    if ($old['opening_time'] === '') {
        $errors[] = 'Jam buka wajib diisi.';
    }

    if ($old['closing_time'] === '') {
        $errors[] = 'Jam tutup wajib diisi.';
    }

    // Validasi format waktu
    if (
        $old['opening_time'] !== '' &&
        $old['closing_time'] !== '' &&
        $old['opening_time'] >= $old['closing_time']
    ) {
        $errors[] = 'Jam tutup harus lebih besar dari jam buka.';
    }

    if ($old['phone'] !== '' && mb_strlen($old['phone']) > 20) {
        $errors[] = 'Nomor telepon maksimal 20 karakter.';
    }

    if ($old['image_url'] !== '') {
        if (
            filter_var($old['image_url'], FILTER_VALIDATE_URL) === false ||
            !in_array(
                strtolower((string) parse_url($old['image_url'], PHP_URL_SCHEME)),
                ['http', 'https'],
                true
            )
        ) {
            $errors[] = 'URL gambar harus menggunakan alamat HTTP atau HTTPS yang valid.';
        } elseif (mb_strlen($old['image_url']) > 500) {
            $errors[] = 'URL gambar maksimal 500 karakter.';
        }
    }

    if (!$errors) {
        try {
            $statement = $pdo->prepare(
                'INSERT INTO cafes (
                    name,
                    description,
                    address,
                    city,
                    phone,
                    opening_time,
                    closing_time,
                    image_url,
                    status
                ) VALUES (
                    :name,
                    :description,
                    :address,
                    :city,
                    :phone,
                    :opening_time,
                    :closing_time,
                    :image_url,
                    :status
                )'
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
                'image_url' => $old['image_url'] !== ''
                    ? $old['image_url']
                    : null,
                'status' => 'aktif',
            ]);

            // Redirect agar data tidak terkirim ulang ketika halaman direfresh
            header('Location: cafes.php?created=1');
            exit;
        } catch (PDOException $exception) {
            error_log('Create cafe failed: ' . $exception->getMessage());
            $errors[] = 'Data kafe gagal disimpan. Silakan coba kembali.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Kafe - Eating Go</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<main class="container">
    <div class="form-card">
        <h1 class="page-title" style="margin-bottom: 0.5rem;">Tambah Data Kafe</h1>
        <p class="page-subtitle" style="margin-bottom: 1.5rem;">Silakan isi formulir di bawah ini untuk menambahkan kafe baru.</p>

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

        <form action="create_cafe.php" method="POST">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e($_SESSION['csrf_token']) ?>"
            >

            <div class="form-group">
                <label for="name">Nama Kafe *</label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    maxlength="150"
                    value="<?= e($old['name']) ?>"
                    placeholder="Contoh: Kopi Titik Koma"
                    required
                >
            </div>

            <div class="form-group">
                <label for="description">Deskripsi Kafe</label>
                <textarea
                    id="description"
                    name="description"
                    rows="3"
                    placeholder="Tulis deskripsi atau suasana kafe..."
                ><?= e($old['description']) ?></textarea>
            </div>

            <div class="form-group">
                <label for="address">Alamat Kafe *</label>
                <textarea
                    id="address"
                    name="address"
                    maxlength="255"
                    rows="2"
                    placeholder="Contoh: Jl. Soekarno Hatta No. 15"
                    required
                ><?= e($old['address']) ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="city">Kota *</label>
                    <input
                        type="text"
                        id="city"
                        name="city"
                        maxlength="100"
                        value="<?= e($old['city']) ?>"
                        placeholder="Contoh: Balikpapan"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="phone">Nomor Telepon</label>
                    <input
                        type="tel"
                        id="phone"
                        name="phone"
                        maxlength="20"
                        value="<?= e($old['phone']) ?>"
                        placeholder="08123456789"
                    >
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="opening_time">Jam Buka *</label>
                    <input
                        type="time"
                        id="opening_time"
                        name="opening_time"
                        value="<?= e($old['opening_time']) ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="closing_time">Jam Tutup *</label>
                    <input
                        type="time"
                        id="closing_time"
                        name="closing_time"
                        value="<?= e($old['closing_time']) ?>"
                        required
                    >
                </div>
            </div>

            <div class="form-group">
                <label for="image_url">URL Gambar Kafe</label>
                <input
                    type="url"
                    id="image_url"
                    name="image_url"
                    maxlength="500"
                    value="<?= e($old['image_url']) ?>"
                    placeholder="https://contoh.com/gambar.jpg"
                >
            </div>

            <p style="font-size: 0.85rem; color: var(--text-muted);">* Wajib diisi.</p>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Simpan Kafe</button>
                <a href="cafes.php" class="btn btn-secondary">Batal</a>
            </div>

        </form>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

</body>
</html>
