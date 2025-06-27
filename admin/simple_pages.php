<?php
// صفحة إدارة صفحات بسيطة
session_start();

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// الاتصال بقاعدة البيانات مباشرة
try {
    $db = new PDO('mysql:host=localhost;dbname=mosque_management;charset=utf8mb4', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('خطأ في الاتصال بقاعدة البيانات: ' . $e->getMessage());
}

// معالجة الإضافة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_page'])) {
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $slug = $_POST['slug'] ?? '';
    $status = $_POST['status'] ?? 'published';
    
    if (empty($slug)) {
        $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($title));
    }
    
    try {
        $stmt = $db->prepare("INSERT INTO pages (title, content, slug, status, created_by, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$title, $content, $slug, $status, $_SESSION['user_id']]);
        $success = 'تم إضافة الصفحة بنجاح!';
    } catch (PDOException $e) {
        $error = 'خطأ في إضافة الصفحة: ' . $e->getMessage();
    }
}

// جلب الصفحات
try {
    $stmt = $db->query("SELECT id, title, slug, status, created_at FROM pages ORDER BY created_at DESC");
    $pages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'خطأ في جلب الصفحات: ' . $e->getMessage();
    $pages = [];
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الصفحات - لوحة التحكم</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .btn-primary {
            background-color: #4e73df;
            border-color: #4e73df;
        }
        .btn-success {
            background-color: #1cc88a;
            border-color: #1cc88a;
        }
        .btn-danger {
            background-color: #e74a3b;
            border-color: #e74a3b;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>إدارة الصفحات</h2>
            <a href="index.php" class="btn btn-secondary">العودة للوحة التحكم</a>
        </div>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">إضافة صفحة جديدة</h5>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="mb-3">
                                <label for="title" class="form-label">عنوان الصفحة</label>
                                <input type="text" class="form-control" id="title" name="title" required>
                            </div>
                            <div class="mb-3">
                                <label for="slug" class="form-label">الرابط المختصر (اختياري)</label>
                                <input type="text" class="form-control" id="slug" name="slug">
                                <small class="text-muted">سيتم إنشاؤه تلقائياً إذا تركته فارغاً</small>
                            </div>
                            <div class="mb-3">
                                <label for="content" class="form-label">محتوى الصفحة</label>
                                <textarea class="form-control" id="content" name="content" rows="5" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="status" class="form-label">الحالة</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="published">منشورة</option>
                                    <option value="draft">مسودة</option>
                                </select>
                            </div>
                            <button type="submit" name="add_page" class="btn btn-primary w-100">إضافة الصفحة</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">الصفحات الحالية</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($pages)): ?>
                            <div class="alert alert-info">لا توجد صفحات حالياً</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>العنوان</th>
                                            <th>الرابط</th>
                                            <th>الحالة</th>
                                            <th>تاريخ الإنشاء</th>
                                            <th>الإجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pages as $page): ?>
                                            <tr>
                                                <td><?php echo $page['id']; ?></td>
                                                <td><?php echo htmlspecialchars($page['title']); ?></td>
                                                <td><?php echo htmlspecialchars($page['slug']); ?></td>
                                                <td>
                                                    <?php if ($page['status'] === 'published'): ?>
                                                        <span class="badge bg-success">منشورة</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning">مسودة</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo $page['created_at']; ?></td>
                                                <td>
                                                    <a href="../?page=<?php echo $page['slug']; ?>" class="btn btn-sm btn-info" target="_blank">عرض</a>
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
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
