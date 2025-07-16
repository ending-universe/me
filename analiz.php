<?php
session_start();
include "db.php";

if (!isset($_SESSION["kullanici"])) {
  header("Location: login.php");
  exit;
}

$kullanici_adi = $_SESSION["kullanici"];
$result = $conn->query("SELECT id, yetki FROM kullanicilar WHERE kullanici_adi='$kullanici_adi'");
$user_row = $result->fetch_assoc();
$kullanici_id = $user_row["id"];
$kullanici_yetki = $user_row["yetki"];

$il_ilce_result = $conn->query("SELECT * FROM il_ilce_limitler ORDER BY il ASC, ilce ASC");
$ilceler = [];
$limitler = [];
while($row = $il_ilce_result->fetch_assoc()){
  $il = $row["il"];
  $ilce = $row["ilce"];
  $limit = $row["limit_degeri"];
  if(!isset($ilceler[$il])){
    $ilceler[$il] = [];
  }
  $ilceler[$il][] = $ilce;
  $limitler[$ilce] = $limit;
}

// Kullanıcı listesi
$kullanicilar = [];
if ($kullanici_yetki == "admin" || $kullanici_yetki == "yetkili") {
  $kullanici_sorgu = $conn->query("SELECT id, kullanici_adi FROM kullanicilar ORDER BY kullanici_adi ASC");
  while($row = $kullanici_sorgu->fetch_assoc()){
    $kullanicilar[] = $row;
  }
}

// Tüm farklı tarihleri çek (dropdown için)
$tarih_sorgu = $conn->query("SELECT DISTINCT tarih FROM veriler ORDER BY tarih DESC");
$tarih_listesi = [];
while ($tr = $tarih_sorgu->fetch_assoc()) {
  $tarih_listesi[] = $tr["tarih"];
}

