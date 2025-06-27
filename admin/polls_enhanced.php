<?php
require_once '../config/config.php';

// التحقق من الصلاحيات
if (!isLoggedIn() || !hasPermission('manage_polls')) {
    header('Location: ../login_enhanced.php');
    exit;
}

$error = '';
$success = '';

// معالجة العمليات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_poll'])) {
        $title = sanitize($_POST['title']);
        $description = sanitize($_POST['description']);
        $options = array_filter($_POST['options']); // إزالة الخيارات الفارغة
        $status = $_POST['status'] ?? 'inactive';
        $start_date = $_POST['start_date'] ?? null;
        $end_date = $_POST['end_date'] ?? null;
        
        if (empty($title)) {
            $error = 'عنوان الاستطلاع مطلوب';
        } elseif (count($options) < 2) {
            $error = 'يجب إضافة خيارين على الأقل';
        } else {
            try {
                $db->beginTransaction();
                
                // إذا كان الاستطلاع نشطاً، إلغاء تنشيط الاستطلاعات الأخرى
                if ($status === 'active') {
                    $db->query("UPDATE polls SET status = 'inactive'");
                }
                
                // إنشاء الاستطلاع
                $pollData = [
                    'title' => $title,
                    'description' => $description,
                    'status' => $status,
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                    'created_by' => $_SESSION['user_id'],
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                $pollId = $db->insert('polls', $pollData);
                
                if ($pollId) {
                    // إضافة الخيارات
                    foreach ($options as $index => $option) {
                        if (!empty(trim($option))) {
                            $db->insert('poll_options', [
                                'poll_id' => $pollId,
                                'option_text' => trim($option),
                                'display_order' => $index + 1
                            ]);
                        }
                    }
                    
                    $db->commit();
                    $success = 'تم إنشاء الاستطلاع بنجاح';
                } else {
                    $db->rollBack();
                    $error = 'حدث خطأ أثناء إنشاء الاستطلاع';
                }
            } catch (Exception $e) {
                $db->rollBack();
                $error = 'حدث خطأ: ' . $e->getMessage();
            }
        }
    }
    
    if (isset($_POST['toggle_status'])) {
        $pollId = (int)$_POST['poll_id'];
        $newStatus = $_POST['new_status'];
        
        try {
            if ($newStatus === 'active') {
                // إلغاء تنشيط الاستطلاعات الأخرى
                $db->query("UPDATE polls SET status = 'inactive' WHERE id != ?", [$pollId]);
            }
            
            $db->update('polls', ['status' => $newStatus], 'id = ?', [$pollId]);
            $success = 'تم تحديث حالة الاستطلاع';
        } catch (Exception $e) {
            $error = 'حدث خطأ أثناء تحديث الحالة';
        }
    }
    
    if (isset($_POST['delete_poll']) && hasPermission('delete_polls')) {
        $pollId = (int)$_POST['poll_id'];
        
        try {
            $db->beginTransaction();
            
            // حذف الأصوات
            $db->query("DELETE FROM poll_votes WHERE poll_id = ?", [$pollId]);
            
            // حذف الخيارات
            $db->query("DELETE FROM poll_options WHERE poll_id = ?", [$pollId]);
            
            // حذف الاستطلاع
            $db->query("DELETE FROM polls WHERE id = ?", [$pollId]);
            
            $db->commit();
            $success = 'تم حذف الاستطلاع بنجاح';
        } catch (Exception $e) {
            $db->rollBack();
            $error = 'حدث خطأ أثناء حذف الاستطلاع';
        }
    }
}

