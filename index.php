<?php
require_once 'config/auto_config.php';

// تسجيل زيارة الصفحة
logVisitor($db, $_SERVER['REQUEST_URI']);

// جلب الصفحة المطلوبة
$pageSlug = isset($_GET['page']) ? sanitize($_GET['page']) : '';
$currentPage = null;

if ($pageSlug) {
    try {
        $currentPage = $db->fetchOne("SELECT * FROM pages WHERE slug = ? AND status = 'published'", [$pageSlug]);
        if ($currentPage) {
            // زيادة عدد المشاهدات
            $db->query("UPDATE pages SET views_count = views_count + 1 WHERE id = ?", [$currentPage['id']]);
        }
    } catch (Exception $e) {
        // تجاهل الخطأ
        error_log("Error fetching page: " . $e->getMessage());
    }
}

// جلب الصفحات الرئيسية
try {
    $featuredPages = $db->fetchAll("SELECT * FROM pages WHERE status = 'published' AND is_featured = 1 ORDER BY created_at DESC LIMIT 6");
    $recentPages = $db->fetchAll("SELECT * FROM pages WHERE status = 'published' ORDER BY created_at DESC LIMIT 5");
} catch (Exception $e) {
    $featuredPages = [];
    $recentPages = [];
    error_log("Error fetching pages: " . $e->getMessage());
}

// دالة لتنظيف محتوى البلوكات
function sanitizeBlockContent($content) {
    $allowed_tags = [
        'marquee' => ['behavior', 'direction', 'scrollamount'],
        'iframe' => ['src', 'width', 'height', 'frameborder', 'allowfullscreen'],
        'p' => ['style'],
        'div' => ['style'],
        'span' => ['style'],
        'a' => ['href', 'target'],
        'img' => ['src', 'alt', 'width', 'height'],
        'ul' => [],
        'ol' => [],
        'li' => [],
        'strong' => [],
        'em' => [],
        'br' => []
    ];
    
    return strip_tags($content, array_keys($allowed_tags));
}

// جلب البلوكات النشطة
try {
    $rightBlocks = $db->fetchAll("SELECT * FROM blocks WHERE status = 'active' AND position = 'right' ORDER BY display_order");
    $leftBlocks = $db->fetchAll("SELECT * FROM blocks WHERE status = 'active' AND position = 'left' ORDER BY display_order");
    $topBlocks = $db->fetchAll("SELECT * FROM blocks WHERE status = 'active' AND position = 'top' ORDER BY display_order");
    $bottomBlocks = $db->fetchAll("SELECT * FROM blocks WHERE status = 'active' AND position = 'bottom' ORDER BY display_order");
    $centerBlocks = $db->fetchAll("SELECT * FROM blocks WHERE status = 'active' AND position = 'center' ORDER BY display_order");
    
    // تطبيق التنظيف على المحتوى
    foreach ([&$rightBlocks, &$leftBlocks, &$topBlocks, &$bottomBlocks, &$centerBlocks] as $blocks) {
        foreach ($blocks as &$block) {
            if (in_array($block['block_type'], ['custom', 'html', 'marquee', 'iframe'])) {
                $block['content'] = sanitizeBlockContent($block['content']);
            }
        }
    }
} catch (Exception $e) {
    $rightBlocks = [];
    $leftBlocks = [];
    $topBlocks = [];
    $bottomBlocks = [];
    $centerBlocks = [];
    error_log("Error fetching blocks: " . $e->getMessage());
}

