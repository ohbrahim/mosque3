<?php
require_once '../config/simple_config.php';
requireAdmin();

$message = '';
$error = '';

// معالجة الإجراءات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_page') {
        $title = sanitize($_POST['title']);
        $content = $_POST['content'];
        $slug = sanitize($_POST['slug']);
        
        if (empty($slug)) {
            $slug = strtolower(str_replace(' ', '-', $title));
        }
        
        try {
            $stmt = $pdo->prepare("INSERT INTO pages (title, content, slug, status, author_id, created_at) VALUES (?, ?, ?, 'published', ?, NOW())");
            $stmt->execute([$title, $content, $slug, $_SESSION['user_id']]);
            $message = "تم إضافة الصفحة بنجاح";
        } catch (Exception $e) {
            $error = "خطأ: " . $e->getMessage();
        }
    }
    
    if ($action === 'delete_page') {
        $id = (int)$_POST['id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM pages WHERE id = ?");
            $stmt->execute([$id]);
            $message = "تم حذف الصفحة بنجاح";
        } catch (Exception $e) {
            $error = "خطأ: " . $e->getMessage();
        }
    }
}

// جلب الصفحات
try {
    $stmt = $pdo->query("SELECT * FROM pages ORDER BY created_at DESC");
    $pages = $stmt->fetchAll();
} catch (Exception $e) {
    $pages = [];
    $error = "خطأ في جلب الصفحات: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم المبسطة</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; }
        .sidebar { background: #343a40; color: white; min-height: 100vh; }
        .sidebar a { color: white; text-decoration: none; padding: 10px; display: block; }
        .sidebar a:hover { background: #495057; color: white; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 sidebar">
                <h4 class="p-3">لوحة التحكم</h4>
                <a href="simple_admin.php">الصفحات</a>
                <a href="../index.php" target="_blank">عرض الموقع</a>
                <a href="../logout.php">تسجيل الخروج</a>
            </div>
            
            <div class="col-md-10 p-4">
                <h2>إدارة الصفحات</h2>
                
                <?php if ($message): ?>
                    <div class="alert alert-success"><?php echo $message; ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <!-- نموذج إضافة صفحة -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>إضافة صفحة جديدة</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="add_page">
                            
                            <div class="mb-3">
                                <label class="form-label">عنوان الصفحة</label>
                                <input type="text" name="title" class="form-control" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">الرابط (اختياري)</label>
                                <input type="text" name="slug" class="form-control">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">محتوى الصفحة</label>
                                <textarea name="content" class="form-control" rows="5" required></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">إضافة الصفحة</button>
                        </form>
                    </div>
                </div>
                
                <!-- قائمة الصفحات -->
                <div class="card">
                    <div class="card-header">
                        <h5>الصفحات الموجودة</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($pages)): ?>
                            <p>لا توجد صفحات</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>العنوان</th>
                                            <th>الرابط</th>
                                            <th>تاريخ الإنشاء</th>
                                            <th>الإجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pages as $page): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($page['title']); ?></td>
                                            <td><?php echo htmlspecialchars($page['slug']); ?></td>
                                            <td><?php echo formatArabicDate($page['created_at']); ?></td>
                                            <td>
                                                <a href="../?page=<?php echo $page['slug']; ?>" target="_blank" class="btn btn-sm btn-info">عرض</a>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="action" value="delete_page">
                                                    <input type="hidden" name="id" value="<?php echo $page['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('هل أنت متأكد؟')">حذف</button>
                                                </form>
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
        </div>
    </div>
</body>
</html>
