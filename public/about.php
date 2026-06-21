<?php
require_once '../includes/config.php';
$title = "About Us - Aksum House Rental System";
include '../includes/header.php';
?>

<!-- About Hero Section -->
<section class="bg-primary text-white py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="display-4 fw-bold mb-4"><?php echo t('about_title'); ?></h1>
                <p class="lead mb-0"><?php echo t('about_subtitle'); ?></p>
            </div>
            <div class="col-lg-4">
                <div class="text-center">
                    <i class="fas fa-home fa-5x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- About Content -->
<section class="py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body p-4">
                        <h2 class="h3 mb-4"><?php echo t('our_story_title'); ?></h2>
                        <p class="mb-3"><?php echo t('our_story_paragraph_1'); ?></p>
                        <p class="mb-3"><?php echo t('our_story_paragraph_2'); ?></p>
                        <p><?php echo t('our_story_paragraph_3'); ?></p>
                    </div>
                </div>

                <!-- Mission & Vision -->
                <div class="row mb-4">
                    <div class="col-md-6 mb-4">
                        <div class="card h-100 shadow-sm border-0">
                            <div class="card-body p-4">
                                <div class="text-center mb-3">
                                    <i class="fas fa-bullseye fa-3x text-primary"></i>
                                </div>
                                <h3 class="h5 text-center mb-3"><?php echo t('our_mission_title'); ?></h3>
                                <p class="text-center mb-0"><?php echo t('our_mission_description'); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="card h-100 shadow-sm border-0">
                            <div class="card-body p-4">
                                <div class="text-center mb-3">
                                    <i class="fas fa-eye fa-3x text-success"></i>
                                </div>
                                <h3 class="h5 text-center mb-3"><?php echo t('our_vision_title'); ?></h3>
                                <p class="text-center mb-0"><?php echo t('our_vision_description'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Why Choose Us -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body p-4">
                        <h3 class="h4 mb-4 text-center"><?php echo t('about_why_choose_title'); ?></h3>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-check-circle text-success fa-2x"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h5 class="h6"><?php echo t('verified_listings'); ?></h5>
                                        <p class="mb-0 small"><?php echo t('verified_listings_desc'); ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-shield-alt text-primary fa-2x"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h5 class="h6"><?php echo t('secure_transactions'); ?></h5>
                                        <p class="mb-0 small"><?php echo t('secure_transactions_desc'); ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-users text-info fa-2x"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h5 class="h6"><?php echo t('support_247'); ?></h5>
                                        <p class="mb-0 small"><?php echo t('support_247_desc'); ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-mobile-alt text-warning fa-2x"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h5 class="h6"><?php echo t('mobile_friendly'); ?></h5>
                                        <p class="mb-0 small"><?php echo t('mobile_friendly_desc'); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistics -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body p-4">
                        <h3 class="h4 mb-4 text-center">Our Impact</h3>
                        <div class="row text-center">
                            <div class="col-md-3 mb-3">
                                <div class="stat-item">
                                    <h3 class="display-6 fw-bold text-primary mb-2">
                                        <?php
                                        try {
                                            $sql = "SELECT COUNT(*) as count FROM properties WHERE status = 'AVAILABLE'";
                                            $stmt = $db->prepare($sql);
                                            $result = $db->getSingle($stmt);
                                            echo $result ? number_format($result['count']) : '500+';
                                        } catch (Exception $e) {
                                            echo '500+';
                                        }
                                        ?>
                                    </h3>
                                    <p class="text-muted mb-0">Active Properties</p>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="stat-item">
                                    <h3 class="display-6 fw-bold text-success mb-2">
                                        <?php
                                        try {
                                            $sql = "SELECT COUNT(*) as count FROM users WHERE role = 'TENANT'";
                                            $stmt = $db->prepare($sql);
                                            $result = $db->getSingle($stmt);
                                            echo $result ? number_format($result['count']) : '1000+';
                                        } catch (Exception $e) {
                                            echo '1000+';
                                        }
                                        ?>
                                    </h3>
                                    <p class="text-muted mb-0">Happy Tenants</p>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="stat-item">
                                    <h3 class="display-6 fw-bold text-info mb-2">
                                        <?php
                                        try {
                                            $sql = "SELECT COUNT(*) as count FROM users WHERE role = 'OWNER'";
                                            $stmt = $db->prepare($sql);
                                            $result = $db->getSingle($stmt);
                                            echo $result ? number_format($result['count']) : '200+';
                                        } catch (Exception $e) {
                                            echo '200+';
                                        }
                                        ?>
                                    </h3>
                                    <p class="text-muted mb-0">Property Owners</p>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="stat-item">
                                    <h3 class="display-6 fw-bold text-warning mb-2">
                                        <?php
                                        try {
                                            $sql = "SELECT COUNT(*) as count FROM rental_agreements WHERE status = 'ACTIVE'";
                                            $stmt = $db->prepare($sql);
                                            $result = $db->getSingle($stmt);
                                            echo $result ? number_format($result['count']) : '800+';
                                        } catch (Exception $e) {
                                            echo '800+';
                                        }
                                        ?>
                                    </h3>
                                    <p class="text-muted mb-0">Active Rentals</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contact CTA -->
                <div class="card bg-primary text-white border-0">
                    <div class="card-body p-4 text-center">
                        <h3 class="h4 mb-3">Ready to Find Your Perfect Home?</h3>
                        <p class="mb-4">Join thousands of satisfied tenants who have found their ideal rental properties through our platform.</p>
                        <div class="d-flex justify-content-center gap-3">
                            <a href="properties.php" class="btn btn-light btn-lg">
                                <i class="fas fa-search me-2"></i>Browse Properties
                            </a>
                            <a href="contact.php" class="btn btn-outline-light btn-lg">
                                <i class="fas fa-envelope me-2"></i>Contact Us
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