// جلب الإعلانات النشطة
try {
    $headerAds = $db->fetchAll("
        SELECT * FROM advertisements 
        WHERE status = 'active' 
        AND position = 'header' 
        AND (start_date IS NULL OR start_date <= CURDATE()) 
        AND (end_date IS NULL OR end_date >= CURDATE())
        ORDER BY created_at DESC
    ");
    
    $sidebarAds = $db->fetchAll("
        SELECT * FROM advertisements 
        WHERE status = 'active' 
        AND position = 'sidebar' 
        AND (start_date IS NULL OR start_date <= CURDATE()) 
        AND (end_date IS NULL OR end_date >= CURDATE())
        ORDER BY created_at DESC
    ");
    
    $footerAds = $db->fetchAll("
        SELECT * FROM advertisements 
        WHERE status = 'active' 
        AND position = 'footer' 
        AND (start_date IS NULL OR start_date <= CURDATE()) 
        AND (end_date IS NULL OR end_date >= CURDATE())
        ORDER BY created_at DESC
    ");
    
    // جلب الإعلانات الهامة بشكل منفصل
    $importantAds = $db->fetchAll("
        SELECT * FROM advertisements 
        WHERE status = 'active' 
        AND is_important = 1 
        AND (start_date IS NULL OR start_date <= CURDATE()) 
        AND (end_date IS NULL OR end_date >= CURDATE())
        ORDER BY created_at DESC
    ");
} catch (Exception $e) {
    $headerAds = [];
    $sidebarAds = [];
    $footerAds = [];
    $importantAds = [];
    error_log("Error fetching ads: " . $e->getMessage());
}

// جلب الاستطلاع النشط مع خياراته
try {
    $activePoll = $db->fetchOne("
        SELECT * FROM polls 
        WHERE status = 'active' 
        AND (start_date IS NULL OR start_date <= CURDATE()) 
        AND (end_date IS NULL OR end_date >= CURDATE()) 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    
    if ($activePoll) {
        $pollOptions = $db->fetchAll("
            SELECT po.*, 
                   COUNT(pv.id) as votes_count
            FROM poll_options po
            LEFT JOIN poll_votes pv ON po.id = pv.option_id
            WHERE po.poll_id = ?
            GROUP BY po.id
            ORDER BY po.display_order
        ", [$activePoll['id']]);
        
        // حساب إجمالي الأصوات
        $totalVotes = $db->fetchOne("
            SELECT COUNT(*) as total 
            FROM poll_votes 
            WHERE poll_id = ?
        ", [$activePoll['id']])['total'] ?? 0;
        
        // حساب النسب المئوية
        foreach ($pollOptions as &$option) {
            $option['percentage'] = $totalVotes > 0 ? 
                round(($option['votes_count'] * 100) / $totalVotes, 1) : 0;
        }
        
        // التحقق من تصويت المستخدم
        $userVoted = false;
        if (isLoggedIn()) {
            $userVote = $db->fetchOne("
                SELECT * FROM poll_votes 
                WHERE poll_id = ? AND user_id = ?
            ", [$activePoll['id'], $_SESSION['user_id']]);
            $userVoted = !empty($userVote);
        } elseif (isset($_COOKIE['poll_' . $activePoll['id']])) {
            $userVoted = true;
        }
    }
} catch (Exception $e) {
    $activePoll = null;
    error_log("Error fetching poll: " . $e->getMessage());
}

// معالجة التصويت
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vote_option']) && $activePoll) {
    $voteOptions = is_array($_POST['vote_option']) ? $_POST['vote_option'] : [$_POST['vote_option']];
    
    try {
        // التحقق من عدم التصويت المسبق
        $hasVoted = false;
        if (isLoggedIn()) {
            $existingVote = $db->fetchOne("
                SELECT id FROM poll_votes 
                WHERE poll_id = ? AND user_id = ?
            ", [$activePoll['id'], $_SESSION['user_id']]);
            $hasVoted = !empty($existingVote);
        } elseif (isset($_COOKIE['poll_' . $activePoll['id']])) {
            $hasVoted = true;
        }
        
        if (!$hasVoted) {
            $db->beginTransaction();
            
            foreach ($voteOptions as $optionId) {
                $optionId = (int)$optionId;
                
                // التحقق من صحة الخيار
                $validOption = $db->fetchOne("
                    SELECT id FROM poll_options 
                    WHERE id = ? AND poll_id = ?
                ", [$optionId, $activePoll['id']]);
                
                if ($validOption) {
                    $db->query("
                        INSERT INTO poll_votes (poll_id, option_id, user_id, voter_ip, created_at) 
                        VALUES (?, ?, ?, ?, NOW())
                    ", [
                        $activePoll['id'],
                        $optionId,
                        isLoggedIn() ? $_SESSION['user_id'] : null,
                        $_SERVER['REMOTE_ADDR']
                    ]);
                }
            }
            
            $db->commit();
            
            // تعيين كوكي للمستخدمين غير المسجلين
            if (!isLoggedIn()) {
                setcookie('poll_' . $activePoll['id'], '1', time() + (30 * 24 * 60 * 60), '/');
            }
            
            $_SESSION['success'] = 'تم تسجيل صوتك بنجاح!';
        } else {
            $_SESSION['error'] = 'لقد قمت بالتصويت مسبقاً في هذا الاستطلاع.';
        }
    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['error'] = 'حدث خطأ أثناء تسجيل الصوت: ' . $e->getMessage();
    }
    
    header('Location: index.php');
    exit;
}



// إعدادات الموقع
$siteName = getSetting($db, 'site_name', 'مسجد النور');
$siteDescription = getSetting($db, 'site_description', 'موقع مسجد النور الرسمي');
$primaryColor = getSetting($db, 'primary_color', '#2c5530');
$secondaryColor = getSetting($db, 'secondary_color', '#1e3a1e');

// جلب أوقات الصلاة
$prayerTimes = getPrayerTimes($db);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $currentPage ? htmlspecialchars($currentPage['title']) . ' - ' : ''; ?><?php echo htmlspecialchars($siteName); ?></title>
    <meta name="description" content="<?php echo $currentPage ? htmlspecialchars($currentPage['meta_description'] ?: $currentPage['excerpt']) : htmlspecialchars($siteDescription); ?>">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts - Arabic -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
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
            background: var(--primary-color);
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
</head>
<body>
 <!-- Header -->
    <?php include 'includes/ui/header.php'; ?>
        <div class="hero-section">
            <div class="hero-section">
                  <div class="container">
                  <h1>مرحباً بكم في <?php echo htmlspecialchars($siteDescription); ?></h1>
                  
            </div>
</div>
        </div>
    <!-- الإعلانات الهامة في أعلى الصفحة -->
    <?php if (!empty($importantAds)): ?>
        <div class="container mt-3">
            <div class="row">
                <?php foreach ($importantAds as $ad): ?>
                    <div class="col-12 mb-3">
                        <div class="important-ad">
                            <?php if ($ad['link_url']): ?>
                                <a href="<?php echo htmlspecialchars($ad['link_url']); ?>" target="_blank" 
                                   onclick="trackAdClick(<?php echo $ad['id']; ?>)">
                            <?php endif; ?>
                            
                            <h6><i class="fas fa-bullhorn me-2"></i><?php echo htmlspecialchars($ad['title']); ?></h6>
                            
                            <div class="ad-content">
                                <?php if ($ad['image']): ?>
                                    <img src="uploads/<?php echo htmlspecialchars($ad['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($ad['title']); ?>" class="img-fluid mb-2">
                                <?php endif; ?>
                                
                                <?php if ($ad['content']): ?>
                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($ad['content'])); ?></p>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($ad['link_url']): ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Header Ads - بعد الهيدر مباشرة -->
    <?php if (!empty($headerAds)): ?>
        <div class="container mt-3">
            <?php foreach ($headerAds as $ad): ?>
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

    <!-- Top Blocks -->
    <?php if (!empty($topBlocks)): ?>
        <div class="container mt-4">
            <div class="row">
                <?php foreach ($topBlocks as $block): ?>
                    <div class="col-12">
                        <?php echo renderBlock($block); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Main Content -->
    <?php if ($currentPage): ?>
        <!-- Single Page View -->
        <div class="page-content">
            <div class="container">
                <div class="row">
                    <!-- Left Sidebar -->
                    <div class="col-lg-3">
                        <?php foreach ($leftBlocks as $block): ?>
                            <?php echo renderBlock($block); ?>
                        <?php endforeach; ?>
                        
                        
                    </div>
                    
                    <!-- Main Content -->
                    <div class="col-lg-6">
                        <article class="card">
                            <?php if ($currentPage['featured_image']): ?>
                                <img src="uploads/<?php echo htmlspecialchars($currentPage['featured_image']); ?>" 
                                     class="card-img-top" alt="<?php echo htmlspecialchars($currentPage['title']); ?>">
                            <?php endif; ?>
                            
                            <div class="card-body">
                                <h1 class="card-title"><?php echo htmlspecialchars($currentPage['title']); ?></h1>
                                
                                <div class="text-muted mb-3">
                                    <i class="fas fa-calendar me-2"></i>
                                    <?php echo formatArabicDate($currentPage['created_at']); ?>
                                    <i class="fas fa-eye me-2 ms-3"></i>
                                    <?php echo convertToArabicNumbers($currentPage['views_count']); ?> مشاهدة
                                </div>
                                
                                <div class="card-text">
                                    <?php echo nl2br($currentPage['content']); ?>
                                </div>
                                
                                <!-- Rating System -->
                                <?php if ($currentPage['allow_ratings']): ?>
                                    <div class="rating-section mt-4">
                                        <h5>قيم هذه الصفحة</h5>
                                        <div class="rating-stars">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star" data-rating="<?php echo $i; ?>" 
                                                   onclick="rateContent(<?php echo $currentPage['id']; ?>, <?php echo $i; ?>)"></i>
                                            <?php endfor; ?>
                                        </div>
                                        <div id="rating-message"></div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </article>
                        
                        <!-- Center Blocks -->
                        <?php foreach ($centerBlocks as $block): ?>
                            <?php echo renderBlock($block); ?>
                        <?php endforeach; ?>
                        
                        <!-- Comments Section -->
                        <?php if ($currentPage['allow_comments']): ?>
                            <div class="comments-section">
                                <h4>التعليقات</h4>
                                
                                <?php if (isLoggedIn()): ?>
                                    <!-- Add Comment Form -->
                                    <form method="POST" action="submit_comment.php" class="mb-4">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <input type="hidden" name="page_id" value="<?php echo $currentPage['id']; ?>">
                                        <input type="hidden" name="return_url" value="<?php echo $_SERVER['REQUEST_URI']; ?>">
                                        
                                        <div class="mb-3">
                                            <label for="comment_content" class="form-label">أضف تعليقك</label>
                                            <textarea class="form-control" id="comment_content" name="content" 
                                                      rows="4" placeholder="شاركنا رأيك..." required></textarea>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-paper-plane me-2"></i>
                                            إرسال التعليق
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        يجب <a href="login.php">تسجيل الدخول</a> لإضافة تعليق
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Display Comments -->
                                <?php
                                try {
                                    $comments = $db->fetchAll("
                                        SELECT c.*, u.full_name 
                                        FROM comments c 
                                        LEFT JOIN users u ON c.user_id = u.id 
                                        WHERE c.page_id = ? AND c.status = 'approved' 
                                        ORDER BY c.created_at DESC
                                    ", [$currentPage['id']]);
                                    
                                    if (empty($comments)): ?>
                                        <div class="text-center text-muted py-4">
                                            <i class="fas fa-comments fa-3x mb-3"></i>
                                            <p>لا توجد تعليقات بعد. كن أول من يعلق!</p>
                                        </div>
                                    <?php else:
                                        foreach ($comments as $comment):
                                    ?>
                                        <div class="comment-item">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <h6 class="mb-0"><?php echo htmlspecialchars($comment['full_name'] ?: 'زائر'); ?></h6>
                                                <small class="text-muted"><?php echo formatArabicDate($comment['created_at']); ?></small>
                                            </div>
                                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>
                                        </div>
                                    <?php 
                                        endforeach;
                                    endif;
                                } catch (Exception $e) {
                                    // تجاهل الخطأ
                                    error_log("Error fetching comments: " . $e->getMessage());
                                }
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Right Sidebar -->
                    <div class="col-lg-3">
                        <?php foreach ($rightBlocks as $block): ?>
                            <?php echo renderBlock($block); ?>
                        <?php endforeach; ?>
                        
<!-- تم إزالة كود عرض الاستطلاع المكرر من هنا -->
                        
                        <!-- Sidebar Ads -->
                        <?php foreach ($sidebarAds as $ad): ?>
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
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Homepage -->
        
        
        <div class="page-content">
            <div class="container">
                <div class="row">
                    <!-- Left Sidebar -->
                    <div class="col-lg-3">
                        <?php foreach ($leftBlocks as $block): ?>
                            <?php echo renderBlock($block); ?>
                        <?php endforeach; ?>
                        
                        
                    </div>
                    
                    <!-- Main Content -->
                    <div class="col-lg-6">
					<!-- Center Blocks -->
                        <?php foreach ($centerBlocks as $block): ?>
                            <?php echo renderBlock($block); ?>
                        <?php endforeach; ?>
                        <?php if (!empty($featuredPages)): ?>
                            <h2 class="mb-4">الصفحات المميزة</h2>
                            <div class="row">
                                <?php foreach ($featuredPages as $page): ?>
                                    <div class="col-md-6 mb-4">
                                        <div class="card h-100">
                                            <?php if ($page['featured_image']): ?>
                                                <img src="uploads/<?php echo htmlspecialchars($page['featured_image']); ?>" 
                                                     class="card-img-top" alt="<?php echo htmlspecialchars($page['title']); ?>">
                                            <?php endif; ?>
                                            
                                            <div class="card-body">
                                                <h5 class="card-title"><?php echo htmlspecialchars($page['title']); ?></h5>
                                                <p class="card-text"><?php echo truncateText(strip_tags($page['excerpt'] ?: $page['content']), 100); ?></p>
                                            </div>
                                            
                                            <div class="card-footer bg-white border-0">
                                                <a href="?page=<?php echo htmlspecialchars($page['slug']); ?>" class="btn btn-primary">
                                                    اقرأ المزيد
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                                <h4 class="text-muted">لا توجد صفحات بعد</h4>
                                <p class="text-muted">سيتم إضافة المحتوى قريباً</p>
                            </div>
                        <?php endif; ?>
                        
                        
                    </div>
                    
                    <!-- Right Sidebar -->
                    <div class="col-lg-3">
                        <?php foreach ($rightBlocks as $block): ?>
                            <?php echo renderBlock($block); ?>
                        <?php endforeach; ?>
                        
                        <!-- Poll Widget -->
                        <?php if ($activePoll): ?>
                            <div class="poll-widget">
                                <h5><?php echo htmlspecialchars($activePoll['title']); ?></h5>
                                <?php if ($activePoll['description']): ?>
                                    <p class="text-muted"><?php echo htmlspecialchars($activePoll['description']); ?></p>
                                <?php endif; ?>
                                
                                <?php if ($userVoted): ?>
                                    <!-- Poll Results -->
                                    <div class="poll-results">
                                        <?php foreach ($pollOptions as $option): ?>
                                            <?php 
                                                $percentage = $totalVotes > 0 ? round(($option['votes_count'] / $totalVotes) * 100) : 0;
                                            ?>
                                            <div class="poll-result">
                                                <div class="d-flex justify-content-between mb-1">
                                                    <span><?php echo htmlspecialchars($option['option_text']); ?></span>
                                                    <span><?php echo $percentage; ?>%</span>
                                                </div>
                                                <div class="poll-progress">
                                                    <div class="poll-progress-bar" style="width: <?php echo $percentage; ?>%"></div>
                                                </div>
                                                <small class="text-muted"><?php echo convertToArabicNumbers($option['votes_count']); ?> صوت</small>
                                            </div>
                                        <?php endforeach; ?>
                                        <div class="mt-3 text-center">
                                            <small class="text-muted">إجمالي الأصوات: <?php echo convertToArabicNumbers($totalVotes); ?></small>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <!-- Poll Voting Form -->
                                    <form method="POST">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <?php foreach ($pollOptions as $option): ?>
                                            <div class="poll-option form-check">
                                                <input class="form-check-input" type="radio" name="vote_option" 
                                                       id="option_<?php echo $option['id']; ?>" value="<?php echo $option['id']; ?>" required>
                                                <label class="form-check-label" for="option_<?php echo $option['id']; ?>">
                                                    <?php echo htmlspecialchars($option['option_text']); ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                        <button type="submit" class="btn btn-primary btn-sm mt-3">
                                            <i class="fas fa-vote-yea me-1"></i> تصويت
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Sidebar Ads -->
                        <?php foreach ($sidebarAds as $ad): ?>
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
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Bottom Blocks -->
    <?php if (!empty($bottomBlocks)): ?>
        <div class="container">
            <div class="row">
                <?php foreach ($bottomBlocks as $block): ?>
                    <div class="col-12">
                        <?php echo renderBlock($block); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

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
    <?php include 'includes/ui/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // تتبع النقرات على الإعلانات
        function trackAdClick(adId) {
            fetch('track_ad_click.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ad_id: adId})
            });
        }
        
        // نظام التقييم
        function rateContent(pageId, rating) {
            fetch('submit_rating.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    page_id: pageId,
                    rating: rating,
                    csrf_token: '<?php echo generateCSRFToken(); ?>'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('rating-message').innerHTML = 
                        '<div class="alert alert-success">شكراً لك على التقييم!</div>';
                    
                    // تحديث لون النجوم
                    const stars = document.querySelectorAll('.rating-stars .fa-star');
                    stars.forEach((star, index) => {
                        if (index < rating) {
                            star.classList.add('text-warning');
                        } else {
                            star.classList.remove('text-warning');
                        }
                    });
                } else {
                    document.getElementById('rating-message').innerHTML = 
                        '<div class="alert alert-danger">حدث خطأ أثناء التقييم. يرجى المحاولة مرة أخرى.</div>';
                }
            });
        }
        
        // تحديث الطقس (وهمي)
        function refreshWeather() {
            alert('سيتم تحديث بيانات الطقس قريباً');
        }
    </script>
</body>
<?php
// دالة لعرض البلوكات
function renderBlock($block) {
    global $db;
    
    $html = '<div class="block-widget ' . ($block['css_class'] ?? '') . '">';
    
    if ($block['show_title']) {
        $icon = $block['icon'] ?? 'fas fa-cube';
        $html .= '<h5><i class="' . $icon . ' me-2"></i>' . htmlspecialchars($block['title']) . '</h5>';
    }
    
    // دعم جميع أنواع البلوكات
    if (in_array($block['block_type'], ['custom', 'html', 'marquee', 'iframe'])) {
        $html .= $block['content'];
    } else {
        switch ($block['block_type']) {
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
                
            case 'important_ads':
                $html .= renderImportantAdsBlock($db);
                break;
                
            case 'news_ticker':
                $html .= renderNewsTickerBlock($db);
                break;
                
            default:
                $html .= $block['content'];
                break;
        }
    }
    
    $html .= '</div>';
    
    return $html;
}

// دالة لعرض الإعلانات الهامة
function renderImportantAdsBlock($db) {
    try {
        // جلب الإعلانات الهامة
        $importantAds = $db->fetchAll("
            SELECT * FROM advertisements 
            WHERE status = 'active' 
            AND is_important = 1 
            AND (start_date IS NULL OR start_date <= CURDATE()) 
            AND (end_date IS NULL OR end_date >= CURDATE())
            ORDER BY created_at DESC
        ");
        
        if (empty($importantAds)) {
            return '<div class="alert alert-info text-center py-2">لا توجد إعلانات هامة حالياً</div>';
        }
        
        $html = '<div class="important-ads">';
        foreach ($importantAds as $ad) {
            $html .= '<div class="important-ad mb-3">';
            
            if ($ad['link_url']) {
                $html .= '<a href="' . htmlspecialchars($ad['link_url']) . '" target="_blank" 
                           onclick="trackAdClick(' . $ad['id'] . ')" class="text-decoration-none">';
            }
            
            $html .= '<h6><i class="fas fa-bullhorn me-2"></i>' . htmlspecialchars($ad['title']) . '</h6>';
            
            if ($ad['image']) {
                $html .= '<img src="uploads/' . htmlspecialchars($ad['image']) . '" 
                             alt="' . htmlspecialchars($ad['title']) . '" class="img-fluid mb-2">';
            }
            
            if ($ad['content']) {
                $html .= '<p>' . nl2br(htmlspecialchars($ad['content'])) . '</p>';
            }
            
            if ($ad['link_url']) {
                $html .= '</a>';
            }
            
            $html .= '</div>';
        }
        $html .= '</div>';
        
        return $html;
    } catch (Exception $e) {
        error_log("Error rendering important ads: " . $e->getMessage());
        return '<div class="alert alert-danger">حدث خطأ في عرض الإعلانات الهامة</div>';
    }
}

// دالة لعرض شريط الأخبار
function renderNewsTickerBlock($db) {
    try {
        // جلب آخر 5 أخبار
        $news = $db->fetchAll("SELECT * FROM pages WHERE status = 'published' AND type = 'news' ORDER BY created_at DESC LIMIT 5");
        
        if (empty($news)) {
            return '<div class="alert alert-info text-center py-2">لا توجد أخبار حالياً</div>';
        }
        
        $html = '<div class="news-ticker">';
        $html .= '<div class="ticker-header">';
        $html .= '<i class="fas fa-bullhorn me-2"></i>آخر الأخبار';
        $html .= '</div>';
        $html .= '<div class="ticker-content">';
        
        foreach ($news as $item) {
            $html .= '<div class="ticker-item">';
            $html .= '<a href="?page=' . htmlspecialchars($item['slug']) . '">';
            $html .= htmlspecialchars($item['title']);
            $html .= '</a>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    } catch (Exception $e) {
        error_log("Error rendering news ticker: " . $e->getMessage());
        return '<div class="alert alert-danger">حدث خطأ في عرض شريط الأخبار</div>';
    }
}

function renderPrayerTimesBlock() {
    global $prayerTimes;
    
    $html = '<div class="prayer-times-widget">';
    $html .= '<h5 class="text-center mb-3">أوقات الصلاة</h5>';
    
    $prayers = [
        'fajr' => 'الفجر',
        'dhuhr' => 'الظهر',
        'asr' => 'العصر',
        'maghrib' => 'المغرب',
        'isha' => 'العشاء'
    ];
    
    foreach ($prayers as $key => $prayer) {
        $html .= '<div class="prayer-time-item">';
        $html .= '<span>' . $prayer . '</span>';
        $html .= '<strong>' . $prayerTimes[$key] . '</strong>';
        $html .= '</div>';
    }
    
    $html .= '</div>';
    
    return $html;
}

function renderWeatherBlock() {
    $city = getSetting(null, 'weather_city', 'مكة المكرمة');
    $temp = rand(20, 35);
    $condition = ['مشمس', 'غائم جزئياً', 'غائم', 'ممطر'][rand(0, 3)];
    $humidity = rand(30, 80);
    
    $html = '<div class="weather-widget">';
    $html .= '<div class="d-flex justify-content-between align-items-center mb-3">';
    $html .= '<h6 class="mb-0">' . htmlspecialchars($city) . '</h6>';
    $html .= '<i class="fas fa-sync-alt" onclick="refreshWeather()" style="cursor: pointer;"></i>';
    $html .= '</div>';
    
    $html .= '<div class="text-center">';
    
    if (strpos($condition, 'مشمس') !== false) {
        $html .= '<i class="fas fa-sun fa-3x text-warning mb-2"></i>';
    } elseif (strpos($condition, 'غائم جزئياً') !== false) {
        $html .= '<i class="fas fa-cloud-sun fa-3x text-secondary mb-2"></i>';
    } elseif (strpos($condition, 'غائم') !== false) {
        $html .= '<i class="fas fa-cloud fa-3x text-secondary mb-2"></i>';
    } else {
        $html .= '<i class="fas fa-cloud-rain fa-3x text-primary mb-2"></i>';
    }
    
    $html .= '<h3 class="mb-0">' . convertToArabicNumbers($temp) . '°C</h3>';
    $html .= '<p>' . $condition . '</p>';
    
    $html .= '<div class="d-flex justify-content-around mt-3">';
    $html .= '<div><i class="fas fa-tint text-primary me-1"></i> ' . convertToArabicNumbers($humidity) . '%</div>';
    $html .= '<div><i class="fas fa-wind text-muted me-1"></i> ' . convertToArabicNumbers(rand(5, 20)) . ' كم/س</div>';
    $html .= '</div>';
    
    $html .= '</div>';
    $html .= '</div>';
    
    return $html;
}

function renderRecentPagesBlock($db) {
    try {
        $pages = $db->fetchAll("SELECT * FROM pages WHERE status = 'published' ORDER BY created_at DESC LIMIT 5");
        
        $html = '<ul class="list-group list-group-flush">';
        
        if (empty($pages)) {
            $html .= '<li class="list-group-item text-center text-muted">لا توجد صفحات حديثة</li>';
        } else {
            foreach ($pages as $page) {
                $html .= '<li class="list-group-item">';
                $html .= '<a href="?page=' . htmlspecialchars($page['slug']) . '" class="text-decoration-none">';
                $html .= htmlspecialchars($page['title']);
                $html .= '</a>';
                $html .= '<br><small class="text-muted">' . formatArabicDate($page['created_at']) . '</small>';
                $html .= '</li>';
            }
        }
        
        $html .= '</ul>';
        
        return $html;
    } catch (Exception $e) {
        return '<div class="alert alert-warning">تعذر جلب الصفحات الحديثة</div>';
    }
}

function renderVisitorStatsBlock($db) {
    try {
        $today = $db->fetchOne("SELECT COUNT(*) as count FROM visitors WHERE DATE(visit_time) = CURDATE()")['count'] ?? 0;
        $yesterday = $db->fetchOne("SELECT COUNT(*) as count FROM visitors WHERE DATE(visit_time) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)")['count'] ?? 0;
        $thisMonth = $db->fetchOne("SELECT COUNT(*) as count FROM visitors WHERE MONTH(visit_time) = MONTH(CURDATE()) AND YEAR(visit_time) = YEAR(CURDATE())")['count'] ?? 0;
        
        // إجمالي الزوار الحقيقي + القيمة الأساسية
        $baseVisitors = 84678;
        $currentVisitors = $db->fetchOne("SELECT COUNT(*) as count FROM visitors")['count'] ?? 0;
        $total = $baseVisitors + $currentVisitors;
        
        $html = '<div class="visitor-stats">';
        $html .= '<div class="row">';
        
        $stats = [
            [
                'icon' => 'fas fa-calendar-day',
                'value' => $today,
                'label' => 'زوار اليوم'
            ],
            [
                'icon' => 'fas fa-calendar-check',
                'value' => $yesterday,
                'label' => 'زوار الأمس'
            ],
            [
                'icon' => 'fas fa-calendar-alt',
                'value' => $thisMonth,
                'label' => 'زوار الشهر'
            ],
            [
                'icon' => 'fas fa-users',
                'value' => $total,
                'label' => 'إجمالي الزوار'
            ]
        ];
        
        foreach ($stats as $stat) {
            $html .= '<div class="col-md-6 mb-3">';
            $html .= '<div class="stat-card">';
            $html .= '<i class="' . $stat['icon'] . '"></i>';
            $html .= '<div class="stat-number">' . convertToArabicNumbers($stat['value']) . '</div>';
            $html .= '<div class="stat-label">' . $stat['label'] . '</div>';
            $html .= '</div>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    } catch (Exception $e) {
        return '<div class="alert alert-warning">تعذر جلب إحصائيات الزوار</div>';
    }
}

function renderQuranVerseBlock() {
    $verses = [
        ['text' => 'إِنَّ اللَّهَ لَا يُضِيعُ أَجْرَ الْمُحْسِنِينَ', 'surah' => 'التوبة', 'ayah' => '120'],
        ['text' => 'وَمَا تَوْفِيقِي إِلَّا بِاللَّهِ عَلَيْهِ تَوَكَّلْتُ وَإِلَيْهِ أُنِيبُ', 'surah' => 'هود', 'ayah' => '88'],
        ['text' => 'رَبَّنَا آتِنَا فِي الدُّنْيَا حَسَنَةً وَفِي الْآخِرَةِ حَسَنَةً وَقِنَا عَذَابَ النَّارِ', 'surah' => 'البقرة', 'ayah' => '201'],
        ['text' => 'وَاللَّهُ يَرْزُقُ مَن يَشَاءُ بِغَيْرِ حِسَابٍ', 'surah' => 'البقرة', 'ayah' => '212'],
        ['text' => 'إِنَّ مَعَ الْعُسْرِ يُسْرًا', 'surah' => 'الشرح', 'ayah' => '6']
    ];
    
    $verse = $verses[array_rand($verses)];
    
    $html = '<div class="quran-verse text-center">';
    $html .= '<i class="fas fa-book-open text-success mb-3 fa-2x"></i>';
    $html .= '<p class="fs-5 fw-bold mb-2">' . $verse['text'] . '</p>';
    $html .= '<p class="text-muted">سورة ' . $verse['surah'] . ' - الآية ' . convertToArabicNumbers($verse['ayah']) . '</p>';
    $html .= '</div>';
    
    return $html;
}

function renderHadithBlock() {
    $hadiths = [
        ['text' => 'إنما الأعمال بالنيات، وإنما لكل امرئ ما نوى', 'narrator' => 'متفق عليه'],
        ['text' => 'من حسن إسلام المرء تركه ما لا يعنيه', 'narrator' => 'رواه الترمذي'],
        ['text' => 'المسلم من سلم المسلمون من لسانه ويده', 'narrator' => 'متفق عليه'],
        ['text' => 'لا يؤمن أحدكم حتى يحب لأخيه ما يحب لنفسه', 'narrator' => 'متفق عليه'],
        ['text' => 'الدين النصيحة', 'narrator' => 'رواه مسلم']
    ];
    
    $hadith = $hadiths[array_rand($hadiths)];
    
    $html = '<div class="hadith text-center">';
    $html .= '<i class="fas fa-quote-right text-primary mb-3 fa-2x"></i>';
    $html .= '<p class="fs-5 fw-bold mb-2">' . $hadith['text'] . '</p>';
    $html .= '<p class="text-muted">' . $hadith['narrator'] . '</p>';
    $html .= '</div>';
    
    return $html;
}

function renderSocialLinksBlock($db) {
    $facebook = getSetting($db, 'facebook_url');
    $twitter = getSetting($db, 'twitter_url');
    $instagram = getSetting($db, 'instagram_url');
    $youtube = getSetting($db, 'youtube_url');
    
    $html = '<div class="social-links text-center">';
    
    if ($facebook || $twitter || $instagram || $youtube) {
        $html .= '<div class="d-flex justify-content-center gap-3 fs-3">';
        
        if ($facebook) {
            $html .= '<a href="' . htmlspecialchars($facebook) . '" target="_blank" class="text-primary">';
            $html .= '<i class="fab fa-facebook"></i>';
            $html .= '</a>';
        }
        
        if ($twitter) {
            $html .= '<a href="' . htmlspecialchars($twitter) . '" target="_blank" class="text-info">';
            $html .= '<i class="fab fa-twitter"></i>';
            $html .= '</a>';
        }
        
        if ($instagram) {
            $html .= '<a href="' . htmlspecialchars($instagram) . '" target="_blank" class="text-danger">';
            $html .= '<i class="fab fa-instagram"></i>';
            $html .= '</a>';
        }
        
        if ($youtube) {
            $html .= '<a href="' . htmlspecialchars($youtube) . '" target="_blank" class="text-danger">';
            $html .= '<i class="fab fa-youtube"></i>';
            $html .= '</a>';
        }
        
        $html .= '</div>';
    } else {
        $html .= '<p class="text-muted">لم يتم تعيين روابط التواصل الاجتماعي بعد</p>';
    }
    
    $html .= '</div>';
    
    return $html;
}

function renderQuickLinksBlock($db) {
    try {
        $pages = $db->fetchAll("SELECT * FROM pages WHERE status = 'published' AND is_featured = 1 ORDER BY title LIMIT 5");
        
        $html = '<ul class="list-group list-group-flush">';
        
        if (empty($pages)) {
            $html .= '<li class="list-group-item text-center text-muted">لا توجد روابط سريعة</li>';
        } else {
            foreach ($pages as $page) {
                $html .= '<li class="list-group-item">';
                $html .= '<a href="?page=' . htmlspecialchars($page['slug']) . '" class="text-decoration-none">';
                $html .= '<i class="fas fa-link me-2 text-primary"></i>' . htmlspecialchars($page['title']);
                $html .= '</a>';
                $html .= '</li>';
            }
        }
        
        $html .= '</ul>';
        
        return $html;
    } catch (Exception $e) {
        return '<div class="alert alert-warning">تعذر جلب الروابط السريعة</div>';
    }
}

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

function getPrayerTimes($db) {
    $city = getSetting($db, 'prayer_times_city', 'مكة المكرمة');
    $method = getSetting($db, 'prayer_calculation_method', '4');
    
    try {
        $today = date('Y-m-d');
        $prayerTimes = $db->fetchOne("SELECT * FROM prayer_times WHERE date = ? AND city = ?", [$today, $city]);
        
        if ($prayerTimes) {
            return [
                'fajr' => $prayerTimes['fajr'],
                'dhuhr' => $prayerTimes['dhuhr'],
                'asr' => $prayerTimes['asr'],
                'maghrib' => $prayerTimes['maghrib'],
                'isha' => $prayerTimes['isha']
            ];
        }
    } catch (Exception $e) {
        error_log("Error fetching prayer times: " . $e->getMessage());
    }
    
    return [
        'fajr' => '04:30',
        'dhuhr' => '12:15',
        'asr' => '15:45',
        'maghrib' => '18:30',
        'isha' => '20:00'
    ];
}

// تأكد من وجود حقل is_important في جدول الإعلانات
function checkImportantAdsField($db) {
    try {
        $columns = $db->fetchAll("SHOW COLUMNS FROM advertisements LIKE 'is_important'");
        if (empty($columns)) {
            $db->query("ALTER TABLE advertisements ADD COLUMN is_important TINYINT(1) DEFAULT 0");
        }
    } catch (Exception $e) {
        error_log("Error checking is_important field: " . $e->getMessage());
    }
}

// تنفيذ التحقق عند التحميل
checkImportantAdsField($db);
?>