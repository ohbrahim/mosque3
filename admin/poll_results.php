<?php
require_once '../config/config.php';

// التحقق من تسجيل الدخول وصلاحيات المستخدم
if (!isLoggedIn() || !hasPermission('manage_polls')) {
    header('Location: login.php');
    exit;
}

// التحقق من وجود معرف الاستطلاع
if (!isset($_GET['id'])) {
    header('Location: polls.php');
    exit;
}

$pollId = (int)$_GET['id'];

try {
    // جلب بيانات الاستطلاع
    $poll = $db->fetchOne("SELECT * FROM polls WHERE id = ?", [$pollId]);
    
    if (!$poll) {
        $_SESSION['error'] = 'الاستطلاع غير موجود.';
        header('Location: polls.php');
        exit;
    }
    
    // جلب خيارات الاستطلاع مع عدد الأصوات
    $options = $db->fetchAll("
        SELECT po.*, COUNT(pv.id) as votes_count
        FROM poll_options po 
        LEFT JOIN poll_votes pv ON po.id = pv.option_id 
        WHERE po.poll_id = ? 
        GROUP BY po.id 
        ORDER BY po.display_order
    ", [$pollId]);
    
    // حساب إجمالي الأصوات
    $totalVotes = $db->fetchOne("SELECT COUNT(*) as total FROM poll_votes WHERE poll_id = ?", [$pollId])['total'] ?? 0;
    
    // جلب آخر 50 صوت مع بيانات المستخدم
    $votes = $db->fetchAll("
        SELECT pv.*, po.option_text, u.full_name, u.email
        FROM poll_votes pv 
        LEFT JOIN poll_options po ON pv.option_id = po.id 
        LEFT JOIN users u ON pv.user_id = u.id 
        WHERE pv.poll_id = ? 
        ORDER BY pv.created_at DESC 
        LIMIT 50
    ", [$pollId]);
    
    // جلب إحصائيات الأصوات حسب اليوم
    $dailyStats = $db->fetchAll("
        SELECT DATE(created_at) as vote_date, COUNT(*) as votes_count
        FROM poll_votes 
        WHERE poll_id = ? 
        GROUP BY DATE(created_at) 
        ORDER BY vote_date DESC 
        LIMIT 10
    ", [$pollId]);
    
} catch (Exception $e) {
    $_SESSION['error'] = 'حدث خطأ أثناء جلب بيانات الاستطلاع: ' . $e->getMessage();
    header('Location: polls.php');
    exit;
}

// تضمين ملف الهيدر
require_once 'header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 mb-0">نتائج الاستطلاع: <?php echo htmlspecialchars($poll['title']); ?></h2>
        <a href="polls.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> العودة إلى الاستطلاعات
        </a>
    </div>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">معلومات الاستطلاع</h5>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <th>العنوان</th>
                            <td><?php echo htmlspecialchars($poll['title']); ?></td>
                        </tr>
                        <?php if ($poll['description']): ?>
                            <tr>
                                <th>الوصف</th>
                                <td><?php echo nl2br(htmlspecialchars($poll['description'])); ?></td>
                            </tr>
                        <?php endif; ?>
                        <tr>
                            <th>الحالة</th>
                            <td>
                                <span class="badge bg-<?php echo $poll['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                    <?php echo $poll['status'] === 'active' ? 'نشط' : 'غير نشط'; ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>تاريخ البدء</th>
                            <td><?php echo $poll['start_date'] ? date('Y-m-d', strtotime($poll['start_date'])) : '-'; ?></td>
                        </tr>
                        <tr>
                            <th>تاريخ الانتهاء</th>
                            <td><?php echo $poll['end_date'] ? date('Y-m-d', strtotime($poll['end_date'])) : '-'; ?></td>
                        </tr>
                        <tr>
                            <th>تاريخ الإنشاء</th>
                            <td><?php echo date('Y-m-d H:i', strtotime($poll['created_at'])); ?></td>
                        </tr>
                        <tr>
                            <th>إجمالي الأصوات</th>
                            <td><?php echo $totalVotes; ?></td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">إحصائيات الأصوات اليومية</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($dailyStats)): ?>
                        <p class="text-center text-muted">لا توجد أصوات بعد.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>التاريخ</th>
                                        <th>عدد الأصوات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($dailyStats as $stat): ?>
                                        <tr>
                                            <td><?php echo date('Y-m-d', strtotime($stat['vote_date'])); ?></td>
                                            <td><?php echo $stat['votes_count']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">نتائج الاستطلاع</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($options)): ?>
                        <p class="text-center text-muted">لا توجد خيارات لهذا الاستطلاع.</p>
                    <?php else: ?>
                        <?php foreach ($options as $option): ?>
                            <?php 
                                $percentage = $totalVotes > 0 ? round(($option['votes_count'] / $totalVotes) * 100) : 0;
                            ?>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span><?php echo htmlspecialchars($option['option_text']); ?></span>
                                    <span><?php echo $option['votes_count']; ?> صوت (<?php echo $percentage; ?>%)</span>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar" role="progressbar" style="width: <?php echo $percentage; ?>%" 
                                         aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">آخر الأصوات</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($votes)): ?>
                        <p class="text-center text-muted">لا توجد أصوات بعد.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>المستخدم</th>
                                        <th>الخيار</th>
                                        <th>التاريخ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($votes as $vote): ?>
                                        <tr>
                                            <td>
                                                <?php if ($vote['user_id']): ?>
                                                    <?php echo htmlspecialchars($vote['full_name'] ?: $vote['email']); ?>
                                                <?php else: ?>
                                                    زائر (<?php echo htmlspecialchars($vote['voter_ip']); ?>)
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($vote['option_text']); ?></td>
                                            <td><?php echo date('Y-m-d H:i', strtotime($vote['created_at'])); ?></td>
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

<?php
// تضمين ملف الفوتر
require_once 'footer.php';
?>
