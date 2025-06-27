<?php
/**
 * بلوكات محسنة الإصدار الثاني - مع المزيد من الآيات والأحاديث
 */

// بلوك آية قرآنية محسن مع مجموعة كبيرة جداً من الآيات
function renderEnhancedQuranVerseBlock() {
    $verses = [
        // آيات التوحيد والإيمان
        ['text' => 'وَمَا خَلَقْتُ الْجِنَّ وَالْإِنسَ إِلَّا لِيَعْبُدُونِ', 'surah' => 'الذاريات', 'ayah' => '56'],
        ['text' => 'قُلْ هُوَ اللَّهُ أَحَدٌ * اللَّهُ الصَّمَدُ * لَمْ يَلِدْ وَلَمْ يُولَدْ * وَلَمْ يَكُن لَّهُ كُفُوًا أَحَدٌ', 'surah' => 'الإخلاص', 'ayah' => '1-4'],
        ['text' => 'اللَّهُ لَا إِلَٰهَ إِلَّا هُوَ الْحَيُّ الْقَيُّومُ', 'surah' => 'البقرة', 'ayah' => '255'],
        ['text' => 'وَإِلَٰهُكُمْ إِلَٰهٌ وَاحِدٌ لَّا إِلَٰهَ إِلَّا هُوَ الرَّحْمَٰنُ الرَّحِيمُ', 'surah' => 'البقرة', 'ayah' => '163'],
        
        // آيات الرحمة والمغفرة
        ['text' => 'إِنَّ اللَّهَ لَا يُضِيعُ أَجْرَ الْمُحْسِنِينَ', 'surah' => 'التوبة', 'ayah' => '120'],
        ['text' => 'وَرَحْمَتِي وَسِعَتْ كُلَّ شَيْءٍ', 'surah' => 'الأعراف', 'ayah' => '156'],
        ['text' => 'قُل يَا عِبَادِيَ الَّذِينَ أَسْرَفُوا عَلَىٰ أَنفُسِهِمْ لَا تَقْنَطُوا مِن رَّحْمَةِ اللَّهِ', 'surah' => 'الزمر', 'ayah' => '53'],
        ['text' => 'وَمَن يَعْمَلْ مِنَ الصَّالِحَاتِ وَهُوَ مُؤْمِنٌ فَلَا يَخَافُ ظُلْمًا وَلَا هَضْمًا', 'surah' => 'طه', 'ayah' => '112'],
        
        // آيات التوكل والثقة بالله
        ['text' => 'وَمَن يَتَّقِ اللَّهَ يَجْعَل لَّهُ مَخْرَجًا', 'surah' => 'الطلاق', 'ayah' => '2'],
        ['text' => 'وَمَن يَتَوَكَّلْ عَلَى اللَّهِ فَهُوَ حَسْبُهُ', 'surah' => 'الطلاق', 'ayah' => '3'],
        ['text' => 'وَمَا تَوْفِيقِي إِلَّا بِاللَّهِ عَلَيْهِ تَوَكَّلْتُ وَإِلَيْهِ أُنِيبُ', 'surah' => 'هود', 'ayah' => '88'],
        ['text' => 'حَسْبُنَا اللَّهُ وَنِعْمَ الْوَكِيلُ', 'surah' => 'آل عمران', 'ayah' => '173'],
        
        // آيات الصبر والابتلاء
        ['text' => 'إِنَّ مَعَ الْعُسْرِ يُسْرًا', 'surah' => 'الشرح', 'ayah' => '6'],
        ['text' => 'وَلَنَبْلُوَنَّكُم بِشَيْءٍ مِّنَ الْخَوْفِ وَالْجُوعِ وَنَقْصٍ مِّنَ الْأَمْوَالِ وَالْأَنفُسِ وَالثَّمَرَاتِ وَبَشِّرِ الصَّابِرِينَ', 'surah' => 'البقرة', 'ayah' => '155'],
        ['text' => 'إِنَّ اللَّهَ مَعَ الصَّابِرِينَ', 'surah' => 'البقرة', 'ayah' => '153'],
        ['text' => 'وَاصْبِرْ وَمَا صَبْرُكَ إِلَّا بِاللَّهِ', 'surah' => 'النحل', 'ayah' => '127'],
        
        // آيات الدعاء والذكر
        ['text' => 'وَإِذَا سَأَلَكَ عِبَادِي عَنِّي فَإِنِّي قَرِيبٌ أُجِيبُ دَعْوَةَ الدَّاعِ إِذَا دَعَانِ', 'surah' => 'البقرة', 'ayah' => '186'],
        ['text' => 'فَاذْكُرُونِي أَذْكُرْكُمْ وَاشْكُرُوا لِي وَلَا تَكْفُرُونِ', 'surah' => 'البقرة', 'ayah' => '152'],
        ['text' => 'وَاذْكُر رَّبَّكَ فِي نَفْسِكَ تَضَرُّعًا وَخِيفَةً', 'surah' => 'الأعراف', 'ayah' => '205'],
        ['text' => 'ادْعُونِي أَسْتَجِبْ لَكُمْ', 'surah' => 'غافر', 'ayah' => '60'],
        
        // آيات العلم والحكمة
        ['text' => 'وَقُل رَّبِّ زِدْنِي عِلْمًا', 'surah' => 'طه', 'ayah' => '114'],
        ['text' => 'يَرْفَعِ اللَّهُ الَّذِينَ آمَنُوا مِنكُمْ وَالَّذِينَ أُوتُوا الْعِلْمَ دَرَجَاتٍ', 'surah' => 'المجادلة', 'ayah' => '11'],
        ['text' => 'وَمَا أُوتِيتُم مِّنَ الْعِلْمِ إِلَّا قَلِيلًا', 'surah' => 'الإسراء', 'ayah' => '85'],
        
        // آيات الرزق والكفاف
        ['text' => 'وَاللَّهُ يَرْزُقُ مَن يَشَاءُ بِغَيْرِ حِسَابٍ', 'surah' => 'البقرة', 'ayah' => '212'],
        ['text' => 'وَمَا مِن دَابَّةٍ فِي الْأَرْضِ إِلَّا عَلَى اللَّهِ رِزْقُهَا', 'surah' => 'هود', 'ayah' => '6'],
        ['text' => 'وَفِي السَّمَاءِ رِزْقُكُمْ وَمَا تُوعَدُونَ', 'surah' => 'الذاريات', 'ayah' => '22'],
        
        // آيات الأخلاق والمعاملة
        ['text' => 'وَقُولُوا لِلنَّاسِ حُسْنًا', 'surah' => 'البقرة', 'ayah' => '83'],
        ['text' => 'وَلَا تَسْتَوِي الْحَسَنَةُ وَلَا السَّيِّئَةُ ادْفَعْ بِالَّتِي هِيَ أَحْسَنُ', 'surah' => 'فصلت', 'ayah' => '34'],
        ['text' => 'وَالْكَاظِمِينَ الْغَيْظَ وَالْعَافِينَ عَنِ النَّاسِ وَاللَّهُ يُحِبُّ الْمُحْسِنِينَ', 'surah' => 'آل عمران', 'ayah' => '134'],
        
        // آيات الآخرة والجنة
        ['text' => 'وَلَسَوْفَ يُعْطِيكَ رَبُّكَ فَتَرْضَى', 'surah' => 'الضحى', 'ayah' => '5'],
        ['text' => 'وَأَمَّا مَنْ خَافَ مَقَامَ رَبِّهِ وَنَهَى النَّفْسَ عَنِ الْهَوَى * فَإِنَّ الْجَنَّةَ هِيَ الْمَأْوَى', 'surah' => 'النازعات', 'ayah' => '40-41'],
        ['text' => 'جَنَّاتُ عَدْنٍ يَدْخُلُونَهَا وَمَن صَلَحَ مِنْ آبَائِهِمْ وَأَزْوَاجِهِمْ وَذُرِّيَّاتِهِمْ', 'surah' => 'الرعد', 'ayah' => '23'],
        
        // دعوات من القرآن
        ['text' => 'رَبَّنَا آتِنَا فِي الدُّنْيَا حَسَنَةً وَفِي الْآخِرَةِ حَسَنَةً وَقِنَا عَذَابَ النَّارِ', 'surah' => 'البقرة', 'ayah' => '201'],
        ['text' => 'رَبَّنَا لَا تُزِغْ قُلُوبَنَا بَعْدَ إِذْ هَدَيْتَنَا وَهَبْ لَنَا مِن لَّدُنكَ رَحْمَةً', 'surah' => 'آل عمران', 'ayah' => '8'],
        ['text' => 'رَبِّ اشْرَحْ لِي صَدْرِي * وَيَسِّرْ لِي أَمْرِي', 'surah' => 'طه', 'ayah' => '25-26']
    ];
    
    // اختيار آية عشوائية
    $verse = $verses[array_rand($verses)];
    
    // إنشاء HTML للبلوك مع تصميم محسن
    $html = '<div class="card border-success shadow-sm">';
    $html .= '<div class="card-body text-center p-4">';
    $html .= '<div class="mb-3">';
    $html .= '<i class="fas fa-book-open text-success" style="font-size: 3rem;"></i>';
    $html .= '</div>';
    $html .= '<div class="quran-text mb-3" style="font-family: \'Amiri\', \'Traditional Arabic\', serif; font-size: 1.2rem; line-height: 1.8; color: #2c5530; font-weight: bold;">';
    $html .= $verse['text'];
    $html .= '</div>';
    $html .= '<footer class="blockquote-footer mt-3">';
    $html .= '<cite title="' . $verse['surah'] . '" style="color: #6c757d; font-size: 0.9rem;">';
    $html .= 'سورة ' . $verse['surah'] . ' - آية ' . $verse['ayah'];
    $html .= '</cite>';
    $html .= '</footer>';
    $html .= '<div class="mt-3">';
    $html .= '<small class="text-muted"><i class="fas fa-sync-alt me-1"></i>يتم تحديث الآية تلقائياً</small>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    
    return $html;
}

