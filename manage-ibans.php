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
$message = '';

// IBAN silme işlemi
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $iban_id = $_GET['delete'];
    
    try {
        $stmt = $db->prepare("DELETE FROM ibans WHERE id = :id AND user_id = :user_id");
        $stmt->bindParam(':id', $iban_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $message = '<div class="alert alert-success">IBAN başarıyla silindi!</div>';
        } else {
            $message = '<div class="alert alert-danger">IBAN silinirken bir hata oluştu!</div>';
        }
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Veritabanı hatası: ' . $e->getMessage() . '</div>';
    }
}

// IBAN durumunu değiştirme (aktif/pasif)
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $iban_id = $_GET['toggle'];
    
    try {
        // Önce mevcut durumu al
        $stmt = $db->prepare("SELECT is_active FROM ibans WHERE id = :id AND user_id = :user_id");
        $stmt->bindParam(':id', $iban_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $iban = $stmt->fetch();
            $new_status = $iban['is_active'] ? 0 : 1;
            
            // Durumu güncelle
            $update = $db->prepare("UPDATE ibans SET is_active = :is_active WHERE id = :id AND user_id = :user_id");
            $update->bindParam(':is_active', $new_status);
            $update->bindParam(':id', $iban_id);
            $update->bindParam(':user_id', $user_id);
            $update->execute();
            
            $status_text = $new_status ? 'aktif' : 'pasif';
            $message = '<div class="alert alert-success">IBAN durumu ' . $status_text . ' olarak güncellendi!</div>';
        }
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Veritabanı hatası: ' . $e->getMessage() . '</div>';
    }
}

// IBAN'ları listeleme
try {
    $stmt = $db->prepare("SELECT * FROM ibans WHERE user_id = :user_id ORDER BY created_at DESC");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $ibans = $stmt->fetchAll();

    // Aktif IBAN sayısı
    $active_count = 0;
    foreach ($ibans as $iban) {
        if ($iban['is_active']) {
            $active_count++;
        }
    }
} catch (PDOException $e) {
    $message = '<div class="alert alert-danger">Veritabanı hatası: ' . $e->getMessage() . '</div>';
    $ibans = [];
    $active_count = 0;
}

// Header ekleniyor
include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h2><i class="fas fa-list"></i> IBAN'ları Yönet</h2>
        <p class="text-muted">Kayıtlı IBAN bilgilerinizi görüntüleyin, düzenleyin veya silin.</p>
    </div>
    <div class="col-md-4 text-end">
        <a href="add-iban.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Yeni IBAN Ekle
        </a>
    </div>
</div>

<?php echo $message; ?>

