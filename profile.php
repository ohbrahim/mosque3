<?php
require_once 'config/config.php';

// التأكد من تسجيل الدخول
requireLogin();

$userId = $_SESSION['user_id'];
$message = '';
$error = '';

// معالجة تحديث الملف الشخصي
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $fullName = sanitize($_POST['full_name']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);
        $bio = sanitize($_POST['bio']);
        
        // التحقق من صحة البيانات
        if (empty($fullName)) {
            $error = 'الاسم الكامل مطلوب';
        } elseif (!validateEmail($email)) {
            $error = 'البريد الإلكتروني غير صحيح';
        } else {
            // التحقق من عدم تكرار البريد الإلكتروني
            $existingUser = $db->fetchOne("SELECT id FROM users WHERE email = ? AND id != ?", [$email, $userId]);
            if ($existingUser) {
                $error = 'البريد الإلكتروني مستخدم من قبل مستخدم آخر';
            } else {
                try {
                    // تحديث البيانات
                    $updateData = [
                        'full_name' => $fullName,
                        'email' => $email,
                        'phone' => $phone,
                        'bio' => $bio,
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                    
                    // معالجة رفع الصورة الشخصية
                    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                        $uploadResult = uploadFile($_FILES['profile_image'], ['jpg', 'jpeg', 'png']);
                        if ($uploadResult['success']) {
                            // حذف الصورة القديمة
                            $oldUser = $db->fetchOne("SELECT profile_image FROM users WHERE id = ?", [$userId]);
                            if ($oldUser && $oldUser['profile_image']) {
                                deleteFile($oldUser['profile_image']);
                            }
                            
                            // تغيير حجم الصورة
                            $originalPath = UPLOAD_PATH . $uploadResult['filename'];
                            $resizedPath = UPLOAD_PATH . 'thumb_' . $uploadResult['filename'];
                            
                            if (resizeImage($originalPath, $resizedPath, 200, 200)) {
                                deleteFile($uploadResult['filename']); // حذف الصورة الأصلية
                                $updateData['profile_image'] = 'thumb_' . $uploadResult['filename'];
                            } else {
                                $updateData['profile_image'] = $uploadResult['filename'];
                            }
                        }
                    }
                    
                    $db->update('users', $updateData, 'id = ?', [$userId]);
                    
                    // تحديث بيانات الجلسة
                    $_SESSION['full_name'] = $fullName;
                    $_SESSION['email'] = $email;
                    
                    logActivity($db, 'profile_updated', 'تم تحديث الملف الشخصي');
                    $message = 'تم تحديث الملف الشخصي بنجاح';
                } catch (Exception $e) {
                    $error = 'حدث خطأ أثناء تحديث الملف الشخصي';
                    error_log("Profile update error: " . $e->getMessage());
                }
            }
        }
    } else {
        $error = 'رمز الأمان غير صحيح';
    }
}

// معالجة تغيير كلمة المرور
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];
        
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $error = 'جميع حقول كلمة المرور مطلوبة';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'كلمة المرور الجديدة وتأكيدها غير متطابقين';
        } elseif (strlen($newPassword) < 6) {
            $error = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل';
        } else {
            try {
                // التحقق من كلمة المرور الحالية
                $user = $db->fetchOne("SELECT password FROM users WHERE id = ?", [$userId]);
                if (!$user || !password_verify($currentPassword, $user['password'])) {
                    $error = 'كلمة المرور الحالية غير صحيحة';
                } else {
                    // تحديث كلمة المرور
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    $db->update('users', [
                        'password' => $hashedPassword,
                        'updated_at' => date('Y-m-d H:i:s')
                    ], 'id = ?', [$userId]);
                    
                    logActivity($db, 'password_changed', 'تم تغيير كلمة المرور');
                    $message = 'تم تغيير كلمة المرور بنجاح';
                }
            } catch (Exception $e) {
                $error = 'حدث خطأ أثناء تغيير كلمة المرور';
                error_log("Password change error: " . $e->getMessage());
            }
        }
    } else {
        $error = 'رمز الأمان غير صحيح';
    }
}

// جلب بيانات المستخدم
try {
    $user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);
    if (!$user) {
        header('Location: logout.php');
        exit;
    }
} catch (Exception $e) {
    die('خطأ في جلب بيانات المستخدم');
}

// جلب إحصائيات المستخدم
$userStats = getUserStats($db, $userId);

