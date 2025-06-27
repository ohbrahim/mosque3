<?php
/**
 * ملف الفوتر
 */

// التحقق من المتغيرات
if (!isset($siteName)) {
    $siteName = getSetting($db, 'site_name', 'مسجد الفجر');
}
if (!isset($siteDescription)) {
    $siteDescription = getSetting($db, 'site_description', '');
}

// جلب إعدادات الفوتر
$footerStyle = getSetting($db, 'footer_style', 'modern');
$footerBgColor = getSetting($db, 'footer_bg_color', '#2c3e50');
$footerTextColor = getSetting($db, 'footer_text_color', '#ffffff');
$footerCopyright = getSetting($db, 'footer_copyright', 'جميع الحقوق محفوظة');
$showSocialLinks = getSetting($db, 'show_social_links', '1') == '1';

// جلب عناصر قائمة الفوتر
try {
    $footerMenuItems = $db->fetchAll("SELECT * FROM menu_items WHERE menu_position = 'footer' AND status = 'active' ORDER BY display_order");
} catch (Exception $e) {
    $footerMenuItems = [];
}

// جلب روابط التواصل الاجتماعي
$socialLinks = [
    'facebook' => getSetting($db, 'social_facebook', ''),
    'twitter' => getSetting($db, 'social_twitter', ''),
    'instagram' => getSetting($db, 'social_instagram', ''),
    'youtube' => getSetting($db, 'social_youtube', ''),
    'telegram' => getSetting($db, 'social_telegram', ''),
    'whatsapp' => getSetting($db, 'social_whatsapp', '')
];

// دالة مساعدة لتعديل سطوع اللون
function adjustBrightness($hex, $steps) {
    $steps = max(-255, min(255, $steps));
    $hex = str_replace('#', '', $hex);
    
    if (strlen($hex) == 3) {
        $hex = str_repeat(substr($hex,0,1), 2).str_repeat(substr($hex,1,1), 2).str_repeat(substr($hex,2,1), 2);
    }
    
    $color_parts = str_split($hex, 2);
    $return = '#';
    
    foreach ($color_parts as $color) {
        $color = hexdec($color);
        $color = max(0, min(255, $color + $steps));
        $return .= str_pad(dechex($color), 2, '0', STR_PAD_LEFT);
    }
    
    return $return;
}
?>

<style>:root {
            --primary-color: <?php echo $primaryColor; ?>;
            --secondary-color: <?php echo $secondaryColor; ?>;
        }
        
        body {
            font-family: 'Cairo', sans-serif;
            line-height: 1.6;
        }
        
        .navbar {
            background: #735C5E;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
        }
        
       .hero-section {
        position: relative;
    background: url('uploads/logo.jpg') no-repeat center center;
    background-size: cover;
    color: white;
    padding: 80px 0;
    text-align: center;
    overflow: hidden;
}

.hero-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
  
}

.hero-section .container {
    position: relative; /* ليكون النص فوق الطبقة الشفافة */
    z-index: 2;
}

.hero-section h1 {
    font-size: 3rem;
    font-weight: 700;
    margin-bottom: 20px;
    text-shadow: 0 2px 4px rgba(0,0,0,0.5); /* تحسين قراءة النص */
}

