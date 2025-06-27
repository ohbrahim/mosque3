<?php
require_once '../config/config.php';
require_once '../includes/functions/all_functions.php';

requireLogin();
requirePermission('manage_pages');

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
                'slug' => sanitize($_POST['slug']),
                'content' => $_POST['content'],
                'excerpt' => sanitize($_POST['excerpt']),
                'category_id' => $_POST['category_id'] ?: null,
                'meta_title' => sanitize($_POST['meta_title']),
                'meta_description' => sanitize($_POST['meta_description']),
                'meta_keywords' => sanitize($_POST['meta_keywords']),
                'status' => sanitize($_POST['status']),
                'visibility' => sanitize($_POST['visibility']),
                'allow_comments' => isset($_POST['allow_comments']) ? 1 : 0,
                'allow_ratings' => isset($_POST['allow_ratings']) ? 1 : 0,
                'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
                'is_sticky' => isset($_POST['is_sticky']) ? 1 : 0,
                'template' => sanitize($_POST['template']),
                'author_id' => $_SESSION['user_id']
            ];
            
            // معالجة الصورة المميزة
            if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = uploadFile($_FILES['featured_image'], ['jpg', 'jpeg', 'png', 'gif']);
                if ($uploadResult['success']) {
                    $data['featured_image'] = $uploadResult['filename'];
                } else {
                    $error = $uploadResult['message'];
                }
            }
            
            // معالجة التاريخ المجدول
            if ($data['status'] === 'scheduled' && !empty($_POST['scheduled_at'])) {
                $data['scheduled_at'] = $_POST['scheduled_at'];
            }
            
            // تحديد تاريخ النشر
            if ($data['status'] === 'published' && $action === 'add') {
                $data['published_at'] = date('Y-m-d H:i:s');
            }
            
            if (!$error) {
                if ($action === 'add') {
                    // التحقق من عدم تكرار الـ slug
                    $existingPage = $db->fetchOne("SELECT id FROM pages WHERE slug = ?", [$data['slug']]);
                    if ($existingPage) {
                        $data['slug'] .= '-' . time();
                    }
                    
                    if ($db->insert('pages', $data)) {
                        $message = 'تم إضافة الصفحة بنجاح';
                        $action = 'list';
                    } else {
                        $error = 'فشل في إضافة الصفحة';
                    }
                } else {
                    $id = (int)$_POST['id'];
                    $data['editor_id'] = $_SESSION['user_id'];
                    unset($data['author_id']);
                    
                    if ($db->update('pages', $data, 'id = ?', [$id])) {
                        $message = 'تم تحديث الصفحة بنجاح';
                        $action = 'list';
                    } else {
                        $error = 'فشل في تحديث الصفحة';
                    }
                }
            }
        }
    }
}

// حذف صفحة
if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $page = $db->fetchOne("SELECT featured_image FROM pages WHERE id = ?", [$id]);
    if ($page && $page['featured_image']) {
        deleteFile($page['featured_image']);
    }
    if ($db->delete('pages', 'id = ?', [$id])) {
        $message = 'تم حذف الصفحة بنجاح';
    } else {
        $error = 'فشل في حذف الصفحة';
    }
    $action = 'list';
}

// جلب البيانات حسب الإجراء
if ($action === 'edit' && isset($_GET['id'])) {
    $pageData = $db->fetchOne("SELECT * FROM pages WHERE id = ?", [(int)$_GET['id']]);
    if (!$pageData) {
        $error = 'الصفحة غير موجودة';
        $action = 'list';
    }
}

