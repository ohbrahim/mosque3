<?php
/**
 * دوال مساعدة كاملة ومحدثة
 */

// التحقق من عدم تعريف الدالة مسبقاً
if (!function_exists('sanitize')) {
    function sanitize($data) {
        if (is_array($data)) {
            return array_map('sanitize', $data);
        }
        return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('generateCSRFToken')) {
    function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('verifyCSRFToken')) {
    function verifyCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}

if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
}

if (!function_exists('isAdmin')) {
    function isAdmin() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }
}

if (!function_exists('hasPermission')) {
    function hasPermission($permission) {
        if (!isLoggedIn()) {
            return false;
        }
        
        // المدير له جميع الصلاحيات
        if (isAdmin()) {
            return true;
        }
        
        // يمكن إضافة منطق صلاحيات أكثر تعقيداً هنا
        return false;
    }
}

if (!function_exists('requireLogin')) {
    function requireLogin() {
        if (!isLoggedIn()) {
            header('Location: ../login.php');
            exit;
        }
    }
}

if (!function_exists('requirePermission')) {
    function requirePermission($permission) {
        if (!hasPermission($permission)) {
            die('ليس لديك صلاحية للوصول لهذه الصفحة');
        }
    }
}

if (!function_exists('getSetting')) {
    function getSetting($db = null, $key = '', $default = '') {
        global $database;
        
        // استخدام قاعدة البيانات العامة إذا لم يتم تمرير واحدة
        $dbConnection = $db ?: $database;
        
        if (!$dbConnection) {
            return $default;
        }
        
        try {
            $result = $dbConnection->fetchOne("SELECT setting_value FROM settings WHERE setting_key = ?", [$key]);
            return $result ? $result['setting_value'] : $default;
        } catch (Exception $e) {
            return $default;
        }
    }
}

if (!function_exists('formatArabicDate')) {
    function formatArabicDate($date) {
        $timestamp = strtotime($date);
        $months = [
            "يناير", "فبراير", "مارس", "أبريل", "مايو", "يونيو",
            "يوليو", "أغسطس", "سبتمبر", "أكتوبر", "نوفمبر", "ديسمبر"
        ];
        
        $day = date('j', $timestamp);
        $month = $months[date('n', $timestamp) - 1];
        $year = date('Y', $timestamp);
        
        return $day . ' ' . $month . ' ' . $year;
    }
}

if (!function_exists('convertToArabicNumbers')) {
    function convertToArabicNumbers($number) {
        $western = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $eastern = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        
        return str_replace($western, $eastern, $number);
    }
}

if (!function_exists('truncateText')) {
    function truncateText($text, $length = 150) {
        $text = strip_tags($text);
        
        if (mb_strlen($text) <= $length) {
            return $text;
        }
        
        $text = mb_substr($text, 0, $length);
        $text = mb_substr($text, 0, mb_strrpos($text, ' '));
        
        return $text . '...';
    }
}

if (!function_exists('getPrayerTimes')) {
    function getPrayerTimes($db = null) {
        // يمكن استبدال هذا بطلب API حقيقي
        return [
            'fajr' => '04:30',
            'dhuhr' => '12:15',
            'asr' => '15:45',
            'maghrib' => '18:30',
            'isha' => '20:00'
        ];
    }
}

// دوال عرض البلوكات
if (!function_exists('renderBlock')) {
    function renderBlock($block) {
        global $db;
        
        $html = '<div class="block-widget ' . ($block['css_class'] ?: '') . '">';
        
        if ($block['show_title'] && !empty($block['title'])) {
            $html .= '<h5 class="block-title mb-3">' . htmlspecialchars($block['title']) . '</h5>';
        }
        
        $html .= '<div class="block-content">';
        
        switch ($block['block_type']) {
            case 'custom':
                $html .= $block['content'];
                break;
                
            case 'prayer_times':
                $html .= renderPrayerTimesBlock();
                break;
                
            case 'weather':
                $html .= renderWeatherBlock();
                break;
                
            case 'recent_pages':
                $html .= renderRecentPagesBlock($db);
                break;
                
            case 'visitor_stats':
                $html .= renderVisitorStatsBlock($db);
                break;
                
            case 'quran_verse':
                $html .= renderQuranVerseBlock();
                break;
                
            case 'hadith':
                $html .= renderHadithBlock();
                break;
                
            case 'social_links':
                $html .= renderSocialLinksBlock($db);
                break;
                
            case 'quick_links':
                $html .= renderQuickLinksBlock($db);
                break;
                
            default:
                $html .= '<p class="text-muted">نوع البلوك غير مدعوم: ' . $block['block_type'] . '</p>';
                break;
        }
        
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }
}

