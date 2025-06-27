<?php
require_once '../config/config.php';
require_once '../includes/functions/all_functions.php';

// التحقق من تسجيل الدخول والصلاحيات
requireLogin();
requirePermission('manage_blocks');

// معالجة الإجراءات
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$message = '';
$error = '';

// جلب أنواع البلوكات
$blockTypes = [
    'custom' => 'محتوى مخصص',
    'html' => 'HTML مخصص',
    'text' => 'نص عادي',
    'announcement' => 'إعلان',
    'prayer_times' => 'أوقات الصلاة',
    'weather' => 'الطقس',
    'iframe' => 'إطار خارجي (iframe)',
    'marquee' => 'نص متحرك',
    'recent_pages' => 'صفحات حديثة',
    'visitor_stats' => 'إحصائيات الزوار',
    'quran_verse' => 'آية قرآنية',
    'hadith' => 'حديث نبوي',
    'social_links' => 'روابط التواصل',
    'quick_links' => 'روابط سريعة'
];

// مواقع البلوكات
$blockPositions = [
    'top' => 'أعلى الصفحة',
    'bottom' => 'أسفل الصفحة',
    'left' => 'يسار الصفحة',
    'right' => 'يمين الصفحة',
    'center' => 'وسط الصفحة',
    'header' => 'الهيدر',
    'footer' => 'الفوتر'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'رمز الأمان غير صحيح';
    } else {
        if ($action === 'add' || $action === 'edit') {
            $data = [
                'title' => sanitize($_POST['title']),
                'content' => $_POST['content'],
                'block_type' => sanitize($_POST['block_type']),
                'position' => sanitize($_POST['position']),
                'status' => sanitize($_POST['status']),
                'show_title' => isset($_POST['show_title']) ? 1 : 0,
                'css_class' => sanitize($_POST['css_class']),
                'custom_css' => $_POST['custom_css'],
                'display_order' => (int)$_POST['display_order'],
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            if ($action === 'add') {
                $data['created_at'] = date('Y-m-d H:i:s');
                $data['created_by'] = $_SESSION['user_id'];
                
                if ($db->insert('blocks', $data)) {
                    $message = 'تم إضافة البلوك بنجاح';
                    $action = 'list';
                } else {
                    $error = 'فشل في إضافة البلوك';
                }
            } else {
                $id = (int)$_POST['id'];
                $data['updated_by'] = $_SESSION['user_id'];
                
                if ($db->update('blocks', $data, 'id = ?', [$id])) {
                    $message = 'تم تحديث البلوك بنجاح';
                    $action = 'list';
                } else {
                    $error = 'فشل في تحديث البلوك';
                }
            }
        }
    }
}

// حذف بلوك
if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    if ($db->delete('blocks', 'id = ?', [$id])) {
        $message = 'تم حذف البلوك بنجاح';
    } else {
        $error = 'فشل في حذف البلوك';
    }
    $action = 'list';
}

// تغيير حالة البلوك
if ($action === 'toggle' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $block = $db->fetchOne("SELECT status FROM blocks WHERE id = ?", [$id]);
    
    if ($block) {
        $newStatus = $block['status'] === 'active' ? 'inactive' : 'active';
        if ($db->update('blocks', ['status' => $newStatus], 'id = ?', [$id])) {
            $message = 'تم تغيير حالة البلوك بنجاح';
        } else {
            $error = 'فشل في تغيير حالة البلوك';
        }
    } else {
        $error = 'البلوك غير موجود';
    }
    
    $action = 'list';
}

// جلب البيانات حسب الإجراء
if ($action === 'edit' && isset($_GET['id'])) {
    $blockData = $db->fetchOne("SELECT * FROM blocks WHERE id = ?", [(int)$_GET['id']]);
    if (!$blockData) {
        $error = 'البلوك غير موجود';
        $action = 'list';
    }
}

if ($action === 'list') {
    $blocks = $db->fetchAll("SELECT * FROM blocks ORDER BY position, display_order");
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة البلوكات - لوحة التحكم</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts - Arabic -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <!-- CodeMirror -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/theme/monokai.min.css">
    
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
        
        .CodeMirror {
            height: 300px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .block-preview {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            margin-top: 15px;
            background-color: #f8f9fa;
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
                            <a class="nav-link active" href="blocks.php">
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
                                <h2 class="mb-2">إدارة البلوكات</h2>
                                <nav aria-label="breadcrumb">
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item"><a href="index.php">لوحة التحكم</a></li>
                                        <li class="breadcrumb-item active">إدارة البلوكات</li>
                                    </ol>
                                </nav>
                            </div>
                            <?php if ($action === 'list'): ?>
                            <div class="col-auto">
                                <a href="?action=add" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> إضافة بلوك جديد
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
                            <!-- Blocks List -->
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>العنوان</th>
                                            <th>النوع</th>
                                            <th>الموقع</th>
                                            <th>الترتيب</th>
                                            <th>الحالة</th>
                                            <th>الإجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($blocks)): ?>
                                            <tr>
                                                <td colspan="6" class="text-center py-4">
                                                    <i class="fas fa-th-large fa-3x text-muted mb-3"></i>
                                                    <h5 class="text-muted">لا توجد بلوكات</h5>
                                                    <a href="?action=add" class="btn btn-primary">إضافة أول بلوك</a>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($blocks as $block): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($block['title']); ?></strong>
                                                    <?php if ($block['show_title']): ?>
                                                        <span class="badge bg-info ms-2">يظهر العنوان</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php echo $blockTypes[$block['block_type']] ?? $block['block_type']; ?>
                                                </td>
                                                <td>
                                                    <?php echo $blockPositions[$block['position']] ?? $block['position']; ?>
                                                </td>
                                                <td>
                                                    <?php echo $block['display_order']; ?>
                                                </td>
                                                <td>
                                                    <?php if ($block['status'] === 'active'): ?>
                                                        <span class="badge bg-success">نشط</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">غير نشط</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="?action=edit&id=<?php echo $block['id']; ?>" class="btn btn-outline-primary">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="?action=toggle&id=<?php echo $block['id']; ?>" class="btn btn-outline-info">
                                                            <i class="fas fa-toggle-<?php echo $block['status'] === 'active' ? 'on' : 'off'; ?>"></i>
                                                        </a>
                                                        <a href="?action=delete&id=<?php echo $block['id']; ?>" 
                                                           class="btn btn-outline-danger"
                                                           onclick="return confirm('هل أنت متأكد من حذف هذا البلوك؟')">
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
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                <?php if ($action === 'edit'): ?>
                                    <input type="hidden" name="id" value="<?php echo $blockData['id']; ?>">
                                <?php endif; ?>
                                
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="mb-3">
                                            <label for="title" class="form-label">عنوان البلوك *</label>
                                            <input type="text" class="form-control" id="title" name="title" 
                                                   value="<?php echo htmlspecialchars($blockData['title'] ?? ''); ?>" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="block_type" class="form-label">نوع البلوك *</label>
                                            <select class="form-select" id="block_type" name="block_type" onchange="toggleContentEditor()">
                                                <?php foreach ($blockTypes as $key => $label): ?>
                                                    <option value="<?php echo $key; ?>" <?php echo ($blockData['block_type'] ?? '') === $key ? 'selected' : ''; ?>>
                                                        <?php echo $label; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="content" class="form-label">محتوى البلوك</label>
                                            <textarea class="form-control" id="content" name="content" rows="10"><?php echo htmlspecialchars($blockData['content'] ?? ''); ?></textarea>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="custom_css" class="form-label">CSS مخصص</label>
                                            <textarea class="form-control" id="custom_css" name="custom_css" rows="5"><?php echo htmlspecialchars($blockData['custom_css'] ?? ''); ?></textarea>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">معاينة البلوك</label>
                                            <div class="block-preview" id="block-preview">
                                                <div class="text-center text-muted">
                                                    <i class="fas fa-eye fa-2x mb-2"></i>
                                                    <p>سيظهر هنا معاينة للبلوك</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <!-- Block Settings -->
                                        <div class="card mb-3">
                                            <div class="card-header">
                                                <h6 class="mb-0">إعدادات البلوك</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="mb-3">
                                                    <label for="position" class="form-label">موقع البلوك *</label>
                                                    <select class="form-select" id="position" name="position">
                                                        <?php foreach ($blockPositions as $key => $label): ?>
                                                            <option value="<?php echo $key; ?>" <?php echo ($blockData['position'] ?? '') === $key ? 'selected' : ''; ?>>
                                                                <?php echo $label; ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="status" class="form-label">حالة البلوك</label>
                                                    <select class="form-select" id="status" name="status">
                                                        <option value="active" <?php echo ($blockData['status'] ?? '') === 'active' ? 'selected' : ''; ?>>نشط</option>
                                                        <option value="inactive" <?php echo ($blockData['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>غير نشط</option>
                                                    </select>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="display_order" class="form-label">ترتيب العرض</label>
                                                    <input type="number" class="form-control" id="display_order" name="display_order" 
                                                           value="<?php echo $blockData['display_order'] ?? 0; ?>" min="0">
                                                </div>
                                                
                                                <div class="form-check mb-3">
                                                    <input class="form-check-input" type="checkbox" id="show_title" name="show_title" 
                                                           <?php echo ($blockData['show_title'] ?? 0) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="show_title">
                                                        عرض عنوان البلوك
                                                    </label>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="css_class" class="form-label">CSS Class</label>
                                                    <input type="text" class="form-control" id="css_class" name="css_class" 
                                                           value="<?php echo htmlspecialchars($blockData['css_class'] ?? ''); ?>">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <?php if ($action === 'edit'): ?>
                                        <div class="card mb-3">
                                            <div class="card-header">
                                                <h6 class="mb-0">معلومات إضافية</h6>
                                            </div>
                                            <div class="card-body">
                                                <p><strong>تاريخ الإنشاء:</strong><br><?php echo formatArabicDate($blockData['created_at']); ?></p>
                                                <p><strong>آخر تحديث:</strong><br><?php echo formatArabicDate($blockData['updated_at']); ?></p>
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
                                        <?php echo $action === 'add' ? 'إضافة البلوك' : 'تحديث البلوك'; ?>
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
    <!-- CodeMirror -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/htmlmixed/htmlmixed.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/xml/xml.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/javascript/javascript.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/css/css.min.js"></script>
    
    <script>
        let contentEditor;
        let cssEditor;
        
        document.addEventListener('DOMContentLoaded', function() {
            // تهيئة محرر المحتوى
            contentEditor = CodeMirror.fromTextArea(document.getElementById('content'), {
                mode: 'htmlmixed',
                theme: 'monokai',
                lineNumbers: true,
                lineWrapping: true,
                autoCloseTags: true,
                autoCloseBrackets: true,
                matchBrackets: true
            });
            
            // تهيئة محرر CSS
            cssEditor = CodeMirror.fromTextArea(document.getElementById('custom_css'), {
                mode: 'css',
                theme: 'monokai',
                lineNumbers: true,
                lineWrapping: true,
                autoCloseBrackets: true,
                matchBrackets: true
            });
            
            // تحديث المعاينة عند تغيير المحتوى
            contentEditor.on('change', updatePreview);
            cssEditor.on('change', updatePreview);
            
            // تحديث المعاينة عند تغيير العنوان أو إظهار العنوان
            document.getElementById('title').addEventListener('input', updatePreview);
            document.getElementById('show_title').addEventListener('change', updatePreview);
            
            // تحديث المعاينة الأولية
            updatePreview();
            
            // تغيير نوع المحرر حسب نوع البلوك
            toggleContentEditor();
        });
        
        function toggleContentEditor() {
            const blockType = document.getElementById('block_type').value;
            
            if (blockType === 'html' || blockType === 'custom' || blockType === 'iframe' || blockType === 'marquee') {
                contentEditor.setOption('mode', 'htmlmixed');
            } else if (blockType === 'text') {
                contentEditor.setOption('mode', 'text/plain');
            }
            
            updatePreview();
        }
        
        function updatePreview() {
            const blockType = document.getElementById('block_type').value;
            const title = document.getElementById('title').value;
            const showTitle = document.getElementById('show_title').checked;
            const content = contentEditor.getValue();
            const customCss = cssEditor.getValue();
            
            let previewHtml = '';
            
            // إضافة CSS مخصص
            if (customCss) {
                previewHtml += `<style>${customCss}</style>`;
            }
            
            // إضافة العنوان إذا كان مطلوباً
            if (showTitle && title) {
                previewHtml += `<div class="card mb-3">`;
                previewHtml += `<div class="card-header bg-primary text-white">`;
                previewHtml += `<h5 class="card-title mb-0">${title}</h5>`;
                previewHtml += `</div>`;
            }
            
            // إضافة المحتوى حسب نوع البلوك
            if (blockType === 'custom' || blockType === 'html' || blockType === 'iframe' || blockType === 'marquee') {
                previewHtml += content;
            } else if (blockType === 'text') {
                previewHtml += `<div class="card-body"><p>${content.replace(/\n/g, '<br>')}</p></div>`;
            } else if (blockType === 'announcement') {
                previewHtml += `<div class="alert alert-info">${content}</div>`;
            } else if (blockType === 'prayer_times') {
                previewHtml += `<div class="text-center"><i class="fas fa-mosque fa-2x text-primary mb-2"></i><p>أوقات الصلاة</p></div>`;
            } else if (blockType === 'weather') {
                previewHtml += `<div class="text-center"><i class="fas fa-cloud-sun fa-2x text-info mb-2"></i><p>حالة الطقس</p></div>`;
            } else {
                previewHtml += `<div class="text-center"><i class="fas fa-th-large fa-2x text-primary mb-2"></i><p>${title || 'بلوك'}</p></div>`;
            }
            
            // إغلاق العنوان إذا كان مطلوباً
            if (showTitle && title) {
                previewHtml += `</div>`;
            }
            
            document.getElementById('block-preview').innerHTML = previewHtml;
        }
    </script>
</body>
</html>