// Form gönderimi
$chart_labels = [];
$chart_data = [];
$chart_colors = [];
$limit_degeri = 0;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $secili_kullanici = $_POST["kullanici_id"];
  $il = $_POST["il"];
  $ilce = $_POST["ilce"];
  $secili_tarih = $_POST["tarih"];

  $limit_degeri = isset($limitler[$ilce]) ? $limitler[$ilce] : 0;

  // Verileri çek (sadece seçilen tarih)
  $veri_sorgu = $conn->query("SELECT * FROM veriler WHERE kullanici_id='$secili_kullanici' AND il='$il' AND ilce='$ilce' AND tarih='$secili_tarih' ORDER BY saat ASC");

  // Saatlik grupla
  $saat_adet = [];
  while($veri = $veri_sorgu->fetch_assoc()){
    $saat = substr($veri["saat"], 0, 2).":00";
    if(!isset($saat_adet[$saat])){
      $saat_adet[$saat] = 0;
    }
    $saat_adet[$saat] += $veri["adet"];
  }

  // Verileri Chart.js için hazırla
  foreach($saat_adet as $saat => $adet){
    $chart_labels[] = $saat;
    $chart_data[] = $adet;

    // 🔴 Renk belirleme
    if($adet < $limit_degeri){
      $chart_colors[] = 'rgba(255, 99, 132, 0.8)'; // kırmızı - limit altında
    } elseif($adet == $limit_degeri){
      $chart_colors[] = 'rgba(201, 203, 207, 0.8)'; // gri - limit eşit
    } else {
      $chart_colors[] = 'rgba(34, 197, 94, 0.8)'; // yeşil - limit üstü
    }
  }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8" />
  <title>Saatlik Grafik Analiz</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="container mt-4">
  <h2>Saatlik Bazda Grafik</h2>

  <form method="post" class="row g-3">
    <?php if ($kullanici_yetki == "admin" || $kullanici_yetki == "yetkili") { ?>
    <div class="col-md-3">
      <label for="kullanici_id" class="form-label">Kullanıcı Seç:</label>
      <select name="kullanici_id" id="kullanici_id" class="form-select" required>
        <option value="">-- Kullanıcı Seçiniz --</option>
        <?php foreach($kullanicilar as $kul){ ?>
          <option value="<?php echo $kul['id']; ?>" <?php if(isset($_POST['kullanici_id']) && $_POST['kullanici_id']==$kul['id']) echo "selected"; ?>>
            <?php echo $kul['kullanici_adi']; ?>
          </option>
        <?php } ?>
      </select>
    </div>
    <?php } else { ?>
      <input type="hidden" name="kullanici_id" value="<?php echo $kullanici_id; ?>">
    <?php } ?>

    <div class="col-md-3">
      <label for="il" class="form-label">İl Seç:</label>
      <select name="il" id="il" class="form-select" required>
        <option value="">-- İl Seçiniz --</option>
        <?php foreach($ilceler as $il => $ilce_listesi){ ?>
          <option value="<?php echo $il; ?>" <?php if(isset($_POST["il"]) && $_POST["il"]==$il) echo "selected"; ?>><?php echo $il; ?></option>
        <?php } ?>
      </select>
    </div>

    <div class="col-md-3">
      <label for="ilce" class="form-label">İlçe Seç:</label>
      <select name="ilce" id="ilce" class="form-select" required>
        <option value="">-- İlçe Seçiniz --</option>
      </select>
    </div>

    <div class="col-md-3">
      <label for="tarih" class="form-label">Tarih Seç:</label>
      <select name="tarih" id="tarih" class="form-select" required>
        <option value="">-- Tarih Seçiniz --</option>
        <?php foreach($tarih_listesi as $tarih){ ?>
          <option value="<?php echo $tarih; ?>" <?php if(isset($_POST["tarih"]) && $_POST["tarih"]==$tarih) echo "selected"; ?>><?php echo $tarih; ?></option>
        <?php } ?>
      </select>
    </div>

    <div class="col-12">
      <button type="submit" class="btn btn-primary">Göster</button>
    </div>
  </form>

  <?php if ($_SERVER["REQUEST_METHOD"] == "POST") { ?>
  <div class="mt-4">
    <div class="row">
      <div class="col-md-8">
        <canvas id="myChart" width="400" height="150"></canvas>
      </div>
      <div class="col-md-4">
        <div class="card">
          <div class="card-body">
            <h5 class="card-title">Limit Bilgisi</h5>
            <p class="card-text">
              <strong>Seçilen İlçe:</strong> <?php echo htmlspecialchars($ilce); ?><br>
              <strong>Saatlik Limit:</strong> <?php echo $limit_degeri; ?> adet
            </p>
            <div class="mt-3">
              <small class="text-muted">Renk Açıklaması:</small><br>
              <span class="badge" style="background-color: rgba(255, 99, 132, 0.8); color: white;">Limit Altı</span>
              <span class="badge" style="background-color: rgba(201, 203, 207, 0.8); color: white;">Limit Eşit</span>
              <span class="badge" style="background-color: rgba(34, 197, 94, 0.8); color: white;">Limit Üstü</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
  const ctx = document.getElementById('myChart').getContext('2d');
  const limitDegeri = <?php echo $limit_degeri; ?>;
  
  // Debug için renkleri kontrol et
  const chartColors = <?php echo json_encode($chart_colors); ?>;
  const chartData = <?php echo json_encode($chart_data); ?>;
  
  console.log('Chart Colors:', chartColors);
  console.log('Chart Data:', chartData);
  console.log('Limit Değeri:', limitDegeri);
  
  const myChart = new Chart(ctx, {
      type: 'bar',
      data: {
          labels: <?php echo json_encode($chart_labels); ?>,
          datasets: [{
              label: 'Saatlik Toplam Adet',
              data: chartData,
              backgroundColor: chartColors,
              borderColor: chartColors.map(color => color.replace('0.6', '1')),
              borderWidth: 2
          }, {
              label: 'Limit Çizgisi',
              type: 'line',
              data: new Array(<?php echo count($chart_labels); ?>).fill(limitDegeri),
              borderColor: 'rgba(255, 206, 86, 1)',
              backgroundColor: 'rgba(255, 206, 86, 0.2)',
              borderWidth: 3,
              fill: false,
              pointRadius: 0,
              pointHoverRadius: 0
          }]
      },
      options: {
          responsive: true,
          scales: {
              y: { 
                  beginAtZero: true,
                  ticks: {
                      stepSize: Math.max(1, Math.ceil(limitDegeri / 10))
                  }
              }
          },
          plugins: {
              legend: {
                  display: true,
                  position: 'top'
              },
              tooltip: {
                  callbacks: {
                      afterLabel: function(context) {
                          if (context.datasetIndex === 0) {
                              const value = context.parsed.y;
                              const limit = limitDegeri;
                              if (value < limit) {
                                  return `Limit altında (${limit - value} eksik)`;
                              } else if (value === limit) {
                                  return 'Limit eşit';
                              } else {
                                  return `Limit üstü (+${value - limit})`;
                              }
                          }
                          return '';
                      }
                  }
              }
          }
      }
  });
  </script>
  <?php } ?>
</div>

<script>
// İl - İlçe bağlantısı
const ilceler = <?php echo json_encode($ilceler); ?>;
document.getElementById("il").addEventListener("change", function(){
  const il = this.value;
  const ilceSelect = document.getElementById("ilce");
  ilceSelect.innerHTML = '<option value="">-- İlçe Seçiniz --</option>';

  if(il && ilceler[il]){
    ilceler[il].forEach(function(ilce){
      const opt = document.createElement("option");
      opt.value = ilce;
      opt.textContent = ilce;
      ilceSelect.appendChild(opt);
    });
  }
});

// Sayfa yüklenince ilçe seçimini koru
window.addEventListener("load", function(){
  const il = document.getElementById("il").value;
  const ilceSecili = "<?php echo isset($_POST['ilce']) ? $_POST['ilce'] : ''; ?>";
  if(il && ilceler[il]){
    const ilceSelect = document.getElementById("ilce");
    ilceler[il].forEach(function(ilce){
      const opt = document.createElement("option");
      opt.value = ilce;
      opt.textContent = ilce;
      if(ilce == ilceSecili){
        opt.selected = true;
      }
      ilceSelect.appendChild(opt);
    });
  }
});
</script>

</body>
</html>