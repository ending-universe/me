<?php
session_start();
include "yetki_kontrol.php";

// Yetki kontrolü - tüm kullanıcılar panel'e erişebilir
yetki_kontrol('kullanici');

include "db.php";

// Kullanıcı ID al
$kullanici_adi = $_SESSION["kullanici"];
$result = $conn->query("SELECT id, yetki FROM kullanicilar WHERE kullanici_adi='$kullanici_adi'");
$user_data = $result->fetch_assoc();
$kullanici_id = $user_data["id"];
$kullanici_yetki = $user_data["yetki"];

// 🔴 Tüm farklı tarihleri çek
$tarih_sorgu = $conn->query("SELECT DISTINCT tarih FROM veriler WHERE kullanici_id='$kullanici_id' ORDER BY tarih DESC");

// 🔴 İl İlçe verilerini veritabanından çek
$il_ilce_result = $conn->query("SELECT * FROM il_ilce_limitler ORDER BY il ASC, ilce ASC");
$ilceler = array();
$limitler = array();

while($row = $il_ilce_result->fetch_assoc()) {
  $il = $row['il'];
  $ilce = $row['ilce'];
  $limit = $row['limit_degeri'];
  
  if (!isset($ilceler[$il])) {
    $ilceler[$il] = array();
  }
  $ilceler[$il][] = $ilce;
  $limitler[$ilce] = $limit;
}

// Silme işlemi
if (isset($_GET["sil"])) {
  $sil_id = $_GET["sil"];
  $conn->query("DELETE FROM veriler WHERE id='$sil_id' AND kullanici_id='$kullanici_id'");
  
  // Tarih filtresi varsa koruyarak yönlendir
  $redirect_url = "panel.php";
  if (isset($_GET['tarih_filtre']) && $_GET['tarih_filtre'] != '') {
    $redirect_url .= "?tarih_filtre=" . urlencode($_GET['tarih_filtre']);
  }
  header("Location: $redirect_url");
  exit;
}

// Güncelleme işlemi
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $il = $_POST["il"];
  $ilce = $_POST["ilce"];
  $adet = $_POST["adet"];
  $limit = $_POST["limit"];
  $durum = $_POST["durum"];
  $tarih = date("Y-m-d");
  $saat = date("H:i:s");

  if (isset($_POST["guncelle_id"]) && $_POST["guncelle_id"] != "") {
    // Güncelleme
    $id = $_POST["guncelle_id"];
    $sql = "UPDATE veriler SET il='$il', ilce='$ilce', adet='$adet', durum='$durum', limit_degeri='$limit'
            WHERE id='$id' AND kullanici_id='$kullanici_id'";
    $conn->query($sql);
    
    // Tarih filtresi varsa koruyarak yönlendir
    $redirect_url = "panel.php";
    if (isset($_GET['tarih_filtre']) && $_GET['tarih_filtre'] != '') {
      $redirect_url .= "?tarih_filtre=" . urlencode($_GET['tarih_filtre']);
    }
    header("Location: $redirect_url");
  } else {
    // Yeni ekleme
    $sql = "INSERT INTO veriler (kullanici_id, tarih, il, ilce, adet, saat, durum, limit_degeri)
            VALUES ('$kullanici_id', '$tarih', '$il', '$ilce', '$adet', '$saat', '$durum', '$limit')";
    $conn->query($sql);
    
    // Beni hatırla parametrelerini oluştur
    $redirect_params = array();
    $redirect_params[] = "keep_il=" . urlencode($il);
    $redirect_params[] = "keep_ilce=" . urlencode($ilce);
    
    // Eğer beni_hatirla POST'ta varsa, adet değerini de koru
    if (isset($_POST["beni_hatirla"]) && $_POST["beni_hatirla"] == "1") {
      $redirect_params[] = "keep_adet=" . urlencode($adet);
      $redirect_params[] = "beni_hatirla=1";
    }
    
    $redirect_url = "panel.php?" . implode("&", $redirect_params);
    header("Location: $redirect_url");
  }
  exit;
}

// Düzenleme için veri çek
$duzenle = null;
if (isset($_GET["duzenle"])) {
  $duzenle_id = $_GET["duzenle"];
  $duzenle_result = $conn->query("SELECT * FROM veriler WHERE id='$duzenle_id' AND kullanici_id='$kullanici_id'");
  if ($duzenle_result && $duzenle_result->num_rows > 0) {
    $duzenle = $duzenle_result->fetch_assoc();
  }
}

