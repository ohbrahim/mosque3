<?php
require_once '../config/config.php';
require_once '../includes/functions/all_functions.php';

requireLogin();
requirePermission('manage_users');

$action = $_GET['action'] ?? 'list';
$message = '';
$error = '';

// معالجة الإجراءات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'رمز الأمان غير صحيح';
    } else {
        $requestId = (int)$_POST['request_id'];
        $action = $_POST['action'];
        $notes = sanitize($_POST['notes'] ?? '');
        
        if ($action === 'approve') {
            $result = approveRegistrationRequest($requestId, $notes);
            if ($result['success']) {
                $message = $result['message'];
            } else {
                $error = $result['message'];
            }
        } elseif ($action === 'reject') {
            $result = rejectRegistrationRequest($requestId, $notes);
            if ($result['success']) {
                $message = $result['message'];
            } else {
                $error = $result['message'];
            }
        }
    }
    $action = 'list';
}

// جلب طلبات التسجيل
if ($action === 'list') {
    $requests = $db->fetchAll("
        SELECT rr.*, u.full_name as processed_by_name 
        FROM registration_requests rr 
        LEFT JOIN users u ON rr.processed_by = u.id 
        ORDER BY rr.created_at DESC
    ");
} elseif ($action === 'view' && isset($_GET['id'])) {
    $request = $db->fetchOne("
        SELECT rr.*, u.full_name as processed_by_name 
        FROM registration_requests rr 
        LEFT JOIN users u ON rr.processed_by = u.id 
        WHERE rr.id = ?
    ", [(int)$_GET['id']]);
    
    if (!$request) {
        $error = 'الطلب غير موجود';
        $action = 'list';
    }
}

/**
 * الموافقة على طلب التسجيل
 */
function approveRegistrationRequest($requestId, $notes) {
    global $db;
    
    try {
        $request = $db->fetchOne("SELECT * FROM registration_requests WHERE id = ? AND status = 'pending'", [$requestId]);
        
        if (!$request) {
            return ['success' => false, 'message' => 'الطلب غير موجود أو تم معالجته بالفعل'];
        }
        
        $db->beginTransaction();
        
        // إنشاء المستخدم الجديد
        $userData = [
            'full_name' => $request['full_name'],
            'username' => $request['username'],
            'email' => $request['email'],
            'password' => $request['password'], // كلمة المرور مشفرة بالفعل
            'phone' => $request['phone'],
            'address' => $request['address'],
            'role' => 'member',
            'status' => 'active',
            'email_verified' => 1,
            'registration_date' => $request['created_at'],
            'approved_by' => $_SESSION['user_id'],
            'approved_at' => date('Y-m-d H:i:s')
        ];
        
        $userId = $db->insert('users', $userData);
        
        if ($userId) {
            // تحديث حالة الطلب
            $db->update('registration_requests', [
                'status' => 'approved',
                'processed_at' => date('Y-m-d H:i:s'),
                'processed_by' => $_SESSION['user_id'],
                'notes' => $notes
            ], 'id = ?', [$requestId]);
            
            // إرسال بريد الموافقة
            sendApprovalEmail($request['email'], $request['full_name']);
            
            // تسجيل النشاط
            logActivity($_SESSION['user_id'], 'approve_registration', "تم قبول طلب التسجيل للمستخدم: {$request['username']}");
            
            $db->commit();
            return ['success' => true, 'message' => 'تم قبول طلب التسجيل وإنشاء الحساب بنجاح'];
        } else {
            $db->rollBack();
            return ['success' => false, 'message' => 'حدث خطأ في إنشاء الحساب'];
        }
    } catch (Exception $e) {
        $db->rollBack();
        return ['success' => false, 'message' => 'حدث خطأ في النظام'];
    }
}

/**
 * رفض طلب التسجيل
 */
function rejectRegistrationRequest($requestId, $notes) {
    global $db;
    
    try {
        $request = $db->fetchOne("SELECT * FROM registration_requests WHERE id = ? AND status = 'pending'", [$requestId]);
        
        if (!$request) {
            return ['success' => false, 'message' => 'الطلب غير موجود أو تم معالجته بالفعل'];
        }
        
        // تحديث حالة الطلب
        $db->update('registration_requests', [
            'status' => 'rejected',
            'processed_at' => date('Y-m-d H:i:s'),
            'processed_by' => $_SESSION['user_id'],
            'notes' => $notes
        ], 'id = ?', [$requestId]);
        
        // إرسال بريد الرفض
        sendRejectionEmail($request['email'], $request['full_name'], $notes);
        
        // تسجيل النشاط
        logActivity($_SESSION['user_id'], 'reject_registration', "تم رفض طلب التسجيل للمستخدم: {$request['username']}");
        
        return ['success' => true, 'message' => 'تم رفض طلب التسجيل'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'حدث خطأ في النظام'];
    }
}

/**
 * إرسال بريد الموافقة
 */
function sendApprovalEmail($email, $fullName) {
    $subject = 'تم قبول طلب التسجيل - ' . SITE_NAME;
    $loginLink = SITE_URL . '/auth/login.php';
    
    $message = "
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: 'Arial', sans-serif; direction: rtl; text-align: right; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .button { display: inline-block; background: #28a745; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            .success-box { background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 20px 0; }
            .footer { text-align: center; margin-top: 30px; color: #666; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>🎉 مبروك! تم قبول طلبك</h2>
            </div>
            <div class='content'>
                <h3>أهلاً وسهلاً {$fullName}</h3>
                
                <div class='success-box'>
                    <h4>✅ تم قبول طلب التسجيل بنجاح!</h4>
                    <p>نحن سعداء لانضمامك إلى مجتمع " . SITE_NAME . "</p>
                </div>
                
                <p>يمكنك الآن تسجيل الدخول إلى حسابك والاستفادة من جميع الخدمات المتاحة:</p>
                
                <ul>
                    <li>متابعة أخبار وأنشطة المسجد</li>
                    <li>التسجيل في الدورات والبرامج</li>
                    <li>التفاعل مع المجتمع</li>
                    <li>الحصول على التنبيهات المهمة</li>
                </ul>
                
                <div style='text-align: center;'>
                    <a href='{$loginLink}' class='button'>تسجيل الدخول الآن</a>
                </div>
                
                <p>إذا كان لديك أي استفسار، لا تتردد في التواصل معنا.</p>
                
                <p>مرحباً بك في عائلة " . SITE_NAME . "! 🕌</p>
            </div>
            <div class='footer'>
                <p>&copy; " . date('Y') . " " . SITE_NAME . " - جميع الحقوق محفوظة</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($email, $subject, $message);
}

/**
 * إرسال بريد الرفض
 */
function sendRejectionEmail($email, $fullName, $notes) {
    $subject = 'بخصوص طلب التسجيل - ' . SITE_NAME;
    $contactLink = SITE_URL . '/contact.php';
    
    $message = "
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: 'Arial', sans-serif; direction: rtl; text-align: right; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
            .button { display: inline-block; background: #007bff; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            .info-box { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0; }
            .footer { text-align: center; margin-top: 30px; color: #666; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>بخصوص طلب التسجيل</h2>
            </div>
            <div class='content'>
                <h3>مرحباً {$fullName}</h3>
                
                <p>شكراً لك على اهتمامك بالانضمام إلى " . SITE_NAME . ".</p>
                
                <p>نأسف لإبلاغك أنه لم يتم قبول طلب التسجيل في الوقت الحالي.</p>
                
                " . ($notes ? "<div class='info-box'><h4>ملاحظات الإدارة:</h4><p>{$notes}</p></div>" : "") . "
                
                <p>إذا كنت تعتقد أن هناك خطأ أو لديك استفسار، يمكنك التواصل معنا:</p>
                
                <div style='text-align: center;'>
                    <a href='{$contactLink}' class='button'>تواصل معنا</a>
                </div>
                
                <p>نقدر تفهمك ونتمنى لك كل التوفيق.</p>
            </div>
            <div class='footer'>
                <p>&copy; " . date('Y') . " " . SITE_NAME . " - جميع الحقوق محفوظة</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($email, $subject, $message);
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>طلبات التسجيل - لوحة التحكم</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
        
        .status-badge {
            font-size: 0.8rem;
        }
        
        .request-card {
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s;
        }
        
        .request-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .request-pending {
            border-left: 4px solid #ffc107;
        }
        
        .request-approved {
            border-left: 4px solid #28a745;
        }
        
        .request-rejected {
            border-left: 4px solid #dc3545;
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
                            <a class="nav-link active" href="registration_requests.php">
                                <i class="fas fa-user-plus"></i>
                                طلبات التسجيل
                            </a>
                            <a class="nav-link" href="users.php">
                                <i class="fas fa-users"></i>
                                إدارة المستخدمين
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
                                <h2 class="mb-2">طلبات التسجيل</h2>
                                <nav aria-label="breadcrumb">
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item"><a href="index.php">لوحة التحكم</a></li>
                                        <li class="breadcrumb-item active">طلبات التسجيل</li>
                                    </ol>
                                </nav>
                            </div>
                            <div class="col-auto">
                                <?php
                                $pendingCount = $db->count('registration_requests', 'status = ?', ['pending']);
                                if ($pendingCount > 0):
                                ?>
                                    <span class="badge bg-warning fs-6">
                                        <?php echo convertToArabicNumbers($pendingCount); ?> طلب معلق
                                    </span>
                                <?php endif; ?>
                            </div>
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
                            <!-- Requests List -->
                            <?php if (empty($requests)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-user-plus fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">لا توجد طلبات تسجيل</h5>
                                    <p class="text-muted">سيظهر هنا طلبات التسجيل الجديدة</p>
                                </div>
                            <?php else: ?>
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <div class="card text-center">
                                            <div class="card-body">
                                                <h5 class="card-title text-warning">معلقة</h5>
                                                <h3 class="text-warning">
                                                    <?php echo convertToArabicNumbers($db->count('registration_requests', 'status = ?', ['pending'])); ?>
                                                </h3>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <div class="card text-center">
                                            <div class="card-body">
                                                <h5 class="card-title text-success">مقبولة</h5>
                                                <h3 class="text-success">
                                                    <?php echo convertToArabicNumbers($db->count('registration_requests', 'status = ?', ['approved'])); ?>
                                                </h3>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <div class="card text-center">
                                            <div class="card-body">
                                                <h5 class="card-title text-danger">مرفوضة</h5>
                                                <h3 class="text-danger">
                                                    <?php echo convertToArabicNumbers($db->count('registration_requests', 'status = ?', ['rejected'])); ?>
                                                </h3>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <div class="card text-center">
                                            <div class="card-body">
                                                <h5 class="card-title text-info">المجموع</h5>
                                                <h3 class="text-info">
                                                    <?php echo convertToArabicNumbers(count($requests)); ?>
                                                </h3>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <hr>
                                
                                <?php foreach ($requests as $request): ?>
                                    <div class="request-card request-<?php echo $request['status']; ?>">
                                        <div class="row align-items-center">
                                            <div class="col-md-8">
                                                <h5 class="mb-2">
                                                    <?php echo htmlspecialchars($request['full_name']); ?>
                                                    <?php
                                                    $statusClasses = [
                                                        'pending' => 'bg-warning',
                                                        'approved' => 'bg-success',
                                                        'rejected' => 'bg-danger'
                                                    ];
                                                    $statusLabels = [
                                                        'pending' => 'معلق',
                                                        'approved' => 'مقبول',
                                                        'rejected' => 'مرفوض'
                                                    ];
                                                    ?>
                                                    <span class="badge <?php echo $statusClasses[$request['status']]; ?> status-badge">
                                                        <?php echo $statusLabels[$request['status']]; ?>
                                                    </span>
                                                </h5>
                                                <p class="mb-1">
                                                    <i class="fas fa-user me-2"></i>
                                                    <strong>اسم المستخدم:</strong> <?php echo htmlspecialchars($request['username']); ?>
                                                </p>
                                                <p class="mb-1">
                                                    <i class="fas fa-envelope me-2"></i>
                                                    <strong>البريد:</strong> <?php echo htmlspecialchars($request['email']); ?>
                                                </p>
                                                <p class="mb-1">
                                                    <i class="fas fa-calendar me-2"></i>
                                                    <strong>تاريخ الطلب:</strong> <?php echo formatArabicDate($request['created_at']); ?>
                                                </p>
                                                <?php if ($request['processed_at']): ?>
                                                    <p class="mb-1">
                                                        <i class="fas fa-check me-2"></i>
                                                        <strong>تاريخ المعالجة:</strong> <?php echo formatArabicDate($request['processed_at']); ?>
                                                        <?php if ($request['processed_by_name']): ?>
                                                            بواسطة <?php echo htmlspecialchars($request['processed_by_name']); ?>
                                                        <?php endif; ?>
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-md-4 text-end">
                                                <a href="?action=view&id=<?php echo $request['id']; ?>" class="btn btn-outline-primary btn-sm mb-2">
                                                    <i class="fas fa-eye"></i> عرض التفاصيل
                                                </a>
                                                
                                                <?php if ($request['status'] === 'pending'): ?>
                                                    <div class="btn-group-vertical w-100">
                                                        <button type="button" class="btn btn-success btn-sm" 
                                                                onclick="showActionModal(<?php echo $request['id']; ?>, 'approve', '<?php echo htmlspecialchars($request['full_name']); ?>')">
                                                            <i class="fas fa-check"></i> قبول
                                                        </button>
                                                        <button type="button" class="btn btn-danger btn-sm" 
                                                                onclick="showActionModal(<?php echo $request['id']; ?>, 'reject', '<?php echo htmlspecialchars($request['full_name']); ?>')">
                                                            <i class="fas fa-times"></i> رفض
                                                        </button>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            
                        <?php elseif ($action === 'view'): ?>
                            <!-- Request Details -->
                            <div class="row">
                                <div class="col-md-8">
                                    <h4>تفاصيل طلب التسجيل</h4>
                                    
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <p><strong>الاسم الكامل:</strong><br><?php echo htmlspecialchars($request['full_name']); ?></p>
                                                    <p><strong>اسم المستخدم:</strong><br><?php echo htmlspecialchars($request['username']); ?></p>
                                                    <p><strong>البريد الإلكتروني:</strong><br><?php echo htmlspecialchars($request['email']); ?></p>
                                                </div>
                                                <div class="col-md-6">
                                                    <p><strong>رقم الهاتف:</strong><br><?php echo $request['phone'] ? htmlspecialchars($request['phone']) : 'غير محدد'; ?></p>
                                                    <p><strong>العنوان:</strong><br><?php echo $request['address'] ? htmlspecialchars($request['address']) : 'غير محدد'; ?></p>
                                                    <p><strong>تاريخ الطلب:</strong><br><?php echo formatArabicDate($request['created_at']); ?></p>
                                                </div>
                                            </div>
                                            
                                            <?php if ($request['notes']): ?>
                                                <hr>
                                                <p><strong>ملاحظات:</strong></p>
                                                <div class="alert alert-info">
                                                    <?php echo nl2br(htmlspecialchars($request['notes'])); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="mb-0">حالة الطلب</h6>
                                        </div>
                                        <div class="card-body text-center">
                                            <?php
                                            $statusClasses = [
                                                'pending' => 'bg-warning',
                                                'approved' => 'bg-success',
                                                'rejected' => 'bg-danger'
                                            ];
                                            $statusLabels = [
                                                'pending' => 'معلق',
                                                'approved' => 'مقبول',
                                                'rejected' => 'مرفوض'
                                            ];
                                            ?>
                                            <span class="badge <?php echo $statusClasses[$request['status']]; ?> fs-6 mb-3">
                                                <?php echo $statusLabels[$request['status']]; ?>
                                            </span>
                                            
                                            <?php if ($request['status'] === 'pending'): ?>
                                                <div class="d-grid gap-2">
                                                    <button type="button" class="btn btn-success" 
                                                            onclick="showActionModal(<?php echo $request['id']; ?>, 'approve', '<?php echo htmlspecialchars($request['full_name']); ?>')">
                                                        <i class="fas fa-check"></i> قبول الطلب
                                                    </button>
                                                    <button type="button" class="btn btn-danger" 
                                                            onclick="showActionModal(<?php echo $request['id']; ?>, 'reject', '<?php echo htmlspecialchars($request['full_name']); ?>')">
                                                        <i class="fas fa-times"></i> رفض الطلب
                                                    </button>
                                                </div>
                                            <?php else: ?>
                                                <p class="mb-0">
                                                    <strong>تمت المعالجة:</strong><br>
                                                    <?php echo formatArabicDate($request['processed_at']); ?>
                                                </p>
                                                <?php if ($request['processed_by_name']): ?>
                                                    <p class="mb-0">
                                                        <strong>بواسطة:</strong><br>
                                                        <?php echo htmlspecialchars($request['processed_by_name']); ?>
                                                    </p>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-3">
                                <a href="?action=list" class="btn btn-secondary">
                                    <i class="fas fa-arrow-right"></i> العودة للقائمة
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Modal -->
    <div class="modal fade" id="actionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="request_id" id="modal_request_id">
                    <input type="hidden" name="action" id="modal_action">
                    
                    <div class="modal-header">
                        <h5 class="modal-title" id="modal_title"></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p id="modal_message"></p>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">ملاحظات (اختيارية)</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" 
                                      placeholder="أضف ملاحظات للمستخدم..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn" id="modal_submit_btn">تأكيد</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function showActionModal(requestId, action, userName) {
            document.getElementById('modal_request_id').value = requestId;
            document.getElementById('modal_action').value = action;
            
            const modal = document.getElementById('actionModal');
            const title = document.getElementById('modal_title');
            const message = document.getElementById('modal_message');
            const submitBtn = document.getElementById('modal_submit_btn');
            
            if (action === 'approve') {
                title.textContent = 'قبول طلب التسجيل';
                message.textContent = `هل أنت متأكد من قبول طلب التسجيل للمستخدم "${userName}"؟`;
                submitBtn.textContent = 'قبول الطلب';
                submitBtn.className = 'btn btn-success';
            } else if (action === 'reject') {
                title.textContent = 'رفض طلب التسجيل';
                message.textContent = `هل أنت متأكد من رفض طلب التسجيل للمستخدم "${userName}"؟`;
                submitBtn.textContent = 'رفض الطلب';
                submitBtn.className = 'btn btn-danger';
            }
            
            new bootstrap.Modal(modal).show();
        }
    </script>
</body>
</html>
