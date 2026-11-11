<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/db.php';

// Ambil semua menu dari tabel "menu"
$result = mysqli_query(
    $conn,
    "SELECT * FROM menu ORDER BY id_menu ASC"
);

if (!$result) {
    die("Gagal mengambil data menu: " . mysqli_error($conn));
}

function h($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function rupiah($angka)
{
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}

// Ikon pengganti jika foto menu belum ada
function ikonMenu($nama)
{
    $ikon = [
        'bakso'       => '🍲',
        'kwetiau'     => '🍜',
        'kwetiaw'     => '🍜',
        'mie sagu'    => '🍝',
        'nasi goreng' => '🍛',
        'es teh'      => '🧋',
        'es jeruk'    => '🍊',
    ];
    $kunci = strtolower(trim($nama));
    return $ikon[$kunci] ?? '🍽️';
}

// Kelompokkan menu: Makanan dulu, lalu Minuman, lalu kategori lain
$kelompok = [];
while ($m = mysqli_fetch_assoc($result)) {
    $kelompok[$m['kategori']][] = $m;
}

$urutan = ['Makanan', 'Minuman'];
uksort($kelompok, function ($a, $b) use ($urutan) {
    $pa = array_search($a, $urutan);
    $pb = array_search($b, $urutan);
    $pa = ($pa === false) ? 99 : $pa;
    $pb = ($pb === false) ? 99 : $pb;
    return $pa <=> $pb;
});

$totalMenu = array_sum(array_map('count', $kelompok));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Menu Café Santuy</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <style>
        body {
            background: #f5f6fa;
        }

        .header {
            background: #3b2418;
            color: white;
            padding: 25px;
            border-radius: 15px;
        }

        .menu-card {
            border: 0;
            border-radius: 15px;
            overflow: hidden;
            height: 100%;
            box-shadow: 0 4px 12px #00000012;
        }

        .menu-img {
            height: 190px;
            width: 100%;
            object-fit: cover;
            background: #eee;
        }

        .menu-ikon {
            height: 190px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 78px;
            background: #f1e9e2;
        }

        .harga {
            color: #198754;
            font-size: 20px;
            font-weight: bold;
        }

        .judul-kategori {
            font-size: 20px;
            font-weight: 600;
            margin: 28px 0 14px;
            padding-bottom: 8px;
            border-bottom: 2px solid #3b2418;
            color: #3b2418;
        }

        .btn-dashboard {
            display: inline-block;
            background: #3b2418;
            color: white;
            padding: 10px 18px;
            border-radius: 8px;
            text-decoration: none;
            margin-bottom: 18px;
        }

        .btn-dashboard:hover {
            background: #5a3827;
            color: white;
        }
    </style>
</head>

<body>
<div class="container py-4">

    <a href="index.html" class="btn-dashboard">← Kembali ke Dashboard</a>

    <div class="header mb-4">
        <h2>☕ Café Santuy</h2>
        <p class="mb-0">Daftar menu makanan dan minuman</p>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-1">
        <h4>Semua Menu (<?= (int)$totalMenu ?>)</h4>
        <a href="tambah_menu.php" class="btn btn-success">+ Tambah Menu</a>
    </div>

    <?php if ($totalMenu > 0): ?>
        <?php foreach ($kelompok as $kategori => $daftar): ?>

            <div class="judul-kategori">
                <?= $kategori === 'Minuman' ? '🥤' : ($kategori === 'Makanan' ? '🍽️' : '📋') ?>
                <?= h($kategori) ?>
            </div>

            <div class="row g-4">
                <?php foreach ($daftar as $m): ?>
                    <?php
                    $namaGambar = basename($m['gambar'] ?? '');
                    $pathGambar = 'gambar/' . $namaGambar;
                    $adaGambar  = $namaGambar !== '' &&
                                  file_exists(__DIR__ . '/' . $pathGambar);
                    ?>
                    <div class="col-12 col-sm-6 col-lg-4">
                        <div class="card menu-card">

                            <?php if ($adaGambar): ?>
                                <img
                                    src="<?= h($pathGambar) ?>"
                                    class="menu-img"
                                    alt="<?= h($m['nama_menu']) ?>">
                            <?php else: ?>
                                <div class="menu-ikon" aria-hidden="true">
                                    <?= ikonMenu($m['nama_menu']) ?>
                                </div>
                            <?php endif; ?>

                            <div class="card-body">
                                <span class="badge text-bg-secondary">
                                    <?= h($m['kategori']) ?>
                                </span>

                                <h5 class="mt-2"><?= h($m['nama_menu']) ?></h5>
                                <p class="harga"><?= rupiah($m['harga']) ?></p>

                                <div class="d-flex gap-2">
                                    <a href="edit_menu.php?id=<?= (int)$m['id_menu'] ?>"
                                       class="btn btn-warning btn-sm">Edit</a>

                                    <form action="hapus_menu.php" method="POST"
                                          onsubmit="return confirm('Yakin ingin menghapus menu ini?')">
                                        <input type="hidden" name="id_menu"
                                               value="<?= (int)$m['id_menu'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php endforeach; ?>
    <?php else: ?>
        <div class="alert alert-info mt-3">
            Belum ada menu. Silakan tambahkan menu terlebih dahulu.
        </div>
    <?php endif; ?>

</div>
</body>
</html>
