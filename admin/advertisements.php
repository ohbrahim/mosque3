<?php
/**
 * إدارة الإعلانات
 */
require_once '../config/config.php';

// التحقق من تسجيل الدخول والصلاحيات
requireLogin();
requirePermission('manage_ads');

// معالجة الإجراءات
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'رمز الأمان غير صحيح';
    } else {
        if ($action === 'add' || $action === 'edit') {
            $data = [
                'title' => sanitize($_POST['title']),
                'content' => sanitize($_POST['content']),
                'link_url' => sanitize($_POST['link_url']),
                'position' => sanitize($_POST['position']),
                'start_date' => $_POST['start_date'],
                'end_date' => $_POST['end_date'],
                'status' => sanitize($_POST['status']),
                'created_by' => $_SESSION['user_id']
            ];
            
            // رفع الصورة إذا تم اختيارها
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = uploadFile($_FILES['image'], ['jpg', 'jpeg', 'png', 'gif']);
                if ($uploadResult['success']) {
                    $data['image'] = $uploadResult['filename'];
                } else {
                    $error = $uploadResult['message'];
                }
            }
            
            if (!$error) {
                if ($action === 'add') {
                    if ($db->insert('advertisements', $data)) {
                        $message = 'تم إضافة الإعلان بنجاح';
                        $action = 'list';
                    } else {
                        $error = 'فشل في إضافة الإعلان';
                    }
                } else {
                    $id = (int)$_POST['id'];
                    unset($data['created_by']);
                    
                    $updateFields = [];
                    $updateValues = [];
                    foreach ($data as $key => $value) {
                        if ($key !== 'image' || !empty($value)) {
                            $updateFields[] = "$key = ?";
                            $updateValues[] = $value;
                        }
                    }
                    $updateValues[] = $id;
                    $sql = "UPDATE advertisements SET " . implode(', ', $updateFields) . " WHERE id = ?";
                    $stmt = $db->query($sql, $updateValues);
                    if ($stmt) {
                        $message = 'تم تحديث الإعلان بنجاح';
                        $action = 'list';
                    } else {
                        $error = 'فشل في تحديث الإعلان';
                    }
                }
            }
        }
    }
}

// حذف إعلان
if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $ad = $db->fetchOne("SELECT image FROM advertisements WHERE id = ?", [$id]);
    if ($ad && $ad['image']) {
        deleteFile($ad['image']);
    }
    if ($db->delete('advertisements', 'id = ?', [$id])) {
        $message = 'تم حذف الإعلان بنجاح';
    } else {
        $error = 'فشل في حذف الإعلان';
    }
    $action = 'list';
}

// جلب البيانات حسب الإجراء
if ($action === 'edit' && isset($_GET['id'])) {
    $adData = $db->fetchOne("SELECT * FROM advertisements WHERE id = ?", [(int)$_GET['id']]);
    if (!$adData) {
        $error = 'الإعلان غير موجود';
        $action = 'list';
    }
}