// بلوك حديث محسن مع مجموعة كبيرة جداً من الأحاديث
function renderEnhancedHadithBlock() {
    $hadiths = [
        // أحاديث الإيمان والعقيدة
        ['text' => 'إنما الأعمال بالنيات، وإنما لكل امرئ ما نوى', 'narrator' => 'متفق عليه'],
        ['text' => 'من قال لا إله إلا الله وحده لا شريك له، له الملك وله الحمد وهو على كل شيء قدير، في يوم مائة مرة كانت له عدل عشر رقاب', 'narrator' => 'متفق عليه'],
        ['text' => 'الإيمان بضع وسبعون شعبة، فأفضلها قول لا إله إلا الله، وأدناها إماطة الأذى عن الطريق', 'narrator' => 'رواه مسلم'],
        
        // أحاديث الأخلاق والمعاملة
        ['text' => 'المسلم من سلم المسلمون من لسانه ويده', 'narrator' => 'متفق عليه'],
        ['text' => 'لا يؤمن أحدكم حتى يحب لأخيه ما يحب لنفسه', 'narrator' => 'متفق عليه'],
        ['text' => 'من كان يؤمن بالله واليوم الآخر فليقل خيراً أو ليصمت', 'narrator' => 'متفق عليه'],
        ['text' => 'إن الله رفيق يحب الرفق في الأمر كله', 'narrator' => 'متفق عليه'],
        ['text' => 'من لا يرحم الناس لا يرحمه الله', 'narrator' => 'متفق عليه'],
        ['text' => 'الكلمة الطيبة صدقة', 'narrator' => 'متفق عليه'],
        ['text' => 'البر حسن الخلق، والإثم ما حاك في نفسك وكرهت أن يطلع عليه الناس', 'narrator' => 'رواه مسلم'],
        
        // أحاديث العلم والتعلم
        ['text' => 'طلب العلم فريضة على كل مسلم', 'narrator' => 'رواه ابن ماجه'],
        ['text' => 'خيركم من تعلم القرآن وعلمه', 'narrator' => 'رواه البخاري'],
        ['text' => 'من سلك طريقاً يلتمس فيه علماً سهل الله له به طريقاً إلى الجنة', 'narrator' => 'رواه مسلم'],
        ['text' => 'إذا مات الإنسان انقطع عنه عمله إلا من ثلاثة: إلا من صدقة جارية، أو علم ينتفع به، أو ولد صالح يدعو له', 'narrator' => 'رواه مسلم'],
        
        // أحاديث العبادة والذكر
        ['text' => 'أحب الكلام إلى الله أربع: سبحان الله، والحمد لله، ولا إله إلا الله، والله أكبر', 'narrator' => 'رواه مسلم'],
        ['text' => 'من قال سبحان الله وبحمده في يوم مائة مرة حطت خطاياه وإن كانت مثل زبد البحر', 'narrator' => 'متفق عليه'],
        ['text' => 'كلمتان خفيفتان على اللسان، ثقيلتان في الميزان، حبيبتان إلى الرحمن: سبحان الله وبحمده، سبحان الله العظيم', 'narrator' => 'متفق عليه'],
        
        // أحاديث الصبر والابتلاء
        ['text' => 'عجباً لأمر المؤمن، إن أمره كله خير، وليس ذاك لأحد إلا للمؤمن، إن أصابته سراء شكر فكان خيراً له، وإن أصابته ضراء صبر فكان خيراً له', 'narrator' => 'رواه مسلم'],
        ['text' => 'ما يصيب المسلم من نصب ولا وصب ولا هم ولا حزن ولا أذى ولا غم حتى الشوكة يشاكها إلا كفر الله بها من خطاياه', 'narrator' => 'متفق عليه'],
        
        // أحاديث الدعاء والاستغفار
        ['text' => 'من لزم الاستغفار جعل الله له من كل ضيق مخرجاً، ومن كل هم فرجاً، ورزقه من حيث لا يحتسب', 'narrator' => 'رواه أبو داود'],
        ['text' => 'الدعاء هو العبادة', 'narrator' => 'رواه الترمذي'],
        
        // أحاديث الوالدين والأسرة
        ['text' => 'الوالد أوسط أبواب الجنة، فإن شئت فأضع ذلك الباب أو احفظه', 'narrator' => 'رواه الترمذي'],
        ['text' => 'رضا الرب في رضا الوالد، وسخط الرب في سخط الوالد', 'narrator' => 'رواه الترمذي'],
        
        // أحاديث الصدقة والزكاة
        ['text' => 'الصدقة تطفئ الخطيئة كما يطفئ الماء النار', 'narrator' => 'رواه الترمذي'],
        ['text' => 'ما نقصت صدقة من مال، وما زاد الله عبداً بعفو إلا عزاً، وما تواضع أحد لله إلا رفعه الله', 'narrator' => 'رواه مسلم'],
        
        // أحاديث التوبة والمغفرة
        ['text' => 'كل ابن آدم خطاء، وخير الخطائين التوابون', 'narrator' => 'رواه الترمذي'],
        ['text' => 'لله أشد فرحاً بتوبة عبده حين يتوب إليه من أحدكم كان على راحلته بأرض فلاة', 'narrator' => 'متفق عليه'],
        
        // أحاديث الحياة والموت
        ['text' => 'اذكروا هادم اللذات: الموت', 'narrator' => 'رواه الترمذي'],
        ['text' => 'أكثروا ذكر هادم اللذات: الموت', 'narrator' => 'رواه النسائي'],
        
        // أحاديث متنوعة
        ['text' => 'الدين النصيحة', 'narrator' => 'رواه مسلم'],
        ['text' => 'لا تغضب', 'narrator' => 'رواه البخاري'],
        ['text' => 'من حسن إسلام المرء تركه ما لا يعنيه', 'narrator' => 'رواه الترمذي'],
        ['text' => 'ازهد في الدنيا يحبك الله، وازهد فيما عند الناس يحبك الناس', 'narrator' => 'رواه ابن ماجه'],
        ['text' => 'اتق الله حيثما كنت، وأتبع السيئة الحسنة تمحها، وخالق الناس بخلق حسن', 'narrator' => 'رواه الترمذي']
    ];
    
    // اختيار حديث عشوائي
    $hadith = $hadiths[array_rand($hadiths)];
    
    // إنشاء HTML للبلوك مع تصميم محسن
    $html = '<div class="card border-warning shadow-sm">';
    $html .= '<div class="card-body text-center p-4">';
    $html .= '<div class="mb-3">';
    $html .= '<i class="fas fa-quote-right text-warning" style="font-size: 2.5rem;"></i>';
    $html .= '</div>';
    $html .= '<div class="hadith-text mb-3" style="font-family: \'Amiri\', \'Traditional Arabic\', serif; font-size: 1.1rem; line-height: 1.7; color: #2c5530; font-weight: bold;">';
    $html .= $hadith['text'];
    $html .= '</div>';
    $html .= '<div class="narrator mt-3" style="color: #6c757d; font-size: 0.9rem; font-style: italic;">';
    $html .= $hadith['narrator'];
    $html .= '</div>';
    $html .= '<div class="mt-3">';
    $html .= '<small class="text-muted"><i class="fas fa-sync-alt me-1"></i>يتم تحديث الحديث تلقائياً</small>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    
    return $html;
}

