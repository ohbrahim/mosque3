<?php
require_once '../config/config_final.php';

// التحقق من تسجيل الدخول وصلاحيات المستخدم
requireLogin();
if (!hasPermission('manage_polls')) {
    die('ليس لديك صلاحية لإدارة الاستطلاعات');
}

// حذف استطلاع
if (isset($_GET['delete']) && verifyCSRFToken($_GET['csrf_token'] ?? '')) {
    $pollId = (int)$_GET['delete'];
    
    try {
        // بدء المعاملة
        $db->beginTransaction();
        
        // حذف أصوات الاستطلاع
        $db->query("DELETE FROM poll_votes WHERE poll_id = ?", [$pollId]);
        
        // حذف خيارات الاستطلاع
        $db->query("DELETE FROM poll_options WHERE poll_id = ?", [$pollId]);
        
        // حذف الاستطلاع
        $db->query("DELETE FROM polls WHERE id = ?", [$pollId]);
        
        // تأكيد المعاملة
        $db->commit();
        
        $_SESSION['success'] = 'تم حذف الاستطلاع بنجاح.';
    } catch (Exception $e) {
        // التراجع عن المعاملة في حالة حدوث خطأ
        $db->rollBack();
        $_SESSION['error'] = 'حدث خطأ أثناء حذف الاستطلاع: ' . $e->getMessage();
    }
    
    header('Location: polls.php');
    exit;
}

// تغيير حالة الاستطلاع
if (isset($_GET['toggle_status']) && verifyCSRFToken($_GET['csrf_token'] ?? '')) {
    $pollId = (int)$_GET['toggle_status'];
    
    try {
        // جلب الاستطلاع
        $poll = $db->fetchOne("SELECT * FROM polls WHERE id = ?", [$pollId]);
        
        if ($poll) {
            // تبديل الحالة
            $newStatus = $poll['status'] === 'active' ? 'inactive' : 'active';
            
            // إذا كان سيتم تنشيط الاستطلاع، تعطيل جميع الاستطلاعات الأخرى
            if ($newStatus === 'active') {
                $db->query("UPDATE polls SET status = 'inactive' WHERE id != ?", [$pollId]);
            }
            
            // تحديث حالة الاستطلاع
            $db->query("UPDATE polls SET status = ? WHERE id = ?", [$newStatus, $pollId]);
            
            $_SESSION['success'] = 'تم تحديث حالة الاستطلاع بنجاح.';
        } else {
            $_SESSION['error'] = 'الاستطلاع غير موجود.';
        }
    } catch (Exception $e) {
        $_SESSION['error'] = 'حدث خطأ أثناء تحديث حالة الاستطلاع: ' . $e->getMessage();
    }
    
    header('Location: polls.php');
    exit;
}