if (!function_exists('renderPrayerTimesBlock')) {
    function renderPrayerTimesBlock() {
        $prayerTimes = getPrayerTimes();
        
        $html = '<div class="prayer-times-list">';
        
        $prayers = [
            'fajr' => 'الفجر',
            'dhuhr' => 'الظهر', 
            'asr' => 'العصر',
            'maghrib' => 'المغرب',
            'isha' => 'العشاء'
        ];
        
        foreach ($prayers as $key => $prayer) {
            $html .= '<div class="prayer-time-item d-flex justify-content-between align-items-center py-2 border-bottom">';
            $html .= '<span class="prayer-name">' . $prayer . '</span>';
            $html .= '<strong class="prayer-time text-primary">' . $prayerTimes[$key] . '</strong>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
}

if (!function_exists('renderWeatherBlock')) {
    function renderWeatherBlock() {
        $temp = rand(20, 35);
        $conditions = ['مشمس', 'غائم جزئياً', 'غائم', 'ممطر'];
        $condition = $conditions[array_rand($conditions)];
        $humidity = rand(30, 80);
        
        $html = '<div class="weather-widget text-center">';
        
        // أيقونة الطقس
        if (strpos($condition, 'مشمس') !== false) {
            $html .= '<i class="fas fa-sun fa-3x text-warning mb-3"></i>';
        } elseif (strpos($condition, 'غائم') !== false) {
            $html .= '<i class="fas fa-cloud fa-3x text-secondary mb-3"></i>';
        } else {
            $html .= '<i class="fas fa-cloud-rain fa-3x text-primary mb-3"></i>';
        }
        
        $html .= '<h3 class="temperature mb-2">' . convertToArabicNumbers($temp) . '°C</h3>';
        $html .= '<p class="condition mb-3">' . $condition . '</p>';
        
        $html .= '<div class="weather-details">';
        $html .= '<small class="text-muted">';
        $html .= '<i class="fas fa-tint me-1"></i> الرطوبة: ' . convertToArabicNumbers($humidity) . '%';
        $html .= '</small>';
        $html .= '</div>';
        
        $html .= '</div>';
        
        return $html;
    }
}

if (!function_exists('renderRecentPagesBlock')) {
    function renderRecentPagesBlock($db) {
        try {
            $pages = $db->fetchAll("SELECT title, slug, created_at FROM pages WHERE status = 'published' ORDER BY created_at DESC LIMIT 5");
            
            if (empty($pages)) {
                return '<p class="text-muted text-center">لا توجد صفحات حديثة</p>';
            }
            
            $html = '<div class="recent-pages-list">';
            
            foreach ($pages as $page) {
                $html .= '<div class="recent-page-item mb-3 pb-2 border-bottom">';
                $html .= '<a href="?page=' . htmlspecialchars($page['slug']) . '" class="text-decoration-none d-block">';
                $html .= '<h6 class="mb-1">' . htmlspecialchars($page['title']) . '</h6>';
                $html .= '<small class="text-muted">' . formatArabicDate($page['created_at']) . '</small>';
                $html .= '</a>';
                $html .= '</div>';
            }
            
            $html .= '</div>';
            
            return $html;
        } catch (Exception $e) {
            return '<p class="text-muted">تعذر جلب الصفحات الحديثة</p>';
        }
    }
}

if (!function_exists('renderVisitorStatsBlock')) {
    function renderVisitorStatsBlock($db) {
        try {
            $today = $db->fetchOne("SELECT COUNT(DISTINCT ip_address) as count FROM visitors WHERE DATE(visit_time) = CURDATE()")['count'] ?? 0;
            $total = $db->fetchOne("SELECT COUNT(DISTINCT ip_address) as count FROM visitors")['count'] ?? 0;
            
            $html = '<div class="visitor-stats">';
            $html .= '<div class="row text-center">';
            
            $html .= '<div class="col-6 mb-3">';
            $html .= '<div class="stat-card p-3 bg-light rounded">';
            $html .= '<h4 class="text-primary mb-1">' . convertToArabicNumbers($today) . '</h4>';
            $html .= '<small class="text-muted">زوار اليوم</small>';
            $html .= '</div>';
            $html .= '</div>';
            
            $html .= '<div class="col-6 mb-3">';
            $html .= '<div class="stat-card p-3 bg-light rounded">';
            $html .= '<h4 class="text-success mb-1">' . convertToArabicNumbers($total) . '</h4>';
            $html .= '<small class="text-muted">إجمالي الزوار</small>';
            $html .= '</div>';
            $html .= '</div>';
            
            $html .= '</div>';
            $html .= '</div>';
            
            return $html;
        } catch (Exception $e) {
            return '<p class="text-muted">تعذر جلب إحصائيات الزوار</p>';
        }
    }
}

if (!function_exists('renderQuranVerseBlock')) {
    function renderQuranVerseBlock() {
        $verses = [
            ['text' => 'إِنَّ اللَّهَ لَا يُضِيعُ أَجْرَ الْمُحْسِنِينَ', 'surah' => 'التوبة', 'ayah' => '120'],
            ['text' => 'وَمَا تَوْفِيقِي إِلَّا بِاللَّهِ عَلَيْهِ تَوَكَّلْتُ وَإِلَيْهِ أُنِيبُ', 'surah' => 'هود', 'ayah' => '88'],
            ['text' => 'رَبَّنَا آتِنَا فِي الدُّنْيَا حَسَنَةً وَفِي الْآخِرَةِ حَسَنَةً وَقِنَا عَذَابَ النَّارِ', 'surah' => 'البقرة', 'ayah' => '201'],
            ['text' => 'إِنَّ مَعَ الْعُسْرِ يُسْرًا', 'surah' => 'الشرح', 'ayah' => '6']
        ];
        
        $verse = $verses[array_rand($verses)];
        
        $html = '<div class="quran-verse text-center">';
        $html .= '<div class="verse-icon mb-3">';
        $html .= '<i class="fas fa-book-open fa-2x text-success"></i>';
        $html .= '</div>';
        $html .= '<div class="verse-text mb-3">';
        $html .= '<p class="fw-bold fs-6 text-dark">' . $verse['text'] . '</p>';
        $html .= '</div>';
        $html .= '<div class="verse-source">';
        $html .= '<small class="text-muted">سورة ' . $verse['surah'] . ' - الآية ' . convertToArabicNumbers($verse['ayah']) . '</small>';
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }
}

if (!function_exists('renderHadithBlock')) {
    function renderHadithBlock() {
        $hadiths = [
            ['text' => 'إنما الأعمال بالنيات، وإنما لكل امرئ ما نوى', 'narrator' => 'متفق عليه'],
            ['text' => 'من حسن إسلام المرء تركه ما لا يعنيه', 'narrator' => 'رواه الترمذي'],
            ['text' => 'المسلم من سلم المسلمون من لسانه ويده', 'narrator' => 'متفق عليه'],
            ['text' => 'لا يؤمن أحدكم حتى يحب لأخيه ما يحب لنفسه', 'narrator' => 'متفق عليه']
        ];
        
        $hadith = $hadiths[array_rand($hadiths)];
        
        $html = '<div class="hadith text-center">';
        $html .= '<div class="hadith-icon mb-3">';
        $html .= '<i class="fas fa-quote-right fa-2x text-primary"></i>';
        $html .= '</div>';
        $html .= '<div class="hadith-text mb-3">';
        $html .= '<p class="fw-bold fs-6 text-dark">' . $hadith['text'] . '</p>';
        $html .= '</div>';
        $html .= '<div class="hadith-source">';
        $html .= '<small class="text-muted">' . $hadith['narrator'] . '</small>';
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }
}

if (!function_exists('renderSocialLinksBlock')) {
    function renderSocialLinksBlock($db) {
        $html = '<div class="social-links text-center">';
        $html .= '<div class="d-flex justify-content-center gap-2">';
        $html .= '<a href="#" class="btn btn-primary btn-sm" title="فيسبوك"><i class="fab fa-facebook-f"></i></a>';
        $html .= '<a href="#" class="btn btn-info btn-sm" title="تويتر"><i class="fab fa-twitter"></i></a>';
        $html .= '<a href="#" class="btn btn-danger btn-sm" title="يوتيوب"><i class="fab fa-youtube"></i></a>';
        $html .= '<a href="#" class="btn btn-success btn-sm" title="واتساب"><i class="fab fa-whatsapp"></i></a>';
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }
}

if (!function_exists('renderQuickLinksBlock')) {
    function renderQuickLinksBlock($db) {
        try {
            $pages = $db->fetchAll("SELECT title, slug FROM pages WHERE status = 'published' AND is_featured = 1 ORDER BY title LIMIT 5");
            
            if (empty($pages)) {
                return '<p class="text-muted text-center">لا توجد روابط سريعة</p>';
            }
            
            $html = '<div class="quick-links">';
            $html .= '<div class="list-group list-group-flush">';
            
            foreach ($pages as $page) {
                $html .= '<a href="?page=' . htmlspecialchars($page['slug']) . '" class="list-group-item list-group-item-action border-0 px-0">';
                $html .= '<i class="fas fa-chevron-left me-2 text-primary"></i>';
                $html .= htmlspecialchars($page['title']);
                $html .= '</a>';
            }
            
            $html .= '</div>';
            $html .= '</div>';
            
            return $html;
        } catch (Exception $e) {
            return '<p class="text-muted">تعذر جلب الروابط السريعة</p>';
        }
    }
}

if (!function_exists('logVisitor')) {
    function logVisitor($db, $page) {
        try {
            $db->insert('visitors', [
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'page_url' => $page,
                'visit_time' => date('Y-m-d H:i:s'),
                'user_id' => $_SESSION['user_id'] ?? null
            ]);
        } catch (Exception $e) {
            error_log("Error logging visitor: " . $e->getMessage());
        }
    }
}
?>