if ($action === 'list') {
    $pages = $db->fetchAll("
        SELECT p.*, c.name as category_name, u.full_name as author_name 
        FROM pages p 
        LEFT JOIN categories c ON p.category_id = c.id 
        LEFT JOIN users u ON p.author_id = u.id 
        ORDER BY p.created_at DESC
    ");
}

// جلب التصنيفات للنموذج
$categories = $db->fetchAll("SELECT * FROM categories WHERE status = 'active' ORDER BY name");

// قوالب الصفحات المتاحة
$templates = [
    'default' => 'افتراضي',
    'full-width' => 'عرض كامل',
    'sidebar-left' => 'شريط جانبي يسار',
    'sidebar-right' => 'شريط جانبي يمين',
    'landing' => 'صفحة هبوط',
    'contact' => 'صفحة اتصال'
];
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
    <!-- CKEditor 5 -->
    <script src="https://cdn.ckeditor.com/ckeditor5/40.0.0/classic/ckeditor.js"></script>
    
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
        
        .ck-editor__editable {
            min-height: 300px;
        }
        
        .page-preview {
            max-width: 100px;
            max-height: 100px;
            object-fit: cover;
            border-radius: 5px;
        }
        
        .status-badge {
            font-size: 0.8rem;
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
                            <a class="nav-link active" href="pages.php">
                                <i class="fas fa-file-alt"></i>
                                إدارة الصفحات
                            </a>
                            <a class="nav-link" href="blocks.php">
                                <i class="fas fa-th-large"></i>
                                إدارة البلوكات
                            </a>
                            <a class="nav-link" href="comments.php">
                                <i class="fas fa-comments"></i>
                                التعليقات
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
                                <h2 class="mb-2">إدارة الصفحات</h2>
                                <nav aria-label="breadcrumb">
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item"><a href="index.php">لوحة التحكم</a></li>
                                        <li class="breadcrumb-item active">إدارة الصفحات</li>
                                    </ol>
                                </nav>
                            </div>
                            <?php if ($action === 'list'): ?>
                            <div class="col-auto">
                                <a href="?action=add" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> إضافة صفحة جديدة
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
                            <!-- Pages List -->
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>العنوان</th>
                                            <th>التصنيف</th>
                                            <th>الحالة</th>
                                            <th>المؤلف</th>
                                            <th>تاريخ الإنشاء</th>
                                            <th>المشاهدات</th>
                                            <th>الإجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($pages)): ?>
                                            <tr>
                                                <td colspan="7" class="text-center py-4">
                                                    <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                                                    <h5 class="text-muted">لا توجد صفحات</h5>
                                                    <a href="?action=add" class="btn btn-primary">إضافة أول صفحة</a>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($pages as $page): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <?php if ($page['featured_image']): ?>
                                                            <img src="../uploads/<?php echo $page['featured_image']; ?>" 
                                                                 alt="<?php echo htmlspecialchars($page['title']); ?>" 
                                                                 class="page-preview me-3">
                                                        <?php endif; ?>
                                                        <div>
                                                            <strong><?php echo htmlspecialchars($page['title']); ?></strong>
                                                            <?php if ($page['is_featured']): ?>
                                                                <span class="badge bg-warning ms-2">مميز</span>
                                                            <?php endif; ?>
                                                            <?php if ($page['is_sticky']): ?>
                                                                <span class="badge bg-info ms-2">مثبت</span>
                                                            <?php endif; ?>
                                                            <br><small class="text-muted">/<?php echo $page['slug']; ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php echo $page['category_name'] ? htmlspecialchars($page['category_name']) : '<span class="text-muted">غير مصنف</span>'; ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    $statusClasses = [
                                                        'published' => 'bg-success',
                                                        'draft' => 'bg-secondary',
                                                        'private' => 'bg-warning',
                                                        'scheduled' => 'bg-info'
                                                    ];
                                                    $statusLabels = [
                                                        'published' => 'منشور',
                                                        'draft' => 'مسودة',
                                                        'private' => 'خاص',
                                                        'scheduled' => 'مجدول'
                                                    ];
                                                    ?>
                                                    <span class="badge <?php echo $statusClasses[$page['status']] ?? 'bg-secondary'; ?> status-badge">
                                                        <?php echo $statusLabels[$page['status']] ?? $page['status']; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($page['author_name']); ?></td>
                                                <td><?php echo formatArabicDate($page['created_at']); ?></td>
                                                <td><?php echo convertToArabicNumbers($page['views_count'] ?? 0); ?></td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="?action=edit&id=<?php echo $page['id']; ?>" class="btn btn-outline-primary">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <?php if ($page['status'] === 'published'): ?>
                                                            <a href="../?page=<?php echo $page['slug']; ?>" target="_blank" class="btn btn-outline-info">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                        <a href="?action=delete&id=<?php echo $page['id']; ?>" 
                                                           class="btn btn-outline-danger"
                                                           onclick="return confirm('هل أنت متأكد من حذف هذه الصفحة؟')">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                        <?php elseif ($action === 'add' || $action === 'edit'): ?>
                            <!-- Add/Edit Form -->
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                <?php if ($action === 'edit'): ?>
                                    <input type="hidden" name="id" value="<?php echo $pageData['id']; ?>">
                                <?php endif; ?>
                                
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="mb-3">
                                            <label for="title" class="form-label">عنوان الصفحة *</label>
                                            <input type="text" class="form-control" id="title" name="title" 
                                                   value="<?php echo htmlspecialchars($pageData['title'] ?? ''); ?>" 
                                                   required onkeyup="generateSlug()">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="slug" class="form-label">الرابط المختصر *</label>
                                            <input type="text" class="form-control" id="slug" name="slug" 
                                                   value="<?php echo htmlspecialchars($pageData['slug'] ?? ''); ?>" required>
                                            <div class="form-text">سيكون الرابط: <?php echo SITE_URL; ?>/<span id="slug-preview"></span></div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="excerpt" class="form-label">المقتطف</label>
                                            <textarea class="form-control" id="excerpt" name="excerpt" rows="3"><?php echo htmlspecialchars($pageData['excerpt'] ?? ''); ?></textarea>
                                            <div class="form-text">وصف مختصر للصفحة</div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="content" class="form-label">محتوى الصفحة</label>
                                            <textarea class="form-control" id="content" name="content" rows="15"><?php echo htmlspecialchars($pageData['content'] ?? ''); ?></textarea>
                                        </div>
                                        
                                        <!-- SEO Settings -->
                                        <div class="card mb-3">
                                            <div class="card-header">
                                                <h6 class="mb-0">إعدادات SEO</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="mb-3">
                                                    <label for="meta_title" class="form-label">عنوان SEO</label>
                                                    <input type="text" class="form-control" id="meta_title" name="meta_title" 
                                                           value="<?php echo htmlspecialchars($pageData['meta_title'] ?? ''); ?>">
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="meta_description" class="form-label">وصف SEO</label>
                                                    <textarea class="form-control" id="meta_description" name="meta_description" rows="3"><?php echo htmlspecialchars($pageData['meta_description'] ?? ''); ?></textarea>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="meta_keywords" class="form-label">الكلمات المفتاحية</label>
                                                    <input type="text" class="form-control" id="meta_keywords" name="meta_keywords" 
                                                           value="<?php echo htmlspecialchars($pageData['meta_keywords'] ?? ''); ?>">
                                                    <div class="form-text">افصل بين الكلمات بفاصلة</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <!-- Publish Settings -->
                                        <div class="card mb-3">
                                            <div class="card-header">
                                                <h6 class="mb-0">إعدادات النشر</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="mb-3">
                                                    <label for="status" class="form-label">حالة الصفحة</label>
                                                    <select class="form-select" id="status" name="status" onchange="toggleScheduleField()">
                                                        <option value="draft" <?php echo ($pageData['status'] ?? '') === 'draft' ? 'selected' : ''; ?>>مسودة</option>
                                                        <option value="published" <?php echo ($pageData['status'] ?? '') === 'published' ? 'selected' : ''; ?>>منشور</option>
                                                        <option value="private" <?php echo ($pageData['status'] ?? '') === 'private' ? 'selected' : ''; ?>>خاص</option>
                                                        <option value="scheduled" <?php echo ($pageData['status'] ?? '') === 'scheduled' ? 'selected' : ''; ?>>مجدول</option>
                                                    </select>
                                                </div>
                                                
                                                <div class="mb-3" id="schedule-field" style="display: none;">
                                                    <label for="scheduled_at" class="form-label">تاريخ النشر المجدول</label>
                                                    <input type="datetime-local" class="form-control" id="scheduled_at" name="scheduled_at" 
                                                           value="<?php echo $pageData['scheduled_at'] ? date('Y-m-d\TH:i', strtotime($pageData['scheduled_at'])) : ''; ?>">
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="visibility" class="form-label">الرؤية</label>
                                                    <select class="form-select" id="visibility" name="visibility">
                                                        <option value="public" <?php echo ($pageData['visibility'] ?? '') === 'public' ? 'selected' : ''; ?>>عام</option>
                                                        <option value="private" <?php echo ($pageData['visibility'] ?? '') === 'private' ? 'selected' : ''; ?>>خاص</option>
                                                        <option value="password" <?php echo ($pageData['visibility'] ?? '') === 'password' ? 'selected' : ''; ?>>محمي بكلمة مرور</option>
                                                        <option value="members_only" <?php echo ($pageData['visibility'] ?? '') === 'members_only' ? 'selected' : ''; ?>>للأعضاء فقط</option>
                                                    </select>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="template" class="form-label">قالب الصفحة</label>
                                                    <select class="form-select" id="template" name="template">
                                                        <?php foreach ($templates as $key => $label): ?>
                                                            <option value="<?php echo $key; ?>" <?php echo ($pageData['template'] ?? 'default') === $key ? 'selected' : ''; ?>>
                                                                <?php echo $label; ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Category and Tags -->
                                        <div class="card mb-3">
                                            <div class="card-header">
                                                <h6 class="mb-0">التصنيف والعلامات</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="mb-3">
                                                    <label for="category_id" class="form-label">التصنيف</label>
                                                    <select class="form-select" id="category_id" name="category_id">
                                                        <option value="">بدون تصنيف</option>
                                                        <?php foreach ($categories as $category): ?>
                                                            <option value="<?php echo $category['id']; ?>" 
                                                                    <?php echo ($pageData['category_id'] ?? '') == $category['id'] ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($category['name']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Featured Image -->
                                        <div class="card mb-3">
                                            <div class="card-header">
                                                <h6 class="mb-0">الصورة المميزة</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="mb-3">
                                                    <input type="file" class="form-control" id="featured_image" name="featured_image" accept="image/*">
                                                    <?php if ($action === 'edit' && $pageData['featured_image']): ?>
                                                        <div class="mt-2">
                                                            <img src="../uploads/<?php echo $pageData['featured_image']; ?>" 
                                                                 alt="الصورة الحالية" class="img-thumbnail" style="max-width: 200px;">
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Page Options -->
                                        <div class="card mb-3">
                                            <div class="card-header">
                                                <h6 class="mb-0">خيارات الصفحة</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" id="allow_comments" name="allow_comments" 
                                                           <?php echo ($pageData['allow_comments'] ?? 1) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="allow_comments">
                                                        السماح بالتعليقات
                                                    </label>
                                                </div>
                                                
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" id="allow_ratings" name="allow_ratings" 
                                                           <?
                                                           <?php echo ($pageData['allow_ratings'] ?? 1) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="allow_ratings">
                                                        السماح بالتقييمات
                                                    </label>
                                                </div>
                                                
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" 
                                                           <?php echo ($pageData['is_featured'] ?? 0) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="is_featured">
                                                        صفحة مميزة
                                                    </label>
                                                </div>
                                                
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" id="is_sticky" name="is_sticky" 
                                                           <?php echo ($pageData['is_sticky'] ?? 0) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="is_sticky">
                                                        صفحة مثبتة
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <?php if ($action === 'edit'): ?>
                                        <div class="card mb-3">
                                            <div class="card-header">
                                                <h6 class="mb-0">معلومات إضافية</h6>
                                            </div>
                                            <div class="card-body">
                                                <p><strong>تاريخ الإنشاء:</strong><br><?php echo formatArabicDate($pageData['created_at']); ?></p>
                                                <p><strong>آخر تحديث:</strong><br><?php echo formatArabicDate($pageData['updated_at']); ?></p>
                                                <p><strong>المشاهدات:</strong> <?php echo convertToArabicNumbers($pageData['views_count'] ?? 0); ?></p>
                                                <?php if ($pageData['published_at']): ?>
                                                    <p><strong>تاريخ النشر:</strong><br><?php echo formatArabicDate($pageData['published_at']); ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-between">
                                    <a href="?action=list" class="btn btn-secondary">
                                        <i class="fas fa-arrow-right"></i> العودة
                                    </a>
                                    <div>
                                        <button type="submit" name="save_draft" class="btn btn-outline-primary me-2">
                                            <i class="fas fa-save"></i> حفظ كمسودة
                                        </button>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-check"></i> 
                                            <?php echo $action === 'add' ? 'إضافة الصفحة' : 'تحديث الصفحة'; ?>
                                        </button>
                                    </div>
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
    
    <script>
        let editor;
        
        // تهيئة محرر النصوص CKEditor 5
        ClassicEditor
            .create(document.querySelector('#content'), {
                language: 'ar',
                toolbar: {
                    items: [
                        'heading', '|',
                        'bold', 'italic', 'underline', 'strikethrough', '|',
                        'fontSize', 'fontColor', 'fontBackgroundColor', '|',
                        'alignment', '|',
                        'numberedList', 'bulletedList', 'outdent', 'indent', '|',
                        'link', 'insertImage', 'insertTable', 'mediaEmbed', '|',
                        'blockQuote', 'horizontalLine', '|',
                        'undo', 'redo', '|',
                        'sourceEditing'
                    ]
                },
                fontSize: {
                    options: [
                        9, 11, 13, 'default', 17, 19, 21, 24, 28, 32
                    ]
                },
                heading: {
                    options: [
                        { model: 'paragraph', title: 'فقرة', class: 'ck-heading_paragraph' },
                        { model: 'heading1', view: 'h1', title: 'عنوان 1', class: 'ck-heading_heading1' },
                        { model: 'heading2', view: 'h2', title: 'عنوان 2', class: 'ck-heading_heading2' },
                        { model: 'heading3', view: 'h3', title: 'عنوان 3', class: 'ck-heading_heading3' },
                        { model: 'heading4', view: 'h4', title: 'عنوان 4', class: 'ck-heading_heading4' }
                    ]
                }
            })
            .then(newEditor => {
                editor = newEditor;
                // تخصيص اتجاه النص للعربية
                editor.editing.view.change(writer => {
                    writer.setStyle('direction', 'rtl', editor.editing.view.document.getRoot());
                    writer.setStyle('text-align', 'right', editor.editing.view.document.getRoot());
                });
            })
            .catch(error => {
                console.error('خطأ في تهيئة المحرر:', error);
            });
        
        // توليد الرابط المختصر تلقائياً
        function generateSlug() {
            const title = document.getElementById('title').value;
            const slug = title
                .toLowerCase()
                .replace(/[أإآ]/g, 'ا')
                .replace(/[ة]/g, 'ه')
                .replace(/[ى]/g, 'ي')
                .replace(/[^\u0600-\u06FF\w\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-')
                .trim('-');
            
            document.getElementById('slug').value = slug;
            document.getElementById('slug-preview').textContent = slug;
        }
        
        // إظهار/إخفاء حقل التاريخ المجدول
        function toggleScheduleField() {
            const status = document.getElementById('status').value;
            const scheduleField = document.getElementById('schedule-field');
            
            if (status === 'scheduled') {
                scheduleField.style.display = 'block';
            } else {
                scheduleField.style.display = 'none';
            }
        }
        
        // تهيئة الصفحة
        document.addEventListener('DOMContentLoaded', function() {
            toggleScheduleField();
            generateSlug();
        });
        
        // حفظ كمسودة
        document.querySelector('button[name="save_draft"]').addEventListener('click', function() {
            document.getElementById('status').value = 'draft';
        });
    </script>
</body>
</html>
