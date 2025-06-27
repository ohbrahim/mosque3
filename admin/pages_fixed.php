<?php
/**
 * إدارة الصفحات المُصلحة
 */
require_once '../config/config_clean.php';

// التحقق من الصلاحيات
requireLogin();
requirePermission('manage_pages');

$message = '';
$error = '';

// معالجة الإجراءات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'رمز الأمان غير صحيح';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'add') {
            // إضافة صفحة جديدة
            $title = sanitize($_POST['title'] ?? '');
            $slug = sanitize($_POST['slug'] ?? '');
            $content = $_POST['content'] ?? '';
            $status = $_POST['status'] ?? 'draft';
            
            if (empty($title)) {
                $error = 'عنوان الصفحة مطلوب';
            } else {
                // إنشاء slug تلقائياً إذا لم يتم تحديده
                if (empty($slug)) {
                    $slug = preg_replace('/[^a-zA-Z0-9\-_]/', '', str_replace(' ', '-', $title));
                    $slug = strtolower($slug);
                }
                
                try {
                    $stmt = $pdo->prepare("INSERT INTO pages (title, slug, content, status, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
                    if ($stmt->execute([$title, $slug, $content, $status, $_SESSION['user_id']])) {
                        $message = 'تم إضافة الصفحة بنجاح';
                    } else {
                        $error = 'فشل في إضافة الصفحة';
                    }
                } catch (Exception $e) {
                    $error = 'خطأ في إضافة الصفحة: ' . $e->getMessage();
                }
            }
        } elseif ($action === 'delete') {
            // حذف صفحة
            $pageId = (int)($_POST['page_id'] ?? 0);
            
            try {
                $stmt = $pdo->prepare("DELETE FROM pages WHERE id = ?");
                if ($stmt->execute([$pageId])) {
                    $message = 'تم حذف الصفحة بنجاح';
                } else {
                    $error = 'فشل في حذف الصفحة';
                }
            } catch (Exception $e) {
                $error = 'خطأ في حذف الصفحة: ' . $e->getMessage();
            }
        }
    }
}

// جلب جميع الصفحات
$pages = [];
try {
    $stmt = $pdo->query("SELECT p.*, u.username as author FROM pages p LEFT JOIN users u ON p.created_by = u.id ORDER BY p.created_at DESC");
    $pages = $stmt->fetchAll();
} catch (Exception $e) {
    $error = 'خطأ في جلب الصفحات: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الصفحات - لوحة التحكم</title>
    
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
        
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .content-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index_fixed.php">
                <i class="fas fa-mosque"></i>
                لوحة التحكم
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="../index.php" target="_blank">
                    <i class="fas fa-external-link-alt"></i>
                    عرض الموقع
                </a>
                <a class="nav-link" href="../logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    تسجيل الخروج
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Page Header -->
        <div class="content-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-2">إدارة الصفحات</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="index_fixed.php">لوحة التحكم</a></li>
                            <li class="breadcrumb-item active">إدارة الصفحات</li>
                        </ol>
                    </nav>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPageModal">
                    <i class="fas fa-plus"></i>
                    إضافة صفحة جديدة
                </button>
            </div>
        </div>
        
        <!-- Messages -->
        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i>
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Pages List -->
        <div class="content-card">
            <?php if (empty($pages)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                    <h4>لا توجد صفحات حتى الآن</h4>
                    <p class="text-muted">ابدأ بإضافة أول صفحة لموقعك</p>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPageModal">
                        <i class="fas fa-plus"></i>
                        إضافة أول صفحة
                    </button>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>العنوان</th>
                                <th>الرابط</th>
                                <th>الحالة</th>
                                <th>الكاتب</th>
                                <th>تاريخ الإنشاء</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pages as $page): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($page['title']); ?></strong>
                                    </td>
                                    <td>
                                        <code><?php echo htmlspecialchars($page['slug']); ?></code>
                                    </td>
                                    <td>
                                        <?php if ($page['status'] === 'published'): ?>
                                            <span class="badge bg-success">منشور</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">مسودة</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($page['author'] ?? 'غير معروف'); ?></td>
                                    <td><?php echo formatArabicDate($page['created_at']); ?></td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="../?page=<?php echo $page['slug']; ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deletePage(<?php echo $page['id']; ?>, '<?php echo htmlspecialchars($page['title']); ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Page Modal -->
    <div class="modal fade" id="addPageModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="modal-header">
                        <h5 class="modal-title">إضافة صفحة جديدة</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">عنوان الصفحة *</label>
                            <input type="text" class="form-control" name="title" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">الرابط (اختياري)</label>
                            <input type="text" class="form-control" name="slug" placeholder="سيتم إنشاؤه تلقائياً">
                            <div class="form-text">يُستخدم في رابط الصفحة</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">محتوى الصفحة</label>
                            <textarea class="form-control" name="content" rows="10" placeholder="اكتب محتوى الصفحة هنا..."></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">حالة الصفحة</label>
                            <select class="form-select" name="status">
                                <option value="draft">مسودة</option>
                                <option value="published">منشور</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            حفظ الصفحة
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Form (Hidden) -->
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="page_id" id="deletePageId">
    </form>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function deletePage(pageId, pageTitle) {
            if (confirm('هل أنت متأكد من حذف الصفحة "' + pageTitle + '"؟')) {
                document.getElementById('deletePageId').value = pageId;
                document.getElementById('deleteForm').submit();
            }
        }
    </script>
</body>
</html>