// إضافة أو تحرير استطلاع
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_poll']) && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $pollId = isset($_POST['poll_id']) ? (int)$_POST['poll_id'] : null;
    $title = sanitize($_POST['title'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $status = sanitize($_POST['status'] ?? 'inactive');
    $startDate = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
    $endDate = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
    $options = $_POST['options'] ?? [];
    
    // التحقق من البيانات
    if (empty($title)) {
        $_SESSION['error'] = 'عنوان الاستطلاع مطلوب.';
        header('Location: polls.php');
        exit;
    }
    
    if (empty($options) || count(array_filter($options)) < 2) {
        $_SESSION['error'] = 'يجب إضافة خيارين على الأقل للاستطلاع.';
        header('Location: polls.php');
        exit;
    }
    
    try {
        // بدء المعاملة
        $db->beginTransaction();
        
        // إذا كان سيتم تنشيط الاستطلاع، تعطيل جميع الاستطلاعات الأخرى
        if ($status === 'active') {
            $db->query("UPDATE polls SET status = 'inactive' WHERE id != ?", [$pollId ?: 0]);
        }
        
        if ($pollId) {
            // تحديث استطلاع موجود
            $db->query("
                UPDATE polls 
                SET title = ?, description = ?, status = ?, start_date = ?, end_date = ?, updated_at = NOW() 
                WHERE id = ?
            ", [$title, $description, $status, $startDate, $endDate, $pollId]);
            
            // حذف الخيارات القديمة
            $db->query("DELETE FROM poll_options WHERE poll_id = ?", [$pollId]);
        } else {
            // إضافة استطلاع جديد
            $pollId = $db->insert('polls', [
                'title' => $title,
                'description' => $description,
                'status' => $status,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }
        
        // إضافة خيارات الاستطلاع
        $displayOrder = 1;
        foreach ($options as $option) {
            if (!empty($option)) {
                $db->insert('poll_options', [
                    'poll_id' => $pollId,
                    'option_text' => sanitize($option),
                    'display_order' => $displayOrder++
                ]);
            }
        }
        
        // تأكيد المعاملة
        $db->commit();
        
        $_SESSION['success'] = 'تم حفظ الاستطلاع بنجاح.';
    } catch (Exception $e) {
        // التراجع عن المعاملة في حالة حدوث خطأ
        $db->rollBack();
        $_SESSION['error'] = 'حدث خطأ أثناء حفظ الاستطلاع: ' . $e->getMessage();
    }
    
    header('Location: polls.php');
    exit;
}

// جلب الاستطلاع للتحرير
$editPoll = null;
$pollOptions = [];

if (isset($_GET['edit'])) {
    $pollId = (int)$_GET['edit'];
    
    try {
        $editPoll = $db->fetchOne("SELECT * FROM polls WHERE id = ?", [$pollId]);
        
        if ($editPoll) {
            $pollOptions = $db->fetchAll("SELECT * FROM poll_options WHERE poll_id = ? ORDER BY display_order", [$pollId]);
        }
    } catch (Exception $e) {
        $_SESSION['error'] = 'حدث خطأ أثناء جلب بيانات الاستطلاع: ' . $e->getMessage();
    }
}

// جلب جميع الاستطلاعات
try {
    $polls = $db->fetchAll("
        SELECT p.*, 
               COUNT(DISTINCT po.id) AS options_count, 
               COUNT(DISTINCT pv.id) AS votes_count 
        FROM polls p 
        LEFT JOIN poll_options po ON p.id = po.poll_id 
        LEFT JOIN poll_votes pv ON p.id = pv.poll_id 
        GROUP BY p.id 
        ORDER BY p.created_at DESC
    ");
} catch (Exception $e) {
    $_SESSION['error'] = 'حدث خطأ أثناء جلب الاستطلاعات: ' . $e->getMessage();
    $polls = [];
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الاستطلاعات - لوحة التحكم</title>
    
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
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="h3 mb-0">إدارة الاستطلاعات</h2>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#pollModal">
                <i class="fas fa-plus me-1"></i> إضافة استطلاع جديد
            </button>
        </div>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-body">
                <?php if (empty($polls)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-poll fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">لا توجد استطلاعات بعد</h4>
                        <p class="text-muted">انقر على زر "إضافة استطلاع جديد" لإنشاء أول استطلاع.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>العنوان</th>
                                    <th>الحالة</th>
                                    <th>الخيارات</th>
                                    <th>الأصوات</th>
                                    <th>تاريخ الإنشاء</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($polls as $poll): ?>
                                    <tr>
                                        <td><?php echo $poll['id']; ?></td>
                                        <td><?php echo htmlspecialchars($poll['title']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $poll['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                <?php echo $poll['status'] === 'active' ? 'نشط' : 'غير نشط'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo $poll['options_count']; ?></td>
                                        <td><?php echo $poll['votes_count']; ?></td>
                                        <td><?php echo formatArabicDate($poll['created_at']); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="?edit=<?php echo $poll['id']; ?>" class="btn btn-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="?toggle_status=<?php echo $poll['id']; ?>&csrf_token=<?php echo generateCSRFToken(); ?>" 
                                                   class="btn btn-<?php echo $poll['status'] === 'active' ? 'warning' : 'success'; ?>"
                                                   title="<?php echo $poll['status'] === 'active' ? 'تعطيل' : 'تنشيط'; ?>">
                                                    <i class="fas fa-<?php echo $poll['status'] === 'active' ? 'pause' : 'play'; ?>"></i>
                                                </a>
                                                <a href="?delete=<?php echo $poll['id']; ?>&csrf_token=<?php echo generateCSRFToken(); ?>" 
                                                   class="btn btn-danger" 
                                                   onclick="return confirm('هل أنت متأكد من حذف هذا الاستطلاع؟');"
                                                   title="حذف">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
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

    <!-- Modal for Add/Edit Poll -->
    <div class="modal fade" id="pollModal" tabindex="-1" aria-labelledby="pollModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="polls.php">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <?php if ($editPoll): ?>
                        <input type="hidden" name="poll_id" value="<?php echo $editPoll['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="modal-header">
                        <h5 class="modal-title" id="pollModalLabel">
                            <?php echo $editPoll ? 'تحرير استطلاع' : 'إضافة استطلاع جديد'; ?>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="title" class="form-label">عنوان الاستطلاع <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" 
                                   value="<?php echo $editPoll ? htmlspecialchars($editPoll['title']) : ''; ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">وصف الاستطلاع</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?php echo $editPoll ? htmlspecialchars($editPoll['description']) : ''; ?></textarea>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="status" class="form-label">الحالة</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="active" <?php echo $editPoll && $editPoll['status'] === 'active' ? 'selected' : ''; ?>>نشط</option>
                                    <option value="inactive" <?php echo $editPoll && $editPoll['status'] === 'inactive' ? 'selected' : ''; ?>>غير نشط</option>
                                </select>
                            </div>
                            
                            <div class="col-md-4">
                                <label for="start_date" class="form-label">تاريخ البدء</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" 
                                       value="<?php echo $editPoll && $editPoll['start_date'] ? date('Y-m-d', strtotime($editPoll['start_date'])) : ''; ?>">
                            </div>
                            
                            <div class="col-md-4">
                                <label for="end_date" class="form-label">تاريخ الانتهاء</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" 
                                       value="<?php echo $editPoll && $editPoll['end_date'] ? date('Y-m-d', strtotime($editPoll['end_date'])) : ''; ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">خيارات الاستطلاع <span class="text-danger">*</span></label>
                            <div id="options-container">
                                <?php if ($editPoll && !empty($pollOptions)): ?>
                                    <?php foreach ($pollOptions as $index => $option): ?>
                                        <div class="input-group mb-2">
                                            <input type="text" class="form-control" name="options[]" 
                                                   value="<?php echo htmlspecialchars($option['option_text']); ?>" required>
                                            <?php if ($index > 1): ?>
                                                <button type="button" class="btn btn-danger remove-option">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="input-group mb-2">
                                        <input type="text" class="form-control" name="options[]" placeholder="الخيار 1" required>
                                    </div>
                                    <div class="input-group mb-2">
                                        <input type="text" class="form-control" name="options[]" placeholder="الخيار 2" required>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <button type="button" class="btn btn-sm btn-secondary" id="add-option">
                                <i class="fas fa-plus me-1"></i> إضافة خيار
                            </button>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" name="save_poll" class="btn btn-primary">حفظ</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // فتح النافذة المنبثقة للتحرير
            <?php if ($editPoll): ?>
                var pollModal = new bootstrap.Modal(document.getElementById('pollModal'));
                pollModal.show();
            <?php endif; ?>
            
            // إضافة خيار جديد
            document.getElementById('add-option').addEventListener('click', function() {
                var container = document.getElementById('options-container');
                var optionCount = container.children.length + 1;
                
                var div = document.createElement('div');
                div.className = 'input-group mb-2';
                
                var input = document.createElement('input');
                input.type = 'text';
                input.className = 'form-control';
                input.name = 'options[]';
                input.placeholder = 'الخيار ' + optionCount;
                input.required = true;
                
                var button = document.createElement('button');
                button.type = 'button';
                button.className = 'btn btn-danger remove-option';
                button.innerHTML = '<i class="fas fa-times"></i>';
                
                div.appendChild(input);
                div.appendChild(button);
                container.appendChild(div);
            });
            
            // حذف خيار
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('remove-option') || e.target.closest('.remove-option')) {
                    var button = e.target.classList.contains('remove-option') ? e.target : e.target.closest('.remove-option');
                    var container = document.getElementById('options-container');
                    
                    if (container.children.length > 2) {
                        button.closest('.input-group').remove();
                    } else {
                        alert('يجب أن يحتوي الاستطلاع على خيارين على الأقل.');
                    }
                }
            });
        });
    </script>
</body>
</html>
