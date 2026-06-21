<?php
if (!defined('SITE_NAME')) {
    require_once __DIR__ . '/config.php';
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(get_current_language()); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title ?? t('site_name')); ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/responsive.css">
    <link rel="icon" href="<?php echo SITE_URL; ?>assets/images/favicon.ico">
    
    <!-- jQuery (required for existing functionality) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap 5 JavaScript Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <?php if ($session->isLoggedIn()): ?>
        <!-- Logged-in User Navigation -->
        <nav class="navbar navbar-expand-lg navbar-dark shadow" style="background-color: #708090 !important;">
            <div class="container">
                <a class="navbar-brand fw-bold" href="<?php echo SITE_URL; ?>public/index.php">
                    <i class="fas fa-home me-2"></i>
                    <span><?php echo htmlspecialchars(t('site_name')); ?></span>
                </a>
                
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <?php if ($session->getUserRole() == 'tenant'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo SITE_URL; ?>tenant/dashboard.php">
                                    <i class="fas fa-tachometer-alt me-1"></i> <?php echo t('dashboard'); ?>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo SITE_URL; ?>tenant/search.php">
                                    <i class="fas fa-search me-1"></i> <?php echo t('properties'); ?>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo SITE_URL; ?>tenant/my-rentals.php">
                                    <i class="fas fa-home me-1"></i> <?php echo t('my_rentals') ?? 'My Rentals'; ?>
                                </a>
                            </li>
                        <?php elseif ($session->getUserRole() == 'owner'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo SITE_URL; ?>owner/dashboard.php">
                                    <i class="fas fa-tachometer-alt me-1"></i> <?php echo t('dashboard'); ?>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo SITE_URL; ?>owner/properties.php">
                                    <i class="fas fa-home me-1"></i> <?php echo t('properties'); ?>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo SITE_URL; ?>owner/requests.php">
                                    <i class="fas fa-inbox me-1"></i> <?php echo t('requests') ?? 'Requests'; ?>
                                </a>
                            </li>
                        <?php elseif ($session->getUserRole() == 'admin'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo SITE_URL; ?>admin/dashboard.php">
                                    <i class="fas fa-tachometer-alt me-1"></i> <?php echo t('dashboard'); ?>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo SITE_URL; ?>admin/users.php">
                                    <i class="fas fa-users me-1"></i> <?php echo t('users') ?? 'Users'; ?>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo SITE_URL; ?>admin/properties.php">
                                    <i class="fas fa-home me-1"></i> <?php echo t('properties'); ?>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle me-1"></i>
                                <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?>
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="userDropdown">
                                <li>
                                    <a class="dropdown-item" href="<?php echo SITE_URL . $session->getUserRole() . '/profile.php'; ?>">
                                        <i class="fas fa-user me-2"></i> <?php echo t('profile'); ?>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo SITE_URL . $session->getUserRole() . '/notifications.php'; ?>">
                                        <i class="fas fa-bell me-2"></i> <?php echo t('notifications'); ?>
                                        <?php if (isset($stats['unread_notifications']) && $stats['unread_notifications'] > 0): ?>
                                            <span class="badge bg-danger float-end"><?php echo $stats['unread_notifications']; ?></span>
                                        <?php endif; ?>
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger" href="<?php echo SITE_URL; ?>logout.php">
                                        <i class="fas fa-sign-out-alt me-2"></i> <?php echo t('logout'); ?>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="languageDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <?php echo strtoupper(htmlspecialchars(get_current_language())); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="languageDropdown">
                                <li><a class="dropdown-item" href="<?php echo htmlspecialchars(build_language_url('en')); ?>">EN - English</a></li>
                                <li><a class="dropdown-item" href="<?php echo htmlspecialchars(build_language_url('am')); ?>">አማርኛ - Amharic</a></li>
                                <li><a class="dropdown-item" href="<?php echo htmlspecialchars(build_language_url('ti')); ?>">ትግርኛ - Tigrinya</a></li>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link btn btn-link" id="theme-toggle" title="Toggle Dark/Light Mode">
                                <i class="fas fa-moon" id="theme-icon"></i>
                            </button>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    <?php else: ?>
        <!-- Public Navigation -->
        <nav class="navbar navbar-expand-lg navbar-dark shadow" style="background-color: #708090 !important;">
            <div class="container">
                <a class="navbar-brand fw-bold" href="<?php echo SITE_URL; ?>public/index.php">
                    <i class="fas fa-home me-2"></i>
                    <span><?php echo htmlspecialchars(t('site_name')); ?></span>
                </a>
                
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#publicNavbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <div class="collapse navbar-collapse" id="publicNavbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>public/index.php">
                                <i class="fas fa-home me-1"></i> <?php echo t('home'); ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>public/properties.php">
                                <i class="fas fa-building me-1"></i> <?php echo t('properties'); ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>public/about.php">
                                <i class="fas fa-info-circle me-1"></i> <?php echo t('about'); ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>public/contact.php">
                                <i class="fas fa-envelope me-1"></i> <?php echo t('contact'); ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>login.php">
                                <i class="fas fa-sign-in-alt me-1"></i> <?php echo t('login'); ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>register.php">
                                <i class="fas fa-user-plus me-1"></i> <?php echo t('register'); ?>
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="languageDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <?php echo strtoupper(htmlspecialchars(get_current_language())); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="languageDropdown">
                                <li><a class="dropdown-item" href="<?php echo htmlspecialchars(build_language_url('en')); ?>">EN - English</a></li>
                                <li><a class="dropdown-item" href="<?php echo htmlspecialchars(build_language_url('am')); ?>">አማርኛ - Amharic</a></li>
                                <li><a class="dropdown-item" href="<?php echo htmlspecialchars(build_language_url('ti')); ?>">ትግርኛ - Tigrinya</a></li>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link btn btn-link" id="theme-toggle" title="Toggle Dark/Light Mode">
                                <i class="fas fa-moon" id="theme-icon"></i>
                            </button>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
            <?php echo $_SESSION['success']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
            <?php echo $_SESSION['error']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>