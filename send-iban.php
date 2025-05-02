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
$shared_iban = null;

// Kayıtlı IBAN'ları listeleme
$stmt = $db->prepare("SELECT * FROM ibans WHERE user_id = :user_id ORDER BY bank_name");
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$ibans = $stmt->fetchAll();

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $iban_id = $_POST['iban_id'];
    $customer_name = trim($_POST['customer_name']);
    $customer_email = trim($_POST['customer_email']);
    
    // Basit doğrulama
    if (empty($iban_id) || empty($customer_name) || empty($customer_email)) {
        $error = "Tüm alanları doldurunuz.";
    } elseif (!filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
        $error = "Geçerli bir e-posta adresi giriniz.";
    } else {
        // IBAN bilgisinin bu kullanıcıya ait olduğunu doğrula
        $stmt = $db->prepare("SELECT * FROM ibans WHERE id = :id AND user_id = :user_id");
        $stmt->bindParam(':id', $iban_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            $error = "Geçersiz IBAN seçimi.";
        } else {
            $shared_iban = $stmt->fetch();
            
            // Paylaşım kaydını ekle
            try {
                $stmt = $db->prepare("INSERT INTO iban_shares (iban_id, customer_email, customer_name) 
                                       VALUES (:iban_id, :customer_email, :customer_name)");
                
                $stmt->bindParam(':iban_id', $iban_id);
                $stmt->bindParam(':customer_email', $customer_email);
                $stmt->bindParam(':customer_name', $customer_name);
                
                $stmt->execute();
                $success = true;
                
                // E-posta gönderme fonksiyonu burada yer alabilir (bu örnekte dahil edilmedi)
                // sendIbanEmail($customer_email, $customer_name, $shared_iban);
                
            } catch (PDOException $e) {
                $error = "Paylaşım kaydedilirken bir hata oluştu: " . $e->getMessage();
            }
        }
    }
}

// Header ekleniyor
include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h2><i class="fas fa-share-alt"></i> IBAN Paylaş</h2>
        <p class="text-muted">Müşterilerinize IBAN bilgilerinizi paylaşın.</p>
    </div>
</div>

<?php if (count($ibans) === 0): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i> Paylaşılacak IBAN bulunamadı. 
        <a href="add-iban.php">Önce bir IBAN ekleyin</a>.
    </div>
<?php else: ?>
    
    <?php if ($success && $shared_iban): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> IBAN bilgisi başarıyla paylaşıldı!
        </div>
        
        <div class="card mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0">Paylaşılan IBAN Bilgisi</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Müşteri:</strong> <?php echo htmlspecialchars($_POST['customer_name']); ?></p>
                        <p><strong>E-posta:</strong> <?php echo htmlspecialchars($_POST['customer_email']); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Banka:</strong> <?php echo htmlspecialchars($shared_iban['bank_name']); ?></p>
                        <p><strong>Hesap Sahibi:</strong> <?php echo htmlspecialchars($shared_iban['account_name']); ?></p>
                    </div>
                </div>
                
                <div class="iban-display d-flex justify-content-between align-items-center">
                    <span><?php echo htmlspecialchars($shared_iban['iban_number']); ?></span>
                    <button class="btn btn-sm btn-outline-secondary copy-btn" 
                            data-copy="<?php echo htmlspecialchars($shared_iban['iban_number']); ?>">
                        <i class="fas fa-copy"></i> Kopyala
                    </button>
                </div>
                
                <?php if (!empty($shared_iban['description'])): ?>
                    <p class="mt-3"><strong>Açıklama:</strong> <?php echo htmlspecialchars($shared_iban['description']); ?></p>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <form method="post" action="send-iban.php" class="needs-validation" novalidate>
                <div class="mb-3">
                    <label for="iban_id" class="form-label">Paylaşılacak IBAN</label>
                    <select class="form-select" id="iban_id" name="iban_id" required>
                        <option value="">-- IBAN Seçin --</option>
                        <?php foreach ($ibans as $iban): ?>
                            <option value="<?php echo $iban['id']; ?>">
                                <?php echo htmlspecialchars($iban['bank_name'] . ' - ' . $iban['account_name'] . ' (' . $iban['iban_number'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback">
                        Lütfen bir IBAN seçin.
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="customer_name" class="form-label">Müşteri Adı</label>
                        <input type="text" class="form-control" id="customer_name" name="customer_name" required>
                        <div class="invalid-feedback">
                            Lütfen müşteri adını girin.
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="customer_email" class="form-label">Müşteri E-posta</label>
                        <input type="email" class="form-control" id="customer_email" name="customer_email" required>
                        <div class="invalid-feedback">
                            Lütfen geçerli bir e-posta adresi girin.
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> IBAN Paylaş
                    </button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php
// Footer ekleniyor
include 'includes/footer.php';
?>