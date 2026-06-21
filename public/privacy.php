<?php
require_once '../includes/config.php';
$title = __('privacy') . " - " . __('site_name');
include '../includes/header.php';
?>

<style>
    .privacy-contact-link {
        color: var(--bs-primary);
        text-decoration: none;
        transition: all 0.3s ease;
    }
    .privacy-contact-link:hover {
        color: var(--bs-info);
        text-decoration: underline;
    }
</style>

<!-- Privacy Policy Hero Section -->
<section class="bg-primary text-white py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="display-4 fw-bold mb-4"><?php echo __('privacy'); ?></h1>
                <p class="lead mb-0"><?php echo __('privacy_hero_subtitle'); ?></p>
            </div>
            <div class="col-lg-4">
                <div class="text-center">
                    <i class="fas fa-user-shield fa-5x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Privacy Policy Content -->
<section class="py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body p-4">
                        <p class="text-muted mb-4"><?php echo __('privacy_last_updated'); ?></p>
                        
                        <h2 class="h4 mb-3"><?php echo __('privacy_section1_title'); ?></h2>
                        <p><?php echo __('privacy_section1_paragraph'); ?></p>

                        <h2 class="h4 mb-3 mt-4"><?php echo __('privacy_section2_title'); ?></h2>
                        <p><?php echo __('privacy_section2_paragraph'); ?></p>
                        <ul>
                            <li><strong><?php echo explode(':', __('privacy_section2_item1'))[0]; ?>:</strong><?php echo explode(':', __('privacy_section2_item1'))[1]; ?></li>
                            <li><strong><?php echo explode(':', __('privacy_section2_item2'))[0]; ?>:</strong><?php echo explode(':', __('privacy_section2_item2'))[1]; ?></li>
                            <li><strong><?php echo explode(':', __('privacy_section2_item3'))[0]; ?>:</strong><?php echo explode(':', __('privacy_section2_item3'))[1]; ?></li>
                            <li><strong><?php echo explode(':', __('privacy_section2_item4'))[0]; ?>:</strong><?php echo explode(':', __('privacy_section2_item4'))[1]; ?></li>
                            <li><strong><?php echo explode(':', __('privacy_section2_item5'))[0]; ?>:</strong><?php echo explode(':', __('privacy_section2_item5'))[1]; ?></li>
                        </ul>

                        <h2 class="h4 mb-3 mt-4"><?php echo __('privacy_section3_title'); ?></h2>
                        <p><?php echo __('privacy_section3_paragraph'); ?></p>
                        <ul>
                            <li><?php echo __('privacy_section3_item1'); ?></li>
                            <li><?php echo __('privacy_section3_item2'); ?></li>
                            <li><?php echo __('privacy_section3_item3'); ?></li>
                            <li><?php echo __('privacy_section3_item4'); ?></li>
                            <li><?php echo __('privacy_section3_item5'); ?></li>
                        </ul>

                        <h2 class="h4 mb-3 mt-4"><?php echo __('privacy_section4_title'); ?></h2>
                        <p><?php echo __('privacy_section4_paragraph'); ?></p>

                        <h2 class="h4 mb-3 mt-4"><?php echo __('privacy_section5_title'); ?></h2>
                        <p><?php echo __('privacy_section5_paragraph'); ?></p>

                        <h2 class="h4 mb-3 mt-4"><?php echo __('privacy_section6_title'); ?></h2>
                        <p><?php echo __('privacy_section6_paragraph'); ?></p>
                        <p class="mb-1">
                            <strong><?php echo __('terms_contact_email_label'); ?></strong> 
                            <a href="mailto:<?php echo __('terms_contact_email'); ?>" class="privacy-contact-link"><?php echo __('terms_contact_email'); ?></a>
                        </p>
                        <p>
                            <strong><?php echo __('terms_contact_phone_label'); ?></strong> 
                            <?php 
                            $phone_str = __('terms_contact_phone');
                            $phones = explode('/', $phone_str);
                            foreach ($phones as $index => $phone) {
                                $phone = trim($phone);
                                $cleanPhone = preg_replace('/[^0-9+]/', '', $phone);
                                echo '<a href="tel:' . $cleanPhone . '" class="privacy-contact-link">' . $phone . '</a>';
                                if ($index < count($phones) - 1) echo ' / ';
                            }
                            ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
