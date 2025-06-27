<?php
/**
 * صفحة المكتبة
 */
require_once 'config/config.php';

// تسجيل زيارة الصفحة
logVisitor($db, $_SERVER['REQUEST_URI']);

// الحصول على الإعدادات
$siteName = getSetting($db, 'site_name', 'مسجد النور');
$siteDescription = getSetting($db, 'site_description', '');
$siteLogo = getSetting($db, 'site_logo', '');

// تحديد الفئة المطلوبة
$category = isset($_GET['category']) ? $_GET['category'] : 'all';

// تحديد الصفحة الحالية
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 12;
$offset = ($page - 1) * $perPage;

// البحث
$search = isset($_GET['search']) ? $_GET['search'] : '';

// قائمة الفئات
$categories = [
    'all' => 'جميع الكتب',
    'quran' => 'علوم القرآن',
    'hadith' => 'الحديث وعلومه',
    'fiqh' => 'الفقه الإسلامي',
    'aqeedah' => 'العقيدة',
    'seerah' => 'السيرة النبوية',
    'history' => 'التاريخ الإسلامي',
    'language' => 'اللغة العربية',
    'children' => 'كتب الأطفال',
    'other' => 'كتب أخرى'
];

// بيانات الكتب (يمكن استبدالها بقاعدة بيانات حقيقية)
$booksData = [
    [
        'id' => 1,
        'title' => 'تفسير القرآن العظيم',
        'author' => 'ابن كثير',
        'category' => 'quran',
        'cover' => 'book1.jpg',
        'description' => 'تفسير القرآن العظيم المعروف باسم تفسير ابن كثير هو من أشهر كتب التفسير بالمأثور.',
        'file' => 'tafsir-ibn-kathir.pdf',
        'pages' => 1400,
        'year' => 1373,
        'downloads' => 1250
    ],
    [
        'id' => 2,
        'title' => 'صحيح البخاري',
        'author' => 'محمد بن إسماعيل البخاري',
        'category' => 'hadith',
        'cover' => 'book2.jpg',
        'description' => 'الجامع المسند الصحيح المختصر من أمور رسول الله صلى الله عليه وسلم وسننه وأيامه.',
        'file' => 'sahih-bukhari.pdf',
        'pages' => 1200,
        'year' => 870,
        'downloads' => 2300
    ],
    [
        'id' => 3,
        'title' => 'فقه السنة',
        'author' => 'سيد سابق',
        'category' => 'fiqh',
        'cover' => 'book3.jpg',
        'description' => 'كتاب يتناول الفقه الإسلامي بأسلوب سهل وميسر، ويعتمد على الأدلة من القرآن والسنة.',
        'file' => 'fiqh-sunnah.pdf',
        'pages' => 850,
        'year' => 1945,
        'downloads' => 1800
    ],
    [
        'id' => 4,
        'title' => 'الرحيق المختوم',
        'author' => 'صفي الرحمن المباركفوري',
        'category' => 'seerah',
        'cover' => 'book4.jpg',
        'description' => 'كتاب في السيرة النبوية، يتناول حياة النبي محمد صلى الله عليه وسلم بالتفصيل.',
        'file' => 'raheeq-makhtum.pdf',
        'pages' => 600,
        'year' => 1976,
        'downloads' => 2100
    ],
    [
        'id' => 5,
        'title' => 'زاد المعاد في هدي خير العباد',
        'author' => 'ابن قيم الجوزية',
        'category' => 'seerah',
        'cover' => 'book5.jpg',
        'description' => 'كتاب يتناول سيرة النبي محمد صلى الله عليه وسلم وهديه في مختلف جوانب الحياة.',
        'file' => 'zad-al-maad.pdf',
        'pages' => 950,
        'year' => 1350,
        'downloads' => 1600
    ],
    [
        'id' => 6,
        'title' => 'العقيدة الواسطية',
        'author' => 'ابن تيمية',
        'category' => 'aqeedah',
        'cover' => 'book6.jpg',
        'description' => 'رسالة في العقيدة الإسلامية، كتبها شيخ الإسلام ابن تيمية.',
        'file' => 'aqeedah-wasitiyah.pdf',
        'pages' => 120,
        'year' => 1306,
        'downloads' => 1400
    ],
    [
        'id' => 7,
        'title' => 'تاريخ الطبري',
        'author' => 'محمد بن جرير الطبري',
        'category' => 'history',
        'cover' => 'book7.jpg',
        'description' => 'كتاب في التاريخ الإسلامي، يتناول تاريخ الأمم والملوك منذ بدء الخليقة حتى عصر المؤلف.',
        'file' => 'tarikh-tabari.pdf',
        'pages' => 1800,
        'year' => 923,
        'downloads' => 950
    ],
    [
        'id' => 8,
        'title' => 'مقدمة ابن خلدون',
        'author' => 'عبد الرحمن بن خلدون',
        'category' => 'history',
        'cover' => 'book8.jpg',
        'description' => 'كتاب في علم الاجتماع والتاريخ، يعتبر من أهم المؤلفات في الحضارة الإسلامية.',
        'file' => 'muqaddimah.pdf',
        'pages' => 750,
        'year' => 1377,
        'downloads' => 1100
    ],
    [
        'id' => 9,
        'title' => 'النحو الواضح',
        'author' => 'علي الجارم ومصطفى أمين',
        'category' => 'language',
        'cover' => 'book9.jpg',
        'description' => 'كتاب في قواعد اللغة العربية، يتميز بأسلوبه السهل والواضح.',
        'file' => 'nahw-wadih.pdf',
        'pages' => 320,
        'year' => 1965,
        'downloads' => 1700
    ],
    [
        'id' => 10,
        'title' => 'قصص الأنبياء للأطفال',
        'author' => 'مجموعة من المؤلفين',
        'category' => 'children',
        'cover' => 'book10.jpg',
        'description' => 'كتاب يحتوي على قصص الأنبياء بأسلوب مبسط للأطفال.',
        'file' => 'prophets-stories.pdf',
        'pages' => 150,
        'year' => 2010,
        'downloads' => 2500
    ],
    [
        'id' => 11,
        'title' => 'رياض الصالحين',
        'author' =>  'النووي',
        'category' => 'hadith',
        'cover' => 'book11.jpg',
        'description' => 'كتاب في الأحاديث النبوية الشريفة، مرتب حسب الموضوعات.',
        'file' => 'riyadh-salihin.pdf',
        'pages' => 450,
        'year' => 1277,
        'downloads' => 1900
    ],
    [
        'id' => 12,
        'title' => 'الأذكار',
        'author' => 'النووي',
        'category' => 'other',
        'cover' => 'book12.jpg',
        'description' => 'كتاب يجمع الأذكار والأدعية المأثورة عن النبي صلى الله عليه وسلم.',
        'file' => 'adhkar.pdf',
        'pages' => 280,
        'year' => 1277,
        'downloads' => 2200
    ]
];

