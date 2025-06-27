<?php
/**
 * إدارة الرسائل
 */
require_once '../config/config.php';

// التحقق من تسجيل الدخول والصلاحيات
requireLogin();
requirePermission('manage_messages');

// معالجة الإجراءات
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'رمز الأمان غير صحيح';
    } else {
        if ($action === 'reply') {
            $id = (int)$_POST['id'];
            $reply_message = sanitize($_POST['reply_message']);
            
            $data = [
                'status' => 'replied',
                'replied_by' => $_SESSION['user_id'],
                'reply_message' => $reply_message,
                'replied_at' => date('Y-m-d H:i:s')
            ];
            
            if ($db->update('messages', $data, 'id = ?', [$id])) {
                // إرسال الرد بالبريد الإلكتروني
                $messageData = $db->fetchOne("SELECT * FROM messages WHERE id = ?", [$id]);
                if ($messageData) {
                    $subject = 'رد على رسالتك - ' . getSetting($db, 'site_name', 'مسجد النور');
                    $emailBody = "السلام عليكم ورحمة الله وبركاته\n\n";
                    $emailBody .= "شكراً لك على تواصلك معنا.\n\n";
                    $emailBody .= "رسالتك الأصلية:\n" . $messageData['message'] . "\n\n";
                    $emailBody .= "ردنا:\n" . $reply_message . "\n\n";
                    $emailBody .= "مع تحيات إدارة " . getSetting($db, 'site_name', 'مسجد النور');
                    
                    sendEmail($messageData['sender_email'], $subject, $emailBody, false);
                }
                
                $message = 'تم إرسال الرد بنجاح';
                $action = 'list';
            } else {
                $error = 'فشل في إرسال الرد';
            }
        }
    }
}

// تغيير حالة الرسالة
if ($action === 'mark_read' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    if ($db->update('messages', ['status' => 'read'], 'id = ?', [$id])) {
        $message = 'تم تحديث حالة الرسالة';
    }
    $action = 'view';
    $_GET['id'] = $id;
}

// حذف رسالة
if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    if ($db->delete('messages', 'id = ?', [$id])) {
        $message = 'تم حذف الرسالة بنجاح';
    } else {
        $error = 'فشل في حذف الرسالة';
    }
    $action = 'list';
}

// جلب البيانات حسب الإجراء
if ($action === 'view' && isset($_GET['id'])) {
    $messageData = $db->fetchOne("SELECT m.*, u.full_name as replier_name FROM messages m LEFT JOIN users u ON m.replied_by = u.id WHERE m.id = ?", [(int)$_GET['id']]);
    if (!$messageData) {
        $error = 'الرسالة غير موجودة';
        $action = 'list';
    }
}

