<?php
// Veritabanı bağlantısı
require_once 'db/config.php';

// Şirket/Kişi ID parametresini kontrol et
$company_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Kontrol et ve IBAN'ları getir
$all_ibans = [];
if ($company_id > 0) {
    try {
        // Belirli bir ID için tüm aktif IBAN'ları getir
        $stmt = $db->prepare("SELECT * FROM ibans WHERE user_id = :user_id AND is_active = 1 ORDER BY bank_name");
        $stmt->bindParam(':user_id', $company_id);
        $stmt->execute();
        $all_ibans = $stmt->fetchAll();
    } catch (PDOException $e) {
        die("Veritabanı hatası oluştu. Lütfen daha sonra tekrar deneyin.");
    }
}

// IBAN bulunamadıysa, kullanıcıya bilgi ver
if (empty($all_ibans)) {
    ?>
    <!DOCTYPE html>
    <html lang="tr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>IBAN Bilgisi Bulunamadı</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body {
                background-color: #f5f7fa;
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            }
            .error-container {
                max-width: 600px;
                text-align: center;
                padding: 2rem;
                background: white;
                border-radius: 1rem;
                box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            }
            .error-icon {
                font-size: 4rem;
                color: #f56565;
                margin-bottom: 1rem;
            }
            h1 {
                color: #4a5568;
                font-size: 1.5rem;
                margin-bottom: 1rem;
            }
            p {
                color: #718096;
                margin-bottom: 1.5rem;
            }
            .btn-back {
                background: linear-gradient(135deg, #4361ee, #7209b7);
                color: white;
                border: none;
                padding: 0.7rem 1.5rem;
                border-radius: 50px;
                font-weight: 600;
                text-decoration: none;
                transition: all 0.3s ease;
                display: inline-block;
            }
            .btn-back:hover {
                transform: translateY(-2px);
                box-shadow: 0 5px 15px rgba(114, 9, 183, 0.4);
                color: white;
                text-decoration: none;
            }
        </style>
    </head>
    <body>
        <div class="error-container">
            <div class="error-icon">⚠️</div>
            <h1>IBAN Bilgisi Bulunamadı</h1>
            <p>Bu kullanıcıya ait aktif IBAN bilgisi bulunamadı veya link geçersiz.</p>
            <a href="index.php" class="btn-back">Ana Sayfaya Dön</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Hesap sahibi bilgisi için ilk IBAN'ı kullan
$account_info = $all_ibans[0];
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($account_info['account_name']); ?> - IBAN Bilgileri</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Mono:wght@400;600&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
    :root {
      --primary-color: #4361ee;
      --primary-dark: #3a56e8;
      --secondary-color: #7209b7;
      --accent-color: #f72585;
      --light-color: #f8f9fa;
      --dark-color: #212529;
      --success-color: #20c997;
      --info-color: #0dcaf0;
      --primary-color-rgb: 67, 97, 238;
      --secondary-color-rgb: 114, 9, 183;
      --accent-color-rgb: 247, 37, 133;
    }
    
    body {
      background-color: #f9fafb;
      font-family: 'Nunito', sans-serif;
      line-height: 1.6;
      color: #444;
      padding: 0;
      margin: 0;
      min-height: 100vh;
      background: linear-gradient(135deg, #f5f7fa 0%, #e4e9f2 100%);
    }
    
    .iban-container {
      max-width: 1100px;
      margin: 3rem auto;
      padding: 0 1rem;
    }
    
    .page-header {
      text-align: center;
      margin-bottom: 2rem;
      color: #fff;
      position: relative;
      padding: 2rem;
      border-radius: 1rem;
      background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }
    
    .page-header::before {
      content: '';
      position: absolute;
      top: -50%;
      right: -50%;
      bottom: -50%;
      left: -50%;
      background: linear-gradient(to bottom right, rgba(255, 255, 255, 0.15) 0%, rgba(255, 255, 255, 0) 100%);
      transform: rotate(30deg);
      pointer-events: none;
    }
    
    .page-header h1 {
      margin: 0 0 0.5rem 0;
      font-weight: 700;
      font-size: 2.5rem;
      text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }
    
    .page-header p {
      opacity: 0.9;
      font-size: 1.2rem;
      margin: 0;
    }
    
    .iban-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
      gap: 2rem;
      margin-bottom: 2rem;
    }
    
    .iban-card {
      background: white;
      border-radius: 1rem;
      overflow: hidden;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
      transition: transform 0.3s, box-shadow 0.3s;
      height: 100%;
      display: flex;
      flex-direction: column;
    }
    
    .iban-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
    }
    
    .card-header {
      background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
      color: white;
      padding: 1.5rem;
      position: relative;
      overflow: hidden;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    
    .card-header::before {
      content: '';
      position: absolute;
      top: -50%;
      right: -50%;
      bottom: -50%;
      left: -50%;
      background: linear-gradient(to bottom right, rgba(255, 255, 255, 0.15) 0%, rgba(255, 255, 255, 0) 100%);
      transform: rotate(30deg);
      pointer-events: none;
    }
    
    .bank-logo {
      max-width: 80px;
      max-height: 40px;
      object-fit: contain;
      filter: drop-shadow(0 2px 5px rgba(0, 0, 0, 0.2));
    }
    
    .bank-name {
      font-weight: 700;
      font-size: 1.2rem;
      text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
      z-index: 1;
    }
    
    .card-body {
      padding: 1.5rem;
      flex-grow: 1;
      display: flex;
      flex-direction: column;
    }
    
    .account-name {
      margin-bottom: 1rem;
      padding-bottom: 1rem;
      border-bottom: 1px solid rgba(0, 0, 0, 0.1);
    }
    
    .account-label {
      font-weight: 600;
      color: var(--primary-color);
      font-size: 0.8rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 0.3rem;
    }
    
    .account-value {
      font-size: 1.1rem;
      color: var(--dark-color);
      font-weight: 600;
    }
    
    .iban-display {
      font-family: 'Roboto Mono', monospace;
      font-size: 1.1rem;
      padding: 0.75rem 1rem;
      background-color: #f8fafd;
      border-radius: 0.5rem;
      border: 1px solid rgba(0, 0, 0, 0.08);
      letter-spacing: 1px;
      overflow-x: auto;
      white-space: nowrap;
      transition: background-color 0.2s;
      font-weight: 600;
      color: #333;
      margin-bottom: 1rem;
    }
    
    .iban-copy {
      margin-top: auto;
    }
    
    .btn {
      border-radius: 50px;
      padding: 0.75rem 1.25rem;
      font-weight: 600;
      letter-spacing: 0.5px;
      font-size: 0.95rem;
      transition: all 0.3s;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      width: 100%;
    }
    
    .btn-primary {
      background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
      border: none;
      color: white;
      box-shadow: 0 4px 10px rgba(67, 97, 238, 0.3);
    }
    
    .btn-primary:hover {
      background: linear-gradient(135deg, var(--primary-dark), var(--secondary-color));
      transform: translateY(-2px);
      box-shadow: 0 6px 15px rgba(67, 97, 238, 0.4);
    }
    
    .description {
      margin-top: 1rem;
      margin-bottom: 1rem;
      font-size: 0.9rem;
      color: #718096;
      font-style: italic;
      flex-grow: 1;
      word-break: break-word;
    }
    
    .copy-alert {
      position: fixed;
      top: 20px;
      right: 20px;
      padding: 0.75rem 1.25rem;
      background: linear-gradient(135deg, var(--success-color), #1aa179);
      color: white;
      border-radius: 50px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
      display: none;
      z-index: 1000;
      animation: fadeIn 0.3s ease-out;
      font-weight: 600;
      font-size: 0.9rem;
    }
    
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    /* QR Kod Ikon */
    .qr-icon {
      position: absolute;
      top: 10px;
      right: 10px;
      background: rgba(255, 255, 255, 0.2);
      border-radius: 50%;
      width: 30px;
      height: 30px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      z-index: 2;
      transition: all 0.2s;
    }
    
    .qr-icon:hover {
      background: rgba(255, 255, 255, 0.3);
      transform: scale(1.1);
    }
    
    .qr-modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      z-index: 1000;
      align-items: center;
      justify-content: center;
    }
    
    .qr-content {
      background: white;
      padding: 2rem;
      border-radius: 1rem;
      text-align: center;
      max-width: 90%;
      width: 350px;
    }
    
    .qr-content h3 {
      margin-top: 0;
      margin-bottom: 1rem;
      color: var(--primary-color);
    }
    
    .qr-close {
      position: absolute;
      top: 10px;
      right: 10px;
      background: rgba(0, 0, 0, 0.1);
      border: none;
      border-radius: 50%;
      width: 30px;
      height: 30px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: all 0.2s;
    }
    
    .qr-close:hover {
      background: rgba(0, 0, 0, 0.2);
    }
    
    .qr-image {
      margin: 1rem auto;
      max-width: 200px;
      height: auto;
    }
    
    .footer {
      text-align: center;
      padding: 1.5rem;
      color: #718096;
      font-size: 0.9rem;
      margin-top: 2rem;
    }
    
    /* Mobil Uyumluluk */
    @media (max-width: 768px) {
      .iban-grid {
        grid-template-columns: 1fr;
      }
      
      .page-header {
        padding: 1.5rem;
        margin-bottom: 1.5rem;
      }
      
      .page-header h1 {
        font-size: 1.8rem;
      }
      
      .iban-container {
        margin: 1.5rem auto;
      }
    }
    
    /* Tema Seçimi */
    .theme-switch {
      position: fixed;
      bottom: 20px;
      right: 20px;
      z-index: 100;
      background: white;
      border-radius: 50%;
      width: 50px;
      height: 50px;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
      cursor: pointer;
      transition: all 0.3s;
    }
    
    .theme-switch:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
    }
    
    /* Dark Mode */
    body.dark-mode {
      background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
      color: #e4e6eb;
    }
    
    body.dark-mode .iban-card {
      background: #242526;
    }
    
    body.dark-mode .iban-display {
      background-color: #3a3b3c;
      border-color: #4a4b4c;
      color: #e4e6eb;
    }
    
    body.dark-mode .account-value {
      color: #e4e6eb;
    }
    
    body.dark-mode .footer {
      color: #b0b3b8;
    }
    
    body.dark-mode .theme-switch {
      background: #3a3b3c;
      color: #e4e6eb;
    }
    </style>
