<?php
/**
 * إدارة المستخدمين
 */
require_once '../config/config.php';

// التحقق من تسجيل الدخول والصلاحيات
requireLogin();
requirePermission('manage_users');

// معالجة الإجراءات
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'رمز الأمان غير صحيح';
    } else {
        if ($action === 'add' || $action === 'edit') {
            $data = [
                'username' => sanitize($_POST['username']),
                'email' => sanitize($_POST['email']),
                'full_name' => sanitize($_POST['full_name']),
                'phone' => sanitize($_POST['phone']),
                'role' => sanitize($_POST['role']),
                'status' => sanitize($_POST['status'])
            ];
            
            // إضافة كلمة المرور للمستخدمين الجدد أو عند التغيير
            if (!empty($_POST['password'])) {
                $data['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
            }
            
            if ($action === 'add') {
                // التحقق من عدم وجود المستخدم
                $existing = $db->fetchOne("SELECT id FROM users WHERE username = ? OR email = ?", [$data['username'], $data['email']]);
                if ($existing) {
                    $error = 'اسم المستخدم أو البريد الإلكتروني موجود مسبقاً';
                } else {
                    if ($db->insert('users', $data)) {
                        $message = 'تم إضافة المستخدم بنجاح';
                        $action = 'list';
                    } else {
                        $error = 'فشل في إضافة المستخدم';
                    }
                }
            } else {
                $id = (int)$_POST['id'];
                // التحقق من عدم تعديل المستخدم لنفسه إذا كان يغير الدور
                if ($id == $_SESSION['user_id'] && $data['role'] !== $_SESSION['role']) {
                    $error = 'لا يمكنك تغيير دورك الخاص';
                } else {
                    // إزالة كلمة المرور إذا كانت فارغة
                    if (empty($_POST['password'])) {
                        unset($data['password']);
                    }
                    
                    $updateFields = [];
                    $updateValues = [];
                    foreach ($data as $key => $value) {
                        $updateFields[] = "$key = ?";
                        $updateValues[] = $value;
                    }
                    $updateValues[] = $id;
                    $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
                    $stmt = $db->query($sql, $updateValues);
                    if ($stmt) {
                        $message = 'تم تحديث المستخدم بنجاح';
                        $action = 'list';
                    } else {
                        $error = 'فشل في تحديث المستخدم';
                    }
                }
            }
        }
    }
}

// حذف مستخدم
if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    if ($id == $_SESSION['user_id']) {
        $error = 'لا يمكنك حذف حسابك الخاص';
    } else {
        if ($db->delete('users', 'id = ?', [$id])) {
            $message = 'تم حذف المستخدم بنجاح';
        } else {
            $error = 'فشل في حذف المستخدم';
        }
    }
    $action = 'list';
}

// جلب البيانات حسب الإجراء
if ($action === 'edit' && isset($_GET['id'])) {
    $userData = $db->fetchOne("SELECT * FROM users WHERE id = ?", [(int)$_GET['id']]);
    if (!$userData) {
        $error = 'المستخدم غير موجود';
        $action = 'list';
    }
}