// جلب الاستطلاعات
$polls = $db->fetchAll("
    SELECT p.*, u.full_name as creator_name,
           (SELECT COUNT(*) FROM poll_votes WHERE poll_id = p.id) as total_votes
    FROM polls p
    LEFT JOIN users u ON p.created_by = u.id
    ORDER BY p.created_at DESC
");

// جلب إحصائيات كل استطلاع
foreach ($polls as &$poll) {
    $poll['options'] = $db->fetchAll("
        SELECT po.*, 
               (SELECT COUNT(*) FROM poll_votes WHERE option_id = po.id) as votes_count
        FROM poll_options po
        WHERE po.poll_id = ?
        ORDER BY po.display_order
    ", [$poll['id']]);
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الاستطلاعات - إدارة المسجد</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .poll-card {
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .poll-status {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .status-active {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .option-input {
            margin-bottom: 10px;
        }
        
        .poll-stats {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 bg-dark text-white p-0">
                <?php include 'header.php'; ?>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10">
                <div class="container-fluid py-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2><i class="fas fa-poll me-2"></i>إدارة الاستطلاعات</h2>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createPollModal">
                            <i class="fas fa-plus me-2"></i>إنشاء استطلاع جديد
                        </button>
                    </div>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo $success; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- قائمة الاستطلاعات -->
                    <div class="row">
                        <?php foreach ($polls as $poll): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card poll-card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0"><?php echo htmlspecialchars($poll['title']); ?></h6>
                                    <span class="poll-status <?php echo $poll['status'] === 'active' ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo $poll['status'] === 'active' ? 'نشط' : 'غير نشط'; ?>
                                    </span>
                                </div>
                                
                                <div class="card-body">
                                    <?php if ($poll['description']): ?>
                                        <p class="text-muted small"><?php echo htmlspecialchars($poll['description']); ?></p>
                                    <?php endif; ?>
                                    
                                    <div class="poll-stats">
                                        <div class="row text-center">
                                            <div class="col-6">
                                                <div class="h5 mb-0 text-primary"><?php echo count($poll['options']); ?></div>
                                                <small class="text-muted">خيارات</small>
                                            </div>
                                            <div class="col-6">
                                                <div class="h5 mb-0 text-success"><?php echo $poll['total_votes']; ?></div>
                                                <small class="text-muted">أصوات</small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- خيارات الاستطلاع -->
                                    <div class="mt-3">
                                        <h6>الخيارات:</h6>
                                        <?php foreach ($poll['options'] as $option): ?>
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <small><?php echo htmlspecialchars($option['option_text']); ?></small>
                                                <span class="badge bg-secondary"><?php echo $option['votes_count']; ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                
                                <div class="card-footer">
                                    <div class="btn-group w-100" role="group">
                                        <!-- تبديل الحالة -->
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="poll_id" value="<?php echo $poll['id']; ?>">
                                            <input type="hidden" name="new_status" value="<?php echo $poll['status'] === 'active' ? 'inactive' : 'active'; ?>">
                                            <button type="submit" name="toggle_status" class="btn btn-sm <?php echo $poll['status'] === 'active' ? 'btn-warning' : 'btn-success'; ?>">
                                                <i class="fas <?php echo $poll['status'] === 'active' ? 'fa-pause' : 'fa-play'; ?>"></i>
                                                <?php echo $poll['status'] === 'active' ? 'إيقاف' : 'تنشيط'; ?>
                                            </button>
                                        </form>
                                        
                                        <!-- عرض النتائج -->
                                        <a href="poll_results.php?id=<?php echo $poll['id']; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-chart-bar"></i> النتائج
                                        </a>
                                        
                                        <!-- حذف (للأدمن فقط) -->
                                        <?php if (hasPermission('delete_polls')): ?>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا الاستطلاع؟')">
                                            <input type="hidden" name="poll_id" value="<?php echo $poll['id']; ?>">
                                            <button type="submit" name="delete_poll" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <?php if (empty($polls)): ?>
                        <div class="col-12">
                            <div class="text-center py-5">
                                <i class="fas fa-poll fa-3x text-muted mb-3"></i>
                                <h4 class="text-muted">لا توجد استطلاعات</h4>
                                <p class="text-muted">ابدأ بإنشاء أول استطلاع</p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- نموذج إنشاء استطلاع -->
    <div class="modal fade" id="createPollModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">إنشاء استطلاع جديد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="title" class="form-label">عنوان الاستطلاع *</label>
                            <input type="text" class="form-control" name="title" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">وصف الاستطلاع</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <label for="start_date" class="form-label">تاريخ البداية</label>
                                <input type="date" class="form-control" name="start_date">
                            </div>
                            <div class="col-md-6">
                                <label for="end_date" class="form-label">تاريخ النهاية</label>
                                <input type="date" class="form-control" name="end_date">
                            </div>
                        </div>
                        
                        <div class="mb-3 mt-3">
                            <label class="form-label">خيارات الاستطلاع *</label>
                            <div id="pollOptions">
                                <div class="option-input">
                                    <input type="text" class="form-control" name="options[]" placeholder="الخيار