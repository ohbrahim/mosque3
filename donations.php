<?php
/**
 * صفحة التبرعات والزكاة
 */
require_once 'config/config.php';

// تسجيل زيارة الصفحة
logVisitor($db, $_SERVER['REQUEST_URI']);

// الحصول على الإعدادات
$siteName = getSetting($db, 'site_name', 'مسجد النور');
$donationGoal = getSetting($db, 'donation_goal', 100000);
$donationAccount = getSetting($db, 'donation_account', '');

// حساب إجمالي التبرعات
$totalDonations = $db->fetchOne("SELECT SUM(amount) as total FROM donations WHERE status = 'completed'")['total'] ?? 0;
$donationProgress = $donationGoal > 0 ? ($totalDonations / $donationGoal) * 100 : 0;

// آخر التبرعات
$recentDonations = $db->fetchAll("
    SELECT donor_name, amount, created_at 
    FROM donations 
    WHERE status = 'completed' AND show_name = 1 
    ORDER BY created_at DESC 
    LIMIT 10
");

// مشاريع التبرع
$donationProjects = [
    [
        'title' => 'صيانة المسجد',
        'description' => 'تجديد وصيانة المسجد وتحسين المرافق',
        'target' => 50000,
        'current' => 25000,
        'icon' => 'fas fa-mosque'
    ],
    [
        'title' => 'كفالة الأيتام',
        'description' => 'كفالة الأطفال الأيتام وتوفير احتياجاتهم',
        'target' => 30000,
        'current' => 18000,
        'icon' => 'fas fa-heart'
    ],
    [
        'title' => 'إفطار الصائم',
        'description' => 'توفير وجبات إفطار للصائمين في رمضان',
        'target' => 20000,
        'current' => 12000,
        'icon' => 'fas fa-utensils'
    ]
];

$message = '';
$error = '';

// معالجة نموذج التبرع
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'رمز الأمان غير صحيح';
    } else {
        $data = [
            'donor_name' => sanitize($_POST['donor_name']),
            'donor_email' => sanitize($_POST['donor_email']),
            'donor_phone' => sanitize($_POST['donor_phone']),
            'amount' => (float)$_POST['amount'],
            'project' => sanitize($_POST['project']),
            'message' => sanitize($_POST['message']),
            'show_name' => isset($_POST['show_name']) ? 1 : 0,
            'status' => 'pending'
        ];
        
        if (empty($data['donor_name']) || empty($data['donor_email']) || $data['amount'] <= 0) {
            $error = 'يرجى ملء جميع الحقول المطلوبة';
        } elseif (!filter_var($data['donor_email'], FILTER_VALIDATE_EMAIL)) {
            $error = 'البريد الإلكتروني غير صحيح';
        } else {
            if ($db->insert('donations', $data)) {
                $message = 'تم تسجيل تبرعك بنجاح. سنتواصل معك قريباً لإتمام العملية.';
                
                // إرسال إشعار للإدارة
                $adminEmail = getSetting($db, 'contact_email', '');
                if ($adminEmail) {
                    $subject = 'تبرع جديد - ' . $siteName;
                    $emailBody = "تبرع جديد من: {$data['donor_name']}\n";
                    $emailBody .= "المبلغ: {$data['amount']} ريال\n";
                    $emailBody .= "المشروع: {$data['project']}\n";
                    $emailBody .= "البريد الإلكتروني: {$data['donor_email']}\n";
                    $emailBody .= "الهاتف: {$data['donor_phone']}\n";
                    if ($data['message']) {
                        $emailBody .= "رسالة: {$data['message']}\n";
                    }
                    
                    sendEmail($adminEmail, $subject, $emailBody, false);
                }
            } else {
                $error = 'حدث خطأ في تسجيل التبرع. يرجى المحاولة مرة أخرى.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التبرعات والزكاة - <?php echo htmlspecialchars($siteName); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts - Arabic -->
    <link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Cairo', sans-serif;
            background-color: #f8f9fa;
        }
        
        .donations-header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 80px 0;
            text-align: center;
        }
        
        .donation-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            transition: transform 0.3s;
        }
        
        .donation-card:hover {
            transform: translateY(-5px);
        }
        
        .project-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            height: 100%;
        }
        
        .project-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            margin: 0 auto 20px;
        }
        
        .progress-container {
            margin: 20px 0;
        }
        
        .progress {
            height: 10px;
            border-radius: 5px;
        }
        
        .progress-bar {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        }
        
        .donation-form {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-top: -50px;
            position: relative;
            z-index: 2;
        }
        
        .amount-btn {
            border: 2px solid #28a745;
            color: #28a745;
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin: 5px;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .amount-btn:hover,
        .amount-btn.active {
            background: #28a745;
            color: white;
        }
        
        .recent-donations {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .donor-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .donor-item:last-child {
            border-bottom: none;
        }
        
        .donor-amount {
            color: #28a745;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <?php echo htmlspecialchars($siteName); ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-home"></i> الرئيسية
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="quran.php">
                            <i class="fas fa-book-quran"></i> القرآن الكريم
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="library.php">
                            <i class="fas fa-book"></i> المكتبة
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="donations.php">
                            <i class="fas fa-hand-holding-heart"></i> التبرعات
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">
                            <i class="fas fa-envelope"></i> اتصل بنا
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Donations Header -->
    <div class="donations-header">
        <div class="container">
            <h1 class="display-4 mb-3">التبرعات والزكاة</h1>
            <p class="lead">﴿مَّن ذَا الَّذِي يُقْرِضُ اللَّهَ قَرْضًا حَسَنًا فَيُضَاعِفَهُ لَهُ أَضْعَافًا كَثِيرَةً﴾</p>
        </div>
    </div>

    <!-- Donation Form -->
    <div class="container">
        <div class="donation-form">
            <h3 class="text-center mb-4">
                <i class="fas fa-heart text-success"></i> تبرع الآن
            </h3>
            
            <?php if ($message): ?>
                <div class="alert alert-success" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="donor_name" class="form-label">الاسم الكامل *</label>
                        <input type="text" class="form-control" id="donor_name" name="donor_name" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="donor_email" class="form-label">البريد الإلكتروني *</label>
                        <input type="email" class="form-control" id="donor_email" name="donor_email" required>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="donor_phone" class="form-label">رقم الهاتف</label>
                        <input type="tel" class="form-control" id="donor_phone" name="donor_phone">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="project" class="form-label">المشروع</label>
                        <select class="form-select" id="project" name="project">
                            <option value="عام">تبرع عام</option>
                            <option value="صيانة المسجد">صيانة المسجد</option>
                            <option value="كفالة الأيتام">كفالة الأيتام</option>
                            <option value="إفطار الصائم">إفطار الصائم</option>
                            <option value="زكاة">زكاة</option>
                            <option value="صدقة">صدقة</option>
                        </select>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">اختر المبلغ (ريال سعودي)</label>
                    <div class="row">
                        <div class="col-6 col-md-3">
                            <div class="amount-btn text-center" onclick="selectAmount(100)">
                                100 ريال
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="amount-btn text-center" onclick="selectAmount(250)">
                                250 ريال
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="amount-btn text-center" onclick="selectAmount(500)">
                                500 ريال
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="amount-btn text-center" onclick="selectAmount(1000)">
                                1000 ريال
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="amount" class="form-label">أو أدخل مبلغاً آخر *</label>
                    <input type="number" class="form-control" id="amount" name="amount" min="1" step="0.01" required>
                </div>
                
                <div class="mb-3">
                    <label for="message" class="form-label">رسالة (اختيارية)</label>
                    <textarea class="form-control" id="message" name="message" rows="3" 
                              placeholder="اكتب رسالة أو دعاء..."></textarea>
                </div>
                
                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="show_name" name="show_name" checked>
                        <label class="form-check-label" for="show_name">
                            أوافق على إظهار اسمي في قائمة المتبرعين
                        </label>
                    </div>
                </div>
                
                <div class="d-grid">
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="fas fa-heart"></i> تبرع الآن
                    </button>
                </div>
            </form>
            
            <?php if ($donationAccount): ?>
                <div class="alert alert-info mt-4">
                    <h6><i class="fas fa-university"></i> معلومات الحساب البنكي:</h6>
                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($donationAccount)); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mt-5">
        <div class="row">
            <!-- Projects -->
            <div class="col-lg-8">
                <h3 class="mb-4">مشاريع التبرع</h3>
                
                <div class="row">
                    <?php foreach ($donationProjects as $project): ?>
                        <div class="col-md-6 mb-4">
                            <div class="project-card">
                                <div class="project-icon">
                                    <i class="<?php echo $project['icon']; ?>"></i>
                                </div>
                                
                                <h5 class="text-center mb-3"><?php echo $project['title']; ?></h5>
                                <p class="text-muted text-center"><?php echo $project['description']; ?></p>
                                
                                <div class="progress-container">
                                    <?php $percentage = ($project['current'] / $project['target']) * 100; ?>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span><?php echo number_format($project['current']); ?> ريال</span>
                                        <span><?php echo number_format($project['target']); ?> ريال</span>
                                    </div>
                                    <div class="progress">
                                        <div class="progress-bar" style="width: <?php echo $percentage; ?>%"></div>
                                    </div>
                                    <div class="text-center mt-2">
                                        <small class="text-muted"><?php echo number_format($percentage, 1); ?>% مكتمل</small>
                                    </div>
                                </div>
                                
                                <div class="d-grid">
                                    <button class="btn btn-outline-success" onclick="selectProject('<?php echo $project['title']; ?>')">
                                        <i class="fas fa-hand-holding-heart"></i> تبرع لهذا المشروع
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Total Progress -->
                <div class="donation-card">
                    <h5 class="text-center mb-3">
                        <i class="fas fa-target"></i> هدف التبرعات
                    </h5>
                    
                    <div class="text-center mb-3">
                        <h3 class="text-success"><?php echo number_format($totalDonations); ?> ريال</h3>
                        <p class="text-muted">من أصل <?php echo number_format($donationGoal); ?> ريال</p>
                    </div>
                    
                    <div class="progress mb-3">
                        <div class="progress-bar" style="width: <?php echo min($donationProgress, 100); ?>%"></div>
                    </div>
                    
                    <div class="text-center">
                        <small class="text-muted"><?php echo number_format($donationProgress, 1); ?>% مكتمل</small>
                    </div>
                </div>
                
                <!-- Recent Donations -->
                <div class="recent-donations">
                    <h5 class="mb-3">
                        <i class="fas fa-users"></i> آخر المتبرعين
                    </h5>
                    
                    <?php if (empty($recentDonations)): ?>
                        <p class="text-muted text-center">لا توجد تبرعات بعد</p>
                    <?php else: ?>
                        <?php foreach ($recentDonations as $donation): ?>
                            <div class="donor-item">
                                <div>
                                    <strong><?php echo htmlspecialchars($donation['donor_name']); ?></strong>
                                    <br><small class="text-muted"><?php echo formatArabicDate($donation['created_at']); ?></small>
                                </div>
                                <div class="donor-amount">
                                    <?php echo number_format($donation['amount']); ?> ريال
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Donation Benefits -->
                <div class="donation-card">
                    <h5 class="mb-3">
                        <i class="fas fa-gift"></i> فوائد التبرع
                    </h5>
                    
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            الأجر والثواب من الله
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            تطهير المال والنفس
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            مساعدة المحتاجين
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            بناء المجتمع
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            البركة في المال
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function selectAmount(amount) {
            document.getElementById('amount').value = amount;
            
            // Remove active class from all buttons
            document.querySelectorAll('.amount-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Add active class to clicked button
            event.target.classList.add('active');
        }
        
        function selectProject(projectName) {
            document.getElementById('project').value = projectName;
            
            // Scroll to form
            document.querySelector('.donation-form').scrollIntoView({
                behavior: 'smooth'
            });
        }
    </script>
</body>
</html>
