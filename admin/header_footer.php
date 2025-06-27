<?php
/**
 * إدارة الهيدر والفوتر
 */
require_once '../config/config.php';

// التحقق من تسجيل الدخول والصلاحيات
requireLogin();
requirePermission('manage_settings');

$message = '';
$error = '';

// معالجة الإجراءات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'save_header_footer_settings':
            $settings = [
                'header_style' => $_POST['header_style'] ?? 'modern',
                'header_fixed' => isset($_POST['header_fixed']) ? '1' : '0',
                'header_transparent' => isset($_POST['header_transparent']) ? '1' : '0',
                'show_search_box' => isset($_POST['show_search_box']) ? '1' : '0',
                'footer_style' => $_POST['footer_style'] ?? 'modern',
                'footer_copyright' => sanitize($_POST['footer_copyright']),
                'show_social_links' => isset($_POST['show_social_links']) ? '1' : '0',
                'header_bg_color' => sanitize($_POST['header_bg_color']),
                'header_text_color' => sanitize($_POST['header_text_color']),
                'footer_bg_color' => sanitize($_POST['footer_bg_color']),
                'footer_text_color' => sanitize($_POST['footer_text_color'])
            ];
            
            $success = true;
            foreach ($settings as $key => $value) {
                if (!saveSetting($db, $key, $value, $_SESSION['user_id'])) {
                    $success = false;
                }
            }
            
            if ($success) {
                $message = 'تم حفظ إعدادات الهيدر والفوتر بنجاح';
            } else {
                $error = 'فشل في حفظ بعض الإعدادات';
            }
            break;
            
        case 'add_header_footer_content':
            $data = [
                'section' => $_POST['section'],
                'content_type' => $_POST['content_type'],
                'title' => sanitize($_POST['title']),
                'content' => $_POST['content'],
                'position' => $_POST['position'],
                'display_order' => (int)$_POST['display_order'],
                'status' => $_POST['status'] ?? 'active'
            ];
            
            if ($db->insert('header_footer_content', $data)) {
                $message = 'تم إضافة المحتوى بنجاح';
            } else {
                $error = 'فشل في إضافة المحتوى';
            }
            break;
            
        case 'edit_header_footer_content':
            $id = (int)$_POST['id'];
            $data = [
                'section' => $_POST['section'],
                'content_type' => $_POST['content_type'],
                'title' => sanitize($_POST['title']),
                'content' => $_POST['content'],
                'position' => $_POST['position'],
                'display_order' => (int)$_POST['display_order'],
                'status' => $_POST['status'] ?? 'active'
            ];
            
            if ($db->update('header_footer_content', $data, 'id = ?', [$id])) {
                $message = 'تم تحديث المحتوى بنجاح';
            } else {
                $error = 'فشل في تحديث المحتوى';
            }
            break;
            
        case 'delete_header_footer_content':
            $id = (int)$_POST['id'];
            if ($db->delete('header_footer_content', 'id = ?', [$id])) {
                $message = 'تم حذف المحتوى بنجاح';
            } else {
                $error = 'فشل في حذف المحتوى';
            }
            break;
    }
}

// جلب الإعدادات الحالية
$headerStyle = getSetting($db, 'header_style', 'modern');
$headerFixed = getSetting($db, 'header_fixed', '1');
$headerTransparent = getSetting($db, 'header_transparent', '0');
$showSearchBox = getSetting($db, 'show_search_box', '1');
$footerStyle = getSetting($db, 'footer_style', 'modern');
$footerCopyright = getSetting($db, 'footer_copyright', 'جميع الحقوق محفوظة');
$showSocialLinks = getSetting($db, 'show_social_links', '1');
$headerBgColor = getSetting($db, 'header_bg_color', '#667eea');
$headerTextColor = getSetting($db, 'header_text_color', '#ffffff');
$footerBgColor = getSetting($db, 'footer_bg_color', '#2c3e50');
$footerTextColor = getSetting($db, 'footer_text_color', '#ffffff');

