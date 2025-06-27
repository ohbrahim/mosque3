<?php
require_once '../config/config.php';
require_once '../includes/functions/all_functions.php';

// التحقق من تسجيل الدخول والصلاحيات
requireLogin();
requirePermission('manage_comments');

// معالجة الإجراءات
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$message = '';
$error = '';

// معالجة الإجراءات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'رمز الأمان غير صحيح';
    } else {
        if ($action === 'edit') {
            $id = (int)$_POST['id'];
            $data = [
                'content' => sanitize($_POST['content']),
                'status' => sanitize($_POST['status']),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            if ($db->update('comments', $data, 'id = ?', [$id])) {
                $message = 'تم تحديث التعليق بنجاح';
                $action = 'list';
            } else {
                $error = 'فشل في تحديث التعليق';
            }
        }
    }
}

// تغيير حالة التعليق
if ($action === 'approve' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    if ($db->update('comments', ['status' => 'approved'], 'id = ?', [$id])) {
        $message = 'تم الموافقة على التعليق بنجاح';
    } else {
        $error = 'فشل في الموافقة على التعليق';
    }
    $action = 'list';
}

// رفض التعليق
if ($action === 'reject' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    if ($db->update('comments', ['status' => 'rejected'], 'id = ?', [$id])) {
        $message = 'تم رفض التعليق بنجاح';
    } else {
        $error = 'فشل في رفض التعليق';
    }
    $action = 'list';
}

// حذف التعليق
if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    if ($db->delete('comments', 'id = ?', [$id])) {
        $message = 'تم حذف التعليق بنجاح';
    } else {
        $error = 'فشل في حذف التعليق';
    }
    $action = 'list';
}

// جلب البيانات حسب الإجراء
if ($action === 'edit' && isset($_GET['id'])) {
    $commentData = $db->fetchOne("SELECT * FROM comments WHERE id = ?", [(int)$_GET['id']]);
    if (!$commentData) {
        $error = 'التعليق غير موجود';
        $action = 'list';
    }
}

// تصفية التعليقات
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$filterSql = '';
$filterParams = [];

if ($filter === 'pending') {
    $filterSql = "WHERE c.status = 'pending'";
} elseif ($filter === 'approved') {
    $filterSql = "WHERE c.status = 'approved'";
} elseif ($filter === 'rejected') {
    $filterSql = "WHERE c.status = 'rejected'";
}