<?php if (count($ibans) > 0): ?>
    <!-- IBAN Paylaşım Linki Kutusu -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-link"></i> IBAN Paylaşım Linkiniz</h5>
        </div>
        <div class="card-body">
            <p>Aktif IBAN'larınızı aşağıdaki link üzerinden paylaşabilirsiniz. Bu link tüm aktif IBAN'larınızı gösterir.</p>
            
            <div class="input-group mb-3">
                <input type="text" class="form-control" value="<?php echo 'http://' . $_SERVER['SERVER_NAME'] . '/iban-paylasim-sistemi/public-view.php?id=' . $user_id; ?>" id="shareLink" readonly>
                <button class="btn btn-outline-primary copy-link-btn" type="button" data-copy="<?php echo 'http://' . $_SERVER['SERVER_NAME'] . '/iban-paylasim-sistemi/public-view.php?id=' . $user_id; ?>">
                    <i class="fas fa-copy"></i> Kopyala
                </button>
                <a href="<?php echo 'public-view.php?id=' . $user_id; ?>" target="_blank" class="btn btn-primary">
                    <i class="fas fa-external-link-alt"></i> Görüntüle
                </a>
            </div>
            
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    <i class="fas fa-info-circle"></i> Şu anda <strong><?php echo $active_count; ?></strong> aktif IBAN'ınız bulunmaktadır.
                </div>
                <?php if ($active_count > 0): ?>
                <div class="d-flex align-items-center">
                    <span class="badge bg-success me-2">
                        <i class="fas fa-check"></i> Link Aktif
                    </span>
                    <span class="text-success">QR kod ile paylaşabilirsiniz</span>
                </div>
                <?php else: ?>
                <div class="d-flex align-items-center">
                    <span class="badge bg-warning me-2">
                        <i class="fas fa-exclamation-triangle"></i> Link Boş
                    </span>
                    <span class="text-warning">Aktif IBAN bulunmamaktadır</span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-credit-card"></i> Kayıtlı IBAN'larınız</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th>Durum</th>
                            <th>Banka</th>
                            <th>Hesap Sahibi</th>
                            <th>IBAN</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ibans as $iban): ?>
                        <tr>
                            <td>
                                <?php if ($iban['is_active']): ?>
                                    <span class="badge bg-success">Aktif</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Pasif</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($iban['bank_logo']) && file_exists($iban['bank_logo'])): ?>
                                    <img src="<?php echo $iban['bank_logo']; ?>" alt="<?php echo htmlspecialchars($iban['bank_name']); ?>" style="height: 20px; margin-right: 5px;">
                                <?php endif; ?>
                                <?php echo htmlspecialchars($iban['bank_name']); ?>
                            </td>
                            <td><?php echo htmlspecialchars($iban['account_name']); ?></td>
                            <td>
                                <span class="d-inline-block text-truncate" style="max-width: 150px;">
                                    <?php echo htmlspecialchars($iban['iban_number']); ?>
                                </span>
                                <button class="btn btn-sm btn-outline-secondary copy-btn" 
                                        data-copy="<?php echo htmlspecialchars($iban['iban_number']); ?>">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="edit-iban.php?id=<?php echo $iban['id']; ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-edit"></i> Düzenle
                                    </a>
                                    <a href="manage-ibans.php?toggle=<?php echo $iban['id']; ?>" 
                                       class="btn btn-sm <?php echo $iban['is_active'] ? 'btn-warning' : 'btn-success'; ?>">
                                        <i class="fas <?php echo $iban['is_active'] ? 'fa-ban' : 'fa-check-circle'; ?>"></i>
                                        <?php echo $iban['is_active'] ? 'Pasif Yap' : 'Aktif Yap'; ?>
                                    </a>
                                    <a href="manage-ibans.php?delete=<?php echo $iban['id']; ?>" 
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('Bu IBAN\'ı silmek istediğinize emin misiniz?');">
                                        <i class="fas fa-trash"></i> Sil
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> Henüz kayıtlı IBAN bulunmamaktadır. <a href="add-iban.php">Yeni bir IBAN ekleyin</a>.
    </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // IBAN kopyalama
    const copyButtons = document.querySelectorAll('.copy-btn');
    if (copyButtons) {
        copyButtons.forEach(button => {
            button.addEventListener('click', function() {
                const textToCopy = this.getAttribute('data-copy');
                copyToClipboard(textToCopy);
                
                // Kopya bildirimini göster
                this.innerHTML = '<i class="fas fa-check"></i> Kopyalandı';
                setTimeout(() => {
                    this.innerHTML = '<i class="fas fa-copy"></i>';
                }, 2000);
            });
        });
    }
    
    // Link kopyalama
    const copyLinkBtn = document.querySelector('.copy-link-btn');
    if (copyLinkBtn) {
        copyLinkBtn.addEventListener('click', function() {
            const textToCopy = this.getAttribute('data-copy');
            copyToClipboard(textToCopy);
            
            // Kopya bildirimini göster
            this.innerHTML = '<i class="fas fa-check"></i> Kopyalandı';
            setTimeout(() => {
                this.innerHTML = '<i class="fas fa-copy"></i> Kopyala';
            }, 2000);
        });
    }
    
    // Kopyalama fonksiyonu
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            console.log('Metin panoya kopyalandı');
        }).catch(err => {
            // Alternatif kopyalama metodu
            const textarea = document.createElement('textarea');
            textarea.value = text;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            console.log('Metin panoya alternatif metod ile kopyalandı');
        });
    }
});
</script>

<?php
// Footer ekleniyor
include 'includes/footer.php';
?>