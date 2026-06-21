<?php
require_once '../includes/config.php';
$title = __('terms') . " - " . __('site_name');
include '../includes/header.php';
?>

<!-- Terms of Service Hero Section -->
<section class="bg-primary text-white py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="display-4 fw-bold mb-4"><?php echo __('terms'); ?></h1>
                <p class="lead mb-0"><?php echo __('terms_hero_subtitle'); ?></p>
            </div>
            <div class="col-lg-4">
                <div class="text-center">
                    <i class="fas fa-file-contract fa-5x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Terms of Service Content -->
<section class="py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body p-4">
                        <p class="text-muted mb-4"><?php echo sprintf(__('terms_last_updated'), 'April 28, 2026'); ?></p>
                        
                        <h2 class="h4 mb-3"><?php echo __('terms_section1_title'); ?></h2>
                        <p><?php echo __('terms_section1_paragraph'); ?></p>

                        <h2 class="h4 mb-3 mt-4"><?php echo __('terms_section2_title'); ?></h2>
                        <p><?php echo __('terms_section2_paragraph'); ?></p>

                        <h2 class="h4 mb-3 mt-4"><?php echo __('terms_section3_title'); ?></h2>
                        <p><?php echo __('terms_section3_paragraph'); ?></p>
                        <ul>
                            <li><?php echo __('terms_user_obligation_1'); ?></li>
                            <li><?php echo __('terms_user_obligation_2'); ?></li>
                            <li><?php echo __('terms_user_obligation_3'); ?></li>
                            <li><?php echo __('terms_user_obligation_4'); ?></li>
                        </ul>

                        <h2 class="h4 mb-3 mt-4"><?php echo __('terms_section4_title'); ?></h2>
                        <p><?php echo __('terms_section4_paragraph'); ?></p>

                        <h2 class="h4 mb-3 mt-4"><?php echo __('terms_section5_title'); ?></h2>
                        <p><?php echo __('terms_section5_paragraph'); ?></p>

                        <h2 class="h4 mb-3 mt-4"><?php echo __('terms_section6_title'); ?></h2>
                        <p><?php echo __('terms_section6_paragraph'); ?></p>

                        <h2 class="h4 mb-3 mt-4"><?php echo __('terms_section7_title'); ?></h2>
                        <p><?php echo __('terms_section7_paragraph'); ?></p>

                        <h2 class="h4 mb-3 mt-4"><?php echo __('terms_section8_title'); ?></h2>
                                <p><?php echo __('terms_section8_paragraph'); ?></p>

                                <h2 class="h4 mb-3 mt-4"><?php echo __('terms_section9_title'); ?></h2>
                                <p><?php echo __('terms_section9_paragraph'); ?></p>
                                <p class="mb-0"><strong><?php echo __('terms_contact_email_label'); ?></strong> <?php echo __('terms_contact_email'); ?></p>
                                <p><strong><?php echo __('terms_contact_phone_label'); ?></strong> <?php echo __('terms_contact_phone'); ?></p>
                            </div>
                        </div>
            </div>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
