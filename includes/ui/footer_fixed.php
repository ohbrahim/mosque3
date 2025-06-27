<?php
/**
 * ملف الفوتر المحدث
 */

// التحقق من المتغيرات
if (!isset($siteName)) {
    $siteName = getSetting($db, 'site_name', 'مسجد النور');
}
if (!isset($siteDescription)) {
    $siteDescription = getSetting($db, 'site_description', 'موقع مسجد النور الرسمي');
}

// جلب إعدادات الفوتر
$footerBgColor = getSetting($db, 'footer_bg_color', '#2c3e50');
$footerTextColor = getSetting($db, 'footer_text_color', '#ffffff');
$footerCopyright = getSetting($db, 'footer_copyright', 'جميع الحقوق محفوظة');
$showSocialLinks = getSetting($db, 'show_social_links', '1') == '1';

// جلب روابط التواصل الاجتماعي
$socialLinks = [
    'facebook' => getSetting($db, 'social_facebook', ''),
    'twitter' => getSetting($db, 'social_twitter', ''),
    'instagram' => getSetting($db, 'social_instagram', ''),
    'youtube' => getSetting($db, 'social_youtube', ''),
    'telegram' => getSetting($db, 'social_telegram', ''),
    'whatsapp' => getSetting($db, 'social_whatsapp', '')
];
?>

<footer class="bg-dark text-light py-5 mt-5">
    <div class="container">
        <div class="row">
            <!-- معلومات المسجد -->
            <div class="col-lg-4 mb-4">
                <h5 class="mb-3"><?php echo htmlspecialchars($siteName); ?></h5>
                <p class="mb-3"><?php echo htmlspecialchars($siteDescription); ?></p>
                
                <?php if ($showSocialLinks && array_filter($socialLinks)): ?>
                    <div class="social-links">
                        <h6 class="mb-2">تابعونا على:</h6>
                        <div class="d-flex gap-2">
                            <?php foreach ($socialLinks as $platform => $url): ?>
                                <?php if ($url): ?>
                                    <a href="<?php echo htmlspecialchars($url); ?>" target="_blank" 
                                       class="btn btn-outline-light btn-sm" title="<?php echo ucfirst($platform); ?>">
                                        <i class="fab fa-<?php echo $platform; ?>"></i>
                                    </a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- معلومات التواصل -->
            <div class="col-lg-4 mb-4">
                <h5 class="mb-3">معلومات التواصل</h5>
                <div class="contact-info">
                    <p class="mb-2">
                        <i class="fas fa-envelope me-2"></i>
                        <?php echo htmlspecialchars(getSetting($db, 'contact_email', 'info@mosque.com')); ?>
                    </p>
                    <p class="mb-2">
                        <i class="fas fa-phone me-2"></i>
                        <?php echo htmlspecialchars(getSetting($db, 'contact_phone', '+213123456789')); ?>
                    </p>
                    <p class="mb-2">
                        <i class="fas fa-map-marker-alt me-2"></i>
                        <?php echo htmlspecialchars(getSetting($db, 'contact_address', 'الجزائر العاصمة، الجزائر')); ?>
                    </p>
                </div>
            </div>
            
            <!-- روابط سريعة -->
            <div class="col-lg-4 mb-4">
                <h5 class="mb-3">روابط سريعة</h5>
                <div class="quick-links">
                    <div class="row">
                        <div class="col-6">
                            <ul class="list-unstyled">
                                <li class="mb-2"><a href="index.php" class="text-light text-decoration-none"><i class="fas fa-home me-2"></i>الرئيسية</a></li>
                                <li class="mb-2"><a href="?page=about2" class="text-light text-decoration-none"><i class="fas fa-info-circle me-2"></i>عن المسجد</a></li>
                                <li class="mb-2"><a href="?page=services" class="text-light text-decoration-none"><i class="fas fa-hands-helping me-2"></i>الخدمات</a></li>
                            </ul>
                        </div>
                        <div class="col-6">
                            <ul class="list-unstyled">
                                <li class="mb-2"><a href="?page=events" class="text-light text-decoration-none"><i class="fas fa-calendar-alt me-2"></i>الفعاليات</a></li>
                                <li class="mb-2"><a href="?page=contact" class="text-light text-decoration-none"><i class="fas fa-envelope me-2"></i>اتصل بنا</a></li>
                                <li class="mb-2"><a href="?page=donations" class="text-light text-decoration-none"><i class="fas fa-hand-holding-heart me-2"></i>التبرعات</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- حقوق النشر -->
        <hr class="my-4">
        <div class="row align-items-center">
            <div class="col-md-6">
                <p class="mb-0">
                    &copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($siteName); ?>. 
                    <?php echo htmlspecialchars($footerCopyright); ?>
                </p>
            </div>
            <div class="col-md-6 text-md-end">
                <p class="mb-0">
                    <small class="text-muted">تم التطوير بواسطة فريق التطوير</small>
                </p>
            </div>
        </div>
    </div>
</footer>

<style>
.footer-links a:hover {
    color: #fff !important;
    text-decoration: underline !important;
}

.social-links .btn:hover {
    background-color: rgba(255,255,255,0.2);
    border-color: rgba(255,255,255,0.3);
}
</style>
