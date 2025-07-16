<?php

session_start();
include "yetki_kontrol.php";
yetki_kontrol('admin');
include "db.php";
if (!isset($_SESSION["kullanici"])) {
  header("Location: login.php");
  exit;
}

include "db.php";

// İl-İlçe-Limit tablosunu oluştur (yoksa)
$tablo_olustur = "CREATE TABLE IF NOT EXISTS il_ilce_limitler (
  id INT AUTO_INCREMENT PRIMARY KEY,
  il VARCHAR(100) NOT NULL,
  ilce VARCHAR(100) NOT NULL,
  limit_degeri INT NOT NULL,
  aktif TINYINT(1) DEFAULT 1,
  olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_il_ilce (il, ilce)
)";
$conn->query($tablo_olustur);

// Varsayılan verileri ekle (tablo boşsa)
$kontrol = $conn->query("SELECT COUNT(*) as toplam FROM il_ilce_limitler");
$satir = $kontrol->fetch_assoc();
if ($satir['toplam'] == 0) {
  $varsayilan_veriler = [
    ['İstanbul', 'Kadıköy', 50],
    ['İstanbul', 'Beşiktaş', 30],
    ['İstanbul', 'Üsküdar', 20],
    ['Ankara', 'Çankaya', 40],
    ['Ankara', 'Keçiören', 25],
    ['Ankara', 'Yenimahalle', 35],
    ['İzmir', 'Konak', 45],
    ['İzmir', 'Bornova', 15],
    ['İzmir', 'Karşıyaka', 55]
  ];
  
  foreach ($varsayilan_veriler as $veri) {
    $conn->query("INSERT INTO il_ilce_limitler (il, ilce, limit_degeri) VALUES ('{$veri[0]}', '{$veri[1]}', {$veri[2]})");
  }
}

// İşlemler
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  if (isset($_POST['ekle'])) {
    // Yeni kayıt ekleme
    $il = $_POST['il'];
    $ilce = $_POST['ilce'];
    $limit = $_POST['limit'];
    
    $sql = "INSERT INTO il_ilce_limitler (il, ilce, limit_degeri) VALUES ('$il', '$ilce', '$limit')";
    if ($conn->query($sql)) {
      $mesaj = "Kayıt başarıyla eklendi!";
      $mesaj_tip = "success";
    } else {
      $mesaj = "Hata: " . $conn->error;
      $mesaj_tip = "danger";
    }
  } elseif (isset($_POST['guncelle'])) {
    // Kayıt güncelleme
    $id = $_POST['id'];
    $il = $_POST['il'];
    $ilce = $_POST['ilce'];
    $limit = $_POST['limit'];
    
    $sql = "UPDATE il_ilce_limitler SET il='$il', ilce='$ilce', limit_degeri='$limit' WHERE id='$id'";
    if ($conn->query($sql)) {
      $mesaj = "Kayıt başarıyla güncellendi!";
      $mesaj_tip = "success";
    } else {
      $mesaj = "Hata: " . $conn->error;
      $mesaj_tip = "danger";
    }
  }
}

// Silme işlemi
if (isset($_GET['sil'])) {
  $id = $_GET['sil'];
  if ($conn->query("DELETE FROM il_ilce_limitler WHERE id='$id'")) {
    $mesaj = "Kayıt başarıyla silindi!";
    $mesaj_tip = "success";
  } else {
    $mesaj = "Hata: " . $conn->error;
    $mesaj_tip = "danger";
  }
}

// Düzenleme için veri çek
$duzenle = null;
if (isset($_GET['duzenle'])) {
  $id = $_GET['duzenle'];
  $result = $conn->query("SELECT * FROM il_ilce_limitler WHERE id='$id'");
  if ($result && $result->num_rows > 0) {
    $duzenle = $result->fetch_assoc();
  }
}

// Tüm kayıtları çek
$kayitlar = $conn->query("SELECT * FROM il_ilce_limitler ORDER BY il, ilce");