</head>
<body>
    <div class="iban-container">
        <div class="page-header">
            <h1><?php echo htmlspecialchars($account_info['account_name']); ?></h1>
            <p>IBAN Bilgileri</p>
        </div>
        
        <div class="iban-grid">
            <?php foreach ($all_ibans as $index => $iban): ?>
            <div class="iban-card" id="iban-card-<?php echo $iban['id']; ?>">
                <div class="card-header">
                    <div class="bank-name"><?php echo htmlspecialchars($iban['bank_name']); ?></div>
                    <?php if (!empty($iban['bank_logo']) && file_exists($iban['bank_logo'])): ?>
                    <img src="<?php echo $iban['bank_logo']; ?>" alt="<?php echo htmlspecialchars($iban['bank_name']); ?>" class="bank-logo">
                    <?php endif; ?>
                    <div class="qr-icon" data-iban="<?php echo htmlspecialchars($iban['iban_number']); ?>">
                        <i class="fas fa-qrcode"></i>
                    </div>
                </div>
                
                <div class="card-body">
                    <div class="account-name">
                        <div class="account-label">Hesap Sahibi</div>
                        <div class="account-value"><?php echo htmlspecialchars($iban['account_name']); ?></div>
                    </div>
                    
                    <div class="account-label">IBAN</div>
                    <div class="iban-display">
                        <?php 
                        // IBAN'ı formatla
                        $iban_number = trim($iban['iban_number']);
                        $formatted_iban = '';
                        
                        for ($i = 0; $i < strlen($iban_number); $i++) {
                            if ($i > 0 && $i % 4 === 0) {
                                $formatted_iban .= ' ';
                            }
                            $formatted_iban .= $iban_number[$i];
                        }
                        
                        echo htmlspecialchars($formatted_iban);
                        ?>
                    </div>
                    
                    <?php if (!empty($iban['description'])): ?>
                    <div class="description">
                        <?php echo htmlspecialchars($iban['description']); ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="iban-copy">
                        <button type="button" class="btn btn-primary copy-btn" 
                                data-copy="<?php echo htmlspecialchars($iban['iban_number']); ?>">
                            <i class="fas fa-copy"></i> IBAN'ı Kopyala
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="footer">
            <p>Bu IBAN bilgileri güvenli bir şekilde paylaşılmaktadır.</p>
        </div>
    </div>
    
    <!-- QR Kod Modal -->
    <div class="qr-modal" id="qrModal">
        <div class="qr-content">
            <button class="qr-close" id="qrClose">×</button>
            <h3>IBAN QR Kodu</h3>
            <p>Bu QR kodu mobil bankacılık uygulamanızla taratabilirsiniz.</p>
            <div id="qrCode" class="qr-image"></div>
        </div>
    </div>
    
    <!-- Tema Değiştirme Butonu -->
    <div class="theme-switch" id="themeSwitch">
        <i class="fas fa-moon"></i>
    </div>
    
    <div class="copy-alert" id="copyAlert">
        <i class="fas fa-check-circle"></i> IBAN başarıyla kopyalandı!
    </div>
    
    <!-- QR Kod Kütüphanesi -->
    <script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const copyButtons = document.querySelectorAll('.copy-btn');
        const copyAlert = document.getElementById('copyAlert');
        const qrIcons = document.querySelectorAll('.qr-icon');
        const qrModal = document.getElementById('qrModal');
        const qrClose = document.getElementById('qrClose');
        const qrCode = document.getElementById('qrCode');
        const themeSwitch = document.getElementById('themeSwitch');
        
        // IBAN kopyalama
        if (copyButtons.length > 0) {
            copyButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    const textToCopy = this.getAttribute('data-copy');
                    copyToClipboard(textToCopy);
                    
                    // Animasyon efekti
                    const ibanCard = this.closest('.iban-card');
                    ibanCard.style.transform = 'scale(1.03)';
                    setTimeout(() => {
                        ibanCard.style.transform = '';
                    }, 300);
                });
            });
        }
        
        // QR Kod gösterme
        if (qrIcons.length > 0) {
            qrIcons.forEach(icon => {
                icon.addEventListener('click', function() {
                    const ibanText = this.getAttribute('data-iban');
                    generateQRCode(ibanText);
                    qrModal.style.display = 'flex';
                });
            });
        }
        
        // QR Modal kapatma
        if (qrClose) {
            qrClose.addEventListener('click', function() {
                qrModal.style.display = 'none';
            });
            
            // Modal dışına tıklayınca kapatma
            qrModal.addEventListener('click', function(e) {
                if (e.target === qrModal) {
                    qrModal.style.display = 'none';
                }
            });
        }
        
        // Tema değiştirme
        if (themeSwitch) {
            themeSwitch.addEventListener('click', function() {
                document.body.classList.toggle('dark-mode');
                
                if (document.body.classList.contains('dark-mode')) {
                    themeSwitch.innerHTML = '<i class="fas fa-sun"></i>';
                    localStorage.setItem('theme', 'dark');
                } else {
                    themeSwitch.innerHTML = '<i class="fas fa-moon"></i>';
                    localStorage.setItem('theme', 'light');
                }
            });
            
            // Kaydedilmiş tema tercihini kontrol et
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'dark') {
                document.body.classList.add('dark-mode');
                themeSwitch.innerHTML = '<i class="fas fa-sun"></i>';
            }
        }
        
        // QR Kod oluşturma fonksiyonu
        function generateQRCode(text) {
            qrCode.innerHTML = '';
            
            try {
                // QR kod kütüphanesi kullan
                var qr = qrcode(0, 'M');
                qr.addData(text);
                qr.make();
                
                qrCode.innerHTML = qr.createImgTag(5);
            } catch (err) {
                qrCode.innerHTML = 'QR kod oluşturma hatası';
                console.error('QR kod oluşturma hatası:', err);
            }
        }
        
        // Kopyalama fonksiyonu
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                showAlert('IBAN başarıyla kopyalandı!');
            }).catch(err => {
                // Alternatif kopyalama metodu
                const textarea = document.createElement('textarea');
                textarea.value = text;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                
                showAlert('IBAN başarıyla kopyalandı!');
            });
        }
        
        // Bildirim gösterme fonksiyonu
        function showAlert(message) {
            copyAlert.innerHTML = '<i class="fas fa-check-circle"></i> ' + message;
            copyAlert.style.display = 'block';
            
            setTimeout(() => {
                copyAlert.style.opacity = '0';
                setTimeout(() => {
                    copyAlert.style.display = 'none';
                    copyAlert.style.opacity = '1';
                }, 300);
            }, 2000);
        }
    });
    </script>
</body>
</html>