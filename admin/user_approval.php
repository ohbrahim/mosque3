<?php
/**
 * صفحة موافقة المستخدمين الجدد
 */
require_once '../config/config.php';
require_once '../config/permissions.php';

requireLogin();
requirePermission('approve_users');

$message = '';
$error = '';

// معالجة الإجراءات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userId = (int)($_POST['user_id'] ?? 0);
    
    if ($action === 'approve' && $userId > 0) {
        $db->update('users', ['status' => 'active'], 'id = ?', [$userId]);
        
        // إرسال بريد إشعار للمستخدم
        $user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);
        if ($user) {
            $subject = "تم تفعيل حسابك - " . SITE_NAME;
            $emailMessage = "مرحباً " . $user['full_name'] . ",\n\n";
            $emailMessage .= "تم تفعيل حسابك بنجاح في " . SITE_NAME . "\n";
            $emailMessage .= "يمكنك الآن تسجيل الدخول والاستفادة من جميع الخدمات.\n\n";
            $emailMessage .= "مرحباً بك معنا!";
            
            // إرسال البريد
            // mail($user['email'], $subject, $emailMessage);
        }
        
        $message = 'تم تفعيل المستخدم بنجاح';
    } elseif ($action === 'reject' && $userId > 0) {
        $db->delete('users', 'id = ?', [$userId]);
        $message = 'تم رفض المستخدم وحذف حسابه';
    }
}

// جلب المستخدمين في انتظار الموافقة
$pendingUsers = $db->fetchAll("
    SELECT * FROM users 
    WHERE status = 'pending' AND email_verified = 1 
    ORDER BY created_at ASC
");
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>موافقة المستخدمين - لوحة التحكم</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Cairo', sans-serif; }
        .approval-card {
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            background: white;
        }
        .user-info {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            margin-left: 15px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>موافقة المستخدمين الجدد</h2>
                    <a href="users.php" class="btn btn-secondary">العودة لإدارة المستخدمين</a>
                </div>
                
                <?php if ($message): ?>
                    <div class="alert alert-success"><?php echo $message; ?></div>
                <?php endif; ?>
                
                <?php if (empty($pendingUsers)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        لا توجد طلبات عضوية في انتظار الموافقة
                    </div>
                <?php else: ?>
                    <?php foreach ($pendingUsers as $user): ?>
                        <div class="approval-card">
                            <div class="user-info">
                                <div class="user-avatar">
                                    <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <h5><?php echo htmlspecialchars($user['full_name']); ?></h5>
                                    <p class="text-muted mb-0">@<?php echo htmlspecialchars($user['username']); ?></p>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>البريد الإلكتروني:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                                    <p><strong>تاريخ التسجيل:</strong> <?php echo formatArabicDate($user['created_at']); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>الحالة:</strong> <span class="badge bg-warning">في انتظار الموافقة</span></p>
                                    <p><strong>البريد مفعل:</strong> 
                                        <?php if ($user['email_verified']): ?>
                                            <span class="badge bg-success">نعم</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">لا</span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                            
                            <div class="d-flex gap-2 mt-3">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="approve">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" class="btn btn-success" 
                                            onclick="return confirm('هل أنت متأكد من تفعيل هذا المستخدم؟')">
                                        <i class="fas fa-check"></i> موافقة
                                    </button>
                                </form>
                                
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="reject">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" class="btn btn-danger" 
                                            onclick="return confirm('هل أنت متأكد من رفض هذا المستخدم؟ سيتم حذف حسابه نهائياً.')">
                                        <i class="fas fa-times"></i> رفض
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>