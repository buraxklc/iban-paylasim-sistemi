<?php
session_start();

// Giriş kontrolü
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Veritabanı bağlantısı
require_once 'db/config.php';

$user_id = $_SESSION['user_id'];

// Veritabanı tablosunu kontrol et ve yoksa oluştur
try {
    $db->exec("CREATE TABLE IF NOT EXISTS iban_views (
        id INT AUTO_INCREMENT PRIMARY KEY,
        iban_id INT NOT NULL,
        ip_address VARCHAR(45),
        view_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (iban_id) REFERENCES ibans(id) ON DELETE CASCADE
    )");
} catch (PDOException $e) {
    // Hata yönetimi
    $error_message = "Veritabanı hatası: " . $e->getMessage();
}

// Toplam IBAN sayısı
$stmt = $db->prepare("SELECT COUNT(*) FROM ibans WHERE user_id = :user_id");
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$total_ibans = $stmt->fetchColumn();

// Aktif IBAN sayısı
$stmt = $db->prepare("SELECT COUNT(*) FROM ibans WHERE user_id = :user_id AND is_active = 1");
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$active_ibans = $stmt->fetchColumn();

// Pasif IBAN sayısı
$inactive_ibans = $total_ibans - $active_ibans;

// Toplam görüntülenme sayısı
$total_views = 0;
try {
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM iban_views
        WHERE iban_id IN (SELECT id FROM ibans WHERE user_id = :user_id)
    ");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $total_views = $stmt->fetchColumn();
} catch (PDOException $e) {
    // Hata yönetimi
}

// Son 7 gündeki görüntülenme sayısı
$last_7_days_views = 0;
try {
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM iban_views
        WHERE iban_id IN (SELECT id FROM ibans WHERE user_id = :user_id)
        AND view_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $last_7_days_views = $stmt->fetchColumn();
} catch (PDOException $e) {
    // Hata yönetimi
}