// Seçimleri korumak için URL parametrelerini al
$keep_il = isset($_GET["keep_il"]) ? $_GET["keep_il"] : "";
$keep_ilce = isset($_GET["keep_ilce"]) ? $_GET["keep_ilce"] : "";
$keep_adet = isset($_GET["keep_adet"]) ? $_GET["keep_adet"] : "";
$beni_hatirla = isset($_GET["beni_hatirla"]) ? $_GET["beni_hatirla"] : "";

// Hangi ilin seçili olduğunu belirle
$secili_il = "";
if ($duzenle) {
  $secili_il = $duzenle['il'];
} else if ($keep_il) {
  $secili_il = $keep_il;
}

// 🔴 Verileri seçilen tarihe göre filtrele
$tarih_bugun = date("Y-m-d"); // bugünün tarihi

if (isset($_GET["tarih_filtre"]) && $_GET["tarih_filtre"] != "") {
  $secili_tarih = $_GET["tarih_filtre"];
  $veriler = $conn->query("SELECT * FROM veriler WHERE kullanici_id='$kullanici_id' AND tarih='$secili_tarih' ORDER BY id ASC");
} else {
  // Eğer tarih filtresi yoksa, sadece bugünün verilerini göster
  $veriler = $conn->query("SELECT * FROM veriler WHERE kullanici_id='$kullanici_id' AND tarih='$tarih_bugun' ORDER BY id ASC");
}

// Zaman farkı hesaplama fonksiyonu (ardışık kayıtlar arası)
function zamanFarkiHesapla($onceki_tarih, $onceki_saat, $su_anki_tarih, $su_anki_saat) {
  $onceki_zaman = strtotime($onceki_tarih . ' ' . $onceki_saat);
  $su_anki_zaman = strtotime($su_anki_tarih . ' ' . $su_anki_saat);
  
  $fark_saniye = $su_anki_zaman - $onceki_zaman;
  
  if ($fark_saniye < 0) {
    return "Geçersiz";
  }
  
  if ($fark_saniye == 0) {
    return "0 sn";
  }
  
  $dakika = floor($fark_saniye / 60);
  $saniye = $fark_saniye % 60;
  
  if ($dakika > 0) {
    return $dakika . " dk " . $saniye . " sn";
  } else {
    return $saniye . " sn";
  }
}?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8" />
  <title>Panel | İl İlçe Adet</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<style>
  body {
    margin: 0;
    padding: 0;
    overflow-x: hidden;
  }
