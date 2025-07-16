<?php
include "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $kullanici = trim($_POST["kullanici"]);
    $sifre = $_POST["sifre"];

    // Kullanıcı adı veya şifre boş mu kontrol et
    if (empty($kullanici) || empty($sifre)) {
        $error = "Kullanıcı adı ve şifre boş bırakılamaz.";
    } else {
        // Kullanıcı adı daha önce alınmış mı kontrol et
        $check_stmt = $conn->prepare("SELECT id FROM kullanicilar WHERE kullanici_adi = ?");
        $check_stmt->bind_param("s", $kullanici);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows > 0) {
            $error = "Bu kullanıcı adı zaten alınmış. Lütfen başka bir kullanıcı adı deneyin.";
        } else {
            // Şifreyi hashlemeden direkt kaydet
            $insert_stmt = $conn->prepare("INSERT INTO kullanicilar (kullanici_adi, sifre) VALUES (?, ?)");
            $insert_stmt->bind_param("ss", $kullanici, $sifre);

            if ($insert_stmt->execute()) {
                $success = "Kayıt başarılı. <a href='login.php' class='alert-link'>Giriş yap</a>";
            } else {
                $error = "Kayıt sırasında hata oluştu: " . $conn->error;
            }

            $insert_stmt->close();
        }

        $check_stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Kayıt Ol</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .register-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            background-color: white;
        }
        .form-title {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="register-container">
        <h2 class="form-title">Kayıt Ol</h2>

        <?php if(isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if(isset($error)): ?>
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
                <button type="submit" class="btn btn-primary">Kayıt Ol</button>
            </div>
            <div class="mt-3 text-center">
                Zaten hesabınız var mı? <a href="login.php">Giriş yap</a>
            </div>
        </form>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