.hero-section p {
    font-size: 1.2rem;
    opacity: 0.9;
    text-shadow: 0 1px 3px rgba(0,0,0,0.5); /* تحسين قراءة النص */
    max-width: 800px;
    margin: 0 auto;
}
        
        .page-content {
            padding: 60px 0;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s;
            margin-bottom: 30px;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .card-img-top {
            border-radius: 15px 15px 0 0;
            height: 200px;
            object-fit: cover;
        }
        
        .sidebar-widget {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .sidebar-widget h5 {
            color: var(--primary-color);
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .footer {
            background: #756364;
            color: white;
            padding: 50px 0 30px;
        }
        
        .footer h5 {
            margin-bottom: 20px;
        }
        
        .footer a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .footer a:hover {
            color: white;
        }
        
        .ad-banner {
            margin: 20px 0;
            text-align: center;
        }
        
        .ad-banner img {
            max-width: 100%;
            height: auto;
            border-radius: 10px;
        }
        
        .poll-widget {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .poll-option {
            margin-bottom: 15px;
        }
        
        .poll-result {
            margin-bottom: 15px;
        }
        
        .poll-progress {
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .poll-progress-bar {
            height: 100%;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            transition: width 0.3s;
        }
        
        .comments-section {
            margin-top: 50px;
            padding-top: 30px;
            border-top: 2px solid #e9ecef;
        }
        
        .comment-item {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .rating-stars {
            color: #ffc107;
            margin: 10px 0;
        }
        
        .prayer-times-widget {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .prayer-time-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }
        
        .prayer-time-item:last-child {
            border-bottom: none;
        }
        
        .block-widget {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .block-widget h5 {
            color: var(--primary-color);
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        /* تنسيقات جديدة للإعلانات الهامة */
        .important-ad {
            border: 2px solid #ffc107;
            background: #fffdf0;
            border-radius: 15px;
            padding: 15px;
            margin-bottom: 20px;
            position: relative;
        }
        
        .important-ad:before {
            content: 'هام';
            position: absolute;
            top: -12px;
            left: 15px;
            background: #ffc107;
            color: #000;
            padding: 2px 10px;
            border-radius: 5px;
            font-weight: bold;
            font-size: 0.8rem;
        }
        
        .important-ad h6 {
            color: #b71c1c;
            font-weight: 700;
            margin-bottom: 10px;
            text-align: center;
        }
        
        .important-ad .ad-content {
            text-align: center;
        }
        
        /* تنسيقات جديدة لشريط الأخبار */
        .news-ticker {
            background: linear-gradient(135deg, #2c5530 0%, #1e3a1e 100%);
            color: white;
            padding: 12px 0;
            overflow: hidden;
            position: relative;
            border-radius: 8px;
            margin-bottom: 25px;
        }
        
        .ticker-header {
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            background: #ffc107;
            color: #000;
            font-weight: bold;
            padding: 0 20px;
            display: flex;
            align-items: center;
            z-index: 2;
        }
        
        .ticker-content {
            padding-left: 120px;
            white-space: nowrap;
            animation: ticker 30s linear infinite;
        }
        
        .ticker-item {
            display: inline-block;
            margin-right: 40px;
            position: relative;
        }
        
        .ticker-item:after {
            content: '•';
            position: absolute;
            right: -25px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255,255,255,0.5);
        }
        
        .ticker-item:last-child:after {
            display: none;
        }
        
        .ticker-item a {
            color: white;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .ticker-item a:hover {
            color: #ffc107;
        }
        
        @keyframes ticker {
            0% { transform: translateX(100%); }
            100% { transform: translateX(-100%); }
        }
</style>

    <!-- Footer Ads -->
    <?php if (!empty($footerAds)): ?>
        <div class="container">
            <?php foreach ($footerAds as $ad): ?>
                <div class="ad-banner">
                    <?php if ($ad['link_url']): ?>
                        <a href="<?php echo htmlspecialchars($ad['link_url']); ?>" target="_blank" 
                           onclick="trackAdClick(<?php echo $ad['id']; ?>)">
                    <?php endif; ?>
                    
                    <?php if ($ad['image']): ?>
                        <img src="uploads/<?php echo htmlspecialchars($ad['image']); ?>" 
                             alt="<?php echo htmlspecialchars($ad['title']); ?>">
                    <?php else: ?>
                        <div class="alert alert-info">
                            <h5><?php echo htmlspecialchars($ad['title']); ?></h5>
                            <?php if ($ad['content']): ?>
                                <p><?php echo htmlspecialchars($ad['content']); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($ad['link_url']): ?>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h5><?php echo htmlspecialchars($siteName); ?></h5>
                    <p><?php echo htmlspecialchars($siteDescription); ?></p>
                    <div class="social-links mt-3">
                        <?php if ($facebook = getSetting($db, 'facebook_url')): ?>
                            <a href="<?php echo htmlspecialchars($facebook); ?>" target="_blank" class="me-2">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($twitter = getSetting($db, 'twitter_url')): ?>
                            <a href="<?php echo htmlspecialchars($twitter); ?>" target="_blank" class="me-2">
                                <i class="fab fa-twitter"></i>
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($instagram = getSetting($db, 'instagram_url')): ?>
                            <a href="<?php echo htmlspecialchars($instagram); ?>" target="_blank" class="me-2">
                                <i class="fab fa-instagram"></i>
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($youtube = getSetting($db, 'youtube_url')): ?>
                            <a href="<?php echo htmlspecialchars($youtube); ?>" target="_blank" class="me-2">
                                <i class="fab fa-youtube"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <h5>روابط سريعة</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php"><i class="fas fa-home me-2"></i>الرئيسية</a></li>
                        <li><a href="?page=about2"><i class="fas fa-info-circle me-2"></i>عن المسجد</a></li>
                        <li><a href="archive.php"><i class="fas fa-hands-helping me-2"></i>أرشيف الصفحات</a></li>
                        <li><a href="?page=events"><i class="fas fa-calendar-alt me-2"></i>الفعاليات</a></li>
                        <li><a href="contact.php"><i class="fas fa-envelope me-2"></i>اتصل بنا</a></li>
                    </ul>
                </div>
                <div class="col-md-4 mb-4">
                    <h5>تواصل معنا</h5>
                    <p>
                        <i class="fas fa-map-marker-alt me-2"></i>
                        <?php echo htmlspecialchars(getSetting($db, 'mosque_address', 'عنوان المسجد')); ?>
                    </p>
                    <p>
                        <i class="fas fa-phone me-2"></i>
                        <?php echo htmlspecialchars(getSetting($db, 'mosque_phone', '0123456789')); ?>
                    </p>
                    <p>
                        <i class="fas fa-envelope me-2"></i>
                        <?php echo htmlspecialchars(getSetting($db, 'mosque_email', 'info@mosque.com')); ?>
                    </p>
                </div>
            </div>
            <hr class="my-4">
            <div class="container">
		    <p style="margin: 0; font-size: 1rem; line-height: 1.6;" align="center">
                تصميم وتركيب واستضافة السيّد: <a href="http://ohbrahim.com/" target="_blank" style="color: #ffd700; text-decoration: none; transition: color 0.3s;">أولاد الحاج إبراهيم إبراهيم</a><br>
                بريد إلكتروني: <a href="mailto:ohbrahim@hotmail.com" style="color: #ffd700; text-decoration: none; transition: color 0.3s;">ohbrahim@hotmail.com</a><br>
                جميع الحقوق محفوظة &copy; <?php echo date("Y"); ?>
            </p>
        </div>
        <div class="container" align="center">
   
        </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
	<!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>