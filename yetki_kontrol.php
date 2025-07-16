<?php
function yetki_kontrol($izinli_yetkiler = 'yetkili') {
    if (session_status() == PHP_SESSION_NONE) {
        session_start(); // Oturum başlatılmamışsa başlat
    }

    if (!isset($_SESSION["kullanici"])) {
        header("Location: login.php");
        exit;
    }

    include "db.php";

    $kullanici_adi = $_SESSION["kullanici"];
    $result = $conn->query("SELECT yetki FROM kullanicilar WHERE kullanici_adi='$kullanici_adi'");

    if ($result->num_rows == 0) {
        header("Location: login.php");
        exit;
    }

    $user = $result->fetch_assoc();
    $kullanici_yetki = $user['yetki'];

    // Gelen izinli yetkiler dizi değilse, diziye çevir
    if (!is_array($izinli_yetkiler)) {
        $izinli_yetkiler = [$izinli_yetkiler];
    }

    // Yetki seviyesi kontrolü
    $yetki_seviyeleri = ['kullanici' => 1, 'yetkili' => 2, 'admin' => 3];

    // Kullanıcının yetkisi izinli yetkilerden herhangi birine eşit ya da yüksek mi?
    foreach ($izinli_yetkiler as $hedef_yetki) {
        if (isset($yetki_seviyeleri[$kullanici_yetki]) && 
            isset($yetki_seviyeleri[$hedef_yetki]) && 
            $yetki_seviyeleri[$kullanici_yetki] >= $yetki_seviyeleri[$hedef_yetki]) {
            return $kullanici_yetki; // Erişim izni var
        }
    }

    // Erişim yoksa hata mesajı göster
    echo "<!DOCTYPE html>
    <html lang='tr'>
    <head>
        <meta charset='UTF-8'>
        <title>Yetki Hatası</title>
        <link href='https://cdn.jsdelivr.net/npm/bootstrap @5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    </head>
    <body>
        <div class='container mt-5'>
            <div class='alert alert-danger text-center'>
                <h4>Yetki Hatası</h4>
                <p>Bu sayfaya erişim yetkiniz bulunmamaktadır.</p>
                <a href='panel.php' class='btn btn-primary'>Ana Sayfaya Dön</a>
            </div>
        </div>
    </body>
    </html>";
    exit;
}
?>