// بلوك إحصائيات الزوار المحسن مع تحديث تلقائي
function renderEnhancedVisitorStatsBlock($db) {
    try {
        // جلب إحصائيات الزوار مع تحديث الوقت الحالي
        $today = $db->fetchOne("SELECT COUNT(DISTINCT ip_address) as count FROM visitors WHERE DATE(visit_time) = CURDATE()")['count'] ?? 0;
        $yesterday = $db->fetchOne("SELECT COUNT(DISTINCT ip_address) as count FROM visitors WHERE DATE(visit_time) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)")['count'] ?? 0;
        $thisWeek = $db->fetchOne("SELECT COUNT(DISTINCT ip_address) as count FROM visitors WHERE WEEK(visit_time) = WEEK(CURDATE()) AND YEAR(visit_time) = YEAR(CURDATE())")['count'] ?? 0;
        $thisMonth = $db->fetchOne("SELECT COUNT(DISTINCT ip_address) as count FROM visitors WHERE MONTH(visit_time) = MONTH(CURDATE()) AND YEAR(visit_time) = YEAR(CURDATE())")['count'] ?? 0;
        $total = $db->fetchOne("SELECT COUNT(DISTINCT ip_address) as count FROM visitors")['count'] ?? 0;
        $online = $db->fetchOne("SELECT COUNT(DISTINCT ip_address) as count FROM visitors WHERE visit_time >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)")['count'] ?? 0;
        
        // إنشاء HTML للبلوك مع تصميم محسن
        $html = '<div class="card border-info shadow-sm">';
        $html .= '<div class="card-body p-3">';
        
        // الصف الأول - زوار اليوم والمتصلون الآن
        $html .= '<div class="row text-center mb-3">';
        $html .= '<div class="col-6">';
        $html .= '<div class="p-2 rounded" style="background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);">';
        $html .= '<i class="fas fa-users fa-lg text-primary mb-1"></i>';
        $html .= '<h5 class="mb-0 text-primary" style="font-weight: bold;">' . convertToArabicNumbers($today) . '</h5>';
        $html .= '<small class="text-muted">زوار اليوم</small>';
        $html .= '</div>';
        $html .= '</div>';
        
        $html .= '<div class="col-6">';
        $html .= '<div class="p-2 rounded" style="background: linear-gradient(135deg, #e8f5e8 0%, #c8e6c9 100%);">';
        $html .= '<i class="fas fa-circle fa-lg text-success mb-1" style="animation: pulse 2s infinite;"></i>';
        $html .= '<h5 class="mb-0 text-success" style="font-weight: bold;">' . convertToArabicNumbers($online) . '</h5>';
        $html .= '<small class="text-muted">متصل الآن</small>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        
        // الصف الثاني - زوار الأمس والأسبوع
        $html .= '<div class="row text-center mb-3">';
        $html .= '<div class="col-6">';
        $html .= '<div class="p-2 rounded" style="background: linear-gradient(135deg, #fff3e0 0%, #ffcc02 30%);">';
        $html .= '<i class="fas fa-calendar-day fa-lg text-warning mb-1"></i>';
        $html .= '<h6 class="mb-0 text-dark" style="font-weight: bold;">' . convertToArabicNumbers($yesterday) . '</h6>';
        $html .= '<small class="text-muted">زوار الأمس</small>';
        $html .= '</div>';
        $html .= '</div>';
        
        $html .= '<div class="col-6">';
        $html .= '<div class="p-2 rounded" style="background: linear-gradient(135deg, #fce4ec 0%, #f8bbd9 100%);">';
        $html .= '<i class="fas fa-calendar-week fa-lg text-danger mb-1"></i>';
        $html .= '<h6 class="mb-0 text-dark" style="font-weight: bold;">' . convertToArabicNumbers($thisWeek) . '</h6>';
        $html .= '<small class="text-muted">زوار الأسبوع</small>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        
        // الصف الثالث - زوار الشهر والإجمالي
        $html .= '<div class="row text-center">';
        $html .= '<div class="col-6">';
        $html .= '<div class="p-2 rounded" style="background: linear-gradient(135deg, #f3e5f5 0%, #e1bee7 100%);">';
        $html .= '<i class="fas fa-calendar-alt fa-lg text-purple mb-1" style="color: #9c27b0;"></i>';
        $html .= '<h6 class="mb-0" style="color: #9c27b0; font-weight: bold;">' . convertToArabicNumbers($thisMonth) . '</h6>';
        $html .= '<small class="text-muted">زوار الشهر</small>';
        $html .= '</div>';
        $html .= '</div>';
        
        $html .= '<div class="col-6">';
        $html .= '<div class="p-2 rounded" style="background: linear-gradient(135deg, #e0f2f1 0%, #b2dfdb 100%);">';
        $html .= '<i class="fas fa-chart-line fa-lg text-teal mb-1" style="color: #009688;"></i>';
        $html .= '<h6 class="mb-0" style="color: #009688; font-weight: bold;">' . convertToArabicNumbers($total) . '</h6>';
        $html .= '<small class="text-muted">إجمالي الزوار</small>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        
        // آخر تحديث
        $html .= '<div class="text-center mt-3 pt-2 border-top">';
        $html .= '<i class="fas fa-sync-alt fa-sm text-muted me-1" id="refresh-icon"></i>';
        $html .= '<small class="text-muted">آخر تحديث: <span id="lastUpdate">' . date('H:i:s') . '</span></small>';
        $html .= '</div>';
        
        $html .= '</div>';
        $html .= '</div>';
        
        // إضافة CSS للأنيميشن
        $html .= '<style>
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        #refresh-icon {
            animation: spin 2s linear infinite;
        }
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        </style>';
        
        return $html;
    } catch (Exception $e) {
        return '<div class="alert alert-warning">تعذر جلب إحصائيات الزوار: ' . $e->getMessage() . '</div>';
    }
}