if ($action === 'list') {
    $status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
    $where_clause = '';
    $params = [];
    
    if ($status_filter !== 'all') {
        $where_clause = 'WHERE status = ?';
        $params[] = $status_filter;
    }
    
    $messages = $db->fetchAll("SELECT * FROM messages $where_clause ORDER BY created_at DESC", $params);
    
    // إحصائيات الرسائل
    $stats = [
        'total' => $db->fetchOne("SELECT COUNT(*) as count FROM messages")['count'],
        'unread' => $db->fetchOne("SELECT COUNT(*) as count FROM messages WHERE status = 'unread'")['count'],
        'read' => $db->fetchOne("SELECT COUNT(*) as count FROM messages WHERE status = 'read'")['count'],
        'replied' => $db->fetchOne("SELECT COUNT(*) as count FROM messages WHERE status = 'replied'")['count']
    ];
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الرسائل - لوحة التحكم</title>
    
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
        
        .message-content {
            background: #f8f9fa;
            border-radius: 5px;
            padding: 15px;
            margin: 15px 0;
        }
        
        .reply-content {
            background: #e3f2fd;
            border-radius: 5px;
            padding: 15px;
            margin: 15px 0;
            border-right: 4px solid #2196f3;
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
        
        .message-row.unread {
            background-color: #fff3cd;
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
                            <a class="nav-link active" href="messages.php">
                                <i class="fas fa-envelope"></i>
                                الرسائل
                                <?php if (isset($stats) && $stats['unread'] > 0): ?>
                                    <span class="badge bg-danger ms-2"><?php echo $stats['unread']; ?></span>
                                <?php endif; ?>
                            </a>
                            <a class="nav-link" href="polls.php">
                                <i class="fas fa-poll"></i>
                                الاستطلاعات
                            </a>
                            <a class="nav-link" href="users.php">
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
                                <h2 class="mb-2">إدارة الرسائل</h2>
                                <nav aria-label="breadcrumb">
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item"><a href="index.php">لوحة التحكم</a></li>
                                        <li class="breadcrumb-item active">الرسائل</li>
                                    </ol>
                                </nav>
                            </div>
                            <?php if ($action === 'view'): ?>
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
                                <div class="col-md-3">
                                    <div class="stats-card">
                                        <div class="stats-number text-primary"><?php echo convertToArabicNumbers($stats['total']); ?></div>
                                        <div class="text-muted">إجمالي الرسائل</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="stats-card">
                                        <div class="stats-number text-danger"><?php echo convertToArabicNumbers($stats['unread']); ?></div>
                                        <div class="text-muted">غير مقروءة</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="stats-card">
                                        <div class="stats-number text-warning"><?php echo convertToArabicNumbers($stats['read']); ?></div>
                                        <div class="text-muted">مقروءة</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="stats-card">
                                        <div class="stats-number text-success"><?php echo convertToArabicNumbers($stats['replied']); ?></div>
                                        <div class="text-muted">تم الرد عليها</div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Filter -->
                            <div class="mb-3">
                                <div class="btn-group" role="group">
                                    <a href="?status=all" class="btn <?php echo $status_filter === 'all' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                        الكل (<?php echo $stats['total']; ?>)
                                    </a>
                                    <a href="?status=unread" class="btn <?php echo $status_filter === 'unread' ? 'btn-danger' : 'btn-outline-danger'; ?>">
                                        غير مقروءة (<?php echo $stats['unread']; ?>)
                                    </a>
                                    <a href="?status=read" class="btn <?php echo $status_filter === 'read' ? 'btn-warning' : 'btn-outline-warning'; ?>">
                                        مقروءة (<?php echo $stats['read']; ?>)
                                    </a>
                                    <a href="?status=replied" class="btn <?php echo $status_filter === 'replied' ? 'btn-success' : 'btn-outline-success'; ?>">
                                        تم الرد عليها (<?php echo $stats['replied']; ?>)
                                    </a>
                                </div>
                            </div>
                            
                            <!-- Messages List -->
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>المرسل</th>
                                            <th>الموضوع</th>
                                            <th>الحالة</th>
                                            <th>تاريخ الإرسال</th>
                                            <th>الإجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($messages as $msg): ?>
                                        <tr class="message-row <?php echo $msg['status'] === 'unread' ? 'unread' : ''; ?>">
                                            <td>
                                                <strong><?php echo htmlspecialchars($msg['sender_name']); ?></strong>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($msg['sender_email']); ?></small>
                                                <?php if ($msg['sender_phone']): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($msg['sender_phone']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($msg['subject']); ?></strong>
                                                <br><small class="text-muted"><?php echo truncateText($msg['message'], 80); ?></small>
                                            </td>
                                            <td>
                                                <?php if ($msg['status'] === 'unread'): ?>
                                                    <span class="badge bg-danger">غير مقروءة</span>
                                                <?php elseif ($msg['status'] === 'read'): ?>
                                                    <span class="badge bg-warning">مقروءة</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success">تم الرد</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo formatArabicDate($msg['created_at']); ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="?action=view&id=<?php echo $msg['id']; ?>" class="btn btn-outline-primary">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <?php if ($msg['status'] === 'unread'): ?>
                                                        <a href="?action=mark_read&id=<?php echo $msg['id']; ?>" class="btn btn-outline-warning">
                                                            <i class="fas fa-check"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <a href="?action=delete&id=<?php echo $msg['id']; ?>" 
                                                       class="btn btn-outline-danger"
                                                       onclick="return confirm('هل أنت متأكد من حذف هذه الرسالة؟')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                        <?php elseif ($action === 'view'): ?>
                            <!-- View Message -->
                            <div class="row">
                                <div class="col-md-8">
                                    <h4><?php echo htmlspecialchars($messageData['subject']); ?></h4>
                                    
                                    <div class="mb-3">
                                        <strong>من:</strong> <?php echo htmlspecialchars($messageData['sender_name']); ?>
                                        (<?php echo htmlspecialchars($messageData['sender_email']); ?>)
                                        <?php if ($messageData['sender_phone']): ?>
                                            <br><strong>الهاتف:</strong> <?php echo htmlspecialchars($messageData['sender_phone']); ?>
                                        <?php endif; ?>
                                        <br><strong>التاريخ:</strong> <?php echo formatArabicDate($messageData['created_at']); ?>
                                    </div>
                                    
                                    <div class="message-content">
                                        <?php echo nl2br(htmlspecialchars($messageData['message'])); ?>
                                    </div>
                                    
                                    <?php if ($messageData['status'] === 'replied' && $messageData['reply_message']): ?>
                                        <h5 class="mt-4">الرد:</h5>
                                        <div class="reply-content">
                                            <?php echo nl2br(htmlspecialchars($messageData['reply_message'])); ?>
                                            <hr>
                                            <small class="text-muted">
                                                تم الرد بواسطة: <?php echo htmlspecialchars($messageData['replier_name'] ?? 'غير محدد'); ?>
                                                في <?php echo formatArabicDate($messageData['replied_at']); ?>
                                            </small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="d-grid gap-2">
                                        <?php if ($messageData['status'] === 'unread'): ?>
                                            <a href="?action=mark_read&id=<?php echo $messageData['id']; ?>" class="btn btn-warning">
                                                <i class="fas fa-check"></i> تحديد كمقروءة
                                            </a>
                                        <?php endif; ?>
                                        
                                        <a href="mailto:<?php echo htmlspecialchars($messageData['sender_email']); ?>" class="btn btn-info">
                                            <i class="fas fa-envelope"></i> رد بالبريد الإلكتروني
                                        </a>
                                        
                                        <?php if ($messageData['sender_phone']): ?>
                                            <a href="tel:<?php echo htmlspecialchars($messageData['sender_phone']); ?>" class="btn btn-success">
                                                <i class="fas fa-phone"></i> اتصال هاتفي
                                            </a>
                                        <?php endif; ?>
                                        
                                        <a href="?action=delete&id=<?php echo $messageData['id']; ?>" 
                                           class="btn btn-danger"
                                           onclick="return confirm('هل أنت متأكد من حذف هذه الرسالة؟')">
                                            <i class="fas fa-trash"></i> حذف الرسالة
                                        </a>
                                    </div>
                                    
                                    <?php if ($messageData['status'] !== 'replied'): ?>
                                        <hr>
                                        <h6>إرسال رد سريع:</h6>
                                        <form method="POST">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                            <input type="hidden" name="id" value="<?php echo $messageData['id']; ?>">
                                            
                                            <div class="mb-3">
                                                <textarea class="form-control" name="reply_message" rows="5" 
                                                          placeholder="اكتب ردك هنا..." required></textarea>
                                            </div>
                                            
                                            <button type="submit" name="action" value="reply" class="btn btn-primary w-100">
                                                <i class="fas fa-reply"></i> إرسال الرد
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
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
