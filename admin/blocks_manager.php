<?php
/**
 * واجهة إدارة البلوكات المحسنة
 * توفر إدارة شاملة للبلوكات مع إمكانيات متقدمة
 */

require_once '../config.php';
require_once '../includes/auth/auth.php';
require_once '../includes/cache_system.php';
require_once '../includes/error_handler.php';

// التحقق من صلاحيات الإدارة
$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->hasRole('admin')) {
    header('Location: login.php');
    exit;
}

// معالجة العمليات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'add':
                addBlock($_POST);
                break;
            case 'edit':
                editBlock($_POST);
                break;
            case 'delete':
                deleteBlock($_POST['id']);
                break;
            case 'toggle_status':
                toggleBlockStatus($_POST['id']);
                break;
            case 'reorder':
                reorderBlocks($_POST['blocks']);
                break;
            case 'duplicate':
                duplicateBlock($_POST['id']);
                break;
        }
        
        // مسح الكاش بعد أي تغيير
        clearCache();
        
        $success = true;
        $message = 'تم تنفيذ العملية بنجاح';
        
    } catch (Exception $e) {
        log_error('خطأ في إدارة البلوكات: ' . $e->getMessage());
        $error = $e->getMessage();
    }
}

// الحصول على البلوكات
$blocks = getBlocks();
$categories = getBlockCategories();
$positions = getBlockPositions();

/**
 * إضافة بلوك جديد
 */
function addBlock($data) {
    global $pdo;
    
    // التحقق من صحة البيانات
    $title = sanitize($data['title']);
    $content = $data['content']; // لا نقوم بتنظيف المحتوى لأنه قد يحتوي على HTML
    $position = sanitize($data['position']);
    $category = sanitize($data['category']);
    $order_num = (int)($data['order_num'] ?? 0);
    $is_active = isset($data['is_active']) ? 1 : 0;
    $show_title = isset($data['show_title']) ? 1 : 0;
    $css_class = sanitize($data['css_class'] ?? '');
    $permissions = $data['permissions'] ?? 'public';
    $schedule_start = $data['schedule_start'] ?: null;
    $schedule_end = $data['schedule_end'] ?: null;
    
    if (empty($title) || empty($content)) {
        throw new Exception('العنوان والمحتوى مطلوبان');
    }
    
    $sql = "INSERT INTO blocks (title, content, position, category, order_num, is_active, 
                              show_title, css_class, permissions, schedule_start, schedule_end, 
                              created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $title, $content, $position, $category, $order_num, $is_active,
        $show_title, $css_class, $permissions, $schedule_start, $schedule_end
    ]);
    
    log_info('تم إضافة بلوك جديد: ' . $title);
}

/**
 * تعديل بلوك
 */
function editBlock($data) {
    global $pdo;
    
    $id = (int)$data['id'];
    $title = sanitize($data['title']);
    $content = $data['content'];
    $position = sanitize($data['position']);
    $category = sanitize($data['category']);
    $order_num = (int)($data['order_num'] ?? 0);
    $is_active = isset($data['is_active']) ? 1 : 0;
    $show_title = isset($data['show_title']) ? 1 : 0;
    $css_class = sanitize($data['css_class'] ?? '');
    $permissions = $data['permissions'] ?? 'public';
    $schedule_start = $data['schedule_start'] ?: null;
    $schedule_end = $data['schedule_end'] ?: null;
    
    if (empty($title) || empty($content)) {
        throw new Exception('العنوان والمحتوى مطلوبان');
    }
    
    $sql = "UPDATE blocks SET title = ?, content = ?, position = ?, category = ?, 
                             order_num = ?, is_active = ?, show_title = ?, css_class = ?, 
                             permissions = ?, schedule_start = ?, schedule_end = ?, 
                             updated_at = NOW() 
            WHERE id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $title, $content, $position, $category, $order_num, $is_active,
        $show_title, $css_class, $permissions, $schedule_start, $schedule_end, $id
    ]);
    
    log_info('تم تعديل البلوك: ' . $title);
}

/**
 * حذف بلوك
 */