// IBAN'ları ve görüntülenme sayılarını al
$iban_stats = [];
try {
    $stmt = $db->prepare("
        SELECT i.id, i.bank_name, i.account_name, i.iban_number, i.is_active,
            (SELECT COUNT(*) FROM iban_views WHERE iban_id = i.id) as view_count,
            (SELECT COUNT(*) FROM iban_views WHERE iban_id = i.id AND view_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)) as recent_views
        FROM ibans i
        WHERE i.user_id = :user_id
        ORDER BY view_count DESC
    ");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $iban_stats = $stmt->fetchAll();
} catch (PDOException $e) {
    // Eğer iban_views tablosu yoksa, sadece IBAN bilgilerini getir
    $stmt = $db->prepare("
        SELECT id, bank_name, account_name, iban_number, is_active, 
               0 as view_count, 0 as recent_views
        FROM ibans 
        WHERE user_id = :user_id
    ");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $iban_stats = $stmt->fetchAll();
}

// Son 30 günlük görüntülenme grafiği için verileri al
$daily_views = [];
try {
    $stmt = $db->prepare("
        SELECT DATE(view_date) AS date, COUNT(*) AS count
        FROM iban_views
        WHERE iban_id IN (SELECT id FROM ibans WHERE user_id = :user_id)
        AND view_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(view_date)
        ORDER BY date
    ");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $daily_views = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Hata yönetimi
}

// Son görüntülenmeler
$recent_views = [];
try {
    $stmt = $db->prepare("
        SELECT v.*, i.bank_name, i.iban_number
        FROM iban_views v
        JOIN ibans i ON v.iban_id = i.id
        WHERE i.user_id = :user_id
        ORDER BY v.view_date DESC
        LIMIT 10
    ");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $recent_views = $stmt->fetchAll();
} catch (PDOException $e) {
    // Hata yönetimi
}

// Header ekleniyor
include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><i class="fas fa-chart-bar"></i> İstatistikler</h2>
            <p class="text-muted">IBAN görüntülenme istatistiklerinizi ve performansınızı görüntüleyin.</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="manage-ibans.php" class="btn btn-primary">
                <i class="fas fa-list"></i> IBAN'larımı Yönet
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
                                Son 7 Gün Görüntülenme</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $last_7_days_views; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Grafik ve IBAN Listesi -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Son 30 Gün Görüntülenme Grafiği</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="viewsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Görüntülenme Oranları</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4">
                        <canvas id="ibansChart"></canvas>
                    </div>
                    <div class="mt-4 text-center small">
                        <?php foreach (array_slice($iban_stats, 0, 3) as $index => $iban): ?>
                        <span class="mr-2">
                            <i class="fas fa-circle" style="color: <?php echo ['#4e73df', '#1cc88a', '#36b9cc'][$index % 3]; ?>"></i> <?php echo htmlspecialchars(substr($iban['bank_name'], 0, 15)); ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- IBAN İstatistikleri Tablosu -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">IBAN Görüntülenme İstatistikleri</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Durum</th>
                            <th>Banka</th>
                            <th>IBAN</th>
                            <th>Toplam Görüntülenme</th>
                            <th>Son 7 Gün</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($iban_stats as $iban): ?>
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
                            <td><?php echo $iban['view_count']; ?></td>
                            <td><?php echo $iban['recent_views']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Son Görüntülenmeler -->
    <?php if (count($recent_views) > 0): ?>
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Son Görüntülenmeler</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Tarih</th>
                            <th>IP Adresi</th>
                            <th>IBAN</th>
                            <th>Banka</th>
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
                            <td><?php echo htmlspecialchars($view['bank_name']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Görüntülenme grafiği
    const ctx = document.getElementById('viewsChart');
    
    // Son 30 günlük veri
    const labels = [];
    const data = [];
    
    <?php
    // Son 30 günü hazırla (bugün dahil)
    $date = new DateTime();
    $date->modify('-29 days');
    
    $dateData = [];
    for ($i = 0; $i < 30; $i++) {
        $currentDate = $date->format('Y-m-d');
        $dateData[$currentDate] = 0;
        $date->modify('+1 day');
    }
    
    // Veritabanından gelen verileri ekleyelim
    if (!empty($daily_views)) {
        foreach ($daily_views as $item) {
            $dateData[$item['date']] = intval($item['count']);
        }
    }
    
    // JavaScript dizilerine ekle
    foreach ($dateData as $date => $count) {
        $formattedDate = date('d.m', strtotime($date));
        echo "labels.push('$formattedDate');\n";
        echo "data.push($count);\n";
    }
    ?>
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Günlük Görüntülenme Sayısı',
                data: data,
                backgroundColor: 'rgba(78, 115, 223, 0.05)',
                borderColor: 'rgba(78, 115, 223, 1)',
                pointRadius: 3,
                pointBackgroundColor: 'rgba(78, 115, 223, 1)',
                pointBorderColor: 'rgba(78, 115, 223, 1)',
                pointHoverRadius: 5,
                pointHoverBackgroundColor: 'rgba(78, 115, 223, 1)',
                pointHoverBorderColor: 'rgba(78, 115, 223, 1)',
                pointHitRadius: 10,
                pointBorderWidth: 2,
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    }
                },
                y: {
                    beginAtZero: true,
                    precision: 0,
                    stepSize: 1
                }
            }
        }
    });

    // IBAN dağılım grafiği
    const pieCtx = document.getElementById('ibansChart');
    const pieLabels = [];
    const pieData = [];
    const pieColors = [];
    
    <?php
    // En fazla görüntülenen 5 IBAN'ı gösterelim
    $colors = ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b'];
    $top_ibans = array_slice($iban_stats, 0, 5);
    
    foreach ($top_ibans as $index => $iban) {
        $name = htmlspecialchars(substr($iban['bank_name'], 0, 15));
        $count = intval($iban['view_count']);
        $color = $colors[$index % count($colors)];
        
        echo "pieLabels.push('$name');\n";
        echo "pieData.push($count);\n";
        echo "pieColors.push('$color');\n";
    }
    ?>
    
    new Chart(pieCtx, {
        type: 'doughnut',
        data: {
            labels: pieLabels,
            datasets: [{
                data: pieData,
                backgroundColor: pieColors,
                hoverBackgroundColor: pieColors,
                hoverBorderColor: 'rgba(234, 236, 244, 1)',
            }]
        },
        options: {
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
});
</script>

<?php
// Footer ekleniyor
include 'includes/footer.php';
?>