</style>
</head>
<body>
<div class="container-fluid mt-4 px-3">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Hoşgeldin <?php echo $_SESSION["kullanici"]; ?> 
        <small class="text-muted">(<?php echo ucfirst($kullanici_yetki); ?>)</small>
    </h2>
    <div>
      <!-- Kullanıcı verileri butonu - sadece yetkili ve admin görebilir -->
      <?php if ($kullanici_yetki == 'yetkili' || $kullanici_yetki == 'admin') { ?>
        <a href="kullanici_verileri.php" class="btn btn-success me-2">Kullanıcı Verileri</a>
      <?php } ?>
      
      <!-- İl İlçe yönetimi butonu - sadece admin görebilir -->
      <?php if ($kullanici_yetki == 'admin') { ?>
        <a href="il_ilce_yonetim.php" class="btn btn-info me-2">İl İlçe Yönetimi</a>
      <?php } ?>
      
      <a href="logout.php" class="btn btn-secondary">Çıkış Yap</a>
    </div>
  </div>

  <!-- 🔴 İl – İlçe – Adet ekleme formu -->
  <form method="post" onsubmit="return prepareForm();" class="mb-4">
    <input type="hidden" name="guncelle_id" value="<?php echo $duzenle ? $duzenle['id'] : ''; ?>">
    <div class="row g-3">
      <div class="col-12">
        <label for="il" class="form-label">İl</label>
        <select id="il" name="il" class="form-select" onchange="ilceGuncelle()" <?php if($duzenle) echo 'disabled'; ?> required>
          <option value="">-- İl Seçiniz --</option>
          <?php foreach($ilceler as $il => $ilce_listesi) { ?>
            <option value="<?php echo $il; ?>" <?php if(($duzenle && $duzenle['il']==$il) || $keep_il==$il) echo 'selected'; ?>><?php echo $il; ?></option>
          <?php } ?>
        </select>
        <?php if($duzenle) { ?>
          <input type="hidden" name="il" value="<?php echo $duzenle['il']; ?>">
        <?php } ?>
      </div>

      <div class="col-12">
        <label for="ilce" class="form-label">İlçe</label>
        <select id="ilce" name="ilce" class="form-select" <?php if($duzenle) echo 'disabled'; ?> required>
          <option value="">-- İlçe Seçiniz --</option>
          <?php 
          if ($duzenle) {
            // Düzenleme modunda sadece seçili ilçeyi göster
            echo '<option value="' . $duzenle['ilce'] . '" selected>' . $duzenle['ilce'] . '</option>';
          } else if ($secili_il && isset($ilceler[$secili_il])) {
            // Seçili ile ait ilçeleri göster
            foreach ($ilceler[$secili_il] as $ilce) {
              $selected = ($keep_ilce == $ilce) ? 'selected' : '';
              echo '<option value="' . $ilce . '" ' . $selected . '>' . $ilce . '</option>';
            }
          }
          ?>
        </select>
        <?php if($duzenle) { ?>
          <input type="hidden" name="ilce" value="<?php echo $duzenle['ilce']; ?>">
        <?php } ?>
      </div>

      <div class="col-12">
        <label for="adet" class="form-label">Adet</label>
        <input type="number" id="adet" name="adet" class="form-control" placeholder="Adet giriniz" value="<?php echo $duzenle ? $duzenle['adet'] : $keep_adet; ?>" required min="0" step="1">
        
        <!-- Beni Hatırla Checkbox -->
        <div class="form-check mt-2">
          <input class="form-check-input" type="checkbox" id="beni_hatirla" name="beni_hatirla" value="1" <?php if($beni_hatirla == "1") echo 'checked'; ?> <?php if($duzenle) echo 'style="display:none;"'; ?>>
          <label class="form-check-label" for="beni_hatirla" <?php if($duzenle) echo 'style="display:none;"'; ?>>
            Beni Hatırla
          </label>
        </div>
      </div>

      <input type="hidden" id="limit" name="limit">
      <input type="hidden" id="durum" name="durum">

      <div class="col-12">
        <button type="submit" class="btn btn-primary" style="width: 65%; padding: 12px 0; font-size: 16px;"><?php echo $duzenle ? "Güncelle" : "Ekle"; ?></button>
      </div>
    </div>
  </form>

  <!-- 🔴 Tarih filtreleme dropdown -->
  <form method="get" class="mb-3">
    <div class="row g-3 align-items-center">
      <div class="col-auto">
        <label for="tarih_filtre" class="col-form-label">Tarih Seç:</label>
      </div>
      <div class="col-auto">
        <select name="tarih_filtre" id="tarih_filtre" class="form-select" onchange="this.form.submit()">
          <option value="">-- Tüm Tarihler --</option>
          <?php while($tarih_row = $tarih_sorgu->fetch_assoc()) { ?>
            <option value="<?php echo $tarih_row['tarih']; ?>" <?php if(isset($_GET['tarih_filtre']) && $_GET['tarih_filtre'] == $tarih_row['tarih']) echo 'selected'; ?>>
              <?php echo $tarih_row['tarih']; ?>
            </option>
          <?php } ?>
        </select>
      </div>
    </div>
  </form>

  <h3>Seçimler Tablosu</h3>
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
        <th>İşlem</th>
      </tr>
    </thead>
    <tbody>
    <?php 
    $kayitlar = array();
    
    // Önce tüm kayıtları array'e al
    while($row = $veriler->fetch_assoc()) {
      $kayitlar[] = $row;
    }
    
    // Kayıtları ID'ye göre ters sırala (yeni kayıtlar üstte görünsün)
    $kayitlar = array_reverse($kayitlar);
    
    foreach($kayitlar as $index => $row) { ?>
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
          if ($index == count($kayitlar) - 1) {
            // Son index (en eski kayıt) = İlk veri
            echo "<span class='text-success fw-bold'>İlk veri</span>";
          } else {
            // Bir önceki kayıtla (kronolojik olarak) zaman farkını hesapla
            $onceki_kayit = $kayitlar[$index + 1];
            echo zamanFarkiHesapla($onceki_kayit["tarih"], $onceki_kayit["saat"], $row["tarih"], $row["saat"]);
          }
          ?>
        </td>
        <td>
          <a href="?duzenle=<?php echo $row['id']; ?><?php if(isset($_GET['tarih_filtre']) && $_GET['tarih_filtre'] != '') echo '&tarih_filtre=' . $_GET['tarih_filtre']; ?>" class="btn btn-sm btn-warning">Düzenle</a>
          <a href="?sil=<?php echo $row['id']; ?><?php if(isset($_GET['tarih_filtre']) && $_GET['tarih_filtre'] != '') echo '&tarih_filtre=' . $_GET['tarih_filtre']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Silmek istediğinize emin misiniz?')">Sil</a>
        </td>
      </tr>
    <?php } ?>
    </tbody>
  </table>
