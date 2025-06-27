<?php
require_once '../config/config_new.php';
requireAdminLogin();

$message = '';
$error = '';

// معالجة إضافة صفحة جديدة
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'add') {
    $title = sanitize($_POST['title']);
    $content = $_POST['content']; // لا نقوم بتنظيف المحتوى لأنه قد يحتوي على HTML
    $slug = sanitize($_POST['slug']);
    $status = sanitize($_POST['status']);
    
    if (empty($title) || empty($content)) {
        $error = 'يرجى ملء جميع الحقول المطلوبة';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO pages (title, content, slug, status, created_at) VALUES (?, ?, ?, ?, NOW())");
            if ($stmt->execute([$title, $content, $slug, $status])) {
                $message = 'تم إضافة الصفحة بنجاح';
            } else {
                $error = 'حدث خطأ أثناء إضافة الصفحة';
            }
        } catch (PDOException $e) {
            $error = 'خطأ في قاعدة البيانات: ' . $e->getMessage();
        }
    }
}

// معالجة حذف صفحة
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM pages WHERE id = ?");
        if ($stmt->execute([$id])) {
            $message = 'تم حذف الصفحة بنجاح';
        } else {
            $error = 'حدث خطأ أثناء حذف الصفحة';
        }
    } catch (PDOException $e) {
        $error = 'خطأ في قاعدة البيانات: ' . $e->getMessage();
    }
}

// جلب جميع الصفحات
try {
    $stmt = $pdo->query("SELECT * FROM pages ORDER BY created_at DESC");
    $pages = $stmt->fetchAll();
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
    <title>إدارة الصفحات</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
            direction: rtl;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input, textarea, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            box-sizing: border-box;
        }
        textarea {
            height: 150px;
            resize: vertical;
        }
        button {
            background: #4CAF50;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background: #45a049;
        }
        .message {
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: right;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .actions a {
            margin-left: 10px;
            padding: 5px 10px;
            text-decoration: none;
            border-radius: 3px;
            font-size: 12px;
        }
        .edit {
            background: #007bff;
            color: white;
        }
        .delete {
            background: #dc3545;
            color: white;
        }
        .nav {
            margin-bottom: 20px;
        }
        .nav a {
            background: #6c757d;
            color: white;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 5px;
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>إدارة الصفحات</h1>
            <p>إضافة وتعديل وحذف صفحات الموقع</p>
        </div>

        <div class="nav">
            <a href="index.php">لوحة التحكم</a>
            <a href="../index.php">الموقع الرئيسي</a>
        </div>

        <?php if ($message): ?>
            <div class="message success"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>

        <h2>إضافة صفحة جديدة</h2>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            
            <div class="form-group">
                <label for="title">عنوان الصفحة:</label>
                <input type="text" id="title" name="title" required>
            </div>

            <div class="form-group">
                <label for="slug">الرابط (slug):</label>
                <input type="text" id="slug" name="slug" placeholder="مثال: about-us">
            </div>

            <div class="form-group">
                <label for="content">محتوى الصفحة:</label>
                <textarea id="content" name="content" required></textarea>
            </div>

            <div class="form-group">
                <label for="status">حالة الصفحة:</label>
                <select id="status" name="status">
                    <option value="published">منشورة</option>
                    <option value="draft">مسودة</option>
                </select>
            </div>

            <button type="submit">إضافة الصفحة</button>
        </form>

        <h2>الصفحات الموجودة</h2>
        <?php if (!empty($pages)): ?>
            <table>
                <thead>
                    <tr>
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
                            <td><?php echo htmlspecialchars($page['title']); ?></td>
                            <td><?php echo htmlspecialchars($page['slug']); ?></td>
                            <td><?php echo $page['status'] === 'published' ? 'منشورة' : 'مسودة'; ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($page['created_at'])); ?></td>
                            <td class="actions">
                                <a href="../?page=<?php echo $page['slug']; ?>" class="edit" target="_blank">عرض</a>
                                <a href="?delete=<?php echo $page['id']; ?>" class="delete" onclick="return confirm('هل أنت متأكد من حذف هذه الصفحة؟')">حذف</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>لا توجد صفحات حالياً.</p>
        <?php endif; ?>
    </div>
</body>
</html>