function deleteBlock($id) {
    global $pdo;
    
    $id = (int)$id;
    
    // الحصول على معلومات البلوك قبل الحذف
    $stmt = $pdo->prepare("SELECT title FROM blocks WHERE id = ?");
    $stmt->execute([$id]);
    $block = $stmt->fetch();
    
    if (!$block) {
        throw new Exception('البلوك غير موجود');
    }
    
    $stmt = $pdo->prepare("DELETE FROM blocks WHERE id = ?");
    $stmt->execute([$id]);
    
    log_info('تم حذف البلوك: ' . $block['title']);
}

/**
 * تبديل حالة البلوك
 */
function toggleBlockStatus($id) {
    global $pdo;
    
    $id = (int)$id;
    
    $stmt = $pdo->prepare("UPDATE blocks SET is_active = NOT is_active WHERE id = ?");
    $stmt->execute([$id]);
    
    log_info('تم تبديل حالة البلوك رقم: ' . $id);
}

/**
 * إعادة ترتيب البلوكات
 */
function reorderBlocks($blocks) {
    global $pdo;
    
    $pdo->beginTransaction();
    
    try {
        foreach ($blocks as $order => $id) {
            $stmt = $pdo->prepare("UPDATE blocks SET order_num = ? WHERE id = ?");
            $stmt->execute([$order + 1, (int)$id]);
        }
        
        $pdo->commit();
        log_info('تم إعادة ترتيب البلوكات');
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * نسخ بلوك
 */
function duplicateBlock($id) {
    global $pdo;
    
    $id = (int)$id;
    
    // الحصول على بيانات البلوك الأصلي
    $stmt = $pdo->prepare("SELECT * FROM blocks WHERE id = ?");
    $stmt->execute([$id]);
    $block = $stmt->fetch();
    
    if (!$block) {
        throw new Exception('البلوك غير موجود');
    }
    
    // إنشاء نسخة جديدة
    $sql = "INSERT INTO blocks (title, content, position, category, order_num, is_active, 
                              show_title, css_class, permissions, schedule_start, schedule_end, 
                              created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, 0, ?, ?, ?, ?, ?, NOW(), NOW())";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $block['title'] . ' (نسخة)',
        $block['content'],
        $block['position'],
        $block['category'],
        $block['order_num'] + 1,
        $block['show_title'],
        $block['css_class'],
        $block['permissions'],
        $block['schedule_start'],
        $block['schedule_end']
    ]);
    
    log_info('تم نسخ البلوك: ' . $block['title']);
}

/**
 * الحصول على جميع البلوكات
 */
function getBlocks() {
    global $pdo;
    
    $sql = "SELECT b.*, 
                   (SELECT COUNT(*) FROM block_views bv WHERE bv.block_id = b.id AND DATE(bv.viewed_at) = CURDATE()) as today_views,
                   (SELECT COUNT(*) FROM block_views bv WHERE bv.block_id = b.id) as total_views
            FROM blocks b 
            ORDER BY b.position, b.order_num, b.title";
    
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}

/**
 * الحصول على فئات البلوكات
 */