// بلوك الروابط الخارجية المحسن
function renderEnhancedExternalLinksBlock() {
    $links = [
        ['title' => 'القرآن الفلاشي ورش', 'url' => 'http://mag.masjidelfadjr.com/quranflash.htm', 'icon' => 'fas fa-book-open', 'color' => 'primary'],
        ['title' => 'المكتبة الصوتية للقرآن', 'url' => 'http://mp3.masjidelfadjr.com/', 'icon' => 'fas fa-volume-up', 'color' => 'success'],
        ['title' => 'التلاوات القرآنية', 'url' => 'http://quran.masjidelfadjr.com/index.html', 'icon' => 'fas fa-headphones', 'color' => 'info'],
        ['title' => 'القارئ القرآني', 'url' => 'https://qari.one/', 'icon' => 'fas fa-microphone', 'color' => 'warning'],
        ['title' => 'مقرأة الجزائر الالكترونية', 'url' => 'https://maqraa.dz/', 'icon' => 'fas fa-graduation-cap', 'color' => 'danger'],
        ['title' => 'المصحف المحفظ', 'url' => 'https://muhaffidh.app/', 'icon' => 'fas fa-memory', 'color' => 'secondary'],
        ['title' => 'المقرئ الصوتي للقرآن', 'url' => 'https://fr.muqri.com/', 'icon' => 'fas fa-play-circle', 'color' => 'primary'],
        ['title' => 'الباحث القرآني', 'url' => 'https://furqan.co/', 'icon' => 'fas fa-search', 'color' => 'success'],
        ['title' => 'التفسير التفاعلي', 'url' => 'https://read.tafsir.one/', 'icon' => 'fas fa-comments', 'color' => 'info'],
        ['title' => 'الباحث الحديثي', 'url' => 'https://sunnah.one/', 'icon' => 'fas fa-quote-right', 'color' => 'warning'],
        ['title' => 'مُقرِئ المتون', 'url' => 'https://mutoon.one/', 'icon' => 'fas fa-scroll', 'color' => 'danger'],
        ['title' => 'اختبر في غريب القرآن', 'url' => 'https://kalimah.app/', 'icon' => 'fas fa-question-circle', 'color' => 'secondary'],
        ['title' => 'تكوين الراسخين', 'url' => 'https://takw.in/', 'icon' => 'fas fa-user-graduate', 'color' => 'primary'],
        ['title' => 'المكتبة الإسلامية الشاملة', 'url' => 'https://www.muslim-library.com/', 'icon' => 'fas fa-library', 'color' => 'success'],
        ['title' => 'منصة سؤال للاختبارات', 'url' => 'https://quizzer.one/', 'icon' => 'fas fa-clipboard-check', 'color' => 'info'],
        ['title' => 'الفتوى الإلكترونية', 'url' => 'https://marw.dz/الفتوى-الإلكترونية', 'icon' => 'fas fa-gavel', 'color' => 'warning'],
        ['title' => 'بنك الفتوى', 'url' => 'https://marw.dz/index.php/بنك-الفتاوى', 'icon' => 'fas fa-university', 'color' => 'danger'],
        ['title' => 'كل ما يخص الميراث', 'url' => 'https://almwareeth.com/', 'icon' => 'fas fa-coins', 'color' => 'secondary'],
        ['title' => 'الزكاة وكيفية إخراجها', 'url' => 'https://www.marw.dz/التعريف-بصندوق-الزكاة', 'icon' => 'fas fa-hand-holding-heart', 'color' => 'primary'],
        ['title' => 'تراث إسلامي', 'url' => 'https://app.turath.io/', 'icon' => 'fas fa-mosque', 'color' => 'success'],
        ['title' => 'الباحث العلمي', 'url' => 'https://bahith.app/', 'icon' => 'fas fa-microscope', 'color' => 'info']
    ];
    
    $html = '<div class="card shadow-sm">';
    $html .= '<div class="card-body p-2">';
    $html .= '<div class="external-links-container">';
    
    foreach ($links as $index => $link) {
        $html .= '<div class="external-link-item mb-2">';
        $html .= '<a href="' . htmlspecialchars($link['url']) . '" target="_blank" class="text-decoration-none d-flex align-items-center p-2 rounded hover-effect" style="transition: all 0.3s ease;">';
        $html .= '<div class="link-icon me-3">';
        $html .= '<i class="' . $link['icon'] . ' fa-lg text-' . $link['color'] . '"></i>';
        $html .= '</div>';
        $html .= '<div class="link-content flex-grow-1">';
        $html .= '<span class="link-title" style="font-family: \'Cairo\', sans-serif; font-size: 0.9rem; font-weight: 500; color: #2c5530;">';
        $html .= ($index + 1) . '- ' . $link['title'];
        $html .= '</span>';
        $html .= '</div>';
        $html .= '<div class="link-arrow">';
        $html .= '<i class="fas fa-external-link-alt fa-sm text-muted"></i>';
        $html .= '</div>';
        $html .= '</a>';
        $html .= '</div>';
    }
    
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    
    // إضافة CSS للتأثيرات
    $html .= '<style>
    .external-link-item .hover-effect:hover {
        background-color: #f8f9fa !important;
        transform: translateX(-5px);
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .external-link-item .hover-effect:hover .link-title {
        color: #1a4d1a !important;
        font-weight: 600;
    }
    .external-link-item .hover-effect:hover .link-arrow {
        transform: translateX(3px);
    }
    .external-links-container {
        max-height: 400px;
        overflow-y: auto;
    }
    .external-links-container::-webkit-scrollbar {
        width: 6px;
    }
    .external-links-container::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 3px;
    }
    .external-links-container::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 3px;
    }
    .external-links-container::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }
    </style>';
    
    return $html;
}
?>
