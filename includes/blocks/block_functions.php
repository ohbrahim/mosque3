<?php
/**
 * دوال عرض البلوكات المحدثة والمحسنة
 */

if (!function_exists('renderBlock')) {
    function renderBlock($block) {
        global $db;
        
        // التأكد من أن المحتوى موجود
        if (empty($block['content'])) {
            return '';
        }
        
        $cssClass = 'block-widget mb-4 ' . (isset($block['css_class']) ? $block['css_class'] : '');
        
        $html = '<div class="' . $cssClass . '" id="block-' . $block['id'] . '">';
        
        // إضافة CSS مخصص
        if (!empty($block['custom_css'])) {
            $html .= '<style>' . $block['custom_css'] . '</style>';
        }
        
        // عرض العنوان إذا كان مطلوباً
        if (isset($block['show_title']) && $block['show_title'] && !empty($block['title'])) {
            $html .= '<div class="block-header mb-3">';
            $html .= '<h5 class="block-title text-primary border-bottom pb-2">';
            $html .= '<i class="fas fa-cube me-2"></i>';
            $html .= htmlspecialchars($block['title']);
            $html .= '</h5>';
            $html .= '</div>';
        }
        
        $html .= '<div class="block-content">';
        
        // عرض المحتوى مباشرة بدون أي تنظيف أو تعديل
        // هذا هو المفتاح - عرض المحتوى كما هو تماماً
        $html .= $block['content'];
        
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }
}

// دالة لجلب إحصائيات الزوار
function getVisitorStats($db) {
    try {
        $today = date('Y-m-d');
        
        // زوار اليوم
        $todayVisitors = $db->fetchOne("SELECT COUNT(DISTINCT ip_address) as count FROM visitor_logs WHERE DATE(visit_time) = ?", [$today]);
        $todayCount = $todayVisitors ? $todayVisitors['count'] : 0;
        
        // إجمالي الزوار
        $totalVisitors = $db->fetchOne("SELECT COUNT(DISTINCT ip_address) as count FROM visitor_logs");
        $totalCount = $totalVisitors ? $totalVisitors['count'] : 0;
        
        return [
            'today' => $todayCount,
            'total' => $totalCount
        ];
    } catch (Exception $e) {
        return [
            'today' => 0,
            'total' => 0
        ];
    }
}

// دالة لجلب حديث عشوائي
function getRandomHadith() {
    $hadiths = [
        [
            'text' => 'إنما الأعمال بالنيات وإنما لكل امرئ ما نوى',
            'source' => 'رواه البخاري ومسلم'
        ],
        [
            'text' => 'المسلم من سلم المسلمون من لسانه ويده',
            'source' => 'رواه البخاري ومسلم'
        ],
        [
            'text' => 'لا يؤمن أحدكم حتى يحب لأخيه ما يحب لنفسه',
            'source' => 'رواه البخاري ومسلم'
        ],
        [
            'text' => 'من كان يؤمن بالله واليوم الآخر فليقل خيراً أو ليصمت',
            'source' => 'رواه البخاري ومسلم'
        ]
    ];
    
    return $hadiths[array_rand($hadiths)];
}

// دالة لجلب آية عشوائية
function getRandomVerse() {
    $verses = [
        [
            'text' => 'وَمَا خَلَقْتُ الْجِنَّ وَالْإِنسَ إِلَّا لِيَعْبُدُونِ',
            'surah' => 'الذاريات',
            'ayah' => '56'
        ],
        [
            'text' => 'إِنَّ اللَّهَ لَا يُضِيعُ أَجْرَ الْمُحْسِنِينَ',
            'surah' => 'التوبة',
            'ayah' => '120'
        ],
        [
            'text' => 'وَمَن يَتَّقِ اللَّهَ يَجْعَل لَّهُ مَخْرَجًا',
            'surah' => 'الطلاق',
            'ayah' => '2'
        ],
        [
            'text' => 'وَاللَّهُ خَيْرٌ حَافِظًا وَهُوَ أَرْحَمُ الرَّاحِمِينَ',
            'surah' => 'يوسف',
            'ayah' => '64'
        ]
    ];
    
    return $verses[array_rand($verses)];
}
?>