// جلب التعليقات
if ($action === 'list') {
    $comments = $db->fetchAll("
        SELECT c.*, p.title as page_title, u.full_name as user_name 
        FROM comments c 
        LEFT JOIN pages p ON c.page_id = p.id 
        LEFT JOIN users u ON c.user_id = u.id 
        $filterSql
        ORDER BY c.created_at DESC
    ", $filterParams);
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة التعليقات - لوحة التحكم</title>
    
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
        
        .comment-content {
            white-space: pre-line;
        }
        
        .badge-pending {
            background-color: #ffc107;
            color: #212529;
        }
        
        .badge-approved {
            background-color: #28a745;
            color: white;
        }
        
        .badge-rejected {
            background-color: #dc3545;
            color: white;
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
                            <a class="nav-link active" href="comments.php">
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
                                <h2 class="mb-2">إدارة التعليقات</h2>
                                <nav aria-label="breadcrumb">
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item"><a href="index.php">لوحة التحكم</a></li>
                                        <li class="breadcrumb-item active">إدارة التعليقات</li>
                                    </ol>
                                </nav>
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
                            <!-- Filter Tabs -->
                            <ul class="nav nav-tabs mb-4">
                                <li class="nav-item">
                                    <a class="nav-link <?php echo $filter === 'all' ? 'active' : ''; ?>" href="?filter=all">
                                        جميع التعليقات
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?php echo $filter === 'pending' ? 'active' : ''; ?>" href="?filter=pending">
                                        <i class="fas fa-clock"></i> بانتظار الموافقة
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?php echo $filter === 'approved' ? 'active' : ''; ?>" href="?filter=approved">
                                        <i class="fas fa-check"></i> تمت الموافقة
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?php echo $filter === 'rejected' ? 'active' : ''; ?>" href="?filter=rejected">
                                        <i class="fas fa-times"></i> مرفوضة
                                    </a>
                                </li>
                            </ul>
                            
                            <!-- Comments List -->
                            <?php if (empty($comments)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-comments fa-4x text-muted mb-3"></i>
                                    <h4 class="text-muted">لا توجد تعليقات</h4>
                                    <?php if ($filter !== 'all'): ?>
                                        <a href="?filter=all" class="btn btn-outline-primary mt-3">عرض جميع التعليقات</a>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>المعلق</th>
                                                <th>التعليق</th>
                                                <th>الصفحة</th>
                                                <th>التاريخ</th>
                                                <th>الحالة</th>
                                                <th>الإجراءات</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($comments as $comment): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($comment['user_name'] ?: $comment['author_name']); ?></strong>
                                                    <?php if ($comment['author_email']): ?>
                                                        <div class="small text-muted"><?php echo htmlspecialchars($comment['author_email']); ?></div>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="comment-content">
                                                        <?php echo nl2br(htmlspecialchars(mb_substr($comment['content'], 0, 100))); ?>
                                                        <?php if (mb_strlen($comment['content']) > 100): ?>
                                                            <a href="?action=edit&id=<?php echo $comment['id']; ?>">...المزيد</a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <a href="../index.php?page_id=<?php echo $comment['page_id']; ?>" target="_blank">
                                                        <?php echo htmlspecialchars($comment['page_title']); ?>
                                                    </a>
                                                </td>
                                                <td>
                                                    <?php echo formatArabicDate($comment['created_at']); ?>
                                                </td>
                                                <td>
                                                    <?php if ($comment['status'] === 'pending'): ?>
                                                        <span class="badge badge-pending">بانتظار الموافقة</span>
                                                    <?php elseif ($comment['status'] === 'approved'): ?>
                                                        <span class="badge badge-approved">تمت الموافقة</span>
                                                    <?php elseif ($comment['status'] === 'rejected'): ?>
                                                        <span class="badge badge-rejected">مرفوض</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="?action=edit&id=<?php echo $comment['id']; ?>" class="btn btn-outline-primary">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <?php if ($comment['status'] !== 'approved'): ?>
                                                            <a href="?action=approve&id=<?php echo $comment['id']; ?>" class="btn btn-outline-success">
                                                                <i class="fas fa-check"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                        <?php if ($comment['status'] !== 'rejected'): ?>
                                                            <a href="?action=reject&id=<?php echo $comment['id']; ?>" class="btn btn-outline-warning">
                                                                <i class="fas fa-times"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                        <a href="?action=delete&id=<?php echo $comment['id']; ?>" 
                                                           class="btn btn-outline-danger"
                                                           onclick="return confirm('هل أنت متأكد من حذف هذا التعليق؟')">
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
                            
                        <?php elseif ($action === 'edit'): ?>
                            <!-- Edit Form -->
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                <input type="hidden" name="id" value="<?php echo $commentData['id']; ?>">
                                
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="mb-3">
                                            <label for="content" class="form-label">التعليق</label>
                                            <textarea class="form-control" id="content" name="content" rows="5"><?php echo htmlspecialchars($commentData['content']); ?></textarea>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <div class="card mb-3">
                                            <div class="card-header">
                                                <h6 class="mb-0">معلومات التعليق</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="mb-3">
                                                    <label for="status" class="form-label">حالة التعليق</label>
                                                    <select class="form-select" id="status" name="status">
                                                        <option value="pending" <?php echo $commentData['status'] === 'pending' ? 'selected' : ''; ?>>بانتظار الموافقة</option>
                                                        <option value="approved" <?php echo $commentData['status'] === 'approved' ? 'selected' : ''; ?>>تمت الموافقة</option>
                                                        <option value="rejected" <?php echo $commentData['status'] === 'rejected' ? 'selected' : ''; ?>>مرفوض</option>
                                                    </select>
                                                </div>
                                                
                                                <p><strong>المعلق:</strong><br><?php echo htmlspecialchars($commentData['author_name']); ?></p>
                                                <?php if ($commentData['author_email']): ?>
                                                    <p><strong>البريد الإلكتروني:</strong><br><?php echo htmlspecialchars($commentData['author_email']); ?></p>
                                                <?php endif; ?>
                                                <p><strong>تاريخ التعليق:</strong><br><?php echo formatArabicDate($commentData['created_at']); ?></p>
                                                <p><strong>عنوان IP:</strong><br><?php echo htmlspecialchars($commentData['author_ip']); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-between">
                                    <a href="?action=list" class="btn btn-secondary">
                                        <i class="fas fa-arrow-right"></i> العودة
                                    </a>
                                    <div>
                                        <a href="?action=delete&id=<?php echo $commentData['id']; ?>" 
                                           class="btn btn-danger me-2"
                                           onclick="return confirm('هل أنت متأكد من حذف هذا التعليق؟')">
                                            <i class="fas fa-trash"></i> حذف
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i> حفظ التغييرات
                                        </button>
                                    </div>
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
