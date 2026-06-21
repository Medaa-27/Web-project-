<footer class="footer mt-auto py-4 bg-dark text-white">
    <style>
        html, body {
            height: 100%;
        }
        body {
            display: flex;
            flex-direction: column;
        }
        .main-content {
            flex: 1 0 auto;
        }
        .footer {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%) !important;
            flex-shrink: 0;
        }
        .footer a:hover {
            color: #17a2b8 !important;
            transition: color 0.3s ease;
        }
        .footer .fab {
            transition: transform 0.3s ease;
        }
        .footer .fab:hover {
            transform: scale(1.2);
        }
        .contact-link {
            color: #d1d1d1 !important;
            text-decoration: none !important;
            transition: all 0.3s ease;
            display: inline-block;
        }
        .contact-link:hover {
            color: #17a2b8 !important;
            transform: translateX(5px);
        }
        .footer-contact-list i {
            width: 20px;
            color: #17a2b8;
        }
    </style>
    <div class="container">
        <div class="row">
            <div class="col-lg-4 mb-4">
                <h5 class="mb-3">
                    <i class="fas fa-home me-2"></i><?php echo htmlspecialchars(__('site_name')); ?>
                </h5>
                <p class="small"><?php echo __('footer_brand_description'); ?></p>
            </div>
            <div class="col-lg-2 mb-4">
                <h6 class="mb-3"><?php echo __('footer_quick_links'); ?></h6>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="../public/about.php" class="text-white text-decoration-none"><?php echo __('about'); ?></a></li>
                    <li class="mb-2"><a href="../public/contact.php" class="text-white text-decoration-none"><?php echo __('contact'); ?></a></li>
                    <li class="mb-2"><a href="../public/terms.php" class="text-white text-decoration-none"><?php echo __('terms'); ?></a></li>
                    <li class="mb-2"><a href="../public/privacy.php" class="text-white text-decoration-none"><?php echo __('privacy'); ?></a></li>
                </ul>
            </div>
            <div class="col-lg-3 mb-4">
                <h6 class="mb-3"><?php echo __('contact_information_title'); ?></h6>
                <ul class="list-unstyled small footer-contact-list">
                    <li class="mb-2 d-flex align-items-start">
                        <i class="fas fa-map-marker-alt mt-1 me-2"></i>
                        <span><?php echo __('footer_address'); ?></span>
                    </li>
                    <li class="mb-2 d-flex align-items-start">
                        <i class="fas fa-phone mt-1 me-2"></i>
                        <div>
                            <a href="tel:+251986332683" class="contact-link">+251 98 633 2683</a><br>
                            <a href="tel:+251968931862" class="contact-link">+251 96 893 1862</a>
                        </div>
                    </li>
                    <li class="mb-2 d-flex align-items-start">
                        <i class="fas fa-envelope mt-1 me-2"></i>
                        <a href="mailto:hagomedhanye85@gmail.com" class="contact-link">hagomedhanye85@gmail.com</a>
                    </li>
                </ul>
            </div>
            
            <div class="col-lg-3 mb-4">
                <h6 class="mb-3"><?php echo __('follow_us_title'); ?></h6>
                <div class="d-flex gap-3">
                    <a href="https://www.facebook.com/" target="_blank" rel="noopener noreferrer" class="text-white" title="Facebook"><i class="fab fa-facebook fa-lg"></i></a>
                    <a href="https://t.me/AxumHouseRentalSystem" target="_blank" rel="noopener noreferrer" class="text-white" title="Telegram"><i class="fab fa-telegram fa-lg"></i></a>
                    <a href="https://web.whatsapp.com/" target="_blank" rel="noopener noreferrer" class="text-white" title="WhatsApp"><i class="fab fa-whatsapp fa-lg"></i></a>
                    <a href="https://www.tiktok.com/" target="_blank" rel="noopener noreferrer" class="text-white" title="TikTok"><i class="fab fa-tiktok fa-lg"></i></a>
                    <a href="https://twitter.com/" target="_blank" rel="noopener noreferrer" class="text-white" title="Twitter"><i class="fab fa-twitter fa-lg"></i></a>
                </div>
            </div>
        </div>
        <hr class="my-4 border-secondary">
        <div class="text-center small">
            <p><?php echo sprintf(__('copyright'), date('Y')); ?></p>
            <p><?php echo __('footer_developed_for'); ?></p>
        </div>
    </div>
</footer>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="<?php echo defined('SITE_URL') ? rtrim(SITE_URL, '/') . '/' : ''; ?>assets/js/bootstrap.bundle.min.js"></script>
    <script>
    // fallback for dismissible alerts in case Bootstrap JS isn't handling clicks
    document.addEventListener('DOMContentLoaded', function() {
        var closeButtons = document.querySelectorAll('.alert-dismissible .btn-close');
        closeButtons.forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                var alert = btn.closest('.alert');
                if (alert) {
                    alert.classList.remove('show');
                    // remove after transition (just in case)
                    setTimeout(function(){ alert.remove(); }, 350);
                }
            });
        });
    });
    </script>
    
    <!-- Theme Toggle Script -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const themeToggle = document.getElementById('theme-toggle');
        const themeIcon = document.getElementById('theme-icon');
        const body = document.body;
        
        // Check for saved theme preference or default to light mode
        const currentTheme = localStorage.getItem('theme') || 'light';
        
        // Apply the current theme
        if (currentTheme === 'dark') {
            body.classList.add('dark-mode');
            themeIcon.classList.remove('fa-moon');
            themeIcon.classList.add('fa-sun');
        } else {
            body.classList.remove('dark-mode');
            themeIcon.classList.remove('fa-sun');
            themeIcon.classList.add('fa-moon');
        }
        
        // Toggle theme on button click
        themeToggle.addEventListener('click', function() {
            if (body.classList.contains('dark-mode')) {
                body.classList.remove('dark-mode');
                themeIcon.classList.remove('fa-sun');
                themeIcon.classList.add('fa-moon');
                localStorage.setItem('theme', 'light');
            } else {
                body.classList.add('dark-mode');
                themeIcon.classList.remove('fa-moon');
                themeIcon.classList.add('fa-sun');
                localStorage.setItem('theme', 'dark');
            }
        });
    });
    </script>
    <!-- existing main.js reference removed because file missing -->
</body>
</html>