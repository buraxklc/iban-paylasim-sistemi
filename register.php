<?php
// Veritabanı bağlantısı
require_once 'db/config.php';

$error = '';
$success = '';

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $company_name = trim($_POST['company_name']);
    $phone = trim($_POST['phone']);
    
    // Boş alan kontrolü
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password) || empty($company_name)) {
        $error = "Lütfen tüm zorunlu alanları doldurun.";
    } elseif ($password !== $confirm_password) {
        $error = "Şifreler eşleşmiyor.";
    } elseif (strlen($password) < 6) {
        $error = "Şifre en az 6 karakter olmalıdır.";
    } else {
        // Kullanıcı adı kontrol et
        $stmt = $db->prepare("SELECT id FROM users WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $error = "Bu kullanıcı adı zaten kullanılıyor. Lütfen başka bir kullanıcı adı seçin.";
        } else {
            // E-posta kontrol et
            $stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $error = "Bu e-posta adresi zaten kullanılıyor. Lütfen başka bir e-posta adresi girin.";
            } else {
                // Kullanıcı oluştur
                try {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $db->prepare("INSERT INTO users (username, password, email, company_name, phone, created_at) 
                                        VALUES (:username, :password, :email, :company_name, :phone, NOW())");
                    
                    $stmt->bindParam(':username', $username);
                    $stmt->bindParam(':password', $hashed_password);
                    $stmt->bindParam(':email', $email);
                    $stmt->bindParam(':company_name', $company_name);
                    $stmt->bindParam(':phone', $phone);
                    
                    $stmt->execute();
                    
                    $success = "Hesabınız başarıyla oluşturuldu! Şimdi <a href='index.php'>giriş</a> yapabilirsiniz.";
                } catch (PDOException $e) {
                    $error = "Kayıt sırasında bir hata oluştu: " . $e->getMessage();
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kayıt Ol - IBAN Paylaşım Sistemi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e9f2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .register-container {
            max-width: 600px;
            width: 100%;
            padding: 2rem;
        }
        
        .card {
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .card-header {
            background: linear-gradient(135deg, #4361ee 0%, #7209b7 100%);
            color: white;
            text-align: center;
            padding: 2rem;
            border-bottom: none;
        }
        
        .card-header h3 {
            margin: 0;
            font-weight: 700;
        }
        
        .card-header p {
            margin-bottom: 0;
            opacity: 0.8;
        }
        
        .card-body {
            padding: 2rem;
        }
        
        .form-label {
            font-weight: 600;
            color: #4a5568;
        }
        
        .form-control {
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            border: 1px solid #e2e8f0;
        }
        
        .form-control:focus {
            border-color: #4361ee;
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #4361ee, #7209b7);
            border: none;
            border-radius: 50px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(67, 97, 238, 0.3);
        }
        
        .form-text {
            color: #718096;
            font-size: 0.85rem;
        }
        
        .required-field::after {
            content: " *";
            color: #e53e3e;
        }
        
        .login-link {
            text-align: center;
            margin-top: 1.5rem;
        }
        
        .login-link a {
            color: #4361ee;
            text-decoration: none;
            font-weight: 600;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php else: ?>
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-user-plus me-2"></i> Hesap Oluştur</h3>
                    <p>IBAN Paylaşım Sistemine Hoş Geldiniz</p>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="post" action="register.php" class="needs-validation" novalidate>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="username" class="form-label required-field">Kullanıcı Adı</label>
                                <input type="text" class="form-control" id="username" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                                <div class="invalid-feedback">Kullanıcı adı gereklidir.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label required-field">E-posta Adresi</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                                <div class="invalid-feedback">Geçerli bir e-posta adresi girin.</div>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="password" class="form-label required-field">Şifre</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <div class="form-text">Şifreniz en az 6 karakter olmalıdır.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="confirm_password" class="form-label required-field">Şifre Tekrar</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                <div class="invalid-feedback">Şifreler eşleşmiyor.</div>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="company_name" class="form-label required-field">Şirket/İşletme Adı</label>
                                <input type="text" class="form-control" id="company_name" name="company_name" value="<?php echo isset($_POST['company_name']) ? htmlspecialchars($_POST['company_name']) : ''; ?>" required>
                                <div class="invalid-feedback">Şirket/İşletme adı gereklidir.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Telefon Numarası</label>
                                <input type="text" class="form-control" id="phone" name="phone" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-user-plus me-2"></i>Kayıt Ol
                            </button>
                        </div>
                    </form>
                    
                    <div class="login-link">
                        Zaten bir hesabınız var mı? <a href="index.php">Giriş Yap</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Form doğrulama
        const form = document.querySelector('.needs-validation');
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');
        
        if (form) {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                
                if (password.value !== confirmPassword.value) {
                    confirmPassword.setCustomValidity('Şifreler eşleşmiyor');
                    event.preventDefault();
                    event.stopPropagation();
                } else {
                    confirmPassword.setCustomValidity('');
                }
                
                form.classList.add('was-validated');
            }, false);
            
            confirmPassword.addEventListener('input', function() {
                if (password.value !== confirmPassword.value) {
                    confirmPassword.setCustomValidity('Şifreler eşleşmiyor');
                } else {
                    confirmPassword.setCustomValidity('');
                }
            });
        }
    });
    </script>
</body>
</html>