<?php
/**
 * عارض رسائل البريد الإلكتروني المحفوظة محلياً
 */
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>رسائل البريد الإلكتروني المحفوظة</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Cairo', sans-serif;
            background: #f8f9fa;
        }
        .email-viewer {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .email-list {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .email-item {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: background 0.3s;
        }
        .email-item:hover {
            background: #f8f9fa;
        }
        .email-item:last-child {
            border-bottom: none;
        }
        .email-preview {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            min-height: 400px;
        }
        .email-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px 10px 0 0;
        }
        .email-content {
            padding: 20px;
        }
        .badge-new {
            background: #28a745;
        }
        .no-emails {
            text-align: center;
            padding: 50px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="email-viewer">
        <div class="row">
            <div class="col-md-4">
                <div class="email-list">
                    <div class="email-header">
                        <h5 class="mb-0">رسائل البريد المحفوظة</h5>
                        <small>بيئة التطوير المحلية</small>
                    </div>
                    
                    <div id="emailList">
                        <?php
                        $emailIndex = __DIR__ . '/index.json';
                        if (file_exists($emailIndex)) {
                            $emails = json_decode(file_get_contents($emailIndex), true) ?: [];
                            $emails = array_reverse($emails); // الأحدث أولاً
                            
                            if (!empty($emails)) {
                                foreach ($emails as $index => $email) {
                                    echo "
                                    <div class='email-item' onclick='loadEmail(\"{$email['id']}\")'>
                                        <div class='d-flex justify-content-between align-items-start'>
                                            <div>
                                                <h6 class='mb-1'>{$email['subject']}</h6>
                                                <small class='text-muted'>إلى: {$email['to']}</small>
                                            </div>
                                            <small class='text-muted'>{$email['date']}</small>
                                        </div>
                                    </div>
                                    ";
                                }
                            } else {
                                echo "<div class='no-emails'>لا توجد رسائل محفوظة</div>";
                            }
                        } else {
                            echo "<div class='no-emails'>لا توجد رسائل محفوظة</div>";
                        }
                        ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="email-preview">
                    <div class="email-header">
                        <h5 class="mb-0">معاينة البريد الإلكتروني</h5>
                    </div>
                    <div class="email-content">
                        <div id="emailContent" class="text-center text-muted" style="padding: 100px 20px;">
                            اختر رسالة من القائمة لعرضها
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-4">
            <a href="../auth/login.php" class="btn btn-primary">العودة لصفحة الدخول</a>
            <a href="../admin/index.php" class="btn btn-secondary">لوحة التحكم</a>
            <button onclick="clearEmails()" class="btn btn-danger">مسح جميع الرسائل</button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function loadEmail(emailId) {
            fetch(`${emailId}.html`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('emailContent').innerHTML = html;
                })
                .catch(error => {
                    document.getElementById('emailContent').innerHTML = 
                        '<div class="alert alert-danger">خطأ في تحميل البريد الإلكتروني</div>';
                });
        }
        
        function clearEmails() {
            if (confirm('هل أنت متأكد من مسح جميع الرسائل؟')) {
                fetch('clear_emails.php', {method: 'POST'})
                    .then(() => location.reload())
                    .catch(error => alert('خطأ في مسح الرسائل'));
            }
        }
    </script>
</body>
</html>
