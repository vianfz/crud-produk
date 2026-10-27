<?php
$host = getenv('DB_HOST');
$db   = getenv('DB_DATABASE');
$user = getenv('DB_USERNAME');
$pass = getenv('DB_PASSWORD');

try {
    $pdo = new PDO("mysql:host=$host;port=3306;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_SSL_CA => '/etc/ssl/certs/ca-certificates.crt',
    ]);
} catch (PDOException $e) {
    die('Koneksi database gagal: ' . htmlspecialchars($e->getMessage()));
}

$pdo->exec("CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_produk VARCHAR(255) NOT NULL,
    harga_satuan DECIMAL(12,2) NOT NULL,
    deskripsi_produk TEXT NOT NULL
)");

function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $st = $pdo->prepare("INSERT INTO products (nama_produk, harga_satuan, deskripsi_produk) VALUES (?, ?, ?)");
        $st->execute([trim($_POST['nama_produk']), $_POST['harga_satuan'], trim($_POST['deskripsi_produk'])]);
    } elseif ($action === 'update') {
        $st = $pdo->prepare("UPDATE products SET nama_produk=?, harga_satuan=?, deskripsi_produk=? WHERE id=?");
        $st->execute([trim($_POST['nama_produk']), $_POST['harga_satuan'], trim($_POST['deskripsi_produk']), (int)$_POST['id']]);
    } elseif ($action === 'delete') {
        $st = $pdo->prepare("DELETE FROM products WHERE id=?");
        $st->execute([(int)$_POST['id']]);
    }
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

$edit = null;
if (isset($_GET['edit'])) {
    $st = $pdo->prepare("SELECT * FROM products WHERE id=?");
    $st->execute([(int)$_GET['edit']]);
    $edit = $st->fetch(PDO::FETCH_ASSOC);
}
$products = $pdo->query("SELECT * FROM products ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Product Monitor</title>
<style>
  body { font-family: Arial, sans-serif; background: #f3f4f6; margin: 0; padding: 24px; color: #222; }
  .wrap { max-width: 900px; margin: 0 auto; }
  .card { background: #fff; border-radius: 10px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
  h1, h2 { margin-top: 0; }
  input, textarea { width: 100%; padding: 8px; margin: 4px 0 12px; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box; }
  button, .btn { padding: 8px 14px; border: 0; border-radius: 6px; cursor: pointer; color: #fff; background: #2563eb; text-decoration: none; font-size: 14px; display: inline-block; }
  .btn-edit { background: #f59e0b; }
  .btn-del { background: #dc2626; }
  .btn-gray { background: #6b7280; }
  table { width: 100%; border-collapse: collapse; }
  th, td { padding: 10px; border-bottom: 1px solid #e5e7eb; text-align: left; vertical-align: top; }
  form.inline { display: inline; }
</style>
</head>
<body>
<div class="wrap">
  <div class="card">
    <h1>📦 Product Monitor</h1>
    <p>Aplikasi CRUD produk (PHP native + MySQL) di Azure App Service.</p>
  </div>

  <div class="card">
    <h2><?= $edit ? 'Edit Produk' : 'Tambah Produk' ?></h2>
    <form method="post">
      <input type="hidden" name="action" value="<?= $edit ? 'update' : 'create' ?>">
      <?php if ($edit): ?><input type="hidden" name="id" value="<?= e($edit['id']) ?>"><?php endif; ?>
      <label>Nama Produk</label>
      <input type="text" name="nama_produk" required value="<?= e($edit['nama_produk'] ?? '') ?>">
      <label>Harga Satuan (Rp)</label>
      <input type="number" name="harga_satuan" step="0.01" min="0" required value="<?= e($edit['harga_satuan'] ?? '') ?>">
      <label>Deskripsi</label>
      <textarea name="deskripsi_produk" rows="3" required><?= e($edit['deskripsi_produk'] ?? '') ?></textarea>
      <button type="submit"><?= $edit ? 'Simpan Perubahan' : 'Tambah' ?></button>
      <?php if ($edit): ?><a class="btn btn-gray" href="./">Batal</a><?php endif; ?>
    </form>
  </div>

  <div class="card">
    <h2>Daftar Produk (<?= count($products) ?>)</h2>
    <table>
      <tr><th>No</th><th>Nama</th><th>Harga</th><th>Deskripsi</th><th>Aksi</th></tr>
      <?php if (!$products): ?>
        <tr><td colspan="5">Belum ada produk.</td></tr>
      <?php endif; ?>
      <?php foreach ($products as $i => $p): ?>
      <tr>
        <td><?= $i + 1 ?></td>
        <td><?= e($p['nama_produk']) ?></td>
        <td>Rp <?= number_format($p['harga_satuan'], 0, ',', '.') ?></td>
        <td><?= e($p['deskripsi_produk']) ?></td>
        <td>
          <a class="btn btn-edit" href="?edit=<?= e($p['id']) ?>">Edit</a>
          <form class="inline" method="post" onsubmit="return confirm('Hapus produk ini?')">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= e($p['id']) ?>">
            <button type="submit" class="btn-del">Hapus</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>
</div>
</body>
</html>
