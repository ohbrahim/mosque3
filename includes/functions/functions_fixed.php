<?php
/**
 * دوال مساعدة محدثة ومصححة
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
    function getSetting($db, $key, $default = '') {
        global $db as $globalDb;
        
        // استخدام قاعدة البيانات العامة إذا لم يتم تمرير واحدة
        $database = $db ?: $globalDb;
        
        if (!$database) {
            return $default;
        }
        
        try {
            $result = $database->fetchOne("SELECT setting_value FROM settings WHERE setting_key = ?", [$key]);
            return $result ? $result['setting_value'] : $default;
        } catch (Exception $e) {
            return $default;
        }
    }
}

if (!function_exists('updateSetting')) {
    function updateSetting($db, $key, $value) {
        try {
            $existing = $db->fetchOne("SELECT id FROM settings WHERE setting_key = ?", [$key]);
            
            if ($existing) {
                return $db->update('settings', ['setting_value' => $value], 'setting_key = ?', [$key]);
            } else {
                return $db->insert('settings', [
                    'setting_key' => $key,
                    'setting_value' => $value,
                    'display_name' => $key
                ]);
            }
        } catch (Exception $e) {
            return false;
        }
    }
}

if (!function_exists('logVisitor')) {
    function logVisitor($db, $page) {
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            $db->insert('visitors', [
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'page_url' => $page,
                'visit_time' => date('Y-m-d H:i:s'),
                'user_id' => $_SESSION['user_id'] ?? null
            ]);
        } catch (Exception $e) {
            // تجاهل الأخطاء
        }
    }
}

if (!function_exists('updatePageViews')) {
    function updatePageViews($db, $pageId) {
        try {
            $db->query("UPDATE pages SET views_count = views_count + 1 WHERE id = ?", [$pageId]);
        } catch (Exception $e) {
            // تجاهل الأخطاء
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

if (!function_exists('renderPrayerTimesBlock')) {
    function renderPrayerTimesBlock($db = null) {
        $prayerTimes = getPrayerTimes($db);
        
        $html = '<div class="prayer-times-widget">';
        $html .= '<div class="prayer-time"><span>الفجر:</span> <strong>' . $prayerTimes['fajr'] . '</strong></div>';
        $html .= '<div class="prayer-time"><span>الظهر:</span> <strong>' . $prayerTimes['dhuhr'] . '</strong></div>';
        $html .= '<div class="prayer-time"><span>العصر:</span> <strong>' . $prayerTimes['asr'] . '</strong></div>';
        $html .= '<div class="prayer-time"><span>المغرب:</span> <strong>' . $prayerTimes['maghrib'] . '</strong></div>';
        $html .= '<div class="prayer-time"><span>العشاء:</span> <strong>' . $prayerTimes['isha'] . '</strong></div>';
        $html .= '</div>';
        
        return $html;
    }
}

if (!function_exists('renderWeatherBlock')) {
    function renderWeatherBlock() {
        $html = '<div class="weather-widget text-center">';
        $html .= '<div class="weather-icon mb-2"><i class="fas fa-sun fa-2x text-warning"></i></div>';
        $html .= '<div class="weather-temp h4">28°C</div>';
        $html .= '<div class="weather-desc">مشمس</div>';
        $html .= '</div>';
        
        return $html;
    }
}

if (!function_exists('renderRecentPagesBlock')) {
    function renderRecentPagesBlock($db) {
        try {
            $pages = $db->fetchAll("SELECT title, slug FROM pages WHERE status = 'published' ORDER BY created_at DESC LIMIT 5");
            
            $html = '<div class="recent-pages-widget">';
            if (empty($pages)) {
                $html .= '<p class="text-muted">لا توجد صفحات حديثة</p>';
            } else {
                foreach ($pages as $page) {
                    $html .= '<div class="recent-page mb-2">';
                    $html .= '<a href="?page=' . htmlspecialchars($page['slug']) . '" class="text-decoration-none">';
                    $html .= htmlspecialchars($page['title']);
                    $html .= '</a>';
                    $html .= '</div>';
                }
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
            $todayVisitors = $db->fetchOne("SELECT COUNT(DISTINCT ip_address) as count FROM visitors WHERE DATE(visit_time) = CURDATE()")['count'] ?? 0;
            $totalVisitors = $db->fetchOne("SELECT COUNT(DISTINCT ip_address) as count FROM visitors")['count'] ?? 0;
            
            $html = '<div class="visitor-stats-widget">';
            $html .= '<div class="stat-item d-flex justify-content-between mb-2">';
            $html .= '<span class="stat-label">زوار اليوم:</span>';
            $html .= '<span class="stat-value badge bg-primary">' . convertToArabicNumbers($todayVisitors) . '</span>';
            $html .= '</div>';
            $html .= '<div class="stat-item d-flex justify-content-between">';
            $html .= '<span class="stat-label">إجمالي الزوار:</span>';
            $html .= '<span class="stat-value badge bg-success">' . convertToArabicNumbers($totalVisitors) . '</span>';
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
            ['text' => 'وَمَن يَتَّقِ اللَّهَ يَجْعَل لَّهُ مَخْرَجًا', 'surah' => 'الطلاق', 'ayah' => '2'],
            ['text' => 'وَاللَّهُ خَيْرٌ حَافِظًا وَهُوَ أَرْحَمُ الرَّاحِمِينَ', 'surah' => 'يوسف', 'ayah' => '64'],
            ['text' => 'وَمَن يَتَوَكَّلْ عَلَى اللَّهِ فَهُوَ حَسْبُهُ', 'surah' => 'الطلاق', 'ayah' => '3']
        ];
        
        $randomVerse = $verses[array_rand($verses)];
        
        $html = '<div class="quran-verse-widget text-center">';
        $html .= '<div class="verse-icon mb-2"><i class="fas fa-book-open fa-2x text-success"></i></div>';
        $html .= '<div class="verse-text mb-2 fw-bold">' . $randomVerse['text'] . '</div>';
        $html .= '<div class="verse-source text-muted small">سورة ' . $randomVerse['surah'] . ' - الآية ' . convertToArabicNumbers($randomVerse['ayah']) . '</div>';
        $html .= '</div>';
        
        return $html;
    }
}

if (!function_exists('renderHadithBlock')) {
    function renderHadithBlock() {
        $hadiths = [
            ['text' => 'إنما الأعمال بالنيات', 'narrator' => 'متفق عليه'],
            ['text' => 'المؤمن للمؤمن كالبنيان يشد بعضه بعضاً', 'narrator' => 'متفق عليه'],
            ['text' => 'خير الناس أنفعهم للناس', 'narrator' => 'رواه الطبراني']
        ];
        
        $randomHadith = $hadiths[array_rand($hadiths)];
        
        $html = '<div class="hadith-widget text-center">';
        $html .= '<div class="hadith-icon mb-2"><i class="fas fa-quote-right fa-2x text-primary"></i></div>';
        $html .= '<div class="hadith-text mb-2 fw-bold">' . $randomHadith['text'] . '</div>';
        $html .= '<div class="hadith-source text-muted small">' . $randomHadith['narrator'] . '</div>';
        $html .= '</div>';
        
        return $html;
    }
}

if (!function_exists('renderSocialLinksBlock')) {
    function renderSocialLinksBlock($db) {
        $html = '<div class="social-links-widget text-center">';
        $html .= '<div class="d-flex justify-content-center gap-2">';
        $html .= '<a href="#" class="btn btn-primary btn-sm"><i class="fab fa-facebook"></i></a>';
        $html .= '<a href="#" class="btn btn-info btn-sm"><i class="fab fa-twitter"></i></a>';
        $html .= '<a href="#" class="btn btn-danger btn-sm"><i class="fab fa-youtube"></i></a>';
        $html .= '<a href="#" class="btn btn-warning btn-sm"><i class="fab fa-instagram"></i></a>';
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }
}

if (!function_exists('renderQuickLinksBlock')) {
    function renderQuickLinksBlock($db) {
        $html = '<div class="quick-links-widget">';
        $html .= '<div class="list-group list-group-flush">';
        $html .= '<a href="?page=quran" class="list-group-item list-group-item-action"><i class="fas fa-book me-2"></i>القرآن الكريم</a>';
        $html .= '<a href="?page=library" class="list-group-item list-group-item-action"><i class="fas fa-book-reader me-2"></i>المكتبة</a>';
        $html .= '<a href="?page=contact" class="list-group-item list-group-item-action"><i class="fas fa-envelope me-2"></i>اتصل بنا</a>';
        $html .= '<a href="?page=donations" class="list-group-item list-group-item-action"><i class="fas fa-hand-holding-heart me-2"></i>التبرعات</a>';
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }
}
?>
