<?php
/**
 * إدارة القوائم والتنقل
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
        case 'add_item':
            $data = [
                'title' => sanitize($_POST['title']),
                'url' => sanitize($_POST['url']),
                'target' => $_POST['target'] ?? '_self',
                'icon' => sanitize($_POST['icon']),
                'parent_id' => $_POST['parent_id'] ?: null,
                'menu_position' => $_POST['menu_position'] ?? 'header',
                'display_order' => (int)$_POST['display_order'],
                'status' => $_POST['status'] ?? 'active'
            ];
            
            if ($db->insert('menu_items', $data)) {
                $message = 'تم إضافة عنصر القائمة بنجاح';
            } else {
                $error = 'فشل في إضافة عنصر القائمة';
            }
            break;
            
        case 'edit_item':
            $id = (int)$_POST['id'];
            $data = [
                'title' => sanitize($_POST['title']),
                'url' => sanitize($_POST['url']),
                'target' => $_POST['target'] ?? '_self',
                'icon' => sanitize($_POST['icon']),
                'parent_id' => $_POST['parent_id'] ?: null,
                'menu_position' => $_POST['menu_position'] ?? 'header',
                'display_order' => (int)$_POST['display_order'],
                'status' => $_POST['status'] ?? 'active'
            ];
            
            if ($db->update('menu_items', $data, 'id = ?', [$id])) {
                $message = 'تم تحديث عنصر القائمة بنجاح';
            } else {
                $error = 'فشل في تحديث عنصر القائمة';
            }
            break;
            
        case 'delete_item':
            $id = (int)$_POST['id'];
            if ($db->delete('menu_items', 'id = ?', [$id])) {
                $message = 'تم حذف عنصر القائمة بنجاح';
            } else {
                $error = 'فشل في حذف عنصر القائمة';
            }
            break;
            
        case 'update_order':
            $items = json_decode($_POST['items'], true);
            foreach ($items as $item) {
                $db->update('menu_items', 
                    ['display_order' => $item['order']], 
                    'id = ?', 
                    [$item['id']]
                );
            }
            $message = 'تم تحديث ترتيب القائمة بنجاح';
            break;
    }
}

// جلب عناصر القائمة
$headerItems = $db->fetchAll("SELECT * FROM menu_items WHERE menu_position = 'header' ORDER BY display_order, id");
$footerItems = $db->fetchAll("SELECT * FROM menu_items WHERE menu_position = 'footer' ORDER BY display_order, id");
$sidebarItems = $db->fetchAll("SELECT * FROM menu_items WHERE menu_position = 'sidebar' ORDER BY display_order, id");

// جلب العناصر الرئيسية للقائمة المنسدلة
$parentItems = $db->fetchAll("SELECT id, title FROM menu_items WHERE parent_id IS NULL ORDER BY title");
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة القوائم - لوحة التحكم</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts - Arabic -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <!-- Sortable JS -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    
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
        
        .menu-item {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            cursor: move;
            transition: all 0.3s;
        }
        
        .menu-item:hover {
            background: #e9ecef;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .menu-item.child {
            margin-right: 30px;
            border-right: 3px solid #667eea;
        }
        
        .sortable-ghost {
            opacity: 0.4;
        }
        
        .sortable-chosen {
            background: #667eea !important;
            color: white;
        }
        
        .menu-tabs .nav-link {
            color: #667eea;
            border: 2px solid transparent;
        }
        
        .menu-tabs .nav-link.active {
            background: #667eea;
            color: white;
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
                            <a class="nav-link active" href="menu_manager.php">
                                <i class="fas fa-bars"></i>
                                إدارة القوائم
                            </a>
                            <a class="nav-link" href="header_footer.php">
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
                        <h2 class="mb-2">إدارة القوائم والتنقل</h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="index.php">لوحة التحكم</a></li>
                                <li class="breadcrumb-item active">إدارة القوائم</li>
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
                    
                    <!-- Add New Item -->
                    <div class="content-card">
                        <h4 class="mb-4">
                            <i class="fas fa-plus"></i>
                            إضافة عنصر جديد
                        </h4>
                        
                        <form method="POST">
                            <input type="hidden" name="action" value="add_item">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="title" class="form-label">عنوان العنصر</label>
                                    <input type="text" class="form-control" id="title" name="title" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="url" class="form-label">الرابط</label>
                                    <input type="text" class="form-control" id="url" name="url" required>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="menu_position" class="form-label">موقع القائمة</label>
                                    <select class="form-select" id="menu_position" name="menu_position">
                                        <option value="header">الهيدر</option>
                                        <option value="footer">الفوتر</option>
                                        <option value="sidebar">الشريط الجانبي</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="target" class="form-label">هدف الرابط</label>
                                    <select class="form-select" id="target" name="target">
                                        <option value="_self">نفس النافذة</option>
                                        <option value="_blank">نافذة جديدة</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="status" class="form-label">الحالة</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="active">نشط</option>
                                        <option value="inactive">غير نشط</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="icon" class="form-label">أيقونة (Font Awesome)</label>
                                    <input type="text" class="form-control" id="icon" name="icon" placeholder="fas fa-home">
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="parent_id" class="form-label">العنصر الأب (للقوائم المنسدلة)</label>
                                    <select class="form-select" id="parent_id" name="parent_id">
                                        <option value="">لا يوجد</option>
                                        <?php foreach ($parentItems as $parent): ?>
                                            <option value="<?php echo $parent['id']; ?>">
                                                <?php echo htmlspecialchars($parent['title']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="display_order" class="form-label">ترتيب العرض</label>
                                    <input type="number" class="form-control" id="display_order" name="display_order" value="0">
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus"></i>
                                إضافة العنصر
                            </button>
                        </form>
                    </div>
                    
                    <!-- Menu Items Management -->
                    <div class="content-card">
                        <h4 class="mb-4">
                            <i class="fas fa-list"></i>
                            إدارة عناصر القائمة
                        </h4>
                        
                        <!-- Menu Tabs -->
                        <ul class="nav nav-tabs menu-tabs mb-4" id="menuTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="header-tab" data-bs-toggle="tab" data-bs-target="#header" type="button" role="tab">
                                    <i class="fas fa-window-maximize"></i>
                                    قائمة الهيدر
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="footer-tab" data-bs-toggle="tab" data-bs-target="#footer" type="button" role="tab">
                                    <i class="fas fa-window-minimize"></i>
                                    قائمة الفوتر
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="sidebar-tab" data-bs-toggle="tab" data-bs-target="#sidebar" type="button" role="tab">
                                    <i class="fas fa-bars"></i>
                                    القائمة الجانبية
                                </button>
                            </li>
                        </ul>
                        
                        <!-- Tab Content -->
                        <div class="tab-content" id="menuTabsContent">
                            <!-- Header Menu -->
                            <div class="tab-pane fade show active" id="header" role="tabpanel">
                                <div id="header-menu" class="sortable-menu">
                                    <?php foreach ($headerItems as $item): ?>
                                        <div class="menu-item <?php echo $item['parent_id'] ? 'child' : ''; ?>" data-id="<?php echo $item['id']; ?>">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div class="flex-grow-1">
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-grip-vertical text-muted me-2"></i>
                                                        <?php if ($item['icon']): ?>
                                                            <i class="<?php echo htmlspecialchars($item['icon']); ?> me-2"></i>
                                                        <?php endif; ?>
                                                        <strong><?php echo htmlspecialchars($item['title']); ?></strong>
                                                        <span class="badge bg-<?php echo $item['status'] === 'active' ? 'success' : 'secondary'; ?> ms-2">
                                                            <?php echo $item['status'] === 'active' ? 'نشط' : 'غير نشط'; ?>
                                                        </span>
                                                    </div>
                                                    <small class="text-muted"><?php echo htmlspecialchars($item['url']); ?></small>
                                                </div>
                                                <div>
                                                    <button class="btn btn-sm btn-outline-primary me-1" onclick="editMenuItem(<?php echo htmlspecialchars(json_encode($item)); ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteMenuItem(<?php echo $item['id']; ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <!-- Footer Menu -->
                            <div class="tab-pane fade" id="footer" role="tabpanel">
                                <div id="footer-menu" class="sortable-menu">
                                    <?php foreach ($footerItems as $item): ?>
                                        <div class="menu-item" data-id="<?php echo $item['id']; ?>">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div class="flex-grow-1">
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-grip-vertical text-muted me-2"></i>
                                                        <strong><?php echo htmlspecialchars($item['title']); ?></strong>
                                                        <span class="badge bg-<?php echo $item['status'] === 'active' ? 'success' : 'secondary'; ?> ms-2">
                                                            <?php echo $item['status'] === 'active' ? 'نشط' : 'غير نشط'; ?>
                                                        </span>
                                                    </div>
                                                    <small class="text-muted"><?php echo htmlspecialchars($item['url']); ?></small>
                                                </div>
                                                <div>
                                                    <button class="btn btn-sm btn-outline-primary me-1" onclick="editMenuItem(<?php echo htmlspecialchars(json_encode($item)); ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteMenuItem(<?php echo $item['id']; ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <!-- Sidebar Menu -->
                            <div class="tab-pane fade" id="sidebar" role="tabpanel">
                                <div id="sidebar-menu" class="sortable-menu">
                                    <?php foreach ($sidebarItems as $item): ?>
                                        <div class="menu-item" data-id="<?php echo $item['id']; ?>">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div class="flex-grow-1">
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-grip-vertical text-muted me-2"></i>
                                                        <?php if ($item['icon']): ?>
                                                            <i class="<?php echo htmlspecialchars($item['icon']); ?> me-2"></i>
                                                        <?php endif; ?>
                                                        <strong><?php echo htmlspecialchars($item['title']); ?></strong>
                                                        <span class="badge bg-<?php echo $item['status'] === 'active' ? 'success' : 'secondary'; ?> ms-2">
                                                            <?php echo $item['status'] === 'active' ? 'نشط' : 'غير نشط'; ?>
                                                        </span>
                                                    </div>
                                                    <small class="text-muted"><?php echo htmlspecialchars($item['url']); ?></small>
                                                </div>
                                                <div>
                                                    <button class="btn btn-sm btn-outline-primary me-1" onclick="editMenuItem(<?php echo htmlspecialchars(json_encode($item)); ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteMenuItem(<?php echo $item['id']; ?>)">
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
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">تعديل عنصر القائمة</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editForm">
                    <input type="hidden" name="action" value="edit_item">
                    <input type="hidden" name="id" id="edit_id">
                    
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_title" class="form-label">عنوان العنصر</label>
                            <input type="text" class="form-control" id="edit_title" name="title" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_url" class="form-label">الرابط</label>
                            <input type="text" class="form-control" id="edit_url" name="url" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_menu_position" class="form-label">موقع القائمة</label>
                                <select class="form-select" id="edit_menu_position" name="menu_position">
                                    <option value="header">الهيدر</option>
                                    <option value="footer">الفوتر</option>
                                    <option value="sidebar">الشريط الجانبي</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="edit_target" class="form-label">هدف الرابط</label>
                                <select class="form-select" id="edit_target" name="target">
                                    <option value="_self">نفس النافذة</option>
                                    <option value="_blank">نافذة جديدة</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_icon" class="form-label">أيقونة</label>
                                <input type="text" class="form-control" id="edit_icon" name="icon">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="edit_status" class="form-label">الحالة</label>
                                <select class="form-select" id="edit_status" name="status">
                                    <option value="active">نشط</option>
                                    <option value="inactive">غير نشط</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_parent_id" class="form-label">العنصر الأب</label>
                                <select class="form-select" id="edit_parent_id" name="parent_id">
                                    <option value="">لا يوجد</option>
                                    <?php foreach ($parentItems as $parent): ?>
                                        <option value="<?php echo $parent['id']; ?>">
                                            <?php echo htmlspecialchars($parent['title']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="edit_display_order" class="form-label">ترتيب العرض</label>
                                <input type="number" class="form-control" id="edit_display_order" name="display_order">
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
        // تفعيل السحب والإفلات
        document.addEventListener('DOMContentLoaded', function() {
            const sortableMenus = document.querySelectorAll('.sortable-menu');
            
            sortableMenus.forEach(menu => {
                new Sortable(menu, {
                    animation: 150,
                    ghostClass: 'sortable-ghost',
                    chosenClass: 'sortable-chosen',
                    onEnd: function(evt) {
                        updateMenuOrder(menu);
                    }
                });
            });
        });
        
        // تحديث ترتيب القائمة
        function updateMenuOrder(menu) {
            const items = menu.querySelectorAll('.menu-item');
            const orderData = [];
            
            items.forEach((item, index) => {
                orderData.push({
                    id: item.dataset.id,
                    order: index + 1
                });
            });
            
            fetch('menu_manager.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=update_order&items=' + encodeURIComponent(JSON.stringify(orderData))
            })
            .then(response => response.text())
            .then(data => {
                // يمكن إضافة رسالة نجاح هنا
            });
        }
        
        // تعديل عنصر القائمة
        function editMenuItem(item) {
            document.getElementById('edit_id').value = item.id;
            document.getElementById('edit_title').value = item.title;
            document.getElementById('edit_url').value = item.url;
            document.getElementById('edit_menu_position').value = item.menu_position;
            document.getElementById('edit_target').value = item.target;
            document.getElementById('edit_icon').value = item.icon || '';
            document.getElementById('edit_status').value = item.status;
            document.getElementById('edit_parent_id').value = item.parent_id || '';
            document.getElementById('edit_display_order').value = item.display_order;
            
            new bootstrap.Modal(document.getElementById('editModal')).show();
        }
        
        // حذف عنصر القائمة
        function deleteMenuItem(id) {
            if (confirm('هل أنت متأكد من حذف هذا العنصر؟')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_item">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
