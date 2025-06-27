<?php
/**
 * صفحة الصيانة
 */
require_once 'config/config.php';

// التحقق من وضع الصيانة
if (getSetting($db, 'maintenance_mode', '0') !== '1') {
    header('Location: index.php');
    exit;
}

// السماح للمدراء بالدخول
if ($auth->isLoggedIn() && $auth->hasRole('admin')) {
    header('Location: index.php');
    exit;
}

$siteName = getSetting($db, 'site_name', 'مسجد النور');
$contactEmail = getSetting($db, 'contact_email', 'info@mosque.com');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الموقع تحت الصيانة - <?php echo htmlspecialchars($siteName); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts - Arabic -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Cairo', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        
        .maintenance-container {
            text-align: center;
            max-width: 600px;
            padding: 40px;
        }
        
        .maintenance-icon {
            font-size: 6rem;
            margin-bottom: 30px;
            opacity: 0.8;
        }
        
        .maintenance-title {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 20px;
        }
        
        .maintenance-message {
            font-size: 1.2rem;
            margin-bottom: 30px;
            opacity: 0.9;
        }
        
        .contact-info {
            background: rgba(255,255,255,0.1);
            border-radius: 15px;
            padding: 20px;
            margin-top: 30px;
        }
        
        .contact-info a {
            color: white;
            text-decoration: none;
        }
        
        .contact-info a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="maintenance-container">
        <div class="maintenance-icon">
            <i class="fas fa-tools"></i>
        </div>
        
        <h1 class="maintenance-title">الموقع تحت الصيانة</h1>
        
        <p class="maintenance-message">
            نعتذر عن الإزعاج. نحن نعمل حالياً على تحسين الموقع لتقديم خدمة أفضل لكم.
            <br>
            سنعود قريباً بإذن الله.
        </p>
        
        <div class="contact-info">
            <h5>للاستفسارات والطوارئ</h5>
            <p>
                <i class="fas fa-envelope me-2"></i>
                <a href="mailto:<?php echo htmlspecialchars($contactEmail); ?>">
                    <?php echo htmlspecialchars($contactEmail); ?>
                </a>
            </p>
        </div>
    </div>
</body>
</html>