</div>

<script>
// PHP verilerini JavaScript'e aktar
const ilceler = <?php echo json_encode($ilceler); ?>;
const limitler = <?php echo json_encode($limitler); ?>;

function ilceGuncelle() {
  const ilSelect = document.getElementById("il");
  const ilceSelect = document.getElementById("ilce");
  const seciliIl = ilSelect.value;
  
  // İlçe seçeneklerini temizle
  ilceSelect.innerHTML = '<option value="">-- İlçe Seçiniz --</option>';

  if (seciliIl in ilceler) {
    ilceler[seciliIl].forEach(ilce => {
      const option = document.createElement("option");
      option.value = ilce;
      option.text = ilce;
      ilceSelect.appendChild(option);
    });
  }
}

function prepareForm() {
  const il = document.getElementById("il").value;
  const ilce = document.getElementById("ilce").value;
  const adet = document.getElementById("adet").value;

  if (!il || !ilce) {
    alert("İl ve İlçe seçiniz!");
    return false;
  }

  const limit = limitler[ilce];
  document.getElementById("limit").value = limit;

  let durum = "";
  if (parseInt(adet) < limit) {
    durum = "Eksik";
  } else if (parseInt(adet) > limit) {
    durum = "Fazla";
  } else {
    durum = "Tam";
  }
  document.getElementById("durum").value = durum;

  return true;
}


// Sayfa yüklendiğinde çalışacak fonksiyon
document.addEventListener('DOMContentLoaded', function() {
  const ilSelect = document.getElementById("il");
  const ilceSelect = document.getElementById("ilce");
  
  // URL parametrelerini al
  const urlParams = new URLSearchParams(window.location.search);
  const keepIlce = urlParams.get('keep_ilce');
  const keepAdet = urlParams.get('keep_adet');
  const beniHatirla = urlParams.get('beni_hatirla');
  
  // Düzenleme modunda mı kontrol et
  const duzenleMode = document.querySelector('input[name="guncelle_id"]').value !== '';
  
  // Eğer il seçilmişse, ilçeleri yükle
  if (ilSelect.value) {
    // İlçe seçeneklerini temizle ve yeniden doldur
    ilceSelect.innerHTML = '<option value="">-- İlçe Seçiniz --</option>';
    
    const seciliIl = ilSelect.value;
    if (seciliIl in ilceler) {
      ilceler[seciliIl].forEach(ilce => {
        const option = document.createElement("option");
        option.value = ilce;
        option.text = ilce;
        
        // Düzenleme modunda ilçe seçimini koru
        if (duzenleMode) {
          const duzenleIlce = document.querySelector('input[name="ilce"][type="hidden"]');
          if (duzenleIlce && ilce === duzenleIlce.value) {
            option.selected = true;
          }
        } else if (keepIlce && ilce === keepIlce) {
          // Normal modda keep_ilce parametresini koru
          option.selected = true;
        }
        
        ilceSelect.appendChild(option);
      });
    }
  }
  
  // Adet değerini URL'den al ve yerleştir (sadece düzenleme modunda değilse)
  if (!duzenleMode && keepAdet) {
    document.getElementById("adet").value = keepAdet;
  }
  
  // Beni hatırla checkbox'ının durumunu URL'den al
  if (beniHatirla === "1") {
    document.getElementById("beni_hatirla").checked = true;
  }
});
</script>
</body>
</html>