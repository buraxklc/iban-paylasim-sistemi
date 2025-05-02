<?php
session_start();

// Giriş yapmış kullanıcı kontrolü
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Veritabanı bağlantısı
require_once 'db/config.php';

// İstatistikler
$user_id = $_SESSION['user_id'];

// Kullanıcı bilgilerini al
$stmt = $db->prepare("SELECT * FROM users WHERE id = :user_id");
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$user_info = $stmt->fetch();

// Toplam IBAN sayısı
$stmt = $db->prepare("SELECT COUNT(*) FROM ibans WHERE user_id = :user_id");
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$total_ibans = $stmt->fetchColumn();

// Toplam görüntülenme sayısı
$total_views = 0;
try {
    // iban_views tablosunun varlığını kontrol et
    $stmt = $db->prepare("SHOW TABLES LIKE 'iban_views'");
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $stmt = $db->prepare("
            SELECT COUNT(*) FROM iban_views
            WHERE iban_id IN (SELECT id FROM ibans WHERE user_id = :user_id)
        ");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $total_views = $stmt->fetchColumn();
    } else {
        // Tablo yoksa oluştur
        $db->exec("CREATE TABLE IF NOT EXISTS iban_views (
            id INT AUTO_INCREMENT PRIMARY KEY,
            iban_id INT NOT NULL,
            ip_address VARCHAR(45),
            view_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (iban_id) REFERENCES ibans(id) ON DELETE CASCADE
        )");
    }
} catch (PDOException $e) {
    // Tablo yoksa veya başka bir hata varsa
    $error = $e->getMessage();
}

// Aktif IBAN sayısı
$stmt = $db->prepare("SELECT COUNT(*) FROM ibans WHERE user_id = :user_id AND is_active = 1");
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$active_ibans = $stmt->fetchColumn();