if ($action === 'list') {
    $ads = $db->fetchAll("SELECT a.*, u.full_name as creator_name FROM advertisements a LEFT JOIN users u ON a.created_by = u.id ORDER BY a.created_at DESC");
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الإعلانات - لوحة التحكم</title>
    
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
        
        .sidebar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: white;
        }
        
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            border-radius: 8px;
            margin: 2px 0;
            transition: all 0.3s;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        
        .main-content {
            padding: 30px;
        }
        
        .page-header {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .content-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .ad-preview {
            max-width: 100px;
            max-height: 100px;
            object-fit: cover;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-2 p-0">
                <div class="sidebar">
                    <div class="p-4">
                        <h4 class="text-center mb-4">
                            <i class="fas fa-mosque"></i>
                            لوحة التحكم
                        </h4>
                        
                        <nav class="nav flex-column">
                            <a class="nav-link" href="index.php">
                                <i class="fas fa-tachometer-alt"></i>
                                الرئيسية
                            </a>
                            <a class="nav-link" href="pages.php">
                                <i class="fas fa-file-alt"></i>
                                إدارة الصفحات
                            </a>
                            <a class="nav-link" href="blocks.php">
                                <i class="fas fa-th-large"></i>
                                إدارة البلوكات
                            </a>
                            <a class="nav-link active" href="advertisements.php">
                                <i class="fas fa-bullhorn"></i>
                                إدارة الإعلانات
                            </a>
                            <a class="nav-link" href="comments.php">
                                <i class="fas fa-comments"></i>
                                التعليقات
                            </a>
                            <a class="nav-link" href="messages.php">
                                <i class="fas fa-envelope"></i>
                                الرسائل
                            </a>
                            <a class="nav-link" href="polls.php">
                                <i class="fas fa-poll"></i>
                                الاستطلاعات
                            </a>
                            <a class="nav-link" href="users.php">
                                <i class="fas fa-users"></i>
                                المستخدمون
                            </a>
                            <a class="nav-link" href="statistics.php">
                                <i class="fas fa-chart-bar"></i>
                                الإحصائيات
                            </a>
                            <a class="nav-link" href="settings.php">
                                <i class="fas fa-cog"></i>
                                الإعدادات
                            </a>
                            <hr class="my-3">
                            <a class="nav-link" href="../index.php" target="_blank">
                                <i class="fas fa-external-link-alt"></i>
                                عرض الموقع
                            </a>
                            <a class="nav-link" href="../logout.php">
                                <i class="fas fa-sign-out-alt"></i>
                                تسجيل الخروج
                            </a>
                        </nav>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-lg-10">
                <div class="main-content">
                    <!-- Page Header -->
                    <div class="page-header">
                        <div class="row align-items-center">
                            <div class="col">
                                <h2 class="mb-2">إدارة الإعلانات</h2>
                                <nav aria-label="breadcrumb">
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item"><a href="index.php">لوحة التحكم</a></li>
                                        <li class="breadcrumb-item active">إدارة الإعلانات</li>
                                    </ol>
                                </nav>
                            </div>
                            <?php if ($action === 'list'): ?>
                            <div class="col-auto">
                                <a href="?action=add" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> إضافة إعلان جديد
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Messages -->
                    <?php if ($message): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Content -->
                    <div class="content-card">
                        <?php if ($action === 'list'): ?>
                            <!-- Ads List -->
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>العنوان</th>
                                            <th>الصورة</th>
                                            <th>الموقع</th>
                                            <th>تاريخ البداية</th>
                                            <th>تاريخ النهاية</th>
                                            <th>الحالة</th>
                                            <th>النقرات</th>
                                            <th>الإجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($ads as $ad): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($ad['title']); ?></strong>
                                                <?php if ($ad['content']): ?>
                                                    <br><small class="text-muted"><?php echo truncateText($ad['content'], 50); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($ad['image']): ?>
                                                    <img src="<?php echo UPLOAD_PATH . $ad['image']; ?>" 
                                                         alt="<?php echo htmlspecialchars($ad['title']); ?>" 
                                                         class="ad-preview">
                                                <?php else: ?>
                                                    <span class="text-muted">لا توجد صورة</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php
                                                $positions = [
                                                    'header' => 'الهيدر',
                                                    'sidebar' => 'الشريط الجانبي',
                                                    'footer' => 'الفوتر',
                                                    'content' => 'المحتوى'
                                                ];
                                                echo $positions[$ad['position']] ?? $ad['position'];
                                                ?>
                                            </td>
                                            <td><?php echo $ad['start_date'] ? formatArabicDate($ad['start_date']) : '-'; ?></td>
                                            <td><?php echo $ad['end_date'] ? formatArabicDate($ad['end_date']) : '-'; ?></td>
                                            <td>
                                                <?php if ($ad['status'] === 'active'): ?>
                                                    <span class="badge bg-success">نشط</span>
                                                <?php elseif ($ad['status'] === 'inactive'): ?>
                                                    <span class="badge bg-secondary">غير نشط</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">منتهي</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo convertToArabicNumbers($ad['clicks_count']); ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="?action=edit&id=<?php echo $ad['id']; ?>" class="btn btn-outline-primary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="?action=delete&id=<?php echo $ad['id']; ?>" 
                                                       class="btn btn-outline-danger"
                                                       onclick="return confirm('هل أنت متأكد من حذف هذا الإعلان؟')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                        <?php elseif ($action === 'add' || $action === 'edit'): ?>
                            <!-- Add/Edit Form -->
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                <?php if ($action === 'edit'): ?>
                                    <input type="hidden" name="id" value="<?php echo $adData['id']; ?>">
                                <?php endif; ?>
                                
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="mb-3">
                                            <label for="title" class="form-label">عنوان الإعلان *</label>
                                            <input type="text" class="form-control" id="title" name="title" 
                                                   value="<?php echo htmlspecialchars($adData['title'] ?? ''); ?>" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="content" class="form-label">محتوى الإعلان</label>
                                            <textarea class="form-control" id="content" name="content" rows="4"><?php echo htmlspecialchars($adData['content'] ?? ''); ?></textarea>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="link_url" class="form-label">رابط الإعلان</label>
                                            <input type="url" class="form-control" id="link_url" name="link_url" 
                                                   value="<?php echo htmlspecialchars($adData['link_url'] ?? ''); ?>">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="image" class="form-label">صورة الإعلان</label>
                                            <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                            <?php if ($action === 'edit' && $adData['image']): ?>
                                                <div class="mt-2">
                                                    <img src="<?php echo UPLOAD_PATH . $adData['image']; ?>" 
                                                         alt="الصورة الحالية" class="img-thumbnail" style="max-width: 200px;">
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="position" class="form-label">موقع العرض</label>
                                            <select class="form-select" id="position" name="position">
                                                <option value="header" <?php echo ($adData['position'] ?? '') === 'header' ? 'selected' : ''; ?>>الهيدر</option>
                                                <option value="sidebar" <?php echo ($adData['position'] ?? '') === 'sidebar' ? 'selected' : ''; ?>>الشريط الجانبي</option>
                                                <option value="footer" <?php echo ($adData['position'] ?? '') === 'footer' ? 'selected' : ''; ?>>الفوتر</option>
                                                <option value="content" <?php echo ($adData['position'] ?? '') === 'content' ? 'selected' : ''; ?>>المحتوى</option>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="start_date" class="form-label">تاريخ البداية</label>
                                            <input type="date" class="form-control" id="start_date" name="start_date" 
                                                   value="<?php echo $adData['start_date'] ?? ''; ?>">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="end_date" class="form-label">تاريخ النهاية</label>
                                            <input type="date" class="form-control" id="end_date" name="end_date" 
                                                   value="<?php echo $adData['end_date'] ?? ''; ?>">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="status" class="form-label">الحالة</label>
                                            <select class="form-select" id="status" name="status">
                                                <option value="active" <?php echo ($adData['status'] ?? '') === 'active' ? 'selected' : ''; ?>>نشط</option>
                                                <option value="inactive" <?php echo ($adData['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>غير نشط</option>
                                            </select>
                                        </div>
                                        
                                        <?php if ($action === 'edit'): ?>
                                        <div class="mb-3">
                                            <label class="form-label">إحصائيات</label>
                                            <div class="bg-light p-3 rounded">
                                                <p><strong>النقرات:</strong> <?php echo convertToArabicNumbers($adData['clicks_count']); ?></p>
                                                <p><strong>المشاهدات:</strong> <?php echo convertToArabicNumbers($adData['impressions_count']); ?></p>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-between">
                                    <a href="?action=list" class="btn btn-secondary">
                                        <i class="fas fa-arrow-right"></i> العودة
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> 
                                        <?php echo $action === 'add' ? 'إضافة الإعلان' : 'تحديث الإعلان'; ?>
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