// تطبيق الفلترة والبحث
$filteredBooks = $booksData;

if ($category !== 'all') {
    $filteredBooks = array_filter($filteredBooks, function($book) use ($category) {
        return $book['category'] === $category;
    });
}

if (!empty($search)) {
    $filteredBooks = array_filter($filteredBooks, function($book) use ($search) {
        return stripos($book['title'], $search) !== false || 
               stripos($book['author'], $search) !== false ||
               stripos($book['description'], $search) !== false;
    });
}

// حساب عدد الصفحات
$totalBooks = count($filteredBooks);
$totalPages = ceil($totalBooks / $perPage);

// تطبيق التقسيم
$books = array_slice($filteredBooks, $offset, $perPage);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>المكتبة الإسلامية - <?php echo htmlspecialchars($siteName); ?></title>
    <meta name="description" content="مكتبة إسلامية شاملة تحتوي على مجموعة واسعة من الكتب الإسلامية">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts - Arabic -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Cairo', sans-serif;
            background-color: #f8f9fa;
        }
        
        .main-content {
            min-height: 500px;
        }
        
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
            border-radius: 0 0 20px 20px;
        }
        
        .book-card {
            height: 100%;
            transition: all 0.3s;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .book-cover {
            height: 200px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
            border-radius: 10px 10px 0 0;
        }
        
        .book-info {
            padding: 15px;
        }
        
        .book-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 5px;
            color: #333;
        }
        
        .book-author {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
        
        .book-description {
            font-size: 0.85rem;
            color: #777;
            margin-bottom: 15px;
            height: 60px;
            overflow: hidden;
        }
        
        .book-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.8rem;
            color: #999;
            margin-bottom: 15px;
        }
        
        .book-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-download {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            font-size: 0.85rem;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .btn-download:hover {
            background: linear-gradient(135deg, #20c997 0%, #28a745 100%);
            color: white;
            transform: scale(1.05);
        }
        
        .btn-read {
            background: linear-gradient(135deg, #007bff 0%, #6610f2 100%);
            border: none;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            font-size: 0.85rem;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .btn-read:hover {
            background: linear-gradient(135deg, #6610f2 0%, #007bff 100%);
            color: white;
            transform: scale(1.05);
        }
        
        .filter-section {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .category-btn {
            margin: 5px;
            padding: 8px 15px;
            border: 1px solid #ddd;
            background: white;
            color: #333;
            border-radius: 20px;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .category-btn:hover,
        .category-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: transparent;
        }
        
        .search-box {
            border-radius: 25px;
            padding: 10px 20px;
            border: 1px solid #ddd;
        }
        
        .search-btn {
            border-radius: 25px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 10px 20px;
        }
        
        .pagination {
            justify-content: center;
            margin-top: 30px;
        }
        
        .page-link {
            color: #667eea;
            border-color: #ddd;
        }
        
        .page-link:hover {
            color: #764ba2;
            background-color: #f8f9fa;
        }
        
        .page-item.active .page-link {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: transparent;
        }
        
        .stats-section {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .stat-item {
            text-align: center;
            padding: 15px;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #667eea;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include 'includes/header.php'; ?>
    
    <!-- Page Header -->
    <div class="page-header">
        <div class="container">
            <h1>المكتبة الإسلامية</h1>
            <p>مجموعة شاملة من الكتب الإسلامية المتنوعة</p>
        </div>
    </div>
    
    <div class="container main-content">
        <!-- Statistics Section -->
        <div class="stats-section">
            <div class="row">
                <div class="col-md-3">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo count($booksData); ?></div>
                        <div class="stat-label">إجمالي الكتب</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo count($categories) - 1; ?></div>
                        <div class="stat-label">الفئات</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo array_sum(array_column($booksData, 'downloads')); ?></div>
                        <div class="stat-label">إجمالي التحميلات</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo array_sum(array_column($booksData, 'pages')); ?></div>
                        <div class="stat-label">إجمالي الصفحات</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Filter Section -->
        <div class="filter-section">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h5 class="mb-3">تصفح حسب الفئة:</h5>
                    <div class="category-filters">
                        <?php foreach ($categories as $catKey => $catName): ?>
                            <a href="?category=<?php echo $catKey; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                               class="category-btn <?php echo ($category === $catKey) ? 'active' : ''; ?>">
                                <?php echo $catName; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="col-md-4">
                    <form method="GET" action="">
                        <input type="hidden" name="category" value="<?php echo $category; ?>">
                        <div class="input-group">
                            <input type="text" class="form-control search-box" name="search" 
                                   placeholder="البحث في الكتب..." value="<?php echo htmlspecialchars($search); ?>">
                            <button class="btn search-btn" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Results Info -->
        <div class="mb-3">
            <p class="text-muted">
                عرض <?php echo count($books); ?> من أصل <?php echo $totalBooks; ?> كتاب
                <?php if (!empty($search)): ?>
                    - نتائج البحث عن: "<?php echo htmlspecialchars($search); ?>"
                <?php endif; ?>
            </p>
        </div>
        
        <!-- Books Grid -->
        <div class="row">
            <?php foreach ($books as $book): ?>
                <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                    <div class="card book-card">
                        <div class="book-cover">
                            <i class="fas fa-book"></i>
                        </div>
                        <div class="book-info">
                            <h6 class="book-title"><?php echo htmlspecialchars($book['title']); ?></h6>
                            <p class="book-author">
                                <i class="fas fa-user me-1"></i>
                                <?php echo htmlspecialchars($book['author']); ?>
                            </p>
                            <p class="book-description">
                                <?php echo htmlspecialchars(substr($book['description'], 0, 100)) . '...'; ?>
                            </p>
                            <div class="book-meta">
                                <span>
                                    <i class="fas fa-file-alt me-1"></i>
                                    <?php echo $book['pages']; ?> صفحة
                                </span>
                                <span>
                                    <i class="fas fa-download me-1"></i>
                                    <?php echo $book['downloads']; ?>
                                </span>
                            </div>
                            <div class="book-actions">
                                <a href="read.php?id=<?php echo $book['id']; ?>" class="btn-read">
                                    <i class="fas fa-book-open me-1"></i>
                                    قراءة
                                </a>
                                <a href="download.php?id=<?php echo $book['id']; ?>" class="btn-download">
                                    <i class="fas fa-download me-1"></i>
                                    تحميل
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if (empty($books)): ?>
                <div class="col-12">
                    <div class="text-center py-5">
                        <i class="fas fa-book-open fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">لا توجد كتب</h4>
                        <p class="text-muted">لم يتم العثور على كتب تطابق معايير البحث.</p>
                        <a href="library.php" class="btn btn-primary">عرض جميع الكتب</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <nav aria-label="تصفح الصفحات">
                <ul class="pagination">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?category=<?php echo $category; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $page - 1; ?>">
                                السابق
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php echo ($i === $page) ? 'active' : ''; ?>">
                            <a class="page-link" href="?category=<?php echo $category; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $i; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?category=<?php echo $category; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $page + 1; ?>">
                                التالي
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
    
    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
