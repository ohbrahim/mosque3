<?php
require_once '../config/config.php';

// التحقق من تسجيل الدخول وصلاحيات المحرر
if (!isLoggedIn() || !hasPermission('manage_own_pages')) {
    header('Location: ../login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// حذف صفحة (المحرر يمكنه حذف صفحاته فقط)
if (isset($_GET['delete']) && verifyCSRFToken($_GET['csrf_token'] ?? '')) {
    $pageId = (int)$_GET['delete'];
    
    try {
        // التحقق من ملكية الصفحة
        $page = $db->fetchOne("SELECT * FROM pages WHERE id = ? AND author_id = ?", [$pageId, $userId]);
        
        if ($page) {
            // حذف التعليقات والتقييمات المرتبطة بالصفحة
            $db->query("DELETE FROM comments WHERE page_id = ?", [$pageId]);
            $db->query("DELETE FROM ratings WHERE page_id = ?", [$pageId]);
            
            // حذف الصفحة
            $db->query("DELETE FROM pages WHERE id = ?", [$pageId]);
            
            $_SESSION['success'] = 'تم حذف الصفحة بنجاح.';
        } else {
            $_SESSION['error'] = 'لا يمكنك حذف هذه الصفحة.';
        }
    } catch (Exception $e) {
        $_SESSION['error'] = 'حدث خطأ أثناء حذف الصفحة: ' . $e->getMessage();
    }
    
    header('Location: editor_pages.php');
    exit;
}

// إضافة أو تحرير صفحة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_page']) && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $pageId = isset($_POST['page_id']) ? (int)$_POST['page_id'] : null;
    $title = sanitize($_POST['title'] ?? '');
    $slug = sanitize($_POST['slug'] ?? '');
    $excerpt = sanitize($_POST['excerpt'] ?? '');
    $content = $_POST['content'] ?? ''; // لا نقوم بتنظيف المحتوى لأنه قد يحتوي على HTML
    $metaDescription = sanitize($_POST['meta_description'] ?? '');
    $status = sanitize($_POST['status'] ?? 'draft');
    $allowComments = isset($_POST['allow_comments']) ? 1 : 0;
    $allowRatings = isset($_POST['allow_ratings']) ? 1 : 0;
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    
    // التحقق من البيانات
    if (empty($title) || empty($content)) {
        $_SESSION['error'] = 'العنوان والمحتوى مطلوبان.';
        header('Location: editor_pages.php');
        exit;
    }
    
    // إنشاء slug تلقائياً إذا لم يتم تقديمه
    if (empty($slug)) {
        $slug = createSlug($title);
    } else {
        $slug = createSlug($slug);
    }
    
    // التحقق من عدم تكرار الـ slug
    $existingPage = $db->fetchOne("SELECT id FROM pages WHERE slug = ? AND id != ?", [$slug, $pageId ?: 0]);
    if ($existingPage) {
        $slug .= '-' . time();
    }
    
    // معالجة الصورة المميزة
    $featuredImage = '';
    if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/';
        
        // التأكد من وجود المجلد
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileName = uniqid() . '_' . basename($_FILES['featured_image']['name']);
        $uploadPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['featured_image']['tmp_name'], $uploadPath)) {
            $featuredImage = $fileName;
        }
    }
    
    try {
        if ($pageId) {
            // التحقق من ملكية الصفحة
            $existingPage = $db->fetchOne("SELECT * FROM pages WHERE id = ? AND author_id = ?", [$pageId, $userId]);
            
            if (!$existingPage) {
                throw new Exception('لا يمكنك تحرير هذه الصفحة.');
            }
            
            // تحديث صفحة موجودة
            $updateData = [
                'title' => $title,
                'slug' => $slug,
                'excerpt' => $excerpt,
                'content' => $content,
                'meta_description' => $metaDescription,
                'status' => $status,
                'allow_comments' => $allowComments,
                'allow_ratings' => $allowRatings,
                'is_featured' => $isFeatured,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            if ($featuredImage) {
                $updateData['featured_image'] = $featuredImage;
            }
            
            $db->update('pages', $updateData, ['id' => $pageId]);
        } else {
            // إضافة صفحة جديدة
            $db->insert('pages', [
                'title' => $title,
                'slug' => $slug,
                'excerpt' => $excerpt,
                'content' => $content,
                'meta_description' => $metaDescription,
                'featured_image' => $featuredImage,
                'status' => $status,
                'allow_comments' => $allowComments,
                'allow_ratings' => $allowRatings,
                'is_featured' => $isFeatured,
                'author_id' => $userId,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }
        
        $_SESSION['success'] = 'تم حفظ الصفحة بنجاح.';
    } catch (Exception $e) {
        $_SESSION['error'] = 'حدث خطأ أثناء حفظ الصفحة: ' . $e->getMessage();
    }
    
    header('Location: editor_pages.php');
    exit;
}

// جلب الصفحة للتحرير
$editPage = null;
if (isset($_GET['edit'])) {
    $pageId = (int)$_GET['edit'];
    
    try {
        $editPage = $db->fetchOne("SELECT * FROM pages WHERE id = ? AND author_id = ?", [$pageId, $userId]);
        
        if (!$editPage) {
            $_SESSION['error'] = 'لا يمكنك تحرير هذه الصفحة.';
            header('Location: editor_pages.php');
            exit;
        }
    } catch (Exception $e) {
        $_SESSION['error'] = 'حدث خطأ أثناء جلب بيانات الصفحة: ' . $e->getMessage();
    }
}

// جلب صفحات المحرر
try {
    $pages = $db->fetchAll("
        SELECT * FROM pages 
        WHERE author_id = ? 
        ORDER BY created_at DESC
    ", [$userId]);
} catch (Exception $e) {
    $_SESSION['error'] = 'حدث خطأ أثناء جلب الصفحات: ' . $e->getMessage();
    $pages = [];
}

// تضمين ملف الهيدر
require_once 'header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 mb-0">إدارة صفحاتي</h2>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#pageModal">
            <i class="fas fa-plus me-1"></i> إضافة صفحة جديدة
        </button>
    </div>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <?php if (empty($pages)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">لا توجد صفحات بعد</h4>
                    <p class="text-muted">انقر على زر "إضافة صفحة جديدة" لإنشاء أول صفحة.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>العنوان</th>
                                <th>الحالة</th>
                                <th>المشاهدات</th>
                                <th>التعليقات</th>
                                <th>تاريخ الإنشاء</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pages as $page): ?>
                                <tr>
                                    <td><?php echo $page['id']; ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($page['title']); ?>
                                        <?php if ($page['is_featured']): ?>
                                            <span class="badge bg-warning ms-1">مميز</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $page['status'] === 'published' ? 'success' : ($page['status'] === 'draft' ? 'warning' : 'secondary'); ?>">
                                            <?php 
                                                echo $page['status'] === 'published' ? 'منشور' : 
                                                    ($page['status'] === 'draft' ? 'مسودة' : 'معلق'); 
                                            ?>
                                        </span>
                                    </td>
                                    <td><?php echo $page['views_count']; ?></td>
                                    <td>
                                        <?php
                                        try {
                                            $commentsCount = $db->fetchOne("SELECT COUNT(*) as count FROM comments WHERE page_id = ?", [$page['id']])['count'] ?? 0;
                                            echo $commentsCount;
                                        } catch (Exception $e) {
                                            echo '0';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo date('Y-m-d', strtotime($page['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="?edit=<?php echo $page['id']; ?>" class="btn btn-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="../?page=<?php echo $page['slug']; ?>" target="_blank" class="btn btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="?delete=<?php echo $page['id']; ?>&csrf_token=<?php echo generateCSRFToken(); ?>" 
                                               class="btn btn-danger" 
                                               onclick="return confirm('هل أنت متأكد من حذف هذه الصفحة؟');">
                                                <i class="fas fa-trash"></i>
                                            </a>
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
</div>

<!-- Modal إضافة/تحرير صفحة -->
<div class="modal fade" id="pageModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo $editPage ? 'تحرير الصفحة' : 'إضافة صفحة جديدة'; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <?php if ($editPage): ?>
                        <input type="hidden" name="page_id" value="<?php echo $editPage['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="title" class="form-label">العنوان *</label>
                                <input type="text" class="form-control" id="title" name="title" 
                                       value="<?php echo htmlspecialchars($editPage['title'] ?? ''); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="slug" class="form-label">الرابط المختصر</label>
                                <input type="text" class="form-control" id="slug" name="slug" 
                                       value="<?php echo htmlspecialchars($editPage['slug'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="excerpt" class="form-label">المقتطف</label>
                        <textarea class="form-control" id="excerpt" name="excerpt" rows="2"><?php echo htmlspecialchars($editPage['excerpt'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="content" class="form-label">المحتوى *</label>
                        <textarea class="form-control" id="content" name="content" rows="10" required><?php echo htmlspecialchars($editPage['content'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="featured_image" class="form-label">الصورة المميزة</label>
                                <input type="file" class="form-control" id="featured_image" name="featured_image" accept="image/*">
                                <?php if ($editPage && $editPage['featured_image']): ?>
                                    <small class="text-muted">الصورة الحالية: <?php echo htmlspecialchars($editPage['featured_image']); ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="status" class="form-label">الحالة</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="draft" <?php echo ($editPage['status'] ?? 'draft') === 'draft' ? 'selected' : ''; ?>>مسودة</option>
                                    <option value="published" <?php echo ($editPage['status'] ?? '') === 'published' ? 'selected' : ''; ?>>منشور</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="meta_description" class="form-label">وصف الميتا</label>
                        <textarea class="form-control" id="meta_description" name="meta_description" rows="2"><?php echo htmlspecialchars($editPage['meta_description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="allow_comments" name="allow_comments" 
                                       <?php echo ($editPage['allow_comments'] ?? 1) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="allow_comments">السماح بالتعليقات</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="allow_ratings" name="allow_ratings" 
                                       <?php echo ($editPage['allow_ratings'] ?? 1) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="allow_ratings">السماح بالتقييم</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" 
                                       <?php echo ($editPage['is_featured'] ?? 0) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_featured">صفحة مميزة</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" name="save_page" class="btn btn-primary">حفظ الصفحة</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($editPage): ?>
<script>
    // فتح المودال تلقائياً عند التحرير
    document.addEventListener('DOMContentLoaded', function() {
        var modal = new bootstrap.Modal(document.getElementById('pageModal'));
        modal.show();
    });
</script>
<?php endif; ?>

<?php
// تضمين ملف الفوتر
require_once 'footer.php';
?>
