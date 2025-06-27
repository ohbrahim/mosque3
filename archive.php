<?php
require_once 'config/config.php';

// تسجيل زيارة الصفحة
logVisitor($db, $_SERVER['REQUEST_URI']);

// معاملات البحث والتصفية
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$sort = isset($_GET['sort']) ? sanitize($_GET['sort']) : 'latest';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

// جلب الصفحة المطلوبة (إذا كان هناك slug)
$pageSlug = isset($_GET['view']) ? sanitize($_GET['view']) : '';
$currentPage = null;

if ($pageSlug) {
    try {
        $currentPage = $db->fetchOne("SELECT * FROM pages WHERE slug = ? AND status = 'published'", [$pageSlug]);
        if ($currentPage) {
            // زيادة عدد المشاهدات
            $db->query("UPDATE pages SET views_count = views_count + 1 WHERE id = ?", [$currentPage['id']]);
        }
    } catch (Exception $e) {
        error_log("Error fetching page: " . $e->getMessage());
    }
}

// بناء استعلام البحث للأرشيف
$where_conditions = ["p.status = 'published'"];
$params = [];

if ($category_id > 0) {
    $where_conditions[] = "p.category_id = ?";
    $params[] = $category_id;
}

if (!empty($search)) {
    $where_conditions[] = "(p.title LIKE ? OR p.content LIKE ? OR p.excerpt LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

$where_clause = implode(' AND ', $where_conditions);

// ترتيب النتائج
$order_by = match($sort) {
    'oldest' => 'p.created_at ASC',
    'title' => 'p.title ASC',
    'views' => 'p.views_count DESC',
    'featured' => 'p.is_featured DESC, p.created_at DESC',
    default => 'p.created_at DESC'
};

// جلب الصفحات للأرشيف
$pages = [];
$total_pages = 0;
$total_pages_count = 0;

if (!$currentPage) {
    try {
        $pages_query = "
            SELECT p.*, c.name as category_name, c.slug as category_slug, c.color as category_color,
                   u.full_name as author_name
            FROM pages p 
            LEFT JOIN categories c ON p.category_id = c.id 
            LEFT JOIN users u ON p.author_id = u.id
            WHERE $where_clause 
            ORDER BY $order_by 
            LIMIT $per_page OFFSET $offset
        ";
        
        $pages = $db->fetchAll($pages_query, $params);
        
        // عدد الصفحات الإجمالي
        $total_query = "SELECT COUNT(*) as total FROM pages p WHERE $where_clause";
        $total_result = $db->fetchOne($total_query, $params);
        $total_pages = $total_result ? $total_result['total'] : 0;
        $total_pages_count = ceil($total_pages / $per_page);
        
    } catch (Exception $e) {
        $pages = [];
        $total_pages = 0;
        $total_pages_count = 0;
        error_log("Error fetching pages: " . $e->getMessage());
    }
}

// جلب التصنيفات للفلترة
try {
    $categories = $db->fetchAll("
        SELECT c.*, COUNT(p.id) as pages_count 
        FROM categories c 
        LEFT JOIN pages p ON c.id = p.category_id AND p.status = 'published'
        GROUP BY c.id 
        ORDER BY c.name ASC
    ");
} catch (Exception $e) {
    $categories = [];
    error_log("Error fetching categories: " . $e->getMessage());
}

$currentCategory = null;
if ($category_id > 0) {
    foreach ($categories as $cat) {
        if ($cat['id'] == $category_id) {
            $currentCategory = $cat;
            break;
        }
    }
}

// جلب البلوكات النشطة (نفس كود index.php)
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

// إعدادات الموقع
$siteName = getSetting($db, 'site_name', 'مسجد النور');
$siteDescription = getSetting($db, 'site_description', 'موقع مسجد النور الرسمي');
$primaryColor = getSetting($db, 'primary_color', '#2c5530');
$secondaryColor = getSetting($db, 'secondary_color', '#1e3a1e');

// جلب أوقات الصلاة
$prayerTimes = getPrayerTimes($db);

// تضمين جميع الدوال من index.php
include_once 'includes/block_functions.php';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php if ($currentPage): ?>
            <?php echo htmlspecialchars($currentPage['title']); ?> - 
        <?php elseif ($currentCategory): ?>
            أرشيف <?php echo htmlspecialchars($currentCategory['name']); ?> - 
        <?php elseif ($search): ?>
            نتائج البحث عن "<?php echo htmlspecialchars($search); ?>" - 
        <?php else: ?>
            أرشيف الصفحات - 
        <?php endif; ?>
        <?php echo htmlspecialchars($siteName); ?>
    </title>
    
    <meta name="description" content="<?php echo $currentPage ? htmlspecialchars($currentPage['meta_description'] ?: $currentPage['excerpt']) : 'أرشيف جميع صفحات ' . htmlspecialchars($siteName); ?>">
    
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
            background-color: #f8f9fa;
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
            position: relative;
            z-index: 2;
        }

        .hero-section h1 {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 20px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.5);
        }

        .hero-section p {
            font-size: 1.2rem;
            opacity: 0.9;
            text-shadow: 0 1px 3px rgba(0,0,0,0.5);
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
        
        .archive-filters {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin: 30px 0;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .category-filter {
            display: inline-block;
            padding: 8px 16px;
            margin: 5px;
            border-radius: 25px;
            text-decoration: none;
            transition: all 0.3s;
            border: 2px solid #e9ecef;
        }
        
        .category-filter:hover,
        .category-filter.active {
            background: #2c5530;
            color: white;
            border-color: #2c5530;
        }
        
        .search-box {
            position: relative;
        }
        
        .search-box .form-control {
            border-radius: 25px;
            padding-right: 50px;
        }
        
        .search-box .btn {
            position: absolute;
            right: 5px;
            top: 50%;
            transform: translateY(-50%);
            border-radius: 20px;
        }
        
        .stats-bar {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .pagination {
            justify-content: center;
            margin-top: 40px;
        }
        
        .pagination .page-link {
            border-radius: 10px;
            margin: 0 5px;
            border: none;
            color: #2c5530;
        }
        
        .pagination .page-link:hover,
        .pagination .page-item.active .page-link {
            background: #2c5530;
            color: white;
        }
        
        .no-results {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        
        .no-results i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #ddd;
        }
        
        .category-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            color: white;
            text-decoration: none;
            margin-bottom: 10px;
        }
        
        .page-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s;
            margin-bottom: 30px;
            height: 100%;
        }
        
        .page-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .page-card-image {
            height: 200px;
            background-size: cover;
            background-position: center;
            position: relative;
        }
        
        .page-card-content {
            padding: 20px;
            display: flex;
            flex-direction: column;
            height: calc(100% - 200px);
        }
        
        .page-card-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: #2c5530;
        }
        
        .page-card-excerpt {
            color: #666;
            margin-bottom: 15px;
            flex-grow: 1;
        }
        
        .page-card-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9rem;
            color: #888;
            border-top: 1px solid #eee;
            padding-top: 15px;
            margin-top: auto;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include 'includes/ui/header.php'; ?>
    
    <!-- Hero Section -->
    <div class="hero-section">
        <div class="container">
            <?php if ($currentPage): ?>
                <h1><?php echo htmlspecialchars($currentPage['title']); ?></h1>
                <p>تفاصيل الصفحة</p>
            <?php elseif ($currentCategory): ?>
                <h1>أرشيف <?php echo htmlspecialchars($currentCategory['name']); ?></h1>
                <p>جميع الصفحات في تصنيف <?php echo htmlspecialchars($currentCategory['name']); ?></p>
            <?php elseif ($search): ?>
                <h1>نتائج البحث</h1>
                <p>نتائج البحث عن "<?php echo htmlspecialchars($search); ?>"</p>
            <?php else: ?>
                <h1>أرشيف الصفحات</h1>
                <p>جميع صفحات الموقع مرتبة ومنظمة</p>
            <?php endif; ?>
        </div>
    </div>

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
                    <?php if ($currentPage): ?>
                        <!-- Single Page View -->
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
                            </div>
                        </article>
                        
                        <!-- Center Blocks -->
                        <?php foreach ($centerBlocks as $block): ?>
                            <?php echo renderBlock($block); ?>
                        <?php endforeach; ?>
                        
                        <div class="mt-4">
                            <a href="archive.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-right me-2"></i>العودة للأرشيف
                            </a>
                        </div>
                        
                    <?php else: ?>
                        <!-- Archive View -->
                        
                        <!-- Filters -->
                        <div class="archive-filters">
                            <div class="row align-items-center">
                                <div class="col-lg-8">
                                    <h5 class="mb-3">تصفية حسب التصنيف:</h5>
                                    <div class="category-filters">
                                        <a href="archive.php" class="category-filter <?php echo $category_id == 0 ? 'active' : ''; ?>">
                                            <i class="fas fa-th-large me-1"></i>
                                            جميع التصنيفات
                                        </a>
                                        
                                        <?php foreach ($categories as $category): ?>
                                            <a href="?category=<?php echo $category['id']; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                                               class="category-filter <?php echo $category_id == $category['id'] ? 'active' : ''; ?>"
                                               style="<?php echo $category['color'] ? 'background-color: ' . $category['color'] . '; color: white; border-color: ' . $category['color'] . ';' : ''; ?>">
                                                <i class="<?php echo $category['icon'] ?: 'fas fa-folder'; ?> me-1"></i>
                                                <?php echo htmlspecialchars($category['name']); ?>
                                                <span class="badge bg-light text-dark ms-1"><?php echo $category['pages_count']; ?></span>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                
                                <div class="col-lg-4">
                                    <form method="GET" class="search-box">
                                        <?php if ($category_id > 0): ?>
                                            <input type="hidden" name="category" value="<?php echo $category_id; ?>">
                                        <?php endif; ?>
                                        
                                        <input type="text" class="form-control" name="search" 
                                               placeholder="البحث في الصفحات..." 
                                               value="<?php echo htmlspecialchars($search); ?>">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                            
                            <!-- Sort Options -->
                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="d-flex align-items-center">
                                        <span class="me-3">ترتيب حسب:</span>
                                        <div class="btn-group btn-group-sm">
                                            <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'latest'])); ?>" 
                                               class="btn <?php echo $sort === 'latest' ? 'btn-primary' : 'btn-outline-primary'; ?>">الأحدث</a>
                                            <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'oldest'])); ?>" 
                                               class="btn <?php echo $sort === 'oldest' ? 'btn-primary' : 'btn-outline-primary'; ?>">الأقدم</a>
                                            <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'title'])); ?>" 
                                               class="btn <?php echo $sort === 'title' ? 'btn-primary' : 'btn-outline-primary'; ?>">العنوان</a>
                                            <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'views'])); ?>" 
                                               class="btn <?php echo $sort === 'views' ? 'btn-primary' : 'btn-outline-primary'; ?>">الأكثر مشاهدة</a>
                                            <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'featured'])); ?>" 
                                               class="btn <?php echo $sort === 'featured' ? 'btn-primary' : 'btn-outline-primary'; ?>">المميزة</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Stats Bar -->
                        <?php if ($total_pages > 0): ?>
                            <div class="stats-bar">
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <i class="fas fa-file-alt me-2"></i>
                                        عرض <?php echo count($pages); ?> من أصل <?php echo $total_pages; ?> صفحة
                                        <?php if ($currentCategory): ?>
                                            في تصنيف <strong><?php echo htmlspecialchars($currentCategory['name']); ?></strong>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-6 text-end">
                                        <small class="text-muted">
                                            صفحة <?php echo $page; ?> من <?php echo $total_pages_count; ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Pages Grid -->
                        <?php if (!empty($pages)): ?>
                            <div class="row">
                                <?php foreach ($pages as $pageItem): ?>
                                    <div class="col-lg-6 col-md-12 mb-4">
                                        <div class="page-card">
                                            <div class="page-card-image" 
                                                 style="background-image: url('<?php echo $pageItem['featured_image'] ? 'uploads/' . htmlspecialchars($pageItem['featured_image']) : '/placeholder.svg?height=200&width=400'; ?>');">
                                                
                                                <?php if ($pageItem['is_featured']): ?>
                                                    <div class="position-absolute top-0 start-0 m-2">
                                                        <span class="badge bg-warning">
                                                            <i class="fas fa-star me-1"></i>مميز
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <?php if ($pageItem['is_sticky']): ?>
                                                    <div class="position-absolute top-0 end-0 m-2">
                                                        <span class="badge bg-info">
                                                            <i class="fas fa-thumbtack me-1"></i>مثبت
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="page-card-content">
                                                <?php if ($pageItem['category_name']): ?>
                                                    <a href="?category=<?php echo $pageItem['category_id']; ?>" 
                                                       class="category-badge"
                                                       style="background-color: <?php echo $pageItem['category_color'] ?: '#6c757d'; ?>">
                                                        <?php echo htmlspecialchars($pageItem['category_name']); ?>
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <h3 class="page-card-title">
                                                    <a href="?view=<?php echo htmlspecialchars($pageItem['slug']); ?>" class="text-decoration-none">
                                                        <?php echo htmlspecialchars($pageItem['title']); ?>
                                                    </a>
                                                </h3>
                                                
                                                <p class="page-card-excerpt">
                                                    <?php echo htmlspecialchars(truncateText($pageItem['excerpt'] ?: $pageItem['content'], 120)); ?>
                                                </p>
                                                
                                                <div class="page-card-meta">
                                                    <div>
                                                        <i class="fas fa-user me-1"></i>
                                                        <?php echo htmlspecialchars($pageItem['author_name'] ?: 'غير محدد'); ?>
                                                    </div>
                                                    <div>
                                                        <i class="fas fa-calendar me-1"></i>
                                                        <?php echo function_exists('formatArabicDate') ? formatArabicDate($pageItem['created_at']) : date('Y-m-d', strtotime($pageItem['created_at'])); ?>
                                                    </div>
                                                    <div>
                                                        <i class="fas fa-eye me-1"></i>
                                                        <?php echo number_format($pageItem['views_count']); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Pagination -->
                            <?php if ($total_pages_count > 1): ?>
                                <nav aria-label="تصفح الصفحات">
                                    <ul class="pagination">
                                        <?php if ($page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                                    <i class="fas fa-chevron-right"></i>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages_count, $page + 2); $i++): ?>
                                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                                    <?php echo $i; ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <?php if ($page < $total_pages_count): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                                    <i class="fas fa-chevron-left"></i>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                            
                        <?php else: ?>
                            <!-- No Results -->
                            <div class="no-results">
                                <i class="fas fa-search"></i>
                                <h3>لا توجد نتائج</h3>
                                <?php if ($search): ?>
                                    <p>لم يتم العثور على صفحات تحتوي على "<?php echo htmlspecialchars($search); ?>"</p>
                                    <a href="archive.php" class="btn btn-primary">عرض جميع الصفحات</a>
                                <?php elseif ($currentCategory): ?>
                                    <p>لا توجد صفحات في تصنيف "<?php echo htmlspecialchars($currentCategory['name']); ?>"</p>
                                    <a href="archive.php" class="btn btn-primary">عرض جميع التصنيفات</a>
                                <?php else: ?>
                                    <p>لا توجد صفحات منشورة حالياً</p>
                                    <a href="index.php" class="btn btn-primary">العودة للرئيسية</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Center Blocks -->
                        <?php foreach ($centerBlocks as $block): ?>
                            <?php echo renderBlock($block); ?>
                        <?php endforeach; ?>
                        
                    <?php endif; ?>
                </div>
                
                <!-- Right Sidebar -->
                <div class="col-lg-3">
                    <?php foreach ($rightBlocks as $block): ?>
                        <?php echo renderBlock($block); ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

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

    <!-- Footer -->
    <?php include 'includes/ui/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// تضمين جميع دوال البلوكات من index.php
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
                
            default:
                $html .= $block['content'];
                break;
        }
    }
    
    $html .= '</div>';
    
    return $html;
}

function renderPrayerTimesBlock() {
    global $prayerTimes;
    
    $html = '<div class="prayer-times-widget" style="background: linear-gradient(135deg, #2c5530 0%, #1e3a1e 100%); color: white; border-radius: 15px; padding: 20px;">';
    $html .= '<h5 class="text-center mb-3">أوقات الصلاة</h5>';
    
    $prayers = [
        'fajr' => 'الفجر',
        'dhuhr' => 'الظهر',
        'asr' => 'العصر',
        'maghrib' => 'المغرب',
        'isha' => 'العشاء'
    ];
    
    foreach ($prayers as $key => $prayer) {
        $html .= '<div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.2);">';
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
                $html .= '<a href="?view=' . htmlspecialchars($page['slug']) . '" class="text-decoration-none">';
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
            $html .= '<div class="stat-card text-center">';
            $html .= '<i class="' . $stat['icon'] . ' fa-2x text-primary mb-2"></i>';
            $html .= '<div class="h4">' . convertToArabicNumbers($stat['value']) . '</div>';
            $html .= '<div class="text-muted">' . $stat['label'] . '</div>';
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
                $html .= '<a href="?view=' . htmlspecialchars($page['slug']) . '" class="text-decoration-none">';
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
?>
