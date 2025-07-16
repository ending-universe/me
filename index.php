<?php
session_start();
include "db.php";

$error = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $kullanici = $_POST["kullanici"];
    $sifre = $_POST["sifre"];

    $sql = "SELECT * FROM kullanicilar WHERE kullanici_adi='$kullanici' AND sifre='$sifre'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $_SESSION["kullanici"] = $kullanici;
        header("Location: panel.php");
        exit();
    } else {
        $error = "Kullanıcı adı veya şifre hatalı";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .auth-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            background-color: white;
        }
        .auth-title {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="auth-container">
            <h2 class="auth-title">Giriş Yap</h2>
            
            <?php if($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="post">
                <div class="mb-3">
                    <label for="kullanici" class="form-label">Kullanıcı Adı</label>
                    <input type="text" class="form-control" id="kullanici" name="kullanici" required>
                </div>
                <div class="mb-3">
                    <label for="sifre" class="form-label">Şifre</label>
                    <input type="password" class="form-control" id="sifre" name="sifre" required>
                </div>
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">Giriş Yap</button>
                </div>
                <div class="mt-3 text-center">
                    Hesabın yok mu? <a href="kayıt.php">Kayıt ol</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>