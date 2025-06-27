<?php
/**
 * قراءة الكتب أونلاين
 */
require_once 'config/config.php';

// التحقق من وجود معرف الكتاب
if (!isset($_GET['book']) || !is_numeric($_GET['book'])) {
    header('Location: library.php');
    exit;
}

$bookId = (int)$_GET['book'];

// جلب معلومات الكتاب
$book = $db->fetchOne("SELECT * FROM library_books WHERE id = ? AND status = 'published' AND online_reading = 1", [$bookId]);

if (!$book) {
    header('Location: library.php');
    exit;
}

// تحديث عداد المشاهدات
$db->query("UPDATE library_books SET views_count = views_count + 1 WHERE id = ?", [$bookId]);

// تسجيل زيارة الصفحة
logVisitor($db, "read_book_{$bookId}");

$siteName = getSetting($db, 'site_name', 'مسجد النور');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($book['title']); ?> - <?php echo htmlspecialchars($siteName); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts - Arabic -->
    <link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Cairo', sans-serif;
            background-color: #f8f9fa;
        }
        
        .reader-header {
            background: white;
            border-bottom: 1px solid #dee2e6;
            padding: 15px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .reader-content {
            background: white;
            border-radius: 15px;
            padding: 40px;
            margin: 30px 0;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            min-height: 600px;
        }
        
        .book-title {
            font-family: 'Amiri', serif;
            font-size: 2rem;
            color: #2c3e50;
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #8B4513;
            padding-bottom: 15px;
        }
        
        .book-author {
            text-align: center;
            color: #7f8c8d;
            font-size: 1.1rem;
            margin-bottom: 30px;
        }
        
        .book-text {
            font-family: 'Amiri', serif;
            font-size: 1.2rem;
            line-height: 2;
            text-align: justify;
            color: #2c3e50;
        }
        
        .reading-controls {
            position: fixed;
            bottom: 20px;
            left: 20px;
            background: white;
            border-radius: 50px;
            padding: 10px 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            z-index: 1000;
        }
        
        .font-size-control {
            margin: 0 10px;
        }
        
        .progress-indicator {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: #e9ecef;
            z-index: 1001;
        }
        
        .progress-bar {
            height: 100%;
            background: linear-gradient(135deg, #8B4513 0%, #A0522D 100%);
            width: 0%;
            transition: width 0.3s;
        }
    </style>
</head>
<body>
    <!-- Progress Indicator -->
    <div class="progress-indicator">
        <div class="progress-bar" id="readingProgress"></div>
    </div>

    <!-- Reader Header -->
    <div class="reader-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <a href="library.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-right"></i> العودة للمكتبة
                    </a>
                </div>
                <div class="col-md-6 text-end">
                    <div class="btn-group">
                        <button class="btn btn-outline-secondary" onclick="decreaseFontSize()">
                            <i class="fas fa-minus"></i> أ
                        </button>
                        <button class="btn btn-outline-secondary" onclick="increaseFontSize()">
                            <i class="fas fa-plus"></i> أ
                        </button>
                        <button class="btn btn-outline-secondary" onclick="toggleTheme()">
                            <i class="fas fa-moon"></i>
                        </button>
                        <?php if ($book['file_path']): ?>
                            <a href="download.php?book=<?php echo $book['id']; ?>" class="btn btn-outline-success">
                                <i class="fas fa-download"></i> تحميل
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Reader Content -->
    <div class="container">
        <div class="reader-content" id="readerContent">
            <div class="book-title">
                <?php echo htmlspecialchars($book['title']); ?>
            </div>
            
            <div class="book-author">
                تأليف: <?php echo htmlspecialchars($book['author']); ?>
            </div>
            
            <div class="book-text" id="bookText">
                <?php if ($book['content']): ?>
                    <?php echo nl2br(htmlspecialchars($book['content'])); ?>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-book-open fa-3x text-muted mb-3"></i>
                        <h5>محتوى الكتاب غير متوفر للقراءة الأونلاين</h5>
                        <p class="text-muted">يمكنك تحميل الكتاب للقراءة</p>
                        <?php if ($book['file_path']): ?>
                            <a href="download.php?book=<?php echo $book['id']; ?>" class="btn btn-primary">
                                <i class="fas fa-download"></i> تحميل الكتاب
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Book Info -->
        <div class="row mt-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">معلومات الكتاب</h5>
                        <p class="card-text"><?php echo htmlspecialchars($book['description']); ?></p>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>المؤلف:</strong> <?php echo htmlspecialchars($book['author']); ?></p>
                                <p><strong>الفئة:</strong> <?php echo htmlspecialchars($book['category']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>عدد التحميلات:</strong> <?php echo convertToArabicNumbers($book['download_count']); ?></p>
                                <p><strong>عدد المشاهدات:</strong> <?php echo convertToArabicNumbers($book['views_count']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">مشاركة الكتاب</h5>
                        <div class="d-grid gap-2">
                            <button class="btn btn-primary" onclick="shareBook()">
                                <i class="fas fa-share"></i> مشاركة
                            </button>
                            <button class="btn btn-outline-primary" onclick="copyLink()">
                                <i class="fas fa-link"></i> نسخ الرابط
                            </button>
                            <button class="btn btn-outline-success" onclick="addToFavorites()">
                                <i class="fas fa-bookmark"></i> إضافة للمفضلة
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        let fontSize = 18;
        let isDarkMode = false;
        
        // تحديث شريط التقدم
        function updateProgress() {
            const content = document.getElementById('readerContent');
            const scrollTop = window.pageYOffset;
            const docHeight = document.body.scrollHeight - window.innerHeight;
            const scrollPercent = (scrollTop / docHeight) * 100;
            
            document.getElementById('readingProgress').style.width = scrollPercent + '%';
        }
        
        // زيادة حجم الخط
        function increaseFontSize() {
            if (fontSize < 24) {
                fontSize += 2;
                document.getElementById('bookText').style.fontSize = fontSize + 'px';
            }
        }
        
        // تقليل حجم الخط
        function decreaseFontSize() {
            if (fontSize > 14) {
                fontSize -= 2;
                document.getElementById('bookText').style.fontSize = fontSize + 'px';
            }
        }
        
        // تبديل الوضع الليلي
        function toggleTheme() {
            const content = document.getElementById('readerContent');
            const text = document.getElementById('bookText');
            
            if (isDarkMode) {
                content.style.background = 'white';
                content.style.color = '#2c3e50';
                text.style.color = '#2c3e50';
                isDarkMode = false;
            } else {
                content.style.background = '#2c3e50';
                content.style.color = 'white';
                text.style.color = 'white';
                isDarkMode = true;
            }
        }
        
        // مشاركة الكتاب
        function shareBook() {
            if (navigator.share) {
                navigator.share({
                    title: '<?php echo addslashes($book['title']); ?>',
                    text: 'كتاب: <?php echo addslashes($book['title']); ?> - <?php echo addslashes($book['author']); ?>',
                    url: window.location.href
                });
            } else {
                copyLink();
            }
        }
        
        // نسخ الرابط
        function copyLink() {
            navigator.clipboard.writeText(window.location.href).then(() => {
                alert('تم نسخ الرابط');
            });
        }
        
        // إضافة للمفضلة
        function addToFavorites() {
            // يمكن تطوير هذه الوظيفة لاحقاً
            alert('تم إضافة الكتاب للمفضلة');
        }
        
        // تحديث شريط التقدم عند التمرير
        window.addEventListener('scroll', updateProgress);
        
        // حفظ موضع القراءة
        window.addEventListener('beforeunload', function() {
            localStorage.setItem('reading_position_<?php echo $book['id']; ?>', window.pageYOffset);
        });
        
        // استعادة موضع القراءة
        window.addEventListener('load', function() {
            const savedPosition = localStorage.getItem('reading_position_<?php echo $book['id']; ?>');
            if (savedPosition) {
                window.scrollTo(0, parseInt(savedPosition));
            }
        });
    </script>
</body>
</html>
