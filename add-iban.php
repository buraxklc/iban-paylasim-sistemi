<?php
session_start();

// Giriş yapmış kullanıcı kontrolü
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Veritabanı bağlantısı
require_once 'db/config.php';

$success = false;
$error = '';
$public_url = '';

// Kullanılabilir banka logoları
$available_banks = [
    '' => 'Logo Yok',
    'garanti.png' => 'Garanti Bankası',
    'yapikredi.png' => 'Yapı Kredi Bankası',
    // Buraya diğer logoları ekleyebilirsiniz
];

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bank_name = trim($_POST['bank_name']);
    $account_name = trim($_POST['account_name']);
    $iban_number = str_replace(' ', '', trim($_POST['iban_number'])); // Boşlukları temizle
    $description = trim($_POST['description']);
    $bank_logo = isset($_POST['bank_logo']) ? trim($_POST['bank_logo']) : '';
    $user_id = $_SESSION['user_id'];
    
    // Basit doğrulama
    if (empty($bank_name) || empty($account_name) || empty($iban_number)) {
        $error = "Banka adı, hesap sahibi ve IBAN numarası gereklidir.";
    } else {
        try {
            // IBAN kaydet - 'slug' sütunu olmadan
            $stmt = $db->prepare("INSERT INTO ibans (user_id, bank_name, account_name, iban_number, description, bank_logo) 
                                  VALUES (:user_id, :bank_name, :account_name, :iban_number, :description, :bank_logo)");
            
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':bank_name', $bank_name);
            $stmt->bindParam(':account_name', $account_name);
            $stmt->bindParam(':iban_number', $iban_number);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':bank_logo', $bank_logo);
            
            $stmt->execute();
            $success = true;
            
            // Genel URL oluştur
            $server_name = $_SERVER['SERVER_NAME'];
            $path = dirname($_SERVER['PHP_SELF']);
            $public_url = "http://$server_name$path/public-view.php?id=$user_id";
        } catch (PDOException $e) {
            $error = "IBAN eklenirken bir hata oluştu: " . $e->getMessage();
        }
    }
}

// Header ekleniyor
include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h2><i class="fas fa-plus-circle"></i> IBAN Ekle</h2>
        <p class="text-muted">Yeni bir IBAN bilgisi ekleyin.</p>
    </div>
</div>

<?php if ($success): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> IBAN başarıyla eklendi!
        <p class="mt-2 mb-0">
            <strong>Genel Erişim URL'i:</strong>
            <a href="public-view.php?id=<?php echo $user_id; ?>" target="_blank">
                <?php echo "http://" . $_SERVER['SERVER_NAME'] . dirname($_SERVER['PHP_SELF']) . "/public-view.php?id=" . $user_id; ?>
            </a>
            <button class="btn btn-sm btn-outline-secondary copy-btn ms-2" 
                    data-copy="<?php echo "http://" . $_SERVER['SERVER_NAME'] . dirname($_SERVER['PHP_SELF']) . "/public-view.php?id=" . $user_id; ?>">
                <i class="fas fa-copy"></i> Kopyala
            </button>
        </p>
        <p class="mt-1 text-muted small">
            <i class="fas fa-info-circle"></i> Bu link, tüm aktif IBAN bilgilerinizi içerir.
        </p>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="post" action="add-iban.php" class="needs-validation" novalidate>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="bank_name" class="form-label">Banka Adı</label>
                    <input type="text" class="form-control" id="bank_name" name="bank_name" required>
                    <div class="invalid-feedback">
                        Lütfen banka adını girin.
                    </div>
                </div>
                <div class="col-md-6">
                    <label for="account_name" class="form-label">Hesap Sahibi</label>
                    <input type="text" class="form-control" id="account_name" name="account_name" required>
                    <div class="invalid-feedback">
                        Lütfen hesap sahibini girin.
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="bank_logo" class="form-label">Banka Logosu (İsteğe Bağlı)</label>
                <div class="input-group">
                    <select class="form-select" id="bank_logo" name="bank_logo">
                        <?php foreach ($available_banks as $logo => $bank): ?>
                            <option value="<?php echo $logo; ?>"><?php echo $bank; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <span class="input-group-text bank-logo-preview" id="logoPreview">
                        <img src="" alt="Logo önizleme" style="height: 30px; display: none;" id="logoImage">
                        <span id="noLogo">Logo Yok</span>
                    </span>
                </div>
                <div class="form-text text-muted">
                    Banka logosunu seçin. Eğer listelenmiyorsa "Logo Yok" seçeneğini bırakın.
                </div>
            </div>
            
            <div class="mb-3">
                <label for="iban_number" class="form-label">IBAN Numarası</label>
                <input type="text" class="form-control iban-input" id="iban_number" name="iban_number" 
                       placeholder="TR00 0000 0000 0000 0000 0000 00" maxlength="32" required>
                <div class="invalid-feedback">
                    Lütfen geçerli bir IBAN numarası girin.
                </div>
                <div class="form-text text-muted">
                    IBAN numarasını boşluksuz veya boşluklu girebilirsiniz.
                </div>
            </div>
            
            <div class="mb-3">
                <label for="description" class="form-label">Açıklama (İsteğe Bağlı)</label>
                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
            </div>
            
            <div class="d-flex justify-content-end">
                <a href="manage-ibans.php" class="btn btn-secondary me-2">
                    <i class="fas fa-times"></i> İptal
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> IBAN Ekle
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Logo önizleme
    const bankLogoSelect = document.getElementById('bank_logo');
    const logoImage = document.getElementById('logoImage');
    const noLogo = document.getElementById('noLogo');
    
    bankLogoSelect.addEventListener('change', function() {
        if (this.value) {
            logoImage.src = this.value;
            logoImage.style.display = 'block';
            noLogo.style.display = 'none';
        } else {
            logoImage.style.display = 'none';
            noLogo.style.display = 'block';
        }
    });
});
</script>

<?php
// Footer ekleniyor
include 'includes/footer.php';
?>