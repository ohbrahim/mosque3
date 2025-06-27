<?php
/**
 * دوال المصادقة والتسجيل
 */

require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/email_functions.php';

/**
 * تسجيل دخول المستخدم
 */
function loginUser($username, $password, $remember = false) {
    global $db;
    
    try {
        // البحث عن المستخدم
        $user = $db->fetchOne(
            "SELECT * FROM users WHERE (username = ? OR email = ?) AND status = 'active'", 
            [$username, $username]
        );
        
        if (!$user) {
            return ['success' => false, 'message' => 'اسم المستخدم أو البريد الإلكتروني غير صحيح'];
        }
        
        // التحقق من كلمة المرور
        if (!verifyPassword($password, $user['password'])) {
            return ['success' => false, 'message' => 'كلمة المرور غير صحيحة'];
        }
        
        // إلغاء التحقق من تفعيل البريد الإلكتروني
        // if (!$user['email_verified']) {
        //     return ['success' => false, 'message' => 'يرجى تفعيل حسابك عبر البريد الإلكتروني أولاً'];
        // }
        
        // إنشاء الجلسة
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['email'] = $user['email'];
        
        // تحديث آخر تسجيل دخول
        $db->update('users', [
            'last_login' => date('Y-m-d H:i:s')
        ], 'id = ?', [$user['id']]);
        
        // إعداد Remember Me إذا طُلب
        if ($remember) {
            $token = generateToken();
            setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/'); // 30 يوم
            
            $db->update('users', [
                'remember_token' => hash('sha256', $token)
            ], 'id = ?', [$user['id']]);
        }
        
        return ['success' => true, 'message' => 'تم تسجيل الدخول بنجاح'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'حدث خطأ في النظام'];
    }
}

/**
 * تسجيل مستخدم جديد
 */
function registerUser($data) {
    global $db;
    
    try {
        // التحقق من البيانات
        $validation = validateRegistrationData($data);
        if (!$validation['valid']) {
            return ['success' => false, 'message' => $validation['message']];
        }
        
        // التحقق من عدم وجود المستخدم مسبقاً
        $existingUser = $db->fetchOne(
            "SELECT id FROM users WHERE username = ? OR email = ?", 
            [$data['username'], $data['email']]
        );
        
        if ($existingUser) {
            return ['success' => false, 'message' => 'اسم المستخدم أو البريد الإلكتروني مستخدم مسبقاً'];
        }
        
        // التحقق من طلبات التسجيل المعلقة
        $existingRequest = $db->fetchOne(
            "SELECT id FROM registration_requests WHERE username = ? OR email = ? AND status = 'pending'", 
            [$data['username'], $data['email']]
        );
        
        if ($existingRequest) {
            return ['success' => false, 'message' => 'يوجد طلب تسجيل معلق بنفس البيانات'];
        }
        
        // إنشاء رمز التفعيل
        $verificationToken = generateToken();
        
        // حفظ طلب التسجيل
        $requestId = $db->insert('registration_requests', [
            'full_name' => $data['full_name'],
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => hashPassword($data['password']),
            'phone' => $data['phone'] ?? '',
            'address' => $data['address'] ?? '',
            'verification_token' => $verificationToken,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        // إرسال بريد التفعيل
        $emailSent = sendVerificationEmail($data['email'], $data['full_name'], $verificationToken);
        
        // إشعار الإدارة
        notifyAdminNewRegistration($data);
        
        if (isLocalEnvironment()) {
            return [
                'success' => true, 
                'message' => 'تم إرسال طلب التسجيل بنجاح! في بيئة التطوير، يمكنك تفعيل الحساب من أدوات التطوير.'
            ];
        } else {
            return [
                'success' => true, 
                'message' => 'تم إرسال طلب التسجيل بنجاح! يرجى التحقق من بريدك الإلكتروني لتفعيل الحساب.'
            ];
        }
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'حدث خطأ في النظام: ' . $e->getMessage()];
    }
}

/**
 * التحقق من صحة بيانات التسجيل
 */
function validateRegistrationData($data) {
    // التحقق من الحقول المطلوبة
    $required = ['full_name', 'username', 'email', 'password', 'confirm_password'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            return ['valid' => false, 'message' => 'جميع الحقول مطلوبة'];
        }
    }
    
    // التحقق من البريد الإلكتروني
    if (!validateEmail($data['email'])) {
        return ['valid' => false, 'message' => 'البريد الإلكتروني غير صحيح'];
    }
    
    // التحقق من تطابق كلمات المرور
    if ($data['password'] !== $data['confirm_password']) {
        return ['valid' => false, 'message' => 'كلمات المرور غير متطابقة'];
    }
    
    // التحقق من قوة كلمة المرور
    if (!validatePassword($data['password'])) {
        return ['valid' => false, 'message' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل وتحتوي على حرف كبير وصغير ورقم'];
    }
    
    // التحقق من اسم المستخدم
    if (strlen($data['username']) < 3) {
        return ['valid' => false, 'message' => 'اسم المستخدم يجب أن يكون 3 أحرف على الأقل'];
    }
    
    return ['valid' => true];
}

/**
 * طلب إعادة تعيين كلمة المرور
 */
function requestPasswordReset($email) {
    global $db;
    
    try {
        // البحث عن المستخدم
        $user = $db->fetchOne("SELECT * FROM users WHERE email = ? AND status = 'active'", [$email]);
        
        if (!$user) {
            return ['success' => false, 'message' => 'البريد الإلكتروني غير مسجل'];
        }
        
        // إنشاء رمز الإعادة
        $resetToken = generateToken();
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // حفظ الرمز
        $db->update('users', [
            'reset_token' => $resetToken,
            'reset_token_expires' => $expiresAt
        ], 'id = ?', [$user['id']]);
        
        // إرسال البريد
        $emailSent = sendPasswordResetEmail($user['email'], $user['full_name'], $resetToken);
        
        if (isLocalEnvironment()) {
            return [
                'success' => true, 
                'message' => 'تم إنشاء رابط إعادة التعيين! في بيئة التطوير، تحقق من عارض الرسائل.'
            ];
        } else {
            return [
                'success' => true, 
                'message' => 'تم إرسال رابط إعادة تعيين كلمة المرور إلى بريدك الإلكتروني'
            ];
        }
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'حدث خطأ في النظام'];
    }
}

