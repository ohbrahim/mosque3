<?php
/**
 * إنشاء الملفات المفقودة في لوحة التحكم
 */
require_once '../config/config.php';

// التحقق من تسجيل الدخول
requireLogin();
requirePermission('admin_access');

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fileToCreate = $_POST['file'] ?? '';
    
    switch ($fileToCreate) {
        case 'statistics.php':
            $content = '<?php
/**
 * صفحة الإحصائيات
 */
require_once "../config/config.php";

requireLogin();
requirePermission("admin_access");

// جلب الإحصائيات
try {
    $stats = [
        "total_users" => $db->fetchOne("SELECT COUNT(*) as count FROM users")["count"],
        "total_pages" => $db->fetchOne("SELECT COUNT(*) as count FROM pages")["count"],
        "total_blocks" => $db->fetchOne("SELECT COUNT(*) as count FROM blocks")["count"],
        "total_messages" => $db->fetchOne("SELECT COUNT(*) as count FROM messages")["count"],
        "total_comments" => $db->fetchOne("SELECT COUNT(*) as count FROM comments")["count"]
    ];
} catch (Exception $e) {
    $stats = array_fill_keys(["total_users", "total_pages", "total_blocks", "total_messages", "total_comments"], 0);
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الإحصائيات - لوحة التحكم</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: "Cairo", sans-serif; background-color: #f8f9fa; }
        .sidebar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; color: white; }
        .sidebar .nav-link { color: rgba(255,255,255,0.8); padding: 12px 20px; border-radius: 8px; margin: 2px 0; transition: all 0.3s; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: rgba(255,255,255,0.2); color: white; }
        .main-content { padding: 30px; }
        .stats-card { background: white; border-radius: 15px; padding: 25px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-2 p-0">
                <div class="sidebar">
                    <div class="p-4">
                        <h4 class="text-center mb-4"><i class="fas fa-mosque"></i> لوحة التحكم</h4>
                        <nav class="nav flex-column">
                            <a class="nav-link" href="index.php"><i class="fas fa-tachometer-alt"></i> الرئيسية</a>
                            <a class="nav-link" href="pages.php"><i class="fas fa-file-alt"></i> إدارة الصفحات</a>
                            <a class="nav-link" href="blocks.php"><i class="fas fa-th-large"></i> إدارة البلوكات</a>
                            <a class="nav-link" href="users.php"><i class="fas fa-users"></i> المستخدمون</a>
                            <a class="nav-link" href="messages.php"><i class="fas fa-envelope"></i> الرسائل</a>
                            <a class="nav-link" href="comments.php"><i class="fas fa-comments"></i> التعليقات</a>
                            <a class="nav-link active" href="statistics.php"><i class="fas fa-chart-bar"></i> الإحصائيات</a>
                            <a class="nav-link" href="settings.php"><i class="fas fa-cog"></i> الإعدادات</a>
                            <hr class="my-3">
                            <a class="nav-link" href="../index.php" target="_blank"><i class="fas fa-external-link-alt"></i> عرض الموقع</a>
                            <a class="nav-link" href="../logout.php"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a>
                        </nav>
                    </div>
                </div>
            </div>
            <div class="col-lg-10">
                <div class="main-content">
                    <h2 class="mb-4">الإحصائيات</h2>
                    <div class="row">
                        <div class="col-md-4"><div class="stats-card"><h5>إجمالي المستخدمين</h5><h2 class="text-primary"><?php echo $stats["total_users"]; ?></h2></div></div>
                        <div class="col-md-4"><div class="stats-card"><h5>إجمالي الصفحات</h5><h2 class="text-success"><?php echo $stats["total_pages"]; ?></h2></div></div>
                        <div class="col-md-4"><div class="stats-card"><h5>إجمالي البلوكات</h5><h2 class="text-warning"><?php echo $stats["total_blocks"]; ?></h2></div></div>
                        <div class="col-md-6"><div class="stats-card"><h5>إجمالي الرسائل</h5><h2 class="text-info"><?php echo $stats["total_messages"]; ?></h2></div></div>
                        <div class="col-md-6"><div class="stats-card"><h5>إجمالي التعليقات</h5><h2 class="text-danger"><?php echo $stats["total_comments"]; ?></h2></div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>';
            break;
            
        case 'polls.php':
            $content = '<?php
/**
 * إدارة الاستطلاعات
 */
require_once "../config/config.php";

requireLogin();
requirePermission("admin_access");

$message = "";
$error = "";

// معالجة الإجراءات
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";
    
    switch ($action) {
        case "add_poll":
            $data = [
                "question" => sanitize($_POST["question"]),
                "options" => json_encode(array_filter($_POST["options"])),
                "status" => $_POST["status"] ?? "active",
                "created_by" => $_SESSION["user_id"]
            ];
            
            if ($db->insert("polls", $data)) {
                $message = "تم إضافة الاستطلاع بنجاح";
            } else {
                $error = "فشل في إضافة الاستطلاع";
            }
            break;
    }
}

// جلب الاستطلاعات
try {
    $polls = $db->fetchAll("SELECT p.*, u.full_name as creator_name FROM polls p LEFT JOIN users u ON p.created_by = u.id ORDER BY p.created_at DESC");
} catch (Exception $e) {
    $polls = [];
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الاستطلاعات - لوحة التحكم</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: "Cairo", sans-serif; background-color: #f8f9fa; }
        .sidebar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; color: white; }
        .sidebar .nav-link { color: rgba(255,255,255,0.8); padding: 12px 20px; border-radius: 8px; margin: 2px 0; transition: all 0.3s; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: rgba(255,255,255,0.2); color: white; }
        .main-content { padding: 30px; }
        .content-card { background: white; border-radius: 15px; padding: 25px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-2 p-0">
                <div class="sidebar">
                    <div class="p-4">
                        <h4 class="text-center mb-4"><i class="fas fa-mosque"></i> لوحة التحكم</h4>
                        <nav class="nav flex-column">
                            <a class="nav-link" href="index.php"><i class="fas fa-tachometer-alt"></i> الرئيسية</a>
                            <a class="nav-link" href="pages.php"><i class="fas fa-file-alt"></i> إدارة الصفحات</a>
                            <a class="nav-link" href="blocks.php"><i class="fas fa-th-large"></i> إدارة البلوكات</a>
                            <a class="nav-link" href="users.php"><i class="fas fa-users"></i> المستخدمون</a>
                            <a class="nav-link" href="messages.php"><i class="fas fa-envelope"></i> الرسائل</a>
                            <a class="nav-link" href="comments.php"><i class="fas fa-comments"></i> التعليقات</a>
                            <a class="nav-link active" href="polls.php"><i class="fas fa-poll"></i> الاستطلاعات</a>
                            <a class="nav-link" href="statistics.php"><i class="fas fa-chart-bar"></i> الإحصائيات</a>
                            <a class="nav-link" href="settings.php"><i class="fas fa-cog"></i> الإعدادات</a>
                            <hr class="my-3">
                            <a class="nav-link" href="../index.php" target="_blank"><i class="fas fa-external-link-alt"></i> عرض الموقع</a>
                            <a class="nav-link" href="../logout.php"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a>
                        </nav>
                    </div>
                </div>
            </div>
            <div class="col-lg-10">
                <div class="main-content">
                    <h2 class="mb-4">إدارة الاستطلاعات</h2>
                    
                    <?php if ($message): ?>
                        <div class="alert alert-success"><?php echo $message; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <div class="content-card">
                        <h4>إضافة استطلاع جديد</h4>
                        <form method="POST">
                            <input type="hidden" name="action" value="add_poll">
                            <div class="mb-3">
                                <label class="form-label">سؤال الاستطلاع</label>
                                <input type="text" class="form-control" name="question" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">الخيارات</label>
                                <input type="text" class="form-control mb-2" name="options[]" placeholder="الخيار الأول" required>
                                <input type="text" class="form-control mb-2" name="options[]" placeholder="الخيار الثاني" required>
                                <input type="text" class="form-control mb-2" name="options[]" placeholder="الخيار الثالث">
                                <input type="text" class="form-control mb-2" name="options[]" placeholder="الخيار الرابع">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">الحالة</label>
                                <select class="form-select" name="status">
                                    <option value="active">نشط</option>
                                    <option value="inactive">غير نشط</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">إضافة الاستطلاع</button>
                        </form>
                    </div>
                    
                    <div class="content-card">
                        <h4>الاستطلاعات الموجودة</h4>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>السؤال</th>
                                        <th>المنشئ</th>
                                        <th>الحالة</th>
                                        <th>تاريخ الإنشاء</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($polls as $poll): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($poll["question"]); ?></td>
                                            <td><?php echo htmlspecialchars($poll["creator_name"]); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $poll["status"] === "active" ? "success" : "secondary"; ?>">
                                                    <?php echo $poll["status"] === "active" ? "نشط" : "غير نشط"; ?>
                                                </span>
                                            </td>
                                            <td><?php echo formatArabicDate($poll["created_at"]); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary">تعديل</button>
                                                <button class="btn btn-sm btn-outline-danger">حذف</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>';
            break;
    }
    
    if (isset($content)) {
        if (file_put_contents($fileToCreate, $content)) {
            $message = "تم إنشاء الملف {$fileToCreate} بنجاح";
        } else {
            $error = "فشل في إنشاء الملف {$fileToCreate}";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إنشاء الملفات المفقودة</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Cairo', sans-serif; background: #f8f9fa; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; }
        .card { border: none; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h3 class="mb-0">إنشاء الملفات المفقودة</h3>
            </div>
            <div class="card-body">
                <?php if ($message): ?>
                    <div class="alert alert-success"><?php echo $message; ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">اختر الملف المراد إنشاؤه:</label>
                        <select class="form-select" name="file" required>
                            <option value="">-- اختر ملف --</option>
                            <option value="statistics.php">صفحة الإحصائيات</option>
                            <option value="polls.php">إدارة الاستطلاعات</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">إنشاء الملف</button>
                    <a href="index.php" class="btn btn-secondary">العودة للوحة التحكم</a>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
