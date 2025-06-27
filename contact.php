<?php
require_once 'config/config.php';

// معالجة نموذج الاتصال
$success = false;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message']) && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $subject = sanitize($_POST['subject'] ?? '');
    $message = sanitize($_POST['message'] ?? '');
    
    // التحقق من البيانات
    if (empty($name) || empty($email) || empty($message)) {
        $error = 'يرجى ملء جميع الحقول المطلوبة.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'يرجى إدخال بريد إلكتروني صحيح.';
    } else {
        try {
            // إضافة الرسالة إلى قاعدة البيانات
            $db->insert('messages', [
                'sender_name' => $name,        // تم تغيير 'name' إلى 'sender_name'
                'sender_email' => $email,      // تم تغيير 'email' إلى 'sender_email'
                'sender_phone' => $phone,      // تم تغيير 'phone' إلى 'sender_phone'
                'subject' => $subject,
                'message' => $message,
                'status' => 'unread',
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            // إرسال بريد إلكتروني للإدارة (اختياري)
            $adminEmail = getSetting($db, 'admin_email');
            if ($adminEmail && function_exists('mail')) {
                $emailSubject = 'رسالة جديدة من موقع ' . getSetting($db, 'site_name', 'المسجد');
                $emailBody = "
                    رسالة جديدة من موقع المسجد:
                    
                    الاسم: $name
                    البريد الإلكتروني: $email
                    الهاتف: $phone
                    الموضوع: $subject
                    
                    الرسالة:
                    $message
                ";
                
                mail($adminEmail, $emailSubject, $emailBody, "From: $email");
            }
            
            $success = true;
        } catch (Exception $e) {
            $error = 'حدث خطأ أثناء إرسال الرسالة. يرجى المحاولة مرة أخرى.';
        }
    }
}

// جلب معلومات الاتصال
$siteName = getSetting($db, 'site_name', 'مسجد النور');
$mosqueAddress = getSetting($db, 'mosque_address', 'عنوان المسجد');
$mosquePhone = getSetting($db, 'mosque_phone', '0123456789');
$mosqueEmail = getSetting($db, 'mosque_email', 'info@mosque.com');
$facebookUrl = getSetting($db, 'facebook_url');
$twitterUrl = getSetting($db, 'twitter_url');
$instagramUrl = getSetting($db, 'instagram_url');
$youtubeUrl = getSetting($db, 'youtube_url');
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>اتصل بنا - <?php echo htmlspecialchars($siteName); ?></title>
    <meta name="description" content="تواصل معنا في <?php echo htmlspecialchars($siteName); ?>">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts - Arabic -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Cairo', sans-serif;
            line-height: 1.6;
            background-color: #f8f9fa;
        }
        
        .navbar {
            background: linear-gradient(135deg, #2c5530 0%, #1e3a1e 100%);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
        }
        
        .hero-section {
            background: linear-gradient(135deg, #2c5530 0%, #1e3a1e 100%);
            color: white;
            padding: 80px 0;
            text-align: center;
        }
        
        .hero-section h1 {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 20px;
        }
        
        .contact-section {
            padding: 80px 0;
        }
        
        .contact-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 40px;
            margin-bottom: 30px;
            transition: all 0.3s;
        }
        
        .contact-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }
        
        .contact-info-item {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            transition: all 0.3s;
        }
        
        .contact-info-item:hover {
            background: #e9ecef;
            transform: translateX(10px);
        }
        
        .contact-info-item i {
            font-size: 2rem;
            color: #2c5530;
            margin-left: 20px;
            width: 60px;
            text-align: center;
        }
        
        .contact-info-item .info {
            flex: 1;
        }
        
        .contact-info-item h5 {
            margin-bottom: 5px;
            color: #2c5530;
            font-weight: 600;
        }
        
        .contact-info-item p {
            margin: 0;
            color: #666;
        }
        
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 15px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: #2c5530;
            box-shadow: 0 0 0 0.2rem rgba(44, 85, 48, 0.25);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #2c5530 0%, #1e3a1e 100%);
            border: none;
            border-radius: 10px;
            padding: 15px 30px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(44, 85, 48, 0.3);
        }
        
        .social-links {
            text-align: center;
            margin-top: 40px;
        }
        
        .social-links a {
            display: inline-block;
            width: 60px;
            height: 60px;
            line-height: 60px;
            margin: 0 10px;
            border-radius: 50%;
            color: white;
            font-size: 1.5rem;
            transition: all 0.3s;
            text-decoration: none;
        }
        
        .social-links a.facebook {
            background: #3b5998;
        }
        
        .social-links a.twitter {
            background: #1da1f2;
        }
        
        .social-links a.instagram {
            background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%);
        }
        
        .social-links a.youtube {
            background: #ff0000;
        }
        
        .social-links a:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        
        .map-container {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .footer {
            background: #2c5530;
            color: white;
            padding: 50px 0 30px;
            margin-top: 80px;
        }
        
        .footer h5 {
            margin-bottom: 20px;
        }
        
        .footer a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .footer a:hover {
            color: white;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include 'includes/ui/header.php'; ?>
	
    <!-- Hero Section -->
    <div class="hero-section">
        <div class="container">
            <h1>تواصل معنا</h1>
            <p>نحن هنا للإجابة على استفساراتكم وخدمتكم</p>
        </div>
    </div>

    <!-- Contact Section -->
    <div class="contact-section">
        <div class="container">
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    تم إرسال رسالتك بنجاح! سنتواصل معك قريباً.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-lg-8">
                    <div class="contact-card">
                        <h2 class="mb-4">أرسل لنا رسالة</h2>
                        
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">الاسم الكامل <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">البريد الإلكتروني <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">رقم الهاتف</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="subject" class="form-label">الموضوع</label>
                                    <select class="form-select" id="subject" name="subject">
                                        <option value="">-- اختر الموضوع --</option>
                                        <option value="استفسار عام" <?php echo (isset($_POST['subject']) && $_POST['subject'] === 'استفسار عام') ? 'selected' : ''; ?>>استفسار عام</option>
                                        <option value="أوقات الصلاة" <?php echo (isset($_POST['subject']) && $_POST['subject'] === 'أوقات الصلاة') ? 'selected' : ''; ?>>أوقات الصلاة</option>
                                        <option value="الدروس والمحاضرات" <?php echo (isset($_POST['subject']) && $_POST['subject'] === 'الدروس والمحاضرات') ? 'selected' : ''; ?>>الدروس والمحاضرات</option>
                                        <option value="الفعاليات" <?php echo (isset($_POST['subject']) && $_POST['subject'] === 'الفعاليات') ? 'selected' : ''; ?>>الفعاليات</option>
                                        <option value="التبرعات" <?php echo (isset($_POST['subject']) && $_POST['subject'] === 'التبرعات') ? 'selected' : ''; ?>>التبرعات</option>
                                        <option value="شكوى أو اقتراح" <?php echo (isset($_POST['subject']) && $_POST['subject'] === 'شكوى أو اقتراح') ? 'selected' : ''; ?>>شكوى أو اقتراح</option>
                                        <option value="أخرى" <?php echo (isset($_POST['subject']) && $_POST['subject'] === 'أخرى') ? 'selected' : ''; ?>>أخرى</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="message" class="form-label">الرسالة <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="message" name="message" rows="6" 
                                          placeholder="اكتب رسالتك هنا..." required><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                            </div>
                            
                            <button type="submit" name="send_message" class="btn btn-primary btn-lg">
                                <i class="fas fa-paper-plane me-2"></i>
                                إرسال الرسالة
                            </button>
                        </form>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="contact-card">
                        <h3 class="mb-4">معلومات الاتصال</h3>
                        
                        <div class="contact-info-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <div class="info">
                                <h5>العنوان</h5>
                                <p><?php echo htmlspecialchars($mosqueAddress); ?></p>
                            </div>
                        </div>
                        
                        <div class="contact-info-item">
                            <i class="fas fa-phone"></i>
                            <div class="info">
                                <h5>الهاتف</h5>
                                <p><a href="tel:<?php echo htmlspecialchars($mosquePhone); ?>"><?php echo htmlspecialchars($mosquePhone); ?></a></p>
                            </div>
                        </div>
                        
                        <div class="contact-info-item">
                            <i class="fas fa-envelope"></i>
                            <div class="info">
                                <h5>البريد الإلكتروني</h5>
                                <p><a href="mailto:<?php echo htmlspecialchars($mosqueEmail); ?>"><?php echo htmlspecialchars($mosqueEmail); ?></a></p>
                            </div>
                        </div>
                        
                        <div class="contact-info-item">
                            <i class="fas fa-clock"></i>
                            <div class="info">
                                <h5>أوقات العمل</h5>
                                <p>يومياً من الفجر إلى العشاء<br>
                                الجمعة: من الفجر إلى صلاة الجمعة</p>
                            </div>
                        </div>
                        
                        <?php if ($facebookUrl || $twitterUrl || $instagramUrl || $youtubeUrl): ?>
                            <div class="social-links">
                                <h5 class="mb-3">تابعونا على</h5>
                                
                                <?php if ($facebookUrl): ?>
                                    <a href="<?php echo htmlspecialchars($facebookUrl); ?>" target="_blank" class="facebook">
                                        <i class="fab fa-facebook-f"></i>
                                    </a>
                                <?php endif; ?>
                                
                                <?php if ($twitterUrl): ?>
                                    <a href="<?php echo htmlspecialchars($twitterUrl); ?>" target="_blank" class="twitter">
                                        <i class="fab fa-twitter"></i>
                                    </a>
                                <?php endif; ?>
                                
                                <?php if ($instagramUrl): ?>
                                    <a href="<?php echo htmlspecialchars($instagramUrl); ?>" target="_blank" class="instagram">
                                        <i class="fab fa-instagram"></i>
                                    </a>
                                <?php endif; ?>
                                
                                <?php if ($youtubeUrl): ?>
                                    <a href="<?php echo htmlspecialchars($youtubeUrl); ?>" target="_blank" class="youtube">
                                        <i class="fab fa-youtube"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Map Section -->
            <div class="row mt-5">
                <div class="col-12">
                    <div class="map-container">
                        <iframe src="https://www.google.com/maps/embed?pb=!1m14!1m12!1m3!1d711.7074630538716!2d5.265756500743475!3d31.948135218048296!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!5e0!3m2!1sfr!2sus!4v1750025676461!5m2!1sfr!2sus" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
<?php include 'includes/ui/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