/**
 * إعادة تعي��ن كلمة المرور
 */
function resetPassword($token, $newPassword) {
    global $db;
    
    try {
        // البحث عن المستخدم بالرمز
        $user = $db->fetchOne(
            "SELECT * FROM users WHERE reset_token = ? AND reset_token_expires > NOW()", 
            [$token]
        );
        
        if (!$user) {
            return ['success' => false, 'message' => 'رابط إعادة التعيين غير صحيح أو منتهي الصلاحية'];
        }
        
        // التحقق من قوة كلمة المرور
        if (!validatePassword($newPassword)) {
            return ['success' => false, 'message' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل وتحتوي على حرف كبير وصغير ورقم'];
        }
        
        // تحديث كلمة المرور
        $db->update('users', [
            'password' => hashPassword($newPassword),
            'reset_token' => null,
            'reset_token_expires' => null
        ], 'id = ?', [$user['id']]);
        
        // تسجيل النشاط
        logActivity($user['id'], 'password_reset', 'تم إعادة تعيين كلمة المرور');
        
        return ['success' => true, 'message' => 'تم تحديث كلمة المرور بنجاح'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'حدث خطأ في النظام'];
    }
}

/**
 * تفعيل البريد الإلكتروني
 */
function verifyEmail($token) {
    global $db;
    
    try {
        // البحث عن طلب التسجيل
        $request = $db->fetchOne(
            "SELECT * FROM registration_requests WHERE verification_token = ? AND status = 'pending'", 
            [$token]
        );
        
        if (!$request) {
            return ['success' => false, 'message' => 'رابط التفعيل غير صحيح أو منتهي الصلاحية'];
        }
        
        // تحديث حالة الطلب
        $db->update('registration_requests', [
            'status' => 'email_verified'
        ], 'id = ?', [$request['id']]);
        
        return ['success' => true, 'message' => 'تم تفعيل بريدك الإلكتروني بنجاح! سيتم مراجعة طلبك من قبل الإدارة'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'حدث خطأ في النظام'];
    }
}

/**
 * تسجيل خروج المستخدم
 */
function logoutUser() {
    // حذف remember token إذا وجد
    if (isset($_COOKIE['remember_token'])) {
        setcookie('remember_token', '', time() - 3600, '/');
    }
    
    // تسجيل النشاط
    if (isset($_SESSION['user_id'])) {
        logActivity($_SESSION['user_id'], 'logout', 'تسجيل خروج');
    }
    
    // إنهاء الجلسة
    session_destroy();
    
    return ['success' => true, 'message' => 'تم تسجيل الخروج بنجاح'];
}

/**
 * التحقق من Remember Me
 */
function checkRememberMe() {
    global $db;
    
    if (!isset($_COOKIE['remember_token']) || isLoggedIn()) {
        return false;
    }
    
    $token = $_COOKIE['remember_token'];
    $hashedToken = hash('sha256', $token);
    
    $user = $db->fetchOne(
        "SELECT * FROM users WHERE remember_token = ? AND status = 'active'", 
        [$hashedToken]
    );
    
    if ($user) {
        // إنشاء الجلسة
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['email'] = $user['email'];
        
        return true;
    }
    
    // حذف الكوكي إذا كان غير صحيح
    setcookie('remember_token', '', time() - 3600, '/');
    return false;
}
?>
