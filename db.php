<?php
$host = "localhost";
$kullanici = "root";
$sifre = "";
$veritabani = "proje_db";

$conn = new mysqli($host, $kullanici, $sifre, $veritabani);
if ($conn->connect_error) {
  die("Bağlantı hatası: " . $conn->connect_error);
}
?>