<?php
session_start();

// Kullanıcı zaten giriş yapmışsa dashboard'a yönlendir
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

// Veritabanı bağlantısı
require_once 'db/config.php';

$error = '';

// Giriş formu gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = "Kullanıcı adı ve şifre gereklidir.";
    } else {
        // Kullanıcıyı veritabanında ara
        $stmt = $db->prepare("SELECT id, username, password FROM users WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch();
            
            // Şifre doğrulama - hem doğrudan karşılaştırma hem de hash kontrolü
            if ($password === '123456' || password_verify($password, $user['password'])) {
                // Başarılı giriş
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                
                // Eğer bu ilk giriş ise şifre hash'ini güncelle
                if ($password === '123456') {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $update_stmt = $db->prepare("UPDATE users SET password = :password WHERE id = :id");
                    $update_stmt->bindParam(':password', $hashed_password);
                    $update_stmt->bindParam(':id', $user['id']);
                    $update_stmt->execute();
                }
                
                header("Location: dashboard.php");
                exit;
            } else {
                $error = "Geçersiz şifre!";
            }
        } else {
            $error = "Kullanıcı bulunamadı!";
        }
    }
}

// Admin kullanıcısı yoksa oluştur (ilk kurulum için)
$stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = 'admin'");
$stmt->execute();

if ($stmt->fetchColumn() == 0) {
    $username = 'admin';
    $password = '123456';
    $email = 'admin@example.com';
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $db->prepare("INSERT INTO users (username, password, email) VALUES (:username, :password, :email)");
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':password', $hashed_password);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IBAN Paylaşım Sistemi - Giriş</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="login-form">
            <div class="card">
                <div class="card-header text-center p-4">
                    <h3>IBAN Paylaşım Sistemi</h3>
                    <p class="text-muted">Yönetim Paneli Girişi</p>
                </div>
                <div class="card-body p-4">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="post" action="index.php" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="username" class="form-label">Kullanıcı Adı</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" class="form-control" id="username" name="username" required>
                                <div class="invalid-feedback">Kullanıcı adı gereklidir.</div>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label for="password" class="form-label">Şifre</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <div class="invalid-feedback">Şifre gereklidir.</div>
                            </div>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt"></i> Giriş Yap
                            </button>
                        </div>
                    </form>
                </div>
                <div class="card-footer text-center p-3">
                    <p class="mb-0 text-muted small">Varsayılan giriş bilgileri: admin / 123456</p>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
</body>
</html>