// جلب محتوى الهيدر والفوتر
$headerContent = $db->fetchAll("SELECT * FROM header_footer_content WHERE section = 'header' ORDER BY display_order");
$footerContent = $db->fetchAll("SELECT * FROM header_footer_content WHERE section = 'footer' ORDER BY display_order");
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الهيدر والفوتر - لوحة التحكم</title>
    
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
            margin-bottom: 20px;
        }
        
        .color-picker {
            width: 50px;
            height: 40px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .preview-header {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            color: white;
        }
        
        .preview-footer {
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            color: white;
        }
        
        .content-item {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
        }
        
        .style-option {
            border: 2px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .style-option.selected {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.1);
        }
        
        .style-option:hover {
            border-color: #667eea;
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
                            <a class="nav-link" href="advertisements.php">
                                <i class="fas fa-bullhorn"></i>
                                إدارة الإعلانات
                            </a>
                            <a class="nav-link" href="menu_manager.php">
                                <i class="fas fa-bars"></i>
                                إدارة القوائم
                            </a>
                            <a class="nav-link active" href="header_footer.php">
                                <i class="fas fa-window-maximize"></i>
                                الهيدر والفوتر
                            </a>
                            <a class="nav-link" href="comments.php">
                                <i class="fas fa-comments"></i>
                                التعليقات
                            </a>
                            <a class="nav-link" href="messages.php">
                                <i class="fas fa-envelope"></i>
                                الرسائل
                            </a>
                            <a class="nav-link" href="users.php">
                                <i class="fas fa-users"></i>
                                المستخدمون
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
                        <h2 class="mb-2">إدارة الهيدر والفوتر</h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="index.php">لوحة التحكم</a></li>
                                <li class="breadcrumb-item active">الهيدر والفوتر</li>
                            </ol>
                        </nav>
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
                    
                    <!-- Header Footer Settings -->
                    <div class="content-card">
                        <h4 class="mb-4">
                            <i class="fas fa-cog"></i>
                            إعدادات الهيدر والفوتر
                        </h4>
                        
                        <form method="POST">
                            <input type="hidden" name="action" value="save_header_footer_settings">
                            
                            <!-- Header Settings -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h5 class="text-primary mb-3">
                                        <i class="fas fa-window-maximize"></i>
                                        إعدادات الهيدر
                                    </h5>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">نمط الهيدر</label>
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="style-option <?php echo $headerStyle === 'modern' ? 'selected' : ''; ?>" onclick="selectStyle('header_style', 'modern')">
                                                <i class="fas fa-laptop fa-2x mb-2"></i>
                                                <div>عصري</div>
                                                <input type="radio" name="header_style" value="modern" <?php echo $headerStyle === 'modern' ? 'checked' : ''; ?> style="display: none;">
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="style-option <?php echo $headerStyle === 'classic' ? 'selected' : ''; ?>" onclick="selectStyle('header_style', 'classic')">
                                                <i class="fas fa-desktop fa-2x mb-2"></i>
                                                <div>كلاسيكي</div>
                                                <input type="radio" name="header_style" value="classic" <?php echo $headerStyle === 'classic' ? 'checked' : ''; ?> style="display: none;">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">الألوان</label>
                                    <div class="row">
                                        <div class="col-6">
                                            <label class="form-label small">لون الخلفية</label>
                                            <input type="color" class="form-control color-picker" name="header_bg_color" value="<?php echo $headerBgColor; ?>">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small">لون النص</label>
                                            <input type="color" class="form-control color-picker" name="header_text_color" value="<?php echo $headerTextColor; ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-12 mb-3">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="header_fixed" <?php echo $headerFixed ? 'checked' : ''; ?>>
                                                <label class="form-check-label">هيدر ثابت</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="header_transparent" <?php echo $headerTransparent ? 'checked' : ''; ?>>
                                                <label class="form-check-label">هيدر شفاف</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="show_search_box" <?php echo $showSearchBox ? 'checked' : ''; ?>>
                                                <label class="form-check-label">عرض مربع البحث</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <!-- Footer Settings -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h5 class="text-primary mb-3">
                                        <i class="fas fa-window-minimize"></i>
                                        إعدادات الفوتر
                                    </h5>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">نمط الفوتر</label>
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="style-option <?php echo $footerStyle === 'modern' ? 'selected' : ''; ?>" onclick="selectStyle('footer_style', 'modern')">
                                                <i class="fas fa-layer-group fa-2x mb-2"></i>
                                                <div>عصري</div>
                                                <input type="radio" name="footer_style" value="modern" <?php echo $footerStyle === 'modern' ? 'checked' : ''; ?> style="display: none;">
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="style-option <?php echo $footerStyle === 'simple' ? 'selected' : ''; ?>" onclick="selectStyle('footer_style', 'simple')">
                                                <i class="fas fa-minus fa-2x mb-2"></i>
                                                <div>بسيط</div>
                                                <input type="radio" name="footer_style" value="simple" <?php echo $footerStyle === 'simple' ? 'checked' : ''; ?> style="display: none;">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">الألوان</label>
                                    <div class="row">
                                        <div class="col-6">
                                            <label class="form-label small">لون الخلفية</label>
                                            <input type="color" class="form-control color-picker" name="footer_bg_color" value="<?php echo $footerBgColor; ?>">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small">لون النص</label>
                                            <input type="color" class="form-control color-picker" name="footer_text_color" value="<?php echo $footerTextColor; ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="footer_copyright" class="form-label">نص حقوق الطبع</label>
                                    <input type="text" class="form-control" id="footer_copyright" name="footer_copyright" value="<?php echo htmlspecialchars($footerCopyright); ?>">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <div class="form-check mt-4">
                                        <input class="form-check-input" type="checkbox" name="show_social_links" <?php echo $showSocialLinks ? 'checked' : ''; ?>>
                                        <label class="form-check-label">عرض روابط التواصل الاجتماعي</label>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Preview -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h5 class="text-primary mb-3">
                                        <i class="fas fa-eye"></i>
                                        معاينة
                                    </h5>
                                    
                                    <div class="preview-header" style="background-color: <?php echo $headerBgColor; ?>; color: <?php echo $headerTextColor; ?>;">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <i class="fas fa-mosque"></i>
                                                مسجد النور
                                            </div>
                                            <div>
                                                <span class="me-3">الرئيسية</span>
                                                <span class="me-3">عن المسجد</span>
                                                <span>اتصل بنا</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="preview-footer" style="background-color: <?php echo $footerBgColor; ?>; color: <?php echo $footerTextColor; ?>;">
                                        <div class="text-center">
                                            <div class="mb-2"><?php echo htmlspecialchars($footerCopyright); ?></div>
                                            <small>© <?php echo date('Y'); ?> مسجد النور</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                حفظ الإعدادات
                            </button>
                        </form>
                    </div>
                    
                    <!-- Header Content Management -->
                    <div class="content-card">
                        <h4 class="mb-4">
                            <i class="fas fa-plus"></i>
                            إضافة محتوى للهيدر/الفوتر
                        </h4>
                        
                        <form method="POST">
                            <input type="hidden" name="action" value="add_header_footer_content">
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="section" class="form-label">القسم</label>
                                    <select class="form-select" id="section" name="section" required>
                                        <option value="header">الهيدر</option>
                                        <option value="footer">الفوتر</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="content_type" class="form-label">نوع المحتوى</label>
                                    <select class="form-select" id="content_type" name="content_type" required>
                                        <option value="text">نص</option>
                                        <option value="html">HTML</option>
                                        <option value="widget">ويدجت</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="position" class="form-label">الموقع</label>
                                    <select class="form-select" id="position" name="position">
                                        <option value="left">يسار</option>
                                        <option value="center">وسط</option>
                                        <option value="right">يمين</option>
                                        <option value="full">كامل</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="title" class="form-label">العنوان</label>
                                    <input type="text" class="form-control" id="title" name="title">
                                </div>
                                
                                <div class="col-md-3 mb-3">
                                    <label for="display_order" class="form-label">ترتيب العرض</label>
                                    <input type="number" class="form-control" id="display_order" name="display_order" value="0">
                                </div>
                                
                                <div class="col-md-3 mb-3">
                                    <label for="status" class="form-label">الحالة</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="active">نشط</option>
                                        <option value="inactive">غير نشط</option>
                                    </select>
                                </div>
                                
                                <div class="col-12 mb-3">
                                    <label for="content" class="form-label">المحتوى</label>
                                    <textarea class="form-control" id="content" name="content" rows="4"></textarea>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus"></i>
                                إضافة المحتوى
                            </button>
                        </form>
                    </div>
                    
                    <!-- Content Lists -->
                    <div class="row">
                        <!-- Header Content -->
                        <div class="col-md-6">
                            <div class="content-card">
                                <h5 class="mb-3">
                                    <i class="fas fa-window-maximize"></i>
                                    محتوى الهيدر
                                </h5>
                                
                                <?php foreach ($headerContent as $content): ?>
                                    <div class="content-item">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <h6><?php echo htmlspecialchars($content['title']); ?></h6>
                                                <small class="text-muted">
                                                    <?php echo ucfirst($content['content_type']); ?> - 
                                                    <?php echo ucfirst($content['position']); ?> - 
                                                    ترتيب: <?php echo $content['display_order']; ?>
                                                </small>
                                                <div class="mt-2">
                                                    <?php if ($content['content_type'] === 'text'): ?>
                                                        <p class="mb-0"><?php echo htmlspecialchars($content['content']); ?></p>
                                                    <?php else: ?>
                                                        <code><?php echo htmlspecialchars(substr($content['content'], 0, 100)); ?>...</code>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div>
                                                <span class="badge bg-<?php echo $content['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                    <?php echo $content['status'] === 'active' ? 'نشط' : 'غير نشط'; ?>
                                                </span>
                                                <button class="btn btn-sm btn-outline-primary ms-1" onclick="editContent(<?php echo htmlspecialchars(json_encode($content)); ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger" onclick="deleteContent(<?php echo $content['id']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- Footer Content -->
                        <div class="col-md-6">
                            <div class="content-card">
                                <h5 class="mb-3">
                                    <i class="fas fa-window-minimize"></i>
                                    محتوى الفوتر
                                </h5>
                                
                                <?php foreach ($footerContent as $content): ?>
                                    <div class="content-item">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <h6><?php echo htmlspecialchars($content['title']); ?></h6>
                                                <small class="text-muted">
                                                    <?php echo ucfirst($content['content_type']); ?> - 
                                                    <?php echo ucfirst($content['position']); ?> - 
                                                    ترتيب: <?php echo $content['display_order']; ?>
                                                </small>
                                                <div class="mt-2">
                                                    <?php if ($content['content_type'] === 'text'): ?>
                                                        <p class="mb-0"><?php echo htmlspecialchars($content['content']); ?></p>
                                                    <?php else: ?>
                                                        <code><?php echo htmlspecialchars(substr($content['content'], 0, 100)); ?>...</code>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div>
                                                <span class="badge bg-<?php echo $content['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                    <?php echo $content['status'] === 'active' ? 'نشط' : 'غير نشط'; ?>
                                                </span>
                                                <button class="btn btn-sm btn-outline-primary ms-1" onclick="editContent(<?php echo htmlspecialchars(json_encode($content)); ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger" onclick="deleteContent(<?php echo $content['id']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Content Modal -->
    <div class="modal fade" id="editContentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">تعديل المحتوى</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editContentForm">
                    <input type="hidden" name="action" value="edit_header_footer_content">
                    <input type="hidden" name="id" id="edit_content_id">
                    
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="edit_section" class="form-label">القسم</label>
                                <select class="form-select" id="edit_section" name="section" required>
                                    <option value="header">الهيدر</option>
                                    <option value="footer">الفوتر</option>
                                </select>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="edit_content_type" class="form-label">نوع المحتوى</label>
                                <select class="form-select" id="edit_content_type" name="content_type" required>
                                    <option value="text">نص</option>
                                    <option value="html">HTML</option>
                                    <option value="widget">ويدجت</option>
                                </select>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="edit_position" class="form-label">الموقع</label>
                                <select class="form-select" id="edit_position" name="position">
                                    <option value="left">يسار</option>
                                    <option value="center">وسط</option>
                                    <option value="right">يمين</option>
                                    <option value="full">كامل</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="edit_title" class="form-label">العنوان</label>
                                <input type="text" class="form-control" id="edit_title" name="title">
                            </div>
                            
                            <div class="col-md-3 mb-3">
                                <label for="edit_display_order" class="form-label">ترتيب العرض</label>
                                <input type="number" class="form-control" id="edit_display_order" name="display_order">
                            </div>
                            
                            <div class="col-md-3 mb-3">
                                <label for="edit_status" class="form-label">الحالة</label>
                                <select class="form-select" id="edit_status" name="status">
                                    <option value="active">نشط</option>
                                    <option value="inactive">غير نشط</option>
                                </select>
                            </div>
                            
                            <div class="col-12 mb-3">
                                <label for="edit_content" class="form-label">المحتوى</label>
                                <textarea class="form-control" id="edit_content" name="content" rows="6"></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // تحديد نمط
        function selectStyle(name, value) {
            document.querySelectorAll(`input[name="${name}"]`).forEach(input => {
                input.checked = input.value === value;
                input.closest('.style-option').classList.toggle('selected', input.checked);
            });
        }
        
        // تعديل المحتوى
        function editContent(content) {
            document.getElementById('edit_content_id').value = content.id;
            document.getElementById('edit_section').value = content.section;
            document.getElementById('edit_content_type').value = content.content_type;
            document.getElementById('edit_position').value = content.position;
            document.getElementById('edit_title').value = content.title || '';
            document.getElementById('edit_display_order').value = content.display_order;
            document.getElementById('edit_status').value = content.status;
            document.getElementById('edit_content').value = content.content || '';
            
            new bootstrap.Modal(document.getElementById('editContentModal')).show();
        }
        
        // حذف المحتوى
        function deleteContent(id) {
            if (confirm('هل أنت متأكد من حذف هذا المحتوى؟')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_header_footer_content">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // تحديث المعاينة عند تغيير الألوان
        document.querySelectorAll('input[type="color"]').forEach(input => {
            input.addEventListener('change', function() {
                updatePreview();
            });
        });
        
        function updatePreview() {
            const headerBg = document.querySelector('input[name="header_bg_color"]').value;
            const headerText = document.querySelector('input[name="header_text_color"]').value;
            const footerBg = document.querySelector('input[name="footer_bg_color"]').value;
            const footerText = document.querySelector('input[name="footer_text_color"]').value;
            
            document.querySelector('.preview-header').style.backgroundColor = headerBg;
            document.querySelector('.preview-header').style.color = headerText;
            document.querySelector('.preview-footer').style.backgroundColor = footerBg;
            document.querySelector('.preview-footer').style.color = footerText;
        }
    </script>
</body>
</html>
