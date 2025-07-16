<?php
session_start();
include "yetki_kontrol.php";

// Yetki kontrolü - sadece yetkili ve admin erişebilir
yetki_kontrol('yetkili');

include "db.php";

// Tüm kullanıcıları çek
$kullanicilar = $conn->query("SELECT id, kullanici_adi FROM kullanicilar");

// Tüm tarihleri çek (distinct)
$tarih_sorgu = $conn->query("SELECT DISTINCT tarih FROM veriler ORDER BY tarih DESC");
$tarih_listesi = [];
while ($tr = $tarih_sorgu->fetch_assoc()) {
  $tarih_listesi[] = $tr["tarih"];
}

// Seçilen kullanıcının verilerini çek
$veriler = null;
$veri_listesi = [];
if (isset($_POST["secili_kullanici"])) {
  $secili_id = $_POST["secili_kullanici"];
  $secili_tarih = isset($_POST["secili_tarih"]) ? $_POST["secili_tarih"] : "";

  $sql = "SELECT * FROM veriler WHERE kullanici_id='$secili_id' ";
  if ($secili_tarih != "") {
    $sql .= "AND tarih='$secili_tarih' ";
  }
  $sql .= "ORDER BY tarih DESC, saat DESC";

  $veriler = $conn->query($sql);

  // Verileri diziye al
  if ($veriler && $veriler->num_rows > 0) {
    while ($row = $veriler->fetch_assoc()) {
      $veri_listesi[] = $row;
    }
  }
}

// Zaman farkı hesaplama fonksiyonu
function zamanFarkiHesapla($onceki_tarih, $onceki_saat, $su_anki_tarih, $su_anki_saat) {
  $onceki_zaman = strtotime($onceki_tarih . ' ' . $onceki_saat);
  $su_anki_zaman = strtotime($su_anki_tarih . ' ' . $su_anki_saat);

  $fark_saniye = $su_anki_zaman - $onceki_zaman;

  if ($fark_saniye < 0) {
    return "Geçersiz";
  }

  $dakika = floor($fark_saniye / 60);
  $saniye = $fark_saniye % 60;

  if ($dakika > 0) {
    return $dakika . " dk " . $saniye . " sn";
  } else {
    return $saniye . " sn";
  }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8" />
  <title>Kullanıcı Verileri</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Kullanıcı Verileri</h2>
    <a href="panel.php" class="btn btn-secondary">Ana Sayfaya Dön</a>
  </div>

  <form method="post" class="mb-4 row g-3">
    <div class="col-md-6">
      <label for="secili_kullanici" class="form-label">Kullanıcı Seç:</label>
      <select id="secili_kullanici" name="secili_kullanici" class="form-select" required>
        <option value="">-- Kullanıcı Seçiniz --</option>
        <?php 
        // Kullanıcı sorgusu yeniden çalıştırılıyor
        $kullanicilar = $conn->query("SELECT id, kullanici_adi FROM kullanicilar");
        while($kullanici = $kullanicilar->fetch_assoc()) { ?>
          <option value="<?php echo $kullanici["id"]; ?>" <?php if(isset($secili_id) && $secili_id==$kullanici["id"]) echo "selected"; ?>>
            <?php echo $kullanici["kullanici_adi"]; ?>
          </option>
        <?php } ?>
      </select>
    </div>

    <div class="col-md-4">
      <label for="secili_tarih" class="form-label">Tarih Seç:</label>
      <select id="secili_tarih" name="secili_tarih" class="form-select">
        <option value="">-- Tüm Tarihler --</option>
        <?php foreach($tarih_listesi as $tarih) { ?>
          <option value="<?php echo $tarih; ?>" <?php if(isset($secili_tarih) && $secili_tarih==$tarih) echo "selected"; ?>>
            <?php echo $tarih; ?>
          </option>
        <?php } ?>
      </select>
    </div>

    <div class="col-md-2 align-self-end">
      <button type="submit" class="btn btn-primary">Göster</button>
    </div>
  </form>

  <?php if(!empty($veri_listesi)) { ?>
    <h3>Seçilen Kullanıcının Verileri</h3>
    <table class="table table-bordered table-striped">
      <thead class="table-dark">
        <tr>
          <th>Tarih</th>
          <th>İl</th>
          <th>İlçe</th>
          <th>Adet</th>
          <th>Saat</th>
          <th>Durum</th>
          <th>Limit</th>
          <th>Zaman Farkı</th>
        </tr>
      </thead>
      <tbody>
        <?php 
        for($i = 0; $i < count($veri_listesi); $i++) { 
          $row = $veri_listesi[$i];
        ?>
          <tr>
            <td><?php echo $row["tarih"]; ?></td>
            <td><?php echo $row["il"]; ?></td>
            <td><?php echo $row["ilce"]; ?></td>
            <td><?php echo $row["adet"]; ?></td>
            <td><?php echo $row["saat"]; ?></td>
            <td><?php echo $row["durum"]; ?></td>
            <td><?php echo $row["limit_degeri"]; ?></td>
            <td>
              <?php
              if ($i == count($veri_listesi) - 1) {
                echo "<span class='text-success fw-bold'>İlk veri</span>";
              } else {
                $sonraki_veri = $veri_listesi[$i + 1]; // Bir sonraki veri (daha eski olan)
                echo zamanFarkiHesapla($sonraki_veri["tarih"], $sonraki_veri["saat"], $row["tarih"], $row["saat"]);
              }
              ?>
            </td>
          </tr>
        <?php } ?>
      </tbody>
    </table>
  <?php } ?>
</div>
</body>
</html>