
<?php
session_start();

/* =====================================
   KONEKSI DATABASE
===================================== */

$db_host = "localhost";
$db_name = "kasir_cafe_santuy";
$db_user = "root";
$db_pass = "";

try {
    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

/* =====================================
   MEMBUAT TABEL ADMIN
===================================== */

$pdo->exec("
    CREATE TABLE IF NOT EXISTS admin_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL
    )
");

$jumlah = (int) $pdo->query(
    "SELECT COUNT(*) FROM admin_users"
)->fetchColumn();

if ($jumlah === 0) {
    $buat = $pdo->prepare("
        INSERT INTO admin_users (username, password)
        VALUES (?, ?)
    ");
    $buat->execute(["widya", "passwordkamu"]);
}

/* =====================================
   PENGATURAN GAMBAR CAFE
===================================== */

// Lokasi foto background
$gambarCafe = "gambar/cafe.jpg";

// Periksa keberadaan foto
$pathGambar = __DIR__ . "/gambar/cafe.jpg";
$adaGambarCafe = file_exists($pathGambar);

/* =====================================
   CEK SESSION
===================================== */

if (isset($_SESSION["user_id"])) {
    header("Location: index.html");
    exit;
}

/* =====================================
   PROSES LOGIN
===================================== */

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    $stmt = $pdo->prepare("
        SELECT id, username, password
        FROM admin_users
        WHERE username = ?
        LIMIT 1
    ");

    $stmt->execute([$username]);
    $user = $stmt->fetch();

    $cocok = false;

    if ($user) {
        $passwordTersimpan = $user["password"];

        if (password_verify($password, $passwordTersimpan)) {
            $cocok = true;
        } elseif (hash_equals($passwordTersimpan, $password)) {
            $cocok = true;

            // Amankan password lama dengan hash
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $update = $pdo->prepare("
                UPDATE admin_users
                SET password = ?
                WHERE id = ?
            ");

            $update->execute([$hash, $user["id"]]);
        }
    }

    if ($cocok) {

        session_regenerate_id(true);

        $_SESSION["user_id"] = $user["id"];
        $_SESSION["username"] = $user["username"];
        $_SESSION["nama"] = $user["username"];
        $_SESSION["role"] = "admin";

        header("Location: index.html");
        exit;

    } else {
        $error = "Username atau password salah. Silakan coba lagi.";
    }
}

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Login - POS Cafe Santuy</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>

:root {
    --utama: #4b55c9;
    --gelap: #303887;
    --teks: #24263b;
    --abu: #777c91;
    --garis: #e4e6f2;
}

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    min-height: 100vh;
    font-family: 'Poppins', sans-serif;
    background: #f1f3fb;
    color: var(--teks);
    display: grid;
    grid-template-columns: 1.1fr 1fr;
}

/* =====================================
   PANEL KIRI - FOTO CAFE
===================================== */

.kiri {
    position: relative;
    min-height: 100vh;
    padding: 38px 45px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    overflow: hidden;
    color: white;

    background-color: #303887;
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
}

.logo {
    position: relative;
    z-index: 2;
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 25px;
    font-weight: 600;
    letter-spacing: 1px;
}

.logo-icon {
    width: 48px;
    height: 48px;
    display: grid;
    place-items: center;
    background: rgba(255,255,255,.18);
    border: 1px solid rgba(255,255,255,.3);
    border-radius: 14px;
    font-size: 27px;
}

.intro {
    position: relative;
    z-index: 2;
    max-width: 500px;
}

.label {
    display: inline-block;
    padding: 8px 14px;
    border-radius: 30px;
    background: rgba(255,255,255,.16);
    border: 1px solid rgba(255,255,255,.3);
    font-size: 12px;
    letter-spacing: 1px;
    margin-bottom: 20px;
}

.intro h1 {
    font-size: clamp(32px, 4vw, 53px);
    line-height: 1.2;
    margin: 0 0 18px;
    font-weight: 600;
}

.intro p {
    color: #f0f1ff;
    line-height: 1.9;
    font-size: 15px;
    max-width: 420px;
    margin: 0;
}

.fitur {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 27px;
}

.fitur span {
    padding: 9px 13px;
    background: rgba(255,255,255,.16);
    border: 1px solid rgba(255,255,255,.22);
    border-radius: 9px;
    font-size: 12px;
}

.footer-kiri {
    position: relative;
    z-index: 2;
    color: #e1e4ff;
    font-size: 12px;
}

/* =====================================
   PANEL KANAN
===================================== */

.kanan {
    display: grid;
    place-items: center;
    padding: 35px;
}

.login-box {
    width: min(420px, 100%);
}

.mini-logo {
    display: none;
    font-weight: 600;
    color: var(--utama);
    margin-bottom: 25px;
}

.login-box h2 {
    margin: 0 0 8px;
    font-size: 30px;
    font-weight: 600;
}

.sub {
    margin: 0 0 30px;
    color: var(--abu);
    font-size: 13px;
    line-height: 1.8;
}

.form-card {
    background: #fff;
    border: 1px solid #eaebf5;
    border-radius: 20px;
    padding: 30px;
    box-shadow: 0 15px 45px rgba(47,54,125,.09);
}

.form-card h3 {
    margin: 0 0 23px;
    font-size: 16px;
    font-weight: 500;
}

.f {
    margin-bottom: 20px;
}

label {
    display: block;
    margin-bottom: 8px;
    font-size: 13px;
    font-weight: 500;
}

.input-wrap {
    position: relative;
}

input {
    width: 100%;
    padding: 13px 14px;
    border: 1.5px solid var(--garis);
    border-radius: 10px;
    outline: none;
    font: inherit;
    font-size: 13px;
    color: var(--teks);
    background: #fff;
    transition: .2s;
}

input:focus {
    border-color: var(--utama);
    box-shadow: 0 0 0 4px rgba(75,85,201,.10);
}

.password-input {
    padding-right: 105px;
}

.lihat {
    position: absolute;
    top: 50%;
    right: 12px;
    transform: translateY(-50%);
    border: 0;
    background: transparent;
    color: var(--utama);
    font-family: inherit;
    font-size: 12px;
    font-weight: 500;
    cursor: pointer;
}

.masuk {
    width: 100%;
    border: 0;
    border-radius: 10px;
    padding: 14px;
    background: var(--utama);
    color: white;
    font: inherit;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: .2s;
}

.masuk:hover {
    background: var(--gelap);
    transform: translateY(-1px);
    box-shadow: 0 8px 18px rgba(75,85,201,.22);
}

.err {
    margin-bottom: 20px;
    padding: 12px 14px;
    border-radius: 9px;
    background: #fff0ef;
    color: #a52822;
    border: 1px solid #ffd5d1;
    font-size: 12px;
    line-height: 1.7;
}

.info {
    margin-top: 23px;
    text-align: center;
    color: var(--abu);
    font-size: 11px;
    line-height: 1.8;
}

@media (max-width: 850px) {

    body {
        grid-template-columns: 1fr;
    }

    .kiri {
        min-height: 350px;
        padding: 25px;
    }

    .intro h1 {
        font-size: 32px;
    }

    .intro p {
        font-size: 13px;
    }

    .footer-kiri {
        margin-top: 30px;
    }

    .kanan {
        padding: 35px 20px;
    }

    .mini-logo {
        display: block;
    }
}

@media (max-width: 480px) {

    .kiri {
        min-height: 300px;
        padding: 22px;
    }

    .logo {
        font-size: 20px;
    }

    .logo-icon {
        width: 40px;
        height: 40px;
    }

    .intro h1 {
        font-size: 27px;
    }

    .fitur {
        gap: 6px;
        margin-top: 18px;
    }

    .fitur span {
        font-size: 10px;
        padding: 7px 9px;
    }

    .form-card {
        padding: 23px 18px;
    }

    .login-box h2 {
        font-size: 26px;
    }
}

</style>

</head>

<body>

<!-- PANEL KIRI: BACKGROUND FOTO CAFE -->

<section class="kiri"
style="
background-image:
linear-gradient(
    180deg,
    rgba(36,42,105,0.45),
    rgba(30,35,90,0.68)
),
url('gambar/cafe.jpg');
">

    <div class="logo">
        <div class="logo-icon">☕</div>
        <span>POS CAFE</span>
    </div>

    <div class="intro">

        <span class="label">
            CAFE MANAGEMENT SYSTEM
        </span>

        <h1>Selamat Datang di Cafe Santuy.</h1>

        <p>
            Kelola pesanan pelanggan dengan lebih mudah.
            Catat transaksi, terima pembayaran, dan pantau
            pendapatan cafe melalui satu sistem kasir.
        </p>

        <div class="fitur">
            <span>☕ Kelola Menu</span>
            <span>🛒 Transaksi</span>
            <span>📊 Laporan</span>
        </div>

    </div>

    <div class="footer-kiri">
        © <?= date("Y") ?> POS Cafe Santuy · Sungai Raya, Kalimantan Barat
    </div>

</section>

<!-- PANEL KANAN: LOGIN -->

<section class="kanan">

    <div class="login-box">

        <div class="mini-logo">
            ☕ POS CAFE
        </div>

        <h2>Masuk ke akun</h2>

        <p class="sub">
            Silakan masuk menggunakan akun yang telah terdaftar
            untuk mengakses dashboard POS Cafe.
        </p>

        <form method="POST" class="form-card">

            <h3>Login Pengguna</h3>

            <?php if ($error !== ""): ?>
                <div class="err" role="alert">
                    <?= h($error) ?>
                </div>
            <?php endif; ?>

            <div class="f">

                <label for="username">Username</label>

                <input
                    id="username"
                    type="text"
                    name="username"
                    placeholder="Masukkan username"
                    required
                    autofocus
                    autocomplete="username"
                    value="<?= h($_POST["username"] ?? "") ?>"
                >

            </div>

            <div class="f">

                <label for="password">Password</label>

                <div class="input-wrap">

                    <input
                        class="password-input"
                        id="password"
                        type="password"
                        name="password"
                        placeholder="Masukkan password"
                        required
                        autocomplete="current-password"
                    >

                    <button
                        class="lihat"
                        type="button"
                        id="togglePassword"
                    >
                        Lihat
                    </button>

                </div>

            </div>

            <button class="masuk" type="submit">
                Masuk ke Dashboard &nbsp; →
            </button>

        </form>

        <div class="info">
            Sistem Point of Sale (POS) Cafe Santuy<br>
            Akses hanya untuk pengguna yang terdaftar.
        </div>

    </div>

</section>

<script>

const password = document.getElementById("password");
const toggle = document.getElementById("togglePassword");

toggle.addEventListener("click", function () {

    const tampil = password.type === "password";

    password.type = tampil ? "text" : "password";

    toggle.textContent = tampil
        ? "Sembunyikan"
        : "Lihat";

});

</script>

</body>
</html>