// Benzersiz illeri çek
$iller = $conn->query("SELECT DISTINCT il FROM il_ilce_limitler ORDER BY il");
?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>İl-İlçe-Limit Yönetimi</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
<div class="container mt-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2>İl-İlçe-Limit Yönetimi</h2>
    <div>
      <a href="panel.php" class="btn btn-secondary me-2">Ana Panele Dön</a>
      <a href="logout.php" class="btn btn-outline-secondary">Çıkış Yap</a>
    </div>
  </div>

  <?php if (isset($mesaj)) { ?>
    <div class="alert alert-<?php echo $mesaj_tip; ?> alert-dismissible fade show" role="alert">
      <?php echo $mesaj; ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php } ?>

  <!-- Ekleme/Güncelleme Formu -->
  <div class="card mb-4">
    <div class="card-header">
      <h5><?php echo $duzenle ? 'Kayıt Düzenle' : 'Yeni Kayıt Ekle'; ?></h5>
    </div>
    <div class="card-body">
      <form method="post">
        <?php if ($duzenle) { ?>
          <input type="hidden" name="id" value="<?php echo $duzenle['id']; ?>">
        <?php } ?>
        
        <div class="row g-3">
          <div class="col-md-4">
            <label for="il" class="form-label">İl</label>
            <input type="text" class="form-control" id="il" name="il" 
                   value="<?php echo $duzenle ? $duzenle['il'] : ''; ?>" 
                   placeholder="İl adını giriniz" required>
          </div>
          
          <div class="col-md-4">
            <label for="ilce" class="form-label">İlçe</label>
            <input type="text" class="form-control" id="ilce" name="ilce" 
                   value="<?php echo $duzenle ? $duzenle['ilce'] : ''; ?>" 
                   placeholder="İlçe adını giriniz" required>
          </div>
          
          <div class="col-md-4">
            <label for="limit" class="form-label">Limit Değeri</label>
            <input type="number" class="form-control" id="limit" name="limit" 
                   value="<?php echo $duzenle ? $duzenle['limit_degeri'] : ''; ?>" 
                   placeholder="Limit değerini giriniz" required min="1">
          </div>
        </div>
        
        <div class="mt-3">
          <button type="submit" name="<?php echo $duzenle ? 'guncelle' : 'ekle'; ?>" 
                  class="btn btn-primary">
            <?php echo $duzenle ? 'Güncelle' : 'Ekle'; ?>
          </button>
          <?php if ($duzenle) { ?>
            <a href="?" class="btn btn-secondary">İptal</a>
          <?php } ?>
        </div>
      </form>
    </div>
  </div>

  <!-- Kayıtlar Tablosu -->
  <div class="card">
    <div class="card-header">
      <h5>Mevcut Kayıtlar</h5>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-striped table-hover">
          <thead class="table-dark">
            <tr>
              <th>ID</th>
              <th>İl</th>
              <th>İlçe</th>
              <th>Limit Değeri</th>
              <th>Oluşturma Tarihi</th>
              <th>İşlemler</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($kayitlar && $kayitlar->num_rows > 0) { ?>
              <?php while ($row = $kayitlar->fetch_assoc()) { ?>
                <tr>
                  <td><?php echo $row['id']; ?></td>
                  <td><?php echo $row['il']; ?></td>
                  <td><?php echo $row['ilce']; ?></td>
                  <td><span class="badge bg-primary"><?php echo $row['limit_degeri']; ?></span></td>
                  <td><?php echo date('d.m.Y H:i', strtotime($row['olusturma_tarihi'])); ?></td>
                  <td>
                    <a href="?duzenle=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning">Düzenle</a>
                    <a href="?sil=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger"
                       onclick="return confirm('Bu kaydı silmek istediğinize emin misiniz?')">Sil</a>
                  </td>
                </tr>
              <?php } ?>
            <?php } else { ?>
              <tr>
                <td colspan="6" class="text-center">Henüz kayıt bulunmamaktadır.</td>
              </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- İstatistikler -->
  <div class="row mt-4">
    <div class="col-md-6">
      <div class="card">
        <div class="card-header">
          <h6>İstatistikler</h6>
        </div>
        <div class="card-body">
          <?php
          $toplam = $conn->query("SELECT COUNT(*) as toplam FROM il_ilce_limitler")->fetch_assoc();
          $il_sayisi = $conn->query("SELECT COUNT(DISTINCT il) as il_sayisi FROM il_ilce_limitler")->fetch_assoc();
          $ortalama_limit = $conn->query("SELECT AVG(limit_degeri) as ortalama FROM il_ilce_limitler")->fetch_assoc();
          ?>
          <p><strong>Toplam Kayıt:</strong> <?php echo $toplam['toplam']; ?></p>
          <p><strong>İl Sayısı:</strong> <?php echo $il_sayisi['il_sayisi']; ?></p>
          <p><strong>Ortalama Limit:</strong> <?php echo number_format($ortalama_limit['ortalama'], 1); ?></p>
        </div>
      </div>
    </div>
    
    <div class="col-md-6">
      <div class="card">
        <div class="card-header">
          <h6>İl Bazında Dağılım</h6>
        </div>
        <div class="card-body">
          <?php
          $il_dagilim = $conn->query("SELECT il, COUNT(*) as adet FROM il_ilce_limitler GROUP BY il ORDER BY adet DESC");
          while ($row = $il_dagilim->fetch_assoc()) {
            echo "<p><strong>{$row['il']}:</strong> {$row['adet']} ilçe</p>";
          }
          ?>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
// Form validasyonu
document.addEventListener('DOMContentLoaded', function() {
  const form = document.querySelector('form');
  const ilInput = document.getElementById('il');
  const ilceInput = document.getElementById('ilce');
  
  // İl ve ilçe adlarını büyük harfle başlat
  ilInput.addEventListener('input', function() {
    this.value = this.value.charAt(0).toUpperCase() + this.value.slice(1).toLowerCase();
  });
  
  ilceInput.addEventListener('input', function() {
    this.value = this.value.charAt(0).toUpperCase() + this.value.slice(1).toLowerCase();
  });
  
  // Form gönderme öncesi kontrol
  form.addEventListener('submit', function(e) {
    const limit = document.getElementById('limit').value;
    if (limit <= 0) {
      e.preventDefault();
      alert('Limit değeri 0\'dan büyük olmalıdır!');
    }
  });
});
</script>
</body>
</html>