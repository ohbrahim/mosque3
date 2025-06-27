<?php
/**
 * بلوكات محسنة للقرآن والحديث
 */

// بلوك آية قرآنية محسن مع مجموعة كبيرة من الآيات
function renderEnhancedQuranVerseBlock() {
    $verses = [
        ['text' => 'وَمَا خَلَقْتُ الْجِنَّ وَالْإِنسَ إِلَّا لِيَعْبُدُونِ', 'surah' => 'الذاريات', 'ayah' => '56'],
        ['text' => 'إِنَّ اللَّهَ لَا يُضِيعُ أَجْرَ الْمُحْسِنِينَ', 'surah' => 'التوبة', 'ayah' => '120'],
        ['text' => 'وَمَن يَتَّقِ اللَّهَ يَجْعَل لَّهُ مَخْرَجًا', 'surah' => 'الطلاق', 'ayah' => '2'],
        ['text' => 'وَاللَّهُ خَيْرٌ حَافِظًا وَهُوَ أَرْحَمُ الرَّاحِمِينَ', 'surah' => 'يوسف', 'ayah' => '64'],
        ['text' => 'وَمَا تَوْفِيقِي إِلَّا بِاللَّهِ عَلَيْهِ تَوَكَّلْتُ وَإِلَيْهِ أُنِيبُ', 'surah' => 'هود', 'ayah' => '88'],
        ['text' => 'رَبَّنَا آتِنَا فِي الدُّنْيَا حَسَنَةً وَفِي الْآخِرَةِ حَسَنَةً وَقِنَا عَذَابَ النَّارِ', 'surah' => 'البقرة', 'ayah' => '201'],
        ['text' => 'وَاللَّهُ يَرْزُقُ مَن يَشَاءُ بِغَيْرِ حِسَابٍ', 'surah' => 'البقرة', 'ayah' => '212'],
        ['text' => 'إِنَّ مَعَ الْعُسْرِ يُسْرًا', 'surah' => 'الشرح', 'ayah' => '6'],
        ['text' => 'وَلَسَوْفَ يُعْطِيكَ رَبُّكَ فَتَرْضَى', 'surah' => 'الضحى', 'ayah' => '5'],
        ['text' => 'وَقُل رَّبِّ زِدْنِي عِلْمًا', 'surah' => 'طه', 'ayah' => '114'],
        ['text' => 'إِنَّ اللَّهَ مَعَ الصَّابِرِينَ', 'surah' => 'البقرة', 'ayah' => '153'],
        ['text' => 'وَإِذَا سَأَلَكَ عِبَادِي عَنِّي فَإِنِّي قَرِيبٌ', 'surah' => 'البقرة', 'ayah' => '186'],
        ['text' => 'وَاذْكُر رَّبَّكَ فِي نَفْسِكَ تَضَرُّعًا وَخِيفَةً وَدُونَ الْجَهْرِ مِنَ الْقَوْلِ بِالْغُدُوِّ وَالْآصَالِ وَلَا تَكُن مِّنَ الْغَافِلِينَ', 'surah' => 'الأعراف', 'ayah' => '205'],
        ['text' => 'وَمَن يَتَوَكَّلْ عَلَى اللَّهِ فَهُوَ حَسْبُهُ', 'surah' => 'الطلاق', 'ayah' => '3'],
        ['text' => 'فَاذْكُرُونِي أَذْكُرْكُمْ وَاشْكُرُوا لِي وَلَا تَكْفُرُونِ', 'surah' => 'البقرة', 'ayah' => '152']
    ];
    
    // اختيار آية عشوائية
    $verse = $verses[array_rand($verses)];
    
    // إنشاء HTML للبلوك
    $html = '<div class="card border-success">';
    $html .= '<div class="card-body text-center">';
    $html .= '<i class="fas fa-book-open text-success mb-3" style="font-size: 2.5rem;"></i>';
    $html .= '<p class="card-text fs-5 fw-bold text-dark mb-3">' . $verse['text'] . '</p>';
    $html .= '<footer class="blockquote-footer">';
    $html .= 'سورة <cite title="' . $verse['surah'] . '">' . $verse['surah'] . '</cite> - آية ' . $verse['ayah'];
    $html .= '</footer>';
    $html .= '</div>';
    $html .= '</div>';
    
    return $html;
}

// بلوك حديث محسن مع مجموعة كبيرة من الأحاديث
function renderEnhancedHadithBlock() {
    $hadiths = [
        ['text' => 'إنما الأعمال بالنيات، وإنما لكل امرئ ما نوى', 'narrator' => 'متفق عليه'],
        ['text' => 'من حسن إسلام المرء تركه ما لا يعنيه', 'narrator' => 'رواه الترمذي'],
        ['text' => 'المسلم من سلم المسلمون من لسانه ويده', 'narrator' => 'متفق عليه'],
        ['text' => 'لا يؤمن أحدكم حتى يحب لأخيه ما يحب لنفسه', 'narrator' => 'متفق عليه'],
        ['text' => 'الدين النصيحة', 'narrator' => 'رواه مسلم'],
        ['text' => 'من كان يؤمن بالله واليوم الآخر فليقل خيراً أو ليصمت', 'narrator' => 'متفق عليه'],
        ['text' => 'لا تغضب', 'narrator' => 'رواه البخاري'],
        ['text' => 'إن الله رفيق يحب الرفق في الأمر كله', 'narrator' => 'متفق عليه'],
        ['text' => 'من لا يرحم الناس لا يرحمه الله', 'narrator' => 'متفق عليه'],
        ['text' => 'الكلمة الطيبة صدقة', 'narrator' => 'متفق عليه'],
        ['text' => 'طلب العلم فريضة على كل مسلم', 'narrator' => 'رواه ابن ماجه'],
        ['text' => 'خيركم من تعلم القرآن وعلمه', 'narrator' => 'رواه البخاري'],
        ['text' => 'من سلك طريقاً يلتمس فيه علماً سهل الله له به طريقاً إلى الجنة', 'narrator' => 'رواه مسلم'],
        ['text' => 'اتق الله حيثما كنت، وأتبع السيئة الحسنة تمحها، وخالق الناس بخلق حسن', 'narrator' => 'رواه الترمذي'],
        ['text' => 'ازهد في الدنيا يحبك الله، وازهد فيما عند الناس يحبك الناس', 'narrator' => 'رواه ابن ماجه']
    ];
    
    // اختيار حديث عشوائي
    $hadith = $hadiths[array_rand($hadiths)];
    
    // إنشاء HTML للبلوك
    $html = '<div class="card border-warning">';
    $html .= '<div class="card-body text-center">';
    $html .= '<i class="fas fa-quote-right text-warning mb-3" style="font-size: 2rem;"></i>';
    $html .= '<p class="fs-6 fw-bold text-dark mb-3">' . $hadith['text'] . '</p>';
    $html .= '<small class="text-muted">' . $hadith['narrator'] . '</small>';
    $html .= '</div>';
    $html .= '</div>';
    
    return $html;
}

// بلوك إحصائيات الزوار المحسن
function renderEnhancedVisitorStatsBlock($db) {
    try {
        // جلب إحصائيات الزوار
        $today = $db->fetchOne("SELECT COUNT(DISTINCT ip_address) as count FROM visitors WHERE DATE(visit_time) = CURDATE()")['count'] ?? 0;
        $yesterday = $db->fetchOne("SELECT COUNT(DISTINCT ip_address) as count FROM visitors WHERE DATE(visit_time) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)")['count'] ?? 0;
        $thisMonth = $db->fetchOne("SELECT COUNT(DISTINCT ip_address) as count FROM visitors WHERE MONTH(visit_time) = MONTH(CURDATE()) AND YEAR(visit_time) = YEAR(CURDATE())")['count'] ?? 0;
        $total = $db->fetchOne("SELECT COUNT(DISTINCT ip_address) as count FROM visitors")['count'] ?? 0;
        
        // إنشاء HTML للبلوك
        $html = '<div class="card border-info">';
        $html .= '<div class="card-body">';
        $html .= '<div class="row text-center">';
        
        // زوار اليوم
        $html .= '<div class="col-6">';
        $html .= '<i class="fas fa-users fa-2x text-info mb-2"></i>';
        $html .= '<h6>زوار اليوم</h6>';
        $html .= '<h4 class="text-primary">' . convertToArabicNumbers($today) . '</h4>';
        $html .= '</div>';
        
        // إجمالي الزوار
        $html .= '<div class="col-6">';
        $html .= '<i class="fas fa-chart-line fa-2x text-success mb-2"></i>';
        $html .= '<h6>إجمالي الزوار</h6>';
        $html .= '<h4 class="text-success">' . convertToArabicNumbers($total) . '</h4>';
        $html .= '</div>';
        
        $html .= '</div>';
        
        // الصف الثاني
        $html .= '<div class="row text-center mt-3">';
        
        // زوار الأمس
        $html .= '<div class="col-6">';
        $html .= '<i class="fas fa-calendar-day fa-lg text-secondary mb-1"></i>';
        $html .= '<h6>زوار الأمس</h6>';
        $html .= '<h5 class="text-secondary">' . convertToArabicNumbers($yesterday) . '</h5>';
        $html .= '</div>';
        
        // زوار الشهر
        $html .= '<div class="col-6">';
        $html .= '<i class="fas fa-calendar-alt fa-lg text-warning mb-1"></i>';
        $html .= '<h6>زوار الشهر</h6>';
        $html .= '<h5 class="text-warning">' . convertToArabicNumbers($thisMonth) . '</h5>';
        $html .= '</div>';
        
        $html .= '</div>';
        
        // آخر تحديث
        $html .= '<div class="row text-center mt-3">';
        $html .= '<div class="col-12">';
        $html .= '<i class="fas fa-clock fa-lg text-muted me-2"></i>';
        $html .= '<small class="text-muted">آخر تحديث: ' . date('H:i') . '</small>';
        $html .= '</div>';
        $html .= '</div>';
        
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    } catch (Exception $e) {
        return '<div class="alert alert-warning">تعذر جلب إحصائيات الزوار: ' . $e->getMessage() . '</div>';
    }
}

