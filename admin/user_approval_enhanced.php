<?php
require_once '../config/config.php';
requireLogin();

if (!hasPermission('approve_users')) {
    die('ليس لديك صلاحية لإدارة موافقات المستخدمين');
}

// معالجة الموافقة/الرفض
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = (int)$_POST['user_id'];
    $action = $_POST['action'];
    
    if ($action === 'approve') {
        $db->update('users', ['status' => 'active'], 'id = ?', [$userId]);
        
        // إرسال بريد إشعار بالموافقة
        $user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);
        if ($user) {
            sendApprovalEmail($user['email'], $user['full_name']);
        }
        
        $_SESSION['success'] = 'تم قبول المستخدم وتفعيل حسابه';
    } elseif ($action === 'reject') {
        $reason = sanitize($_POST['reason'] ?? 'لم يتم تحديد السبب');
        
        // إرسال بريد إشعار بالرفض
        $user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);
        if ($user) {
            sendRejectionEmail($user['email'], $user['full_name'], $reason);
        }
        
        $db->delete('users', 'id = ?', [$userId]);
        $_SESSION['success'] = 'تم رفض المستخدم وحذف حسابه';
    }
    
    header('Location: user_approval.php');
    exit;
}

// جلب المستخدمين المعلقين
$pendingUsers = $db->fetchAll("
    SELECT id, username, email, full_name, phone, created_at 
    FROM users 
    WHERE status = 'pending' 
    ORDER BY created_at DESC
");
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>موافقة المستخدمين الجدد</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4><i class="fas fa-user-check"></i> موافقة المستخدمين الجدد</h4>
                    </div>
                    <div class="card-body">
                        <?php if (empty($pendingUsers)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                لا توجد طلبات تسجيل معلقة حالياً
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>الاسم الكامل</th>
                                            <th>اسم المستخدم</th>
                                            <th>البريد الإلكتروني</th>
                                            <th>الهاتف</th>
                                            <th>تاريخ التسجيل</th>
                                            <th>الإجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pendingUsers as $user): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                <td><?php echo htmlspecialchars($user['phone'] ?: 'غير محدد'); ?></td>
                                                <td><?php echo date('Y-m-d H:i', strtotime($user['created_at'])); ?></td>
                                                <td>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <input type="hidden" name="action" value="approve">
                                                        <button type="submit" class="btn btn-success btn-sm">
                                                            <i class="fas fa-check"></i> قبول
                                                        </button>
                                                    </form>
                                                    
                                                    <button type="button" class="btn btn-danger btn-sm" 
                                                            onclick="showRejectModal(<?php echo $user['id']; ?>)">
                                                        <i class="fas fa-times"></i> رفض
                                                    </button>
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
        </div>
    </div>
    
    <!-- Modal للرفض -->
    <div class="modal fade" id="rejectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">رفض طلب التسجيل</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="user_id" id="rejectUserId">
                        <input type="hidden" name="action" value="reject">
                        
                        <div class="mb-3">
                            <label for="reason" class="form-label">سبب الرفض:</label>
                            <textarea class="form-control" name="reason" id="reason" rows="3" 
                                    placeholder="اختياري - سيتم إرسال السبب للمستخدم"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-danger">رفض الطلب</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showRejectModal(userId) {
            document.getElementById('rejectUserId').value = userId;
            new bootstrap.Modal(document.getElementById('rejectModal')).show();
        }
    </script>
</body>
</html>