// جلب النشاطات الأخيرة
try {
    $recentActivities = $db->fetchAll("
        SELECT * FROM activity_log 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 10
    ", [$userId]);
} catch (Exception $e) {
    $recentActivities = [];
}

// إعدادات الموقع
$siteName = getSetting($db, 'site_name', 'مسجد النور');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الملف الشخصي - <?php echo htmlspecialchars($siteName); ?></title>
    
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
        
        .profile-header {
            background: linear-gradient(135deg, #2c5530 0%, #1e3a1e 100%);
            color: white;
            padding: 60px 0;
        }
        
        .profile-image {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid white;
            object-fit: cover;
        }
        
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .stats-card h3 {
            color: #2c5530;
            margin-bottom: 5px;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .nav-pills .nav-link {
            border-radius: 10px;
            margin-bottom: 10px;
        }
        
        .nav-pills .nav-link.active {
            background-color: #2c5530;
        }
        
        .activity-item {
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: 15px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(135deg, #2c5530 0%, #1e3a1e 100%);">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-mosque me-2"></i>
                <?php echo htmlspecialchars($siteName); ?>
            </a>
            
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php">
                    <i class="fas fa-home me-1"></i> الرئيسية
                </a>
                <?php if (hasPermission('admin_access')): ?>
                    <a class="nav-link" href="admin/index.php">
                        <i class="fas fa-tachometer-alt me-1"></i> لوحة التحكم
                    </a>
                <?php endif; ?>
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i> تسجيل الخروج
                </a>
            </div>
        </div>
    </nav>

    <!-- Profile Header -->
    <div class="profile-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-3 text-center">
                    <?php if ($user['profile_image']): ?>
                        <img src="uploads/<?php echo htmlspecialchars($user['profile_image']); ?>" 
                             alt="الصورة الشخصية" class="profile-image">
                    <?php else: ?>
                        <div class="profile-image bg-light d-flex align-items-center justify-content-center">
                            <i class="fas fa-user fa-3x text-muted"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-9">
                    <h1><?php echo htmlspecialchars($user['full_name']); ?></h1>
                    <p class="mb-2">
                        <i class="fas fa-envelope me-2"></i>
                        <?php echo htmlspecialchars($user['email']); ?>
                    </p>
                    <?php if ($user['phone']): ?>
                        <p class="mb-2">
                            <i class="fas fa-phone me-2"></i>
                            <?php echo htmlspecialchars($user['phone']); ?>
                        </p>
                    <?php endif; ?>
                    <p class="mb-0">
                        <i class="fas fa-user-tag me-2"></i>
                        <?php 
                        $roles = [
                            'admin' => 'مدير',
                            'moderator' => 'مشرف',
                            'editor' => 'محرر',
                            'member' => 'عضو'
                        ];
                        echo $roles[$user['role']] ?? 'عضو';
                        ?>
                    </p>
                    <p class="mb-0">
                        <i class="fas fa-calendar me-2"></i>
                        عضو منذ <?php echo formatArabicDate($user['created_at']); ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mt-5">
        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Statistics -->
            <div class="col-lg-3">
                <div class="stats-card">
                    <h3><?php echo convertToArabicNumbers($userStats['pages']); ?></h3>
                    <p class="text-muted mb-0">الصفحات</p>
                </div>
                
                <div class="stats-card">
                    <h3><?php echo convertToArabicNumbers($userStats['comments']); ?></h3>
                    <p class="text-muted mb-0">التعليقات</p>
                </div>
                
                <div class="stats-card">
                    <h3><?php echo convertToArabicNumbers($userStats['ratings']); ?></h3>
                    <p class="text-muted mb-0">التقييمات</p>
                </div>
                
                <div class="stats-card">
                    <h3><?php echo convertToArabicNumbers($userStats['votes']); ?></h3>
                    <p class="text-muted mb-0">الأصوات</p>
                </div>
            </div>

            <!-- Profile Content -->
            <div class="col-lg-9">
                <!-- Navigation Tabs -->
                <ul class="nav nav-pills mb-4" id="profileTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="profile-tab" data-bs-toggle="pill" 
                                data-bs-target="#profile" type="button" role="tab">
                            <i class="fas fa-user me-2"></i>الملف الشخصي
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="password-tab" data-bs-toggle="pill" 
                                data-bs-target="#password" type="button" role="tab">
                            <i class="fas fa-lock me-2"></i>كلمة المرور
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="activity-tab" data-bs-toggle="pill" 
                                data-bs-target="#activity" type="button" role="tab">
                            <i class="fas fa-history me-2"></i>النشاطات
                        </button>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content" id="profileTabsContent">
                    <!-- Profile Tab -->
                    <div class="tab-pane fade show active" id="profile" role="tabpanel">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-edit me-2"></i>
                                    تحديث الملف الشخصي
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                    <input type="hidden" name="update_profile" value="1">
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="full_name" class="form-label">الاسم الكامل *</label>
                                            <input type="text" class="form-control" id="full_name" name="full_name" 
                                                   value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="email" class="form-label">البريد الإلكتروني *</label>
                                            <input type="email" class="form-control" id="email" name="email" 
                                                   value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="phone" class="form-label">رقم الهاتف</label>
                                            <input type="tel" class="form-control" id="phone" name="phone" 
                                                   value="<?php echo htmlspecialchars($user['phone']); ?>">
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="profile_image" class="form-label">الصورة الشخصية</label>
                                            <input type="file" class="form-control" id="profile_image" name="profile_image" 
                                                   accept="image/*">
                                            <div class="form-text">يُفضل صورة مربعة بحجم 200x200 بكسل</div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="bio" class="form-label">نبذة شخصية</label>
                                        <textarea class="form-control" id="bio" name="bio" rows="4" 
                                                  placeholder="اكتب نبذة مختصرة عنك..."><?php echo htmlspecialchars($user['bio']); ?></textarea>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>
                                        حفظ التغييرات
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Password Tab -->
                    <div class="tab-pane fade" id="password" role="tabpanel">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-key me-2"></i>
                                    تغيير كلمة المرور
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                    <input type="hidden" name="change_password" value="1">
                                    
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">كلمة المرور الحالية *</label>
                                        <input type="password" class="form-control" id="current_password" 
                                               name="current_password" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">كلمة المرور الجديدة *</label>
                                        <input type="password" class="form-control" id="new_password" 
                                               name="new_password" minlength="6" required>
                                        <div class="form-text">يجب أن تكون 6 أحرف على الأقل</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">تأكيد كلمة المرور *</label>
                                        <input type="password" class="form-control" id="confirm_password" 
                                               name="confirm_password" minlength="6" required>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-warning">
                                        <i class="fas fa-lock me-2"></i>
                                        تغيير كلمة المرور
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Activity Tab -->
                    <div class="tab-pane fade" id="activity" role="tabpanel">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-history me-2"></i>
                                    النشاطات الأخيرة
                                </h5>
                            </div>
                            <div class="card-body p-0">
                                <?php if (empty($recentActivities)): ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-history fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">لا توجد نشاطات بعد</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($recentActivities as $activity): ?>
                                        <div class="activity-item d-flex align-items-center">
                                            <div class="activity-icon bg-primary text-white">
                                                <?php
                                                $icons = [
                                                    'login' => 'fas fa-sign-in-alt',
                                                    'logout' => 'fas fa-sign-out-alt',
                                                    'profile_updated' => 'fas fa-user-edit',
                                                    'password_changed' => 'fas fa-key',
                                                    'page_created' => 'fas fa-file-plus',
                                                    'page_updated' => 'fas fa-edit',
                                                    'comment_added' => 'fas fa-comment',
                                                    'rating_added' => 'fas fa-star',
                                                    'vote_cast' => 'fas fa-vote-yea'
                                                ];
                                                $icon = $icons[$activity['action']] ?? 'fas fa-info-circle';
                                                ?>
                                                <i class="<?php echo $icon; ?>"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="fw-bold">
                                                    <?php
                                                    $actions = [
                                                        'login' => 'تسجيل دخول',
                                                        'logout' => 'تسجيل خروج',
                                                        'profile_updated' => 'تحديث الملف الشخصي',
                                                        'password_changed' => 'تغيير كلمة المرور',
                                                        'page_created' => 'إنشاء صفحة جديدة',
                                                        'page_updated' => 'تحديث صفحة',
                                                        'comment_added' => 'إضافة تعليق',
                                                        'rating_added' => 'إضافة تقييم',
                                                        'vote_cast' => 'التصويت في استطلاع'
                                                    ];
                                                    echo $actions[$activity['action']] ?? $activity['action'];
                                                    ?>
                                                </div>
                                                <?php if ($activity['details']): ?>
                                                    <div class="text-muted small">
                                                        <?php echo htmlspecialchars($activity['details']); ?>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="text-muted small">
                                                    <i class="fas fa-clock me-1"></i>
                                                    <?php echo formatArabicDate($activity['created_at']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // التحقق من تطابق كلمة المرور
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (newPassword !== confirmPassword) {
                this.setCustomValidity('كلمة المرور غير متطابقة');
            } else {
                this.setCustomValidity('');
            }
        });
        
        // معاينة الصورة الشخصية
        document.getElementById('profile_image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    // يمكن إضافة معاينة للصورة هنا
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>
