<?php
// Veritabanı bağlantı ayarları
$host = "localhost"; 
$dbname = "iban_db"; 
$username = "root"; 
$password = ""; 

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Veritabanı bağlantısı başarısız: " . $e->getMessage());
}

// Veritabanı tablolarını oluşturmak için fonksiyon
function createTables($db) {
    try {
        // Kullanıcılar tablosu
        $db->exec("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        // IBAN bilgileri tablosu (slug alanı eklendi)
        $db->exec("CREATE TABLE IF NOT EXISTS ibans (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            bank_name VARCHAR(100) NOT NULL,
            account_name VARCHAR(100) NOT NULL,
            iban_number VARCHAR(50) NOT NULL,
            description TEXT,
            slug VARCHAR(100) NOT NULL UNIQUE,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        // IBAN paylaşım kayıtları tablosu
        $db->exec("CREATE TABLE IF NOT EXISTS iban_shares (
            id INT AUTO_INCREMENT PRIMARY KEY,
            iban_id INT NOT NULL,
            customer_email VARCHAR(100) NOT NULL,
            customer_name VARCHAR(100),
            share_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (iban_id) REFERENCES ibans(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        return true;
    } catch(PDOException $e) {
        die("Tablo oluşturma hatası: " . $e->getMessage());
    }
}

// Slug oluşturma fonksiyonu
function createSlug($text) {
    // Türkçe karakterleri değiştir
    $turkishChars = array('ı', 'ğ', 'ü', 'ş', 'ö', 'ç', 'İ', 'Ğ', 'Ü', 'Ş', 'Ö', 'Ç');
    $englishChars = array('i', 'g', 'u', 's', 'o', 'c', 'i', 'g', 'u', 's', 'o', 'c');
    $text = str_replace($turkishChars, $englishChars, $text);
    
    // Diğer özel karakterleri temizle
    $text = preg_replace('/[^a-zA-Z0-9\s]/', '', $text);
    
    // Küçük harfe çevir ve boşlukları tire ile değiştir
    $text = strtolower(trim($text));
    $text = preg_replace('/\s+/', '-', $text);
    
    return $text;
}

// db/config.php dosyasının en altında
// Yorum satırını kaldırarak tabloları oluşturun
createTables($db);