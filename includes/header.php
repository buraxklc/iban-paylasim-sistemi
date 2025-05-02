<!-- includes/header.php -->
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IBAN Paylaşım Sistemi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Sidebar Stili */
        body {
            overflow-x: hidden;
        }
        
        #wrapper {
            display: flex;
        }
        
        #sidebar-wrapper {
            min-height: 100vh;
            width: 15rem;
            margin-left: -15rem;
            transition: margin 0.25s ease-out;
            background: #4e73df;
            background: linear-gradient(180deg, #4e73df 10%, #224abe 100%);
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            z-index: 1040;
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
        }
        
        #sidebar-wrapper .sidebar-heading {
            padding: 1.5rem 1.25rem;
            font-size: 1.2rem;
            font-weight: 700;
            color: white;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        #sidebar-wrapper .list-group {
            width: 15rem;
        }
        
        #sidebar-wrapper .list-group-item {
            background: transparent;
            color: rgba(255, 255, 255, 0.8);
            border: none;
            padding: 1rem 1.5rem;
            font-weight: 500;
            border-left: 3px solid transparent;
            transition: all 0.2s;
        }
        
        #sidebar-wrapper .list-group-item:hover, 
        #sidebar-wrapper .list-group-item.active {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
            border-left: 3px solid white;
        }
        
        #sidebar-wrapper .list-group-item i {
            margin-right: 0.75rem;
        }
        
        #page-content-wrapper {
            min-width: 100vw;
            transition: margin 0.25s ease-out;
        }
        
        #wrapper.toggled #sidebar-wrapper {
            margin-left: 0;
        }
        
        @media (min-width: 768px) {
            #sidebar-wrapper {
                margin-left: 0;
            }
            
            #page-content-wrapper {
                min-width: 0;
                width: 100%;
                margin-left: 15rem;
            }
            
            #wrapper.toggled #sidebar-wrapper {
                margin-left: -15rem;
            }
            
            #wrapper.toggled #page-content-wrapper {
                margin-left: 0;
            }
        }
        
        /* Navbar Stili */
        .navbar {
            padding: 0.75rem 1rem;
            z-index: 1030;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            background: white;
        }
        
        .navbar .navbar-brand {
            color: #4e73df;
            font-weight: 700;
            display: flex;
            align-items: center;
        }
        
        .navbar-light .navbar-toggler {
            border-color: transparent;
        }
        
        .navbar-user {
            color: #5a5c69;
            font-weight: 600;
        }
        
        .dropdown-menu {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            border: none;
        }
        
        .dropdown-item {
            padding: 0.5rem 1.25rem;
        }
        
        .dropdown-item:hover {
            background-color: #4e73df;
            color: white;
        }
        
        /* İçerik Konteyneri Stili */
        .content-container {
            padding: 1.5rem;
        }
        
        /* Mobil görünüm için sidebar kontrolü */
        @media (max-width: 767.98px) {
            #sidebar-wrapper {
                margin-left: -15rem; /* Başlangıçta gizli */
            }
            
            #wrapper.toggled #sidebar-wrapper {
                margin-left: 0; /* Butona tıklayınca görünür */
                width: 15rem;
            }
            
            #wrapper #page-content-wrapper {
                margin-left: 0;
            }
            
            .content-container {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="d-flex" id="wrapper">
        <!-- Sidebar -->
        <div class="border-end" id="sidebar-wrapper">
            <div class="sidebar-heading">IBAN Paylaşım</div>
            <div class="list-group list-group-flush">
                <?php if(isset($_SESSION['user_id'])): ?>
                <a href="dashboard.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i> Panel
                </a>
                <a href="add-iban.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'add-iban.php' ? 'active' : ''; ?>">
                    <i class="fas fa-plus"></i> IBAN Ekle
                </a>
                <a href="manage-ibans.php" class="list-group-item list-group-item-action <?php echo basename($_SERVER['PHP_SELF']) == 'manage-ibans.php' ? 'active' : ''; ?>">
                    <i class="fas fa-list"></i> IBAN'ları Yönet
                </a>
                <a href="logout.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-sign-out-alt"></i> Çıkış
                </a>
                <?php endif; ?>
            </div>
        </div>
        <!-- Page Content -->
        <div id="page-content-wrapper">
            <!-- Top navigation -->
            <nav class="navbar navbar-light">
                <div class="container-fluid">
                    <button class="navbar-toggler" id="sidebarToggle" type="button">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <a class="navbar-brand" href="dashboard.php">
                        <i class="fas fa-credit-card me-2"></i> IBAN Paylaşım Sistemi
                    </a>
                    <?php if(isset($_SESSION['user_id'])): ?>
                    <div class="dropdown">
                        <a class="navbar-user dropdown-toggle" href="#" role="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle me-1"></i> <?php echo htmlspecialchars($_SESSION['username']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Çıkış</a></li>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
            </nav>
            
            <!-- Page content -->
            <div class="content-container">
                <div class="container-fluid">