function getBlockCategories() {
    global $pdo;
    
    $stmt = $pdo->query("SELECT DISTINCT category FROM blocks WHERE category IS NOT NULL AND category != '' ORDER BY category");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * الحصول على مواضع البلوكات
 */
function getBlockPositions() {
    return [
        'header' => 'الرأس',
        'sidebar' => 'الشريط الجانبي',
        'footer' => 'التذييل',
        'content_top' => 'أعلى المحتوى',
        'content_bottom' => 'أسفل المحتوى',
        'home_top' => 'أعلى الصفحة الرئيسية',
        'home_bottom' => 'أسفل الصفحة الرئيسية'
    ];
}

/**
 * مسح الكاش
 */
function clearCache() {
    if (function_exists('cache_clear')) {
        cache_clear();
    }
}

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة البلوكات</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-bs5.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .block-card { transition: all 0.3s ease; }
        .block-card:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .drag-handle { cursor: move; }
        .block-preview { max-height: 100px; overflow: hidden; }
        .status-badge { font-size: 0.8em; }
        .stats-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1><i class="fas fa-cubes me-2"></i>إدارة البلوكات</h1>
                    <div>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBlockModal">
                            <i class="fas fa-plus me-2"></i>إضافة بلوك جديد
                        </button>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>العودة للوحة التحكم
                        </a>
                    </div>
                </div>

                <?php if (isset($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?= $message ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- إحصائيات سريعة -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card stats-card">
                            <div class="card-body text-center">
                                <h3><?= count($blocks) ?></h3>
                                <p class="mb-0">إجمالي البلوكات</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stats-card">
                            <div class="card-body text-center">
                                <h3><?= count(array_filter($blocks, fn($b) => $b['is_active'])) ?></h3>
                                <p class="mb-0">البلوكات النشطة</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stats-card">
                            <div class="card-body text-center">
                                <h3><?= count($categories) ?></h3>
                                <p class="mb-0">الفئات</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stats-card">
                            <div class="card-body text-center">
                                <h3><?= array_sum(array_column($blocks, 'today_views')) ?></h3>
                                <p class="mb-0">مشاهدات اليوم</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- فلاتر -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <select class="form-select" id="filterPosition">
                                    <option value="">جميع المواضع</option>
                                    <?php foreach ($positions as $key => $value): ?>
                                        <option value="<?= $key ?>"><?= $value ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" id="filterCategory">
                                    <option value="">جميع الفئات</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= htmlspecialchars($category) ?>"><?= htmlspecialchars($category) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" id="filterStatus">
                                    <option value="">جميع الحالات</option>
                                    <option value="1">نشط</option>
                                    <option value="0">غير نشط</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="text" class="form-control" id="searchBlocks" placeholder="البحث في البلوكات...">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- قائمة البلوكات -->
                <div class="row" id="blocksContainer">
                    <?php foreach ($blocks as $block): ?>
                        <div class="col-md-6 col-lg-4 mb-4 block-item" 
                             data-position="<?= htmlspecialchars($block['position']) ?>"
                             data-category="<?= htmlspecialchars($block['category']) ?>"
                             data-status="<?= $block['is_active'] ?>"
                             data-title="<?= htmlspecialchars($block['title']) ?>">
                            <div class="card block-card h-100">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="fas fa-grip-vertical drag-handle text-muted me-2"></i>
                                        <strong><?= htmlspecialchars($block['title']) ?></strong>
                                    </div>
                                    <div>
                                        <?php if ($block['is_active']): ?>
                                            <span class="badge bg-success status-badge">نشط</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary status-badge">غير نشط</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="block-preview mb-3">
                                        <?= substr(strip_tags($block['content']), 0, 100) ?>...
                                    </div>
                                    <div class="row text-center">
                                        <div class="col-6">
                                            <small class="text-muted">الموضع</small><br>
                                            <span class="badge bg-info"><?= $positions[$block['position']] ?? $block['position'] ?></span>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted">المشاهدات</small><br>
                                            <span class="badge bg-primary"><?= $block['today_views'] ?> اليوم</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <div class="btn-group w-100" role="group">
                                        <button class="btn btn-sm btn-outline-primary" onclick="editBlock(<?= $block['id'] ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-success" onclick="toggleStatus(<?= $block['id'] ?>)">
                                            <i class="fas fa-power-off"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-info" onclick="duplicateBlock(<?= $block['id'] ?>)">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteBlock(<?= $block['id'] ?>, '<?= htmlspecialchars($block['title']) ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- نافذة إضافة/تعديل بلوك -->
    <div class="modal fade" id="addBlockModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" id="blockForm">
                    <div class="modal-header">
                        <h5 class="modal-title">إضافة بلوك جديد</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="id" id="blockId">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">العنوان *</label>
                                    <input type="text" class="form-control" name="title" id="blockTitle" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">الموضع *</label>
                                    <select class="form-select" name="position" id="blockPosition" required>
                                        <?php foreach ($positions as $key => $value): ?>
                                            <option value="<?= $key ?>"><?= $value ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">الفئة</label>
                                    <input type="text" class="form-control" name="category" id="blockCategory" list="categoriesList">
                                    <datalist id="categoriesList">
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?= htmlspecialchars($category) ?>">
                                        <?php endforeach; ?>
                                    </datalist>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">ترتيب العرض</label>
                                    <input type="number" class="form-control" name="order_num" id="blockOrder" value="0">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">المحتوى *</label>
                            <textarea class="form-control" name="content" id="blockContent" rows="10" required></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">فئة CSS</label>
                                    <input type="text" class="form-control" name="css_class" id="blockCssClass">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">الصلاحيات</label>
                                    <select class="form-select" name="permissions" id="blockPermissions">
                                        <option value="public">عام</option>
                                        <option value="members">الأعضاء فقط</option>
                                        <option value="admin">المديرين فقط</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">تاريخ البداية</label>
                                    <input type="datetime-local" class="form-control" name="schedule_start" id="blockScheduleStart">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">تاريخ النهاية</label>
                                    <input type="datetime-local" class="form-control" name="schedule_end" id="blockScheduleEnd">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="blockIsActive" checked>
                                    <label class="form-check-label">نشط</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="show_title" id="blockShowTitle" checked>
                                    <label class="form-check-label">إظهار العنوان</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-primary">حفظ</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-bs5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // تفعيل محرر النصوص
            $('#blockContent').summernote({
                height: 200,
                lang: 'ar-AR',
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'underline', 'clear']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['table', ['table']],
                    ['insert', ['link', 'picture', 'video']],
                    ['view', ['fullscreen', 'codeview', 'help']]
                ]
            });
            
            // تفعيل الفلاتر
            $('#filterPosition, #filterCategory, #filterStatus').on('change', filterBlocks);
            $('#searchBlocks').on('keyup', filterBlocks);
            
            // تفعيل السحب والإفلات
            new Sortable(document.getElementById('blocksContainer'), {
                animation: 150,
                handle: '.drag-handle',
                onEnd: function(evt) {
                    updateBlockOrder();
                }
            });
        });
        
        function filterBlocks() {
            const position = $('#filterPosition').val();
            const category = $('#filterCategory').val();
            const status = $('#filterStatus').val();
            const search = $('#searchBlocks').val().toLowerCase();
            
            $('.block-item').each(function() {
                const $item = $(this);
                let show = true;
                
                if (position && $item.data('position') !== position) show = false;
                if (category && $item.data('category') !== category) show = false;
                if (status !== '' && $item.data('status').toString() !== status) show = false;
                if (search && !$item.data('title').toLowerCase().includes(search)) show = false;
                
                $item.toggle(show);
            });
        }
        
        function editBlock(id) {
            // جلب بيانات البلوك وملء النموذج
            $.get('get_block.php', {id: id}, function(data) {
                const block = JSON.parse(data);
                
                $('#blockForm input[name="action"]').val('edit');
                $('#blockId').val(block.id);
                $('#blockTitle').val(block.title);
                $('#blockPosition').val(block.position);
                $('#blockCategory').val(block.category);
                $('#blockOrder').val(block.order_num);
                $('#blockContent').summernote('code', block.content);
                $('#blockCssClass').val(block.css_class);
                $('#blockPermissions').val(block.permissions);
                $('#blockScheduleStart').val(block.schedule_start);
                $('#blockScheduleEnd').val(block.schedule_end);
                $('#blockIsActive').prop('checked', block.is_active == 1);
                $('#blockShowTitle').prop('checked', block.show_title == 1);
                
                $('.modal-title').text('تعديل البلوك');
                $('#addBlockModal').modal('show');
            });
        }
        
        function toggleStatus(id) {
            $.post('', {action: 'toggle_status', id: id}, function() {
                location.reload();
            });
        }
        
        function duplicateBlock(id) {
            if (confirm('هل تريد نسخ هذا البلوك؟')) {
                $.post('', {action: 'duplicate', id: id}, function() {
                    location.reload();
                });
            }
        }
        
        function deleteBlock(id, title) {
            if (confirm('هل تريد حذف البلوك "' + title + '"؟\nهذا الإجراء لا يمكن التراجع عنه.')) {
                $.post('', {action: 'delete', id: id}, function() {
                    location.reload();
                });
            }
        }
        
        function updateBlockOrder() {
            const blocks = [];
            $('#blocksContainer .block-item').each(function(index) {
                const id = $(this).find('.btn-outline-primary').attr('onclick').match(/\d+/)[0];
                blocks.push(id);
            });
            
            $.post('', {action: 'reorder', blocks: blocks});
        }
        
        // إعادة تعيين النموذج عند إغلاق النافذة
        $('#addBlockModal').on('hidden.bs.modal', function() {
            $('#blockForm')[0].reset();
            $('#blockForm input[name="action"]').val('add');
            $('#blockId').val('');
            $('#blockContent').summernote('code', '');
            $('.modal-title').text('إضافة بلوك جديد');
        });
    </script>
</body>
</html>