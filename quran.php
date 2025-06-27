<?php
/**
 * صفحة القرآن الكريم
 */
require_once 'config/config.php';



// الحصول على الإعدادات
$siteName = getSetting($db, 'site_name', 'مسجد النور');
$siteDescription = getSetting($db, 'site_description', '');
$siteLogo = getSetting($db, 'site_logo', '');

// الحصول على السورة المطلوبة
$surahId = isset($_GET['surah']) ? (int)$_GET['surah'] : 1;
if ($surahId < 1 || $surahId > 114) {
    $surahId = 1;
}

// قائمة السور
$surahs = [
    1 => ['name' => 'الفاتحة', 'verses' => 7],
    2 => ['name' => 'البقرة', 'verses' => 286],
    3 => ['name' => 'آل عمران', 'verses' => 200],
    4 => ['name' => 'النساء', 'verses' => 176],
    5 => ['name' => 'المائدة', 'verses' => 120],
    6 => ['name' => 'الأنعام', 'verses' => 165],
    7 => ['name' => 'الأعراف', 'verses' => 206],
    8 => ['name' => 'الأنفال', 'verses' => 75],
    9 => ['name' => 'التوبة', 'verses' => 129],
    10 => ['name' => 'يونس', 'verses' => 109],
    11 => ['name' => 'هود', 'verses' => 123],
    12 => ['name' => 'يوسف', 'verses' => 111],
    13 => ['name' => 'الرعد', 'verses' => 43],
    14 => ['name' => 'إبراهيم', 'verses' => 52],
    15 => ['name' => 'الحجر', 'verses' => 99],
    16 => ['name' => 'النحل', 'verses' => 128],
    17 => ['name' => 'الإسراء', 'verses' => 111],
    18 => ['name' => 'الكهف', 'verses' => 110],
    19 => ['name' => 'مريم', 'verses' => 98],
    20 => ['name' => 'طه', 'verses' => 135],
    21 => ['name' => 'الأنبياء', 'verses' => 112],
    22 => ['name' => 'الحج', 'verses' => 78],
    23 => ['name' => 'المؤمنون', 'verses' => 118],
    24 => ['name' => 'النور', 'verses' => 64],
    25 => ['name' => 'الفرقان', 'verses' => 77],
    26 => ['name' => 'الشعراء', 'verses' => 227],
    27 => ['name' => 'النمل', 'verses' => 93],
    28 => ['name' => 'القصص', 'verses' => 88],
    29 => ['name' => 'العنكبوت', 'verses' => 69],
    30 => ['name' => 'الروم', 'verses' => 60],
    31 => ['name' => 'لقمان', 'verses' => 34],
    32 => ['name' => 'السجدة', 'verses' => 30],
    33 => ['name' => 'الأحزاب', 'verses' => 73],
    34 => ['name' => 'سبأ', 'verses' => 54],
    35 => ['name' => 'فاطر', 'verses' => 45],
    36 => ['name' => 'يس', 'verses' => 83],
    37 => ['name' => 'الصافات', 'verses' => 182],
    38 => ['name' => 'ص', 'verses' => 88],
    39 => ['name' => 'الزمر', 'verses' => 75],
    40 => ['name' => 'غافر', 'verses' => 85],
    41 => ['name' => 'فصلت', 'verses' => 54],
    42 => ['name' => 'الشورى', 'verses' => 53],
    43 => ['name' => 'الزخرف', 'verses' => 89],
    44 => ['name' => 'الدخان', 'verses' => 59],
    45 => ['name' => 'الجاثية', 'verses' => 37],
    46 => ['name' => 'الأحقاف', 'verses' => 35],
    47 => ['name' => 'محمد', 'verses' => 38],
    48 => ['name' => 'الفتح', 'verses' => 29],
    49 => ['name' => 'الحجرات', 'verses' => 18],
    50 => ['name' => 'ق', 'verses' => 45],
    51 => ['name' => 'الذاريات', 'verses' => 60],
    52 => ['name' => 'الطور', 'verses' => 49],
    53 => ['name' => 'النجم', 'verses' => 62],
    54 => ['name' => 'القمر', 'verses' => 55],
    55 => ['name' => 'الرحمن', 'verses' => 78],
    56 => ['name' => 'الواقعة', 'verses' => 96],
    57 => ['name' => 'الحديد', 'verses' => 29],
    58 => ['name' => 'المجادلة', 'verses' => 22],
    59 => ['name' => 'الحشر', 'verses' => 24],
    60 => ['name' => 'الممتحنة', 'verses' => 13],
    61 => ['name' => 'الصف', 'verses' => 14],
    62 => ['name' => 'الجمعة', 'verses' => 11],
    63 => ['name' => 'المنافقون', 'verses' => 11],
    64 => ['name' => 'التغابن', 'verses' => 18],
    65 => ['name' => 'الطلاق', 'verses' => 12],
    66 => ['name' => 'التحريم', 'verses' => 12],
    67 => ['name' => 'الملك', 'verses' => 30],
    68 => ['name' => 'القلم', 'verses' => 52],
    69 => ['name' => 'الحاقة', 'verses' => 52],
    70 => ['name' => 'المعارج', 'verses' => 44],
    71 => ['name' => 'نوح', 'verses' => 28],
    72 => ['name' => 'الجن', 'verses' => 28],
    73 => ['name' => 'المزمل', 'verses' => 20],
    74 => ['name' => 'المدثر', 'verses' => 56],
    75 => ['name' => 'القيامة', 'verses' => 40],
    76 => ['name' => 'الإنسان', 'verses' => 31],
    77 => ['name' => 'المرسلات', 'verses' => 50],
    78 => ['name' => 'النبأ', 'verses' => 40],
    79 => ['name' => 'النازعات', 'verses' => 46],
    80 => ['name' => 'عبس', 'verses' => 42],
    81 => ['name' => 'التكوير', 'verses' => 29],
    82 => ['name' => 'الانفطار', 'verses' => 19],
    83 => ['name' => 'المطففين', 'verses' => 36],
    84 => ['name' => 'الانشقاق', 'verses' => 25],
    85 => ['name' => 'البروج', 'verses' => 22],
    86 => ['name' => 'الطارق', 'verses' => 17],
    87 => ['name' => 'الأعلى', 'verses' => 19],
    88 => ['name' => 'الغاشية', 'verses' => 26],
    89 => ['name' => 'الفجر', 'verses' => 30],
    90 => ['name' => 'البلد', 'verses' => 20],
    91 => ['name' => 'الشمس', 'verses' => 15],
    92 => ['name' => 'الليل', 'verses' => 21],
    93 => ['name' => 'الضحى', 'verses' => 11],
    94 => ['name' => 'الشرح', 'verses' => 8],
    95 => ['name' => 'التين', 'verses' => 8],
    96 => ['name' => 'العلق', 'verses' => 19],
    97 => ['name' => 'القدر', 'verses' => 5],
    98 => ['name' => 'البينة', 'verses' => 8],
    99 => ['name' => 'الزلزلة', 'verses' => 8],
    100 => ['name' => 'العاديات', 'verses' => 11],
    101 => ['name' => 'القارعة', 'verses' => 11],
    102 => ['name' => 'التكاثر', 'verses' => 8],
    103 => ['name' => 'العصر', 'verses' => 3],
    104 => ['name' => 'الهمزة', 'verses' => 9],
    105 => ['name' => 'الفيل', 'verses' => 5],
    106 => ['name' => 'قريش', 'verses' => 4],
    107 => ['name' => 'الماعون', 'verses' => 7],
    108 => ['name' => 'الكوثر', 'verses' => 3],
    109 => ['name' => 'الكافرون', 'verses' => 6],
    110 => ['name' => 'النصر', 'verses' => 3],
    111 => ['name' => 'المسد', 'verses' => 5],
    112 => ['name' => 'الإخلاص', 'verses' => 4],
    113 => ['name' => 'الفلق', 'verses' => 5],
    114 => ['name' => 'الناس', 'verses' => 6]
];

// الحصول على معلومات السورة الحالية
$currentSurah = $surahs[$surahId];

// الحصول على القراء المتاحين
$reciters = [
    'mishary' => 'مشاري راشد العفاسي',
    'sudais' => 'عبد الرحمن السديس',
    'shuraim' => 'سعود الشريم',
    'maher' => 'ماهر المعيقلي',
    'husary' => 'محمود خليل الحصري'
];

// القارئ المختار
$reciter = isset($_GET['reciter']) ? $_GET['reciter'] : 'mishary';
if (!array_key_exists($reciter, $reciters)) {
    $reciter = 'mishary';
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>القرآن الكريم - سورة <?php echo $currentSurah['name']; ?> - <?php echo htmlspecialchars($siteName); ?></title>
    <meta name="description" content="قراءة واستماع إلى سورة <?php echo $currentSurah['name']; ?> من القرآن الكريم">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts - Arabic -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&family=Amiri:wght@400;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Cairo', sans-serif;
            background-color: #f8f9fa;
        }
        
        .main-content {
            min-height: 500px;
        }
        
        .page-header {
            background: #917476;
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
            border-radius: 0 0 20px 20px;
        }
        
        .quran-container {
            background-color: #fff;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .surah-title {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .surah-bismillah {
            text-align: center;
            font-family: 'Amiri', serif;
            font-size: 2rem;
            margin-bottom: 30px;
            color: #333;
        }
        
        .quran-text {
            font-family: 'Amiri', serif;
            font-size: 1.5rem;
            line-height: 2.5;
            text-align: justify;
            color: #333;
        }
        
        .verse-number {
            display: inline-block;
            width: 40px;
            height: 40px;
            line-height: 40px;
            text-align: center;
            background-color: #f0f0f0;
            border-radius: 50%;
            margin: 0 5px;
            font-family: 'Cairo', sans-serif;
            font-size: 0.9rem;
            color: #666;
        }
        
        .surah-list {
            max-height: 500px;
            overflow-y: auto;
        }
        
        .surah-list-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 15px;
            border-bottom: 1px solid #f0f0f0;
            transition: all 0.3s;
        }
        
        .surah-list-item:hover {
            background-color: #f8f9fa;
        }
        
        .surah-list-item.active {
            background-color: #e9ecef;
            font-weight: bold;
        }
        
        .audio-player {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .audio-controls {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }
        
        .audio-btn {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .audio-btn:hover {
            transform: scale(1.1);
        }
        
        .audio-progress {
            width: 100%;
            height: 10px;
            background-color: #e9ecef;
            border-radius: 5px;
            margin-top: 15px;
            position: relative;
            cursor: pointer;
        }
        
        .audio-progress-bar {
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 5px;
            width: 0;
        }
        
        .audio-time {
            display: flex;
            justify-content: space-between;
            margin-top: 5px;
            font-size: 0.9rem;
            color: #666;
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
    </style>
</head>
<body>
    <!-- Header -->
    <?php include 'includes/header.php'; ?>
    
	<!-- Hero Section -->
    <div class="hero-section">
        <div class="container">
            <h1>القرآن الكريم</h1>
            <p> ورتل القرآن ترتيلا</p>
        </div>
    </div>

	
    <!-- Page Header -->
    
    
    <div class="container main-content">
        <div class="row">
            <!-- Sidebar - Surah List -->
            <div class="col-lg-3 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">قائمة السور</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="surah-list">
                            <?php foreach ($surahs as $id => $surah): ?>
                                <a href="?surah=<?php echo $id; ?>&reciter=<?php echo $reciter; ?>" class="text-decoration-none">
                                    <div class="surah-list-item <?php echo ($id === $surahId) ? 'active' : ''; ?>">
                                        <span><?php echo $surah['name']; ?></span>
                                        <span class="badge bg-secondary"><?php echo $surah['verses']; ?></span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Main Content - Quran Text -->
            <div class="col-lg-9">
                <!-- Audio Player -->
                <div class="audio-player">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="reciter" class="form-label">اختر القارئ:</label>
                                <select id="reciter" class="form-select" onchange="changeReciter(this.value)">
                                    <?php foreach ($reciters as $id => $name): ?>
                                        <option value="<?php echo $id; ?>" <?php echo ($id === $reciter) ? 'selected' : ''; ?>>
                                            <?php echo $name; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="audio-controls">
                                <button class="audio-btn" id="prevBtn" title="السورة السابقة">
                                    <i class="fas fa-step-backward"></i>
                                </button>
                                <button class="audio-btn" id="playBtn" title="تشغيل/إيقاف">
                                    <i class="fas fa-play" id="playIcon"></i>
                                </button>
                                <button class="audio-btn" id="nextBtn" title="السورة التالية">
                                    <i class="fas fa-step-forward"></i>
                                </button>
                            </div>
                            <div class="audio-progress" id="progressContainer">
                                <div class="audio-progress-bar" id="progressBar"></div>
                            </div>
                            <div class="audio-time">
                                <span id="currentTime">00:00</span>
                                <span id="duration">00:00</span>
                            </div>
                        </div>
                    </div>
                    <audio id="quranAudio" style="display: none;"></audio>
                </div>
                
                <!-- Quran Text -->
                <div class="quran-container">
                    <div class="surah-title">
                        <h2>سورة <?php echo $currentSurah['name']; ?></h2>
                        <p>عدد الآيات: <?php echo $currentSurah['verses']; ?></p>
                    </div>
                    
                    <?php if ($surahId != 9): // التوبة هي السورة الوحيدة التي لا تبدأ بالبسملة ?>
                        <div class="surah-bismillah">
                            بِسْمِ اللَّهِ الرَّحْمَنِ الرَّحِيمِ
                        </div>
                    <?php endif; ?>
                    
                    <div class="quran-text">
                        <!-- هنا يتم عرض نص السورة من API أو قاعدة البيانات -->
                        <p class="text-center">
                            جاري تحميل نص السورة...
                        </p>
                    </div>
                </div>
                
                <!-- Navigation -->
                <div class="d-flex justify-content-between mb-4">
                    <?php if ($surahId > 1): ?>
                        <a href="?surah=<?php echo $surahId - 1; ?>&reciter=<?php echo $reciter; ?>" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-right"></i> السورة السابقة: <?php echo $surahs[$surahId - 1]['name']; ?>
                        </a>
                    <?php else: ?>
                        <div></div>
                    <?php endif; ?>
                    
                    <?php if ($surahId < 114): ?>
                        <a href="?surah=<?php echo $surahId + 1; ?>&reciter=<?php echo $reciter; ?>" class="btn btn-outline-primary">
                            السورة التالية: <?php echo $surahs[$surahId + 1]['name']; ?> <i class="fas fa-arrow-left"></i>
                        </a>
                    <?php else: ?>
                        <div></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // متغيرات عامة
        const audio = document.getElementById('quranAudio');
        const playBtn = document.getElementById('playBtn');
        const playIcon = document.getElementById('playIcon');
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        const progressBar = document.getElementById('progressBar');
        const progressContainer = document.getElementById('progressContainer');
        const currentTimeEl = document.getElementById('currentTime');
        const durationEl = document.getElementById('duration');
        
        // الحصول على معلومات السورة الحالية
        const currentSurahId = <?php echo $surahId; ?>;
        const currentReciter = '<?php echo $reciter; ?>';
        
        // تحميل ملف الصوت
        function loadAudio() {
            // يمكن استبدال هذا برابط API حقيقي
            const audioUrl = `https://server8.mp3quran.net/${currentReciter}/${currentSurahId.toString().padStart(3, '0')}.mp3`;
            audio.src = audioUrl;
            audio.load();
        }
        
        // تحميل نص السورة
        function loadSurahText() {
            const quranTextEl = document.querySelector('.quran-text');
            
            // يمكن استبدال هذا بطلب API حقيقي
            fetch(`https://api.alquran.cloud/v1/surah/${currentSurahId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.code === 200) {
                        const verses = data.data.ayahs;
                        let html = '';
                        
                        verses.forEach((verse, index) => {
                            html += `${verse.text} <span class="verse-number">${convertToArabicNumbers(index + 1)}</span> `;
                        });
                        
                        quranTextEl.innerHTML = html;
                    } else {
                        quranTextEl.innerHTML = '<p class="text-center text-danger">حدث خطأ أثناء تحميل نص السورة</p>';
                    }
                })
                .catch(error => {
                    quranTextEl.innerHTML = '<p class="text-center text-danger">حدث خطأ أثناء تحميل نص السورة</p>';
                    console.error('Error loading surah text:', error);
                });
        }
        
        // تحويل الأرقام إلى الأرقام العربية
        function convertToArabicNumbers(number) {
            const arabicNumbers = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
            return number.toString().split('').map(digit => arabicNumbers[digit]).join('');
        }
        
        // تنسيق الوقت
        function formatTime(seconds) {
            const minutes = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            return `${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        }
        
        // تحديث شريط التقدم
        function updateProgress() {
            const percent = (audio.currentTime / audio.duration) * 100;
            progressBar.style.width = `${percent}%`;
            currentTimeEl.textContent = formatTime(audio.currentTime);
        }
        
        // تغيير القارئ
        function changeReciter(reciter) {
            window.location.href = `?surah=${currentSurahId}&reciter=${reciter}`;
        }
        
        // تشغيل/إيقاف الصوت
        playBtn.addEventListener('click', () => {
            if (audio.paused) {
                audio.play();
                playIcon.classList.replace('fa-play', 'fa-pause');
            } else {
                audio.pause();
                playIcon.classList.replace('fa-pause', 'fa-play');
            }
        });
        
        // الانتقال إلى السورة السابقة
        prevBtn.addEventListener('click', () => {
            if (currentSurahId > 1) {
                window.location.href = `?surah=${currentSurahId - 1}&reciter=${currentReciter}`;
            }
        });
        
        // الانتقال إلى السورة التالية
        nextBtn.addEventListener('click', () => {
            if (currentSurahId < 114) {
                window.location.href = `?surah=${currentSurahId + 1}&reciter=${currentReciter}`;
            }
        });
        
        // تحديث مدة الصوت
        audio.addEventListener('loadedmetadata', () => {
            durationEl.textContent = formatTime(audio.duration);
        });
        
        // تحديث شريط التقدم أثناء التشغيل
        audio.addEventListener('timeupdate', updateProgress);
        
        // تغيير موضع التشغيل
        progressContainer.addEventListener('click', (e) => {
            const width = progressContainer.clientWidth;
            const clickX = e.offsetX;
            const duration = audio.duration;
            
            audio.currentTime = (clickX / width) * duration;
        });
        
        // عند انتهاء الصوت
        audio.addEventListener('ended', () => {
            playIcon.classList.replace('fa-pause', 'fa-play');
            progressBar.style.width = '0%';
            currentTimeEl.textContent = '00:00';
        });
        
        // تحميل الصوت ونص السورة عند تحميل الصفحة
        document.addEventListener('DOMContentLoaded', () => {
            loadAudio();
            loadSurahText();
        });
    </script>
</body>
</html>