if ($action === 'list') {
    $users = $db->fetchAll("SELECT * FROM users ORDER BY created_at DESC");
    
    // إحصائيات المستخدمين
    $stats = [
        'total' => count($users),
        'active' => count(array_filter($users, function($u) { return $u['status'] === 'active'; })),
        'inactive' => count(array_filter($users, function($u) { return $u['status'] === 'inactive'; })),
        'banned' => count(array_filter($users, function($u) { return $u['status'] === 'banned'; })),
        'admins' => count(array_filter($users, function($u) { return $u['role'] === 'admin'; })),
        'moderators' => count(array_filter($users, function($u) { return $u['role'] === 'moderator'; }))
    ];
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة المستخدمين - لوحة التحكم</title>
    
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
        }
        
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
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
                            <a class="nav-link" href="comments.php">
                                <i class="fas fa-comments"></i>
                                التعليقات
                            </a>
                            <a class="nav-link" href="messages.php">
                                <i class="fas fa-envelope"></i>
                                الرسائل
                            </a>
                            <a class="nav-link" href="polls.php">
                                <i class="fas fa-poll"></i>
                                الاستطلاعات
                            </a>
                            <a class="nav-link active" href="users.php">
                                <i class="fas fa-users"></i>
                                المستخدمون
                            </a>
                            <a class="nav-link" href="statistics.php">
                                <i class="fas fa-chart-bar"></i>
                                الإحصائيات
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
                                <h2 class="mb-2">إدارة المستخدمين</h2>
                                <nav aria-label="breadcrumb">
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item"><a href="index.php">لوحة التحكم</a></li>
                                        <li class="breadcrumb-item active">المستخدمون</li>
                                    </ol>
                                </nav>
                            </div>
                            <?php if ($action === 'list'): ?>
                            <div class="col-auto">
                                <a href="?action=add" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> إضافة مستخدم جديد
                                </a>
                            </div>
                            <?php elseif (in_array($action, ['add', 'edit'])): ?>
                            <div class="col-auto">
                                <a href="?action=list" class="btn btn-secondary">
                                    <i class="fas fa-arrow-right"></i> العودة للقائمة
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
                            <!-- Statistics -->
                            <div class="row mb-4">
                                <div class="col-md-2">
                                    <div class="stats-card">
                                        <div class="stats-number text-primary"><?php echo convertToArabicNumbers($stats['total']); ?></div>
                                        <div class="text-muted">إجمالي</div>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="stats-card">
                                        <div class="stats-number text-success"><?php echo convertToArabicNumbers($stats['active']); ?></div>
                                        <div class="text-muted">نشط</div>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="stats-card">
                                        <div class="stats-number text-warning"><?php echo convertToArabicNumbers($stats['inactive']); ?></div>
                                        <div class="text-muted">غير نشط</div>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="stats-card">
                                        <div class="stats-number text-danger"><?php echo convertToArabicNumbers($stats['banned']); ?></div>
                                        <div class="text-muted">محظور</div>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="stats-card">
                                        <div class="stats-number text-info"><?php echo convertToArabicNumbers($stats['admins']); ?></div>
                                        <div class="text-muted">مدراء</div>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="stats-card">
                                        <div class="stats-number text-secondary"><?php echo convertToArabicNumbers($stats['moderators']); ?></div>
                                        <div class="text-muted">مشرفون</div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Users List -->
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>المستخدم</th>
                                            <th>البريد الإلكتروني</th>
                                            <th>الهاتف</th>
                                            <th>الدور</th>
                                            <th>الحالة</th>
                                            <th>آخر دخول</th>
                                            <th>الإجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="user-avatar me-3">
                                                        <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                                                    </div>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>
                                                        <br><small class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td><?php echo htmlspecialchars($user['phone'] ?? '-'); ?></td>
                                            <td>
                                                <?php
                                                $roles = [
                                                    'admin' => '<span class="badge bg-danger">مدير عام</span>',
                                                    'moderator' => '<span class="badge bg-warning">مشرف</span>',
                                                    'editor' => '<span class="badge bg-info">محرر</span>',
                                                    'member' => '<span class="badge bg-secondary">عضو</span>'
                                                ];
                                                echo $roles[$user['role']] ?? $user['role'];
                                                ?>
                                            </td>
                                            <td>
                                                <?php if ($user['status'] === 'active'): ?>
                                                    <span class="badge bg-success">نشط</span>
                                                <?php elseif ($user['status'] === 'inactive'): ?>
                                                    <span class="badge bg-warning">غير نشط</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">محظور</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($user['last_login']): ?>
                                                    <?php echo formatArabicDate($user['last_login']); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">لم يسجل دخول</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="?action=edit&id=<?php echo $user['id']; ?>" class="btn btn-outline-primary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                        <a href="?action=delete&id=<?php echo $user['id']; ?>" 
                                                           class="btn btn-outline-danger"
                                                           onclick="return confirm('هل أنت متأكد من حذف هذا المستخدم؟')">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                        <?php elseif ($action === 'add' || $action === 'edit'): ?>
                            <!-- Add/Edit Form -->
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                <?php if ($action === 'edit'): ?>
                                    <input type="hidden" name="id" value="<?php echo $userData['id']; ?>">
                                <?php endif; ?>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="username" class="form-label">اسم المستخدم *</label>
                                            <input type="text" class="form-control" id="username" name="username" 
                                                   value="<?php echo htmlspecialchars($userData['username'] ?? ''); ?>" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="email" class="form-label">البريد الإلكتروني *</label>
                                            <input type="email" class="form-control" id="email" name="email" 
                                                   value="<?php echo htmlspecialchars($userData['email'] ?? ''); ?>" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="full_name" class="form-label">الاسم الكامل *</label>
                                            <input type="text" class="form-control" id="full_name" name="full_name" 
                                                   value="<?php echo htmlspecialchars($userData['full_name'] ?? ''); ?>" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="phone" class="form-label">رقم الهاتف</label>
                                            <input type="tel" class="form-control" id="phone" name="phone" 
                                                   value="<?php echo htmlspecialchars($userData['phone'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="password" class="form-label">
                                                كلمة المرور <?php echo $action === 'add' ? '*' : '(اتركها فارغة إذا لم تريد تغييرها)'; ?>
                                            </label>
                                            <input type="password" class="form-control" id="password" name="password" 
                                                   <?php echo $action === 'add' ? 'required' : ''; ?>>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="role" class="form-label">الدور *</label>
                                            <select class="form-select" id="role" name="role" required>
                                                <option value="member" <?php echo ($userData['role'] ?? '') === 'member' ? 'selected' : ''; ?>>عضو</option>
                                                <option value="editor" <?php echo ($userData['role'] ?? '') === 'editor' ? 'selected' : ''; ?>>محرر</option>
                                                <option value="moderator" <?php echo ($userData['role'] ?? '') === 'moderator' ? 'selected' : ''; ?>>مشرف</option>
                                                <?php if ($_SESSION['role'] === 'admin'): ?>
                                                    <option value="admin" <?php echo ($userData['role'] ?? '') === 'admin' ? 'selected' : ''; ?>>مدير عام</option>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="status" class="form-label">الحالة *</label>
                                            <select class="form-select" id="status" name="status" required>
                                                <option value="active" <?php echo ($userData['status'] ?? '') === 'active' ? 'selected' : ''; ?>>نشط</option>
                                                <option value="inactive" <?php echo ($userData['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>غير نشط</option>
                                                <option value="banned" <?php echo ($userData['status'] ?? '') === 'banned' ? 'selected' : ''; ?>>محظور</option>
                                            </select>
                                        </div>
                                        
                                        <?php if ($action === 'edit'): ?>
                                        <div class="mb-3">
                                            <label class="form-label">معلومات إضافية</label>
                                            <div class="bg-light p-3 rounded">
                                                <p><strong>تاريخ التسجيل:</strong> <?php echo formatArabicDate($userData['created_at']); ?></p>
                                                <?php if ($userData['last_login']): ?>
                                                    <p><strong>آخر دخول:</strong> <?php echo formatArabicDate($userData['last_login']); ?></p>
                                                <?php endif; ?>
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
                                        <?php echo $action === 'add' ? 'إضافة المستخدم' : 'تحديث المستخدم'; ?>
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
</body>
</html>