// بلوك روابط التواصل الاجتماعي المحسن
function renderEnhancedSocialLinksBlock($db) {
    $facebook = getSetting($db, 'facebook_url');
    $twitter = getSetting($db, 'twitter_url');
    $instagram = getSetting($db, 'instagram_url');
    $youtube = getSetting($db, 'youtube_url');
    $telegram = getSetting($db, 'telegram_url');
    $whatsapp = getSetting($db, 'whatsapp_url');
    
    $html = '<div class="card">';
    $html .= '<div class="card-body">';
    
    if ($facebook || $twitter || $instagram || $youtube || $telegram || $whatsapp) {
        $html .= '<div class="d-flex flex-wrap justify-content-center gap-4 py-2">';
        
        if ($facebook) {
            $html .= '<a href="' . htmlspecialchars($facebook) . '" target="_blank" class="text-decoration-none">';
            $html .= '<div class="text-center">';
            $html .= '<i class="fab fa-facebook fa-2x text-primary"></i>';
            $html .= '<p class="mb-0 mt-1">فيسبوك</p>';
            $html .= '</div>';
            $html .= '</a>';
        }
        
        if ($twitter) {
            $html .= '<a href="' . htmlspecialchars($twitter) . '" target="_blank" class="text-decoration-none">';
            $html .= '<div class="text-center">';
            $html .= '<i class="fab fa-twitter fa-2x text-info"></i>';
            $html .= '<p class="mb-0 mt-1">تويتر</p>';
            $html .= '</div>';
            $html .= '</a>';
        }
        
        if ($instagram) {
            $html .= '<a href="' . htmlspecialchars($instagram) . '" target="_blank" class="text-decoration-none">';
            $html .= '<div class="text-center">';
            $html .= '<i class="fab fa-instagram fa-2x text-danger"></i>';
            $html .= '<p class="mb-0 mt-1">انستغرام</p>';
            $html .= '</div>';
            $html .= '</a>';
        }
        
        if ($youtube) {
            $html .= '<a href="' . htmlspecialchars($youtube) . '" target="_blank" class="text-decoration-none">';
            $html .= '<div class="text-center">';
            $html .= '<i class="fab fa-youtube fa-2x text-danger"></i>';
            $html .= '<p class="mb-0 mt-1">يوتيوب</p>';
            $html .= '</div>';
            $html .= '</a>';
        }
        
        if ($telegram) {
            $html .= '<a href="' . htmlspecialchars($telegram) . '" target="_blank" class="text-decoration-none">';
            $html .= '<div class="text-center">';
            $html .= '<i class="fab fa-telegram fa-2x text-info"></i>';
            $html .= '<p class="mb-0 mt-1">تلغرام</p>';
            $html .= '</div>';
            $html .= '</a>';
        }
        
        if ($whatsapp) {
            $html .= '<a href="' . htmlspecialchars($whatsapp) . '" target="_blank" class="text-decoration-none">';
            $html .= '<div class="text-center">';
            $html .= '<i class="fab fa-whatsapp fa-2x text-success"></i>';
            $html .= '<p class="mb-0 mt-1">واتساب</p>';
            $html .= '</div>';
            $html .= '</a>';
        }
        
        $html .= '</div>';
    } else {
        $html .= '<p class="text-center text-muted">لم يتم تعيين روابط التواصل الاجتماعي بعد</p>';
    }
    
    $html .= '</div>';
    $html .= '</div>';
    
    return $html;
}

// بلوك الصفحات الحديثة المحسن
function renderEnhancedRecentPagesBlock($db) {
    try {
        $pages = $db->fetchAll("SELECT * FROM pages WHERE status = 'published' ORDER BY created_at DESC LIMIT 5");
        
        $html = '<div class="card">';
        $html .= '<div class="card-body p-0">';
        $html .= '<ul class="list-group list-group-flush">';
        
        if (empty($pages)) {
            $html .= '<li class="list-group-item text-center text-muted">لا توجد صفحات حديثة</li>';
        } else {
            foreach ($pages as $page) {
                $html .= '<li class="list-group-item">';
                $html .= '<a href="?page=' . htmlspecialchars($page['slug']) . '" class="text-decoration-none d-flex justify-content-between align-items-center">';
                $html .= '<span><i class="fas fa-file-alt text-primary me-2"></i>' . htmlspecialchars($page['title']) . '</span>';
                $html .= '<small class="text-muted">' . formatArabicDate($page['created_at'], true) . '</small>';
                $html .= '</a>';
                $html .= '</li>';
            }
        }
        
        $html .= '</ul>';
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    } catch (Exception $e) {
        return '<div class="alert alert-warning">تعذر جلب الصفحات الحديثة: ' . $e->getMessage() . '</div>';
    }
}
?>