// Son eklenen IBAN'lar
$stmt = $db->prepare("
    SELECT * FROM ibans
    WHERE user_id = :user_id
    ORDER BY created_at DESC
    LIMIT 5
");
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$recent_ibans = $stmt->fetchAll();

// Son görüntülenmeler
$recent_views = [];
try {
    $stmt = $db->prepare("SHOW TABLES LIKE 'iban_views'");
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $stmt = $db->prepare("
            SELECT v.*, i.bank_name, i.iban_number
            FROM iban_views v
            JOIN ibans i ON v.iban_id = i.id
            WHERE i.user_id = :user_id
            ORDER BY v.view_date DESC
            LIMIT 5
        ");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $recent_views = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    // Hata yönetimi
}

// Header ekleniyor
include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><i class="fas fa-tachometer-alt"></i> Kontrol Paneli</h2>
            <p class="text-muted">Hoş geldiniz, <?php echo htmlspecialchars($_SESSION['username']); ?>! IBAN bilgilerinizi yönetin ve istatistiklerinizi görüntüleyin.</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="add-iban.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Yeni IBAN Ekle
            </a>
        </div>
    </div>
    
    <!-- İstatistik Kartları -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Toplam IBAN Sayısı</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_ibans; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-credit-card fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Aktif IBAN'lar</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $active_ibans; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Toplam Görüntülenme</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_views; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-eye fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                IBAN Paylaşım Linki</div>
                            <div class="input-group">
                                <input type="text" class="form-control form-control-sm" value="<?php echo 'http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['PHP_SELF']) . '/public-view.php?id=' . $user_id; ?>" readonly id="shareLink">
                                <button class="btn btn-sm btn-outline-secondary copy-link-btn" type="button">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Son Eklenen IBAN'lar -->
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Son Eklenen IBAN'lar</h6>
                    <a href="manage-ibans.php" class="btn btn-sm btn-primary">
                        Tümünü Görüntüle
                    </a>
                </div>
                <div class="card-body">
                    <?php if (count($recent_ibans) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Durum</th>
                                        <th>Banka</th>
                                        <th>IBAN</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_ibans as $iban): ?>
                                    <tr>
                                        <td>
                                            <?php if ($iban['is_active']): ?>
                                                <span class="badge bg-success">Aktif</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Pasif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($iban['bank_name']); ?></td>
                                        <td>
                                            <span class="d-inline-block text-truncate" style="max-width: 150px;">
                                                <?php echo htmlspecialchars($iban['iban_number']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="edit-iban.php?id=<?php echo $iban['id']; ?>" class="btn btn-sm btn-info">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button class="btn btn-sm btn-outline-secondary copy-btn" 
                                                    data-copy="<?php echo htmlspecialchars($iban['iban_number']); ?>">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center p-4">
                            <p class="text-muted mb-0">Henüz IBAN eklenmemiş.</p>
                            <a href="add-iban.php" class="btn btn-primary mt-3">
                                <i class="fas fa-plus me-2"></i>IBAN Ekle
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Son Görüntülenmeler -->
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Son Görüntülenmeler</h6>
                    <a href="statistics.php" class="btn btn-sm btn-primary">
                        Tüm İstatistikler
                    </a>
                </div>
                <div class="card-body">
                    <?php if (count($recent_views) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Tarih</th>
                                        <th>IP Adresi</th>
                                        <th>IBAN</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_views as $view): ?>
                                    <tr>
                                        <td><?php echo date('d.m.Y H:i', strtotime($view['view_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($view['ip_address']); ?></td>
                                        <td>
                                            <span class="d-inline-block text-truncate" style="max-width: 150px;">
                                                <?php echo htmlspecialchars($view['iban_number']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center p-4">
                            <p class="text-muted mb-0">Henüz görüntülenme kaydı bulunmamaktadır.</p>
                            <p class="text-muted mt-2">IBAN'larınız görüntülendiğinde burada gösterilecektir.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Hızlı Erişim Kartları -->
    <div class="row">
        <!-- IBAN Yönetimi Kartı -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">IBAN Yönetimi</h6>
                </div>
                <div class="card-body">
                    <p>IBAN'larınızı ekleyin, düzenleyin veya silin. Aktif/pasif durumlarını kontrol edin.</p>
                    <a href="manage-ibans.php" class="btn btn-primary btn-block">
                        <i class="fas fa-list me-2"></i>IBAN'ları Yönet
                    </a>
                </div>
            </div>
        </div>
        
        <!-- İstatistikler Kartı -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">İstatistikler</h6>
                </div>
                <div class="card-body">
                    <p>IBAN'larınızın görüntülenme istatistiklerini ve performansını analiz edin.</p>
                    <a href="statistics.php" class="btn btn-primary btn-block">
                        <i class="fas fa-chart-bar me-2"></i>İstatistikleri Görüntüle
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Paylaşım Linki Kartı -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <!-- Paylaşım Linki Kartı -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Paylaşım Linki</h6>
                </div>
                <div class="card-body">
                    <p>IBAN'larınızı tek bir link üzerinden paylaşın. Müşterilerinize kolayca ulaştırın.</p>
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" value="<?php echo 'http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['PHP_SELF']) . '/public-view.php?id=' . $user_id; ?>" readonly id="shareLink2">
                        <button class="btn btn-outline-secondary copy-link-btn" type="button" data-target="shareLink2">
                            <i class="fas fa-copy"></i> Kopyala
                        </button>
                    </div>
                    <a href="<?php echo 'public-view.php?id=' . $user_id; ?>" target="_blank" class="btn btn-primary btn-block">
                        <i class="fas fa-external-link-alt me-2"></i>Önizleme
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // IBAN kopyalama
    const copyButtons = document.querySelectorAll('.copy-btn');
    if (copyButtons.length > 0) {
        copyButtons.forEach(button => {
            button.addEventListener('click', function() {
                const textToCopy = this.getAttribute('data-copy');
                copyToClipboard(textToCopy);
                
                // Kopya bildirimini göster
                this.innerHTML = '<i class="fas fa-check"></i>';
                setTimeout(() => {
                    this.innerHTML = '<i class="fas fa-copy"></i>';
                }, 2000);
            });
        });
    }
    
    // Link kopyalama butonları
    const copyLinkBtns = document.querySelectorAll('.copy-link-btn');
    if (copyLinkBtns.length > 0) {
        copyLinkBtns.forEach(button => {
            button.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const input = targetId ? document.getElementById(targetId) : button.previousElementSibling;
                
                if (input) {
                    copyToClipboard(input.value);
                    
                    // Kopya bildirimini göster
                    const originalHtml = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-check"></i> Kopyalandı';
                    setTimeout(() => {
                        this.innerHTML = originalHtml;
                    }, 2000);
                }
            });
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