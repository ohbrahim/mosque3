<?php
/**
 * عرض البلوكات والودجات
 */

/**
 * عرض البلوكات حسب الموضع
 */
function renderBlocks($db, $position) {
    try {
        $blocks = $db->fetchAll("
            SELECT * FROM blocks 
            WHERE position = ? AND status = 'active' 
            ORDER BY display_order ASC
        ", [$position]);
        
        $output = '';
        
        foreach ($blocks as $block) {
            $output .= '<div class="block-widget ' . htmlspecialchars($block['css_class']) . '" id="block-' . $block['id'] . '">';
            
            // عرض العنوان إذا كان مطلوباً
            if ($block['show_title'] && !empty($block['title'])) {
                $output .= '<h3 class="block-title">' . htmlspecialchars($block['title']) . '</h3>';
            }
            
            // عرض المحتوى حسب نوع البلوك
            $output .= '<div class="block-content">';
            
            switch ($block['block_type']) {
                case 'custom':
                    // عرض المحتوى المخصص بدون تنظيف HTML
                    $output .= $block['content'];
                    break;
                    
                case 'prayer_times':
                    $output .= renderPrayerTimesBlock($db);
                    break;
                    
                case 'weather':
                    $output .= renderWeatherBlock();
                    break;
                    
                case 'recent_pages':
                    $output .= renderRecentPagesBlock($db);
                    break;
                    
                case 'visitor_stats':
                    $output .= renderVisitorStatsBlock($db);
                    break;
                    
                case 'quran_verse':
                    $output .= renderQuranVerseBlock();
                    break;
                    
                case 'hadith':
                    $output .= renderHadithBlock();
                    break;
                    
                case 'social_links':
                    $output .= renderSocialLinksBlock($db);
                    break;
                    
                case 'quick_links':
                    $output .= renderQuickLinksBlock($db);
                    break;
                    
                default:
                    $output .= $block['content'];
                    break;
            }
            
            $output .= '</div>';
            $output .= '</div>';
        }
        
        return $output;
    } catch (Exception $e) {
        return '';
    }
}

/**
 * عرض بلوك أوقات الصلاة
 */
function renderPrayerTimesBlock($db) {
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

/**
 * عرض بلوك الصفحات الأخيرة
 */
function renderRecentPagesBlock($db) {
    try {
        $pages = $db->fetchAll("SELECT title, slug FROM pages WHERE status = 'published' ORDER BY created_at DESC LIMIT 5");
        
        $html = '<div class="recent-pages-widget">';
        foreach ($pages as $page) {
            $html .= '<div class="recent-page">';
            $html .= '<a href="?page=' . $page['slug'] . '">' . htmlspecialchars($page['title']) . '</a>';
            $html .= '</div>';
        }
        $html .= '</div>';
        
        return $html;
    } catch (Exception $e) {
        return '';
    }
}

/**
 * عرض بلوك الطقس
 */
function renderWeatherBlock() {
    $html = '<div class="weather-widget">';
    $html .= '<div class="weather-icon"><i class="fas fa-sun"></i></div>';
    $html .= '<div class="weather-temp">28°C</div>';
    $html .= '<div class="weather-desc">مشمس</div>';
    $html .= '</div>';
    
    return $html;
}

/**
 * عرض بلوك إحصائيات الزوار
 */
function renderVisitorStatsBlock($db) {
    try {
        $todayVisitors = $db->fetchOne("SELECT COUNT(DISTINCT visitor_ip) as count FROM visitor_stats WHERE DATE(visit_date) = CURDATE()")['count'] ?? 0;
        $totalVisitors = $db->fetchOne("SELECT COUNT(DISTINCT visitor_ip) as count FROM visitor_stats")['count'] ?? 0;
        
        $html = '<div class="visitor-stats-widget">';
        $html .= '<div class="stat-item">';
        $html .= '<span class="stat-label">زوار اليوم:</span>';
        $html .= '<span class="stat-value">' . convertToArabicNumbers($todayVisitors) . '</span>';
        $html .= '</div>';
        $html .= '<div class="stat-item">';
        $html .= '<span class="stat-label">إجمالي الزوار:</span>';
        $html .= '<span class="stat-value">' . convertToArabicNumbers($totalVisitors) . '</span>';
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    } catch (Exception $e) {
        return '';
    }
}

/**
 * عرض بلوك آية قرآنية
 */
function renderQuranVerseBlock() {
    $verses = [
        'وَمَن يَتَّقِ اللَّهَ يَجْعَل لَّهُ مَخْرَجًا',
        'وَاللَّهُ خَيْرٌ حَافِظًا وَهُوَ أَرْحَمُ الرَّاحِمِينَ',
        'وَمَن يَتَوَكَّلْ عَلَى اللَّهِ فَهُوَ حَسْبُهُ'
    ];
    
    $randomVerse = $verses[array_rand($verses)];
    
    $html = '<div class="quran-verse-widget">';
    $html .= '<div class="verse-text">' . $randomVerse . '</div>';
    $html .= '<div class="verse-source">القرآن الكريم</div>';
    $html .= '</div>';
    
    return $html;
}

/**
 * عرض بلوك حديث شريف
 */
function renderHadithBlock() {
    $hadiths = [
        'إنما الأعمال بالنيات',
        'المؤمن للمؤمن كالبنيان يشد بعضه بعضاً',
        'خير الناس أنفعهم للناس'
    ];
    
    $randomHadith = $hadiths[array_rand($hadiths)];
    
    $html = '<div class="hadith-widget">';
    $html .= '<div class="hadith-text">' . $randomHadith . '</div>';
    $html .= '<div class="hadith-source">الحديث الشريف</div>';
    $html .= '</div>';
    
    return $html;
}

/**
 * عرض بلوك روابط التواصل الاجتماعي
 */
function renderSocialLinksBlock($db) {
    $html = '<div class="social-links-widget">';
    $html .= '<a href="#" class="social-link facebook"><i class="fab fa-facebook"></i></a>';
    $html .= '<a href="#" class="social-link twitter"><i class="fab fa-twitter"></i></a>';
    $html .= '<a href="#" class="social-link youtube"><i class="fab fa-youtube"></i></a>';
    $html .= '<a href="#" class="social-link instagram"><i class="fab fa-instagram"></i></a>';
    $html .= '</div>';
    
    return $html;
}

/**
 * عرض بلوك الروابط السريعة
 */
function renderQuickLinksBlock($db) {
    $html = '<div class="quick-links-widget">';
    $html .= '<a href="?page=quran" class="quick-link">القرآن الكريم</a>';
    $html .= '<a href="?page=library" class="quick-link">المكتبة</a>';
    $html .= '<a href="?page=contact" class="quick-link">اتصل بنا</a>';
    $html .= '<a href="?page=donations" class="quick-link">التبرعات</a>';
    $html .= '</div>';
    
    return $html;
}
?>
