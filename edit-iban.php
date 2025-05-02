<?php
session_start();

// Giriş yapmış kullanıcı kontrolü
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Veritabanı bağlantısı
require_once 'db/config.php';

$user_id = $_SESSION['user_id'];
$success = false;
$error = '';

// ID parametresi kontrolü
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: manage-ibans.php");
    exit;
}

$iban_id = $_GET['id'];

// IBAN kaydını getirme
$stmt = $db->prepare("SELECT * FROM ibans WHERE id = :id AND user_id = :user_id");
$stmt->bindParam(':id', $iban_id);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();

if ($stmt->rowCount() === 0) {
    header("Location: manage-ibans.php");
    exit;
}

$iban = $stmt->fetch();

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bank_name = trim($_POST['bank_name']);
    $account_name = trim($_POST['account_name']);
    $iban_number = str_replace(' ', '', trim($_POST['iban_number'])); // Boşlukları temizle
    $description = trim($_POST['description']);
    
    // Basit doğrulama
    if (empty($bank_name) || empty($account_name) || empty($iban_number)) {
        $error = "Banka adı, hesap sahibi ve IBAN numarası gereklidir.";
    } else {
        try {
            $stmt = $db->prepare("UPDATE ibans SET 
                                    bank_name = :bank_name, 
                                    account_name = :account_name, 
                                    iban_number = :iban_number, 
                                    description = :description 
                                  WHERE id = :id AND user_id = :user_id");
            
            $stmt->bindParam(':bank_name', $bank_name);
            $stmt->bindParam(':account_name', $account_name);
            $stmt->bindParam(':iban_number', $iban_number);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':id', $iban_id);
            $stmt->bindParam(':user_id', $user_id);
            
            $stmt->execute();
            $success = true;
            
            // Güncellenmiş veriyi al
            $stmt = $db->prepare("SELECT * FROM ibans WHERE id = :id");
            $stmt->bindParam(':id', $iban_id);
            $stmt->execute();
            $iban = $stmt->fetch();
            
        } catch (PDOException $e) {
            $error = "IBAN güncellenirken bir hata oluştu: " . $e->getMessage();
        }
    }
}

// Header ekleniyor
include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h2><i class="fas fa-edit"></i> IBAN Düzenle</h2>
        <p class="text-muted">IBAN bilgilerinizi güncelleyin.</p>
    </div>
</div>

<?php if ($success): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> IBAN başarıyla güncellendi!
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="post" action="edit-iban.php?id=<?php echo $iban_id; ?>" class="needs-validation" novalidate>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="bank_name" class="form-label">Banka Adı</label>
                    <input type="text" class="form-control" id="bank_name" name="bank_name" 
                           value="<?php echo htmlspecialchars($iban['bank_name']); ?>" required>
                    <div class="invalid-feedback">
                        Lütfen banka adını girin.
                    </div>
                </div>
                <div class="col-md-6">
                    <label for="account_name" class="form-label">Hesap Sahibi</label>
                    <input type="text" class="form-control" id="account_name" name="account_name" 
                           value="<?php echo htmlspecialchars($iban['account_name']); ?>" required>
                    <div class="invalid-feedback">
                        Lütfen hesap sahibini girin.
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="iban_number" class="form-label">IBAN Numarası</label>
                <input type="text" class="form-control iban-input" id="iban_number" name="iban_number" 
                       value="<?php echo htmlspecialchars($iban['iban_number']); ?>"
                       maxlength="32" required>
                <div class="invalid-feedback">
                    Lütfen geçerli bir IBAN numarası girin.
                </div>
            </div>
            
            <div class="mb-3">
                <label for="description" class="form-label">Açıklama (İsteğe Bağlı)</label>
                <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($iban['description']); ?></textarea>
            </div>
            
            <div class="d-flex justify-content-end">
                <a href="manage-ibans.php" class="btn btn-secondary me-2">
                    <i class="fas fa-arrow-left"></i> Geri
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Güncelle
                </button>
            </div>
        </form>
    </div>
</div>

<?php
// Footer ekleniyor
include 'includes/footer.php';
?>