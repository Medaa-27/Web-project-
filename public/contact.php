<?php
require_once '../includes/config.php';
$title = t('contact_title') . ' - ' . t('site_name');

// Process contact form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    
    $errors = [];
    
    if (empty($name)) $errors[] = t('name_required');
    if (empty($email)) $errors[] = t('email_required');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = t('invalid_email_format');
    if (empty($subject)) $errors[] = t('subject_required');
    if (empty($message)) $errors[] = t('message_required');
    
    if (empty($errors)) {
        // In a real application, you would send an email here
        $_SESSION['success'] = t('contact_success_message');
        header("Location: contact.php");
        exit();
    } else {
        $_SESSION['error'] = implode("<br>", $errors);
    }
}

include '../includes/header.php';
?>

<!-- Contact Page -->
<section class="contact-page py-5">
    <div class="container">
        <!-- Hero Section -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="text-center">
                    <h1 class="display-4 fw-bold mb-4"><?php echo t('contact_us_heading'); ?></h1>
                    <p class="lead text-muted"><?php echo t('contact_us_subtitle'); ?></p>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Contact Form -->
            <div class="col-lg-8 mb-4">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h4 class="card-title mb-4"><?php echo t('send_message_title'); ?></h4>
                        
                        <?php if (isset($_SESSION['success'])): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?php echo $_SESSION['success']; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            <?php unset($_SESSION['success']); ?>
                        <?php endif; ?>
                        
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo $_SESSION['error']; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            <?php unset($_SESSION['error']); ?>
                        <?php endif; ?>
                        
                        <form action="contact.php" method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label"><?php echo t('full_name_label'); ?> *</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label"><?php echo t('email_address_label'); ?> *</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="subject" class="form-label"><?php echo t('subject_label'); ?> *</label>
                                <input type="text" class="form-control" id="subject" name="subject" required>
                            </div>
                            <div class="mb-3">
                                <label for="message" class="form-label"><?php echo t('message_label'); ?> *</label>
                                <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                            </div>
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="copy" name="copy">
                                    <label class="form-check-label" for="copy">
                                        <?php echo t('send_copy_label'); ?>
                                    </label>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-2"></i><?php echo t('send_message_button'); ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="col-lg-4 mb-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="card-title mb-4"><?php echo t('contact_information_title'); ?></h5>
                        
                        <div class="mb-4">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-map-marker-alt text-primary me-3 mt-1"></i>
                                <div>
                                    <h6 class="mb-1"><?php echo t('office_address_label'); ?></h6>
                                    <p class="mb-0 text-muted small">
                                        Aksum City, Tigray Region<br>
                                        Ethiopia
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-phone text-primary me-3 mt-1"></i>
                                <div>
                                    <h6 class="mb-1"><?php echo t('phone_numbers_label'); ?></h6>
                                    <p class="mb-0 text-muted small">
                                        Main: +251 968931862<br>
                                        Support: +251 986764608
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-envelope text-primary me-3 mt-1"></i>
                                <div>
                                    <h6 class="mb-1"><?php echo t('email_addresses_label'); ?></h6>
                                    <p class="mb-0 text-muted small">
                                        General: hagomedhanye85@gmail.com<br>
                                        Support: support@aksumrental.com
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-clock text-primary me-3 mt-1"></i>
                                <div>
                                    <h6 class="mb-1"><?php echo t('business_hours_label'); ?></h6>
                                    <p class="mb-0 text-muted small">
                                        Monday - Friday: 8:00 AM - 6:00 PM<br>
                                        Saturday: 9:00 AM - 4:00 PM<br>
                                        Sunday: Closed
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Social Media -->
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title mb-4"><?php echo t('follow_us_title'); ?></h5>
                        <div class="d-flex gap-3">
                            <a href="#" class="btn btn-outline-primary btn-sm">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="#" class="btn btn-outline-info btn-sm">
                                <i class="fab fa-twitter"></i>
                            </a>
                            <a href="#" class="btn btn-outline-success btn-sm">
                                <i class="fab fa-whatsapp"></i>
                            </a>
                            <a href="#" class="btn btn-outline-danger btn-sm">
                                <i class="fab fa-instagram"></i>
                            </a>
                            <a href="#" class="btn btn-outline-warning btn-sm">
                                <i class="fab fa-telegram"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- FAQ Section -->
        <div class="row mt-5">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h4 class="card-title mb-4"><?php echo t('faq_title'); ?></h4>
                        
                        <div class="accordion" id="faqAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                        How do I list my property on Aksum House Rental System?
                                    </button>
                                </h2>
                                <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        Simply register as a property owner, complete your profile, and click on "Add Property" in your dashboard. Fill in the property details, upload photos, and submit for review. Our team will verify and approve your listing within 24 hours.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                        What are the rental requirements?
                                    </button>
                                </h2>
                                <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        You need to be at least 18 years old, provide valid identification, have proof of income or employment, and pass our background check. Security deposit equivalent to 2 months' rent is required along with the first month's rent.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                        How does the rental process work?
                                    </button>
                                </h2>
                                <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        Browse properties, submit rental requests, get approved by property owner, sign digital agreement, pay security deposit and advance payment, then move in. The entire process is handled through our secure platform.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                        Are there any additional fees?
                                    </button>
                                </h2>
                                <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        No hidden fees! We charge a small service fee to property owners only. Tenants pay exactly what's listed - no booking fees, no processing charges, no hidden costs.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>