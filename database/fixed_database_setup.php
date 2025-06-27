<?php
/**
 * ملف إعداد قاعدة البيانات المصحح
 * يعالج مشاكل الإجراءات المخزنة والمحفزات والدوال
 */

// إعدادات قاعدة البيانات
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'mosque_management';

// دالة لتنفيذ استعلام SQL
function executeQuery($pdo, $query, $description = '') {
    try {
        $pdo->exec($query);
        echo "<div style='color: green; margin: 5px 0;'>✓ تم تنفيذ: $description</div>";
        return true;
    } catch (PDOException $e) {
        echo "<div style='color: #ff6600; margin: 5px 0;'>⚠️ تحذير: $description - " . $e->getMessage() . "</div>";
        return false;
    }
}

echo "<!DOCTYPE html>
<html lang='ar' dir='rtl'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>إعداد قاعدة البيانات المصحح</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link href='https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap' rel='stylesheet'>
    <style>
        body { font-family: 'Cairo', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .setup-container { max-width: 800px; margin: 50px auto; }
        .setup-card { background: white; border-radius: 15px; padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        .step { padding: 15px; margin: 10px 0; border-radius: 8px; }
        .step.success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .step.error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .step.info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
        .progress-bar { transition: width 0.3s ease; }
        .log-container { max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #f8f9fa; margin: 20px 0; }
    </style>
</head>
<body>
    <div class='container setup-container'>
        <div class='setup-card'>
            <h2 class='text-center mb-4'>
                <i class='fas fa-database'></i>
                إعداد قاعدة البيانات المصحح - نظام إدارة المسجد
            </h2>";

try {
    // الاتصال بـ MySQL بدون تحديد قاعدة بيانات
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "<div class='step success'>
            <strong>تم الاتصال بخادم قاعدة البيانات بنجاح</strong>
          </div>";
    
    echo "<div class='log-container'>";
    
    // حذف قاعدة البيانات إذا كانت موجودة
    executeQuery($pdo, "DROP DATABASE IF EXISTS $database", "حذف قاعدة البيانات إذا كانت موجودة");
    
    // إنشاء قاعدة البيانات
    executeQuery($pdo, "CREATE DATABASE $database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", "إنشاء قاعدة البيانات");
    
    // استخدام قاعدة البيانات
    executeQuery($pdo, "USE $database", "استخدام قاعدة البيانات");
    
    // ===================================
    // جداول النظام الأساسية
    // ===================================
    
    // جدول المستخدمين
    executeQuery($pdo, "CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        phone VARCHAR(20),
        address TEXT,
        date_of_birth DATE,
        gender ENUM('male', 'female') DEFAULT 'male',
        role ENUM('admin', 'moderator', 'editor', 'teacher', 'student', 'member') DEFAULT 'member',
        status ENUM('active', 'inactive', 'banned', 'pending') DEFAULT 'active',
        avatar VARCHAR(255),
        bio TEXT,
        last_login TIMESTAMP NULL,
        email_verified BOOLEAN DEFAULT FALSE,
        phone_verified BOOLEAN DEFAULT FALSE,
        two_factor_enabled BOOLEAN DEFAULT FALSE,
        two_factor_secret VARCHAR(32),
        reset_token VARCHAR(100),
        reset_expires TIMESTAMP NULL,
        verification_token VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_username (username),
        INDEX idx_email (email),
        INDEX idx_role (role),
        INDEX idx_status (status)
    )", "إنشاء جدول المستخدمين");
    
    // جدول الصلاحيات
    executeQuery($pdo, "CREATE TABLE permissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL,
        display_name VARCHAR(100) NOT NULL,
        description TEXT,
        module VARCHAR(50) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )", "إنشاء جدول الصلاحيات");
    
    // جدول ربط المستخدمين بالصلاحيات
    executeQuery($pdo, "CREATE TABLE user_permissions (
        user_id INT,
        permission_id INT,
        granted_by INT,
        granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        expires_at TIMESTAMP NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
        FOREIGN KEY (granted_by) REFERENCES users(id),
        PRIMARY KEY (user_id, permission_id)
    )", "إنشاء جدول ربط المستخدمين بالصلاحيات");
    
    // جدول الإعدادات العامة
    executeQuery($pdo, "CREATE TABLE settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) UNIQUE NOT NULL,
        setting_value LONGTEXT,
        setting_type ENUM('text', 'textarea', 'number', 'boolean', 'json', 'image', 'file', 'color', 'date', 'time') DEFAULT 'text',
        category VARCHAR(50) DEFAULT 'general',
        display_name VARCHAR(200),
        description TEXT,
        validation_rules JSON,
        is_public BOOLEAN DEFAULT FALSE,
        sort_order INT DEFAULT 0,
        updated_by INT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (updated_by) REFERENCES users(id),
        INDEX idx_category (category),
        INDEX idx_key (setting_key)
    )", "إنشاء جدول الإعدادات العامة");
    
    // ===================================
    // جداول إدارة المحتوى
    // ===================================
    
    // جدول التصنيفات
    executeQuery($pdo, "CREATE TABLE categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        slug VARCHAR(100) UNIQUE NOT NULL,
        description TEXT,
        parent_id INT NULL,
        image VARCHAR(255),
        color VARCHAR(7) DEFAULT '#007bff',
        icon VARCHAR(50),
        sort_order INT DEFAULT 0,
        status ENUM('active', 'inactive') DEFAULT 'active',
        meta_title VARCHAR(200),
        meta_description TEXT,
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL,
        FOREIGN KEY (created_by) REFERENCES users(id),
        INDEX idx_slug (slug),
        INDEX idx_parent (parent_id),
        INDEX idx_status (status)
    )", "إنشاء جدول التصنيفات");
    
    // جدول الصفحات
    executeQuery($pdo, "CREATE TABLE pages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200) NOT NULL,
        slug VARCHAR(200) UNIQUE NOT NULL,
        content LONGTEXT,
        excerpt TEXT,
        featured_image VARCHAR(255),
        gallery JSON,
        category_id INT,
        tags JSON,
        meta_title VARCHAR(200),
        meta_description TEXT,
        meta_keywords TEXT,
        status ENUM('published', 'draft', 'private', 'scheduled') DEFAULT 'draft',
        visibility ENUM('public', 'private', 'password', 'members_only') DEFAULT 'public',
        password VARCHAR(100),
        author_id INT,
        editor_id INT,
        views_count INT DEFAULT 0,
        likes_count INT DEFAULT 0,
        shares_count INT DEFAULT 0,
        allow_comments BOOLEAN DEFAULT TRUE,
        allow_ratings BOOLEAN DEFAULT TRUE,
        is_featured BOOLEAN DEFAULT FALSE,
        is_sticky BOOLEAN DEFAULT FALSE,
        template VARCHAR(50) DEFAULT 'default',
        custom_css TEXT,
        custom_js TEXT,
        published_at TIMESTAMP NULL,
        scheduled_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
        FOREIGN KEY (author_id) REFERENCES users(id),
        FOREIGN KEY (editor_id) REFERENCES users(id),
        INDEX idx_slug (slug),
        INDEX idx_status (status),
        INDEX idx_category (category_id),
        INDEX idx_author (author_id),
        INDEX idx_featured (is_featured),
        INDEX idx_published (published_at),
        FULLTEXT idx_search (title, content, excerpt)
    )", "إنشاء جدول الصفحات");
    
    // جدول المرفقات
    executeQuery($pdo, "CREATE TABLE attachments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        filename VARCHAR(255) NOT NULL,
        original_name VARCHAR(255) NOT NULL,
        file_path VARCHAR(500) NOT NULL,
        file_size INT NOT NULL,
        mime_type VARCHAR(100) NOT NULL,
        file_type ENUM('image', 'video', 'audio', 'document', 'archive', 'other') NOT NULL,
        alt_text VARCHAR(255),
        caption TEXT,
        description TEXT,
        metadata JSON,
        uploaded_by INT,
        used_in JSON,
        download_count INT DEFAULT 0,
        is_public BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (uploaded_by) REFERENCES users(id),
        INDEX idx_type (file_type),
        INDEX idx_uploader (uploaded_by),
        INDEX idx_public (is_public)
    )", "إنشاء جدول المرفقات");
    
    // ===================================
    // جداول التفاعل والمشاركة
    // ===================================
    
    // جدول التعليقات
    executeQuery($pdo, "CREATE TABLE comments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        content_type ENUM('page', 'post', 'lesson', 'event') NOT NULL,
        content_id INT NOT NULL,
        parent_id INT NULL,
        author_name VARCHAR(100) NOT NULL,
        author_email VARCHAR(100) NOT NULL,
        author_website VARCHAR(200),
        author_ip VARCHAR(45),
        user_agent TEXT,
        content TEXT NOT NULL,
        status ENUM('pending', 'approved', 'rejected', 'spam') DEFAULT 'pending',
        is_pinned BOOLEAN DEFAULT FALSE,
        likes_count INT DEFAULT 0,
        replies_count INT DEFAULT 0,
        reported_count INT DEFAULT 0,
        moderated_by INT NULL,
        moderated_at TIMESTAMP NULL,
        moderation_reason TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (parent_id) REFERENCES comments(id) ON DELETE CASCADE,
        FOREIGN KEY (moderated_by) REFERENCES users(id),
        INDEX idx_content (content_type, content_id),
        INDEX idx_status (status),
        INDEX idx_parent (parent_id),
        INDEX idx_created (created_at)
    )", "إنشاء جدول التعليقات");
    
    // جدول التقييمات
    executeQuery($pdo, "CREATE TABLE ratings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        content_type ENUM('page', 'post', 'lesson', 'event', 'teacher') NOT NULL,
        content_id INT NOT NULL,
        user_id INT NULL,
        user_ip VARCHAR(45),
        rating TINYINT CHECK (rating >= 1 AND rating <= 5),
        review TEXT,
        is_verified BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
        UNIQUE KEY unique_rating (content_type, content_id, user_id),
        UNIQUE KEY unique_ip_rating (content_type, content_id, user_ip),
        INDEX idx_content (content_type, content_id),
        INDEX idx_rating (rating)
    )", "إنشاء جدول التقييمات");
    
    // جدول الإعجابات والمشاركات
    executeQuery($pdo, "CREATE TABLE interactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        content_type ENUM('page', 'post', 'comment', 'lesson', 'event') NOT NULL,
        content_id INT NOT NULL,
        user_id INT NULL,
        user_ip VARCHAR(45),
        interaction_type ENUM('like', 'dislike', 'share', 'bookmark', 'report') NOT NULL,
        platform VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
        UNIQUE KEY unique_interaction (content_type, content_id, user_id, interaction_type),
        INDEX idx_content (content_type, content_id),
        INDEX idx_type (interaction_type),
        INDEX idx_user (user_id)
    )", "إنشاء جدول التفاعلات");
    
    // ===================================
    // جداول التعليم القرآني
    // ===================================
    
    // جدول المعلمين
    executeQuery($pdo, "CREATE TABLE teachers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNIQUE NOT NULL,
        specialization JSON,
        qualifications TEXT,
        experience_years INT DEFAULT 0,
        bio TEXT,
        teaching_style TEXT,
        available_times JSON,
        hourly_rate DECIMAL(10,2) DEFAULT 0.00,
        rating_average DECIMAL(3,2) DEFAULT 0.00,
        total_students INT DEFAULT 0,
        total_lessons INT DEFAULT 0,
        is_verified BOOLEAN DEFAULT FALSE,
        verification_documents JSON,
        status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
        joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_status (status),
        INDEX idx_rating (rating_average),
        INDEX idx_verified (is_verified)
    )", "إنشاء جدول المعلمين");
    
    // جدول الطلاب
    executeQuery($pdo, "CREATE TABLE students (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNIQUE NOT NULL,
        guardian_name VARCHAR(100),
        guardian_phone VARCHAR(20),
        guardian_email VARCHAR(100),
        level ENUM('beginner', 'intermediate', 'advanced') DEFAULT 'beginner',
        goals TEXT,
        preferred_schedule JSON,
        medical_conditions TEXT,
        emergency_contact JSON,
        enrollment_date DATE,
        graduation_date DATE NULL,
        status ENUM('active', 'inactive', 'graduated', 'suspended') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_status (status),
        INDEX idx_level (level)
    )", "إنشاء جدول الطلاب");
    
    // جدول الدورات التعليمية
    executeQuery($pdo, "CREATE TABLE courses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200) NOT NULL,
        slug VARCHAR(200) UNIQUE NOT NULL,
        description TEXT,
        objectives TEXT,
        prerequisites TEXT,
        syllabus JSON,
        category_id INT,
        teacher_id INT,
        level ENUM('beginner', 'intermediate', 'advanced') DEFAULT 'beginner',
        duration_weeks INT DEFAULT 0,
        lessons_count INT DEFAULT 0,
        max_students INT DEFAULT 0,
        current_students INT DEFAULT 0,
        price DECIMAL(10,2) DEFAULT 0.00,
        discount_price DECIMAL(10,2) NULL,
        featured_image VARCHAR(255),
        video_preview VARCHAR(255),
        materials JSON,
        certificate_template VARCHAR(255),
        status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
        is_featured BOOLEAN DEFAULT FALSE,
        enrollment_start DATE,
        enrollment_end DATE,
        course_start DATE,
        course_end DATE,
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
        FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE SET NULL,
        FOREIGN KEY (created_by) REFERENCES users(id),
        INDEX idx_slug (slug),
        INDEX idx_status (status),
        INDEX idx_teacher (teacher_id),
        INDEX idx_level (level),
        INDEX idx_featured (is_featured)
    )", "إنشاء جدول الدورات");
    
    // جدول الدروس
    executeQuery($pdo, "CREATE TABLE lessons (
        id INT AUTO_INCREMENT PRIMARY KEY,
        course_id INT,
        title VARCHAR(200) NOT NULL,
        slug VARCHAR(200) NOT NULL,
        description TEXT,
        content LONGTEXT,
        video_url VARCHAR(500),
        audio_url VARCHAR(500),
        attachments JSON,
        duration_minutes INT DEFAULT 0,
        lesson_order INT DEFAULT 0,
        is_preview BOOLEAN DEFAULT FALSE,
        is_mandatory BOOLEAN DEFAULT TRUE,
        quiz_id INT NULL,
        homework TEXT,
        notes TEXT,
        status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
        UNIQUE KEY unique_course_slug (course_id, slug),
        INDEX idx_course (course_id),
        INDEX idx_order (lesson_order),
        INDEX idx_status (status)
    )", "إنشاء جدول الدروس");
    
    // جدول التسجيل في الدورات
    executeQuery($pdo, "CREATE TABLE course_enrollments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        course_id INT NOT NULL,
        student_id INT NOT NULL,
        enrollment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        completion_date TIMESTAMP NULL,
        progress_percentage DECIMAL(5,2) DEFAULT 0.00,
        status ENUM('enrolled', 'in_progress', 'completed', 'dropped', 'suspended') DEFAULT 'enrolled',
        payment_status ENUM('pending', 'paid', 'refunded') DEFAULT 'pending',
        payment_amount DECIMAL(10,2) DEFAULT 0.00,
        certificate_issued BOOLEAN DEFAULT FALSE,
        certificate_url VARCHAR(255),
        final_grade DECIMAL(5,2) NULL,
        notes TEXT,
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
        UNIQUE KEY unique_enrollment (course_id, student_id),
        INDEX idx_course (course_id),
        INDEX idx_student (student_id),
        INDEX idx_status (status)
    )", "إنشاء جدول التسجيل في الدورات");
    
    // جدول تقدم الطلاب في الدروس
    executeQuery($pdo, "CREATE TABLE lesson_progress (
        id INT AUTO_INCREMENT PRIMARY KEY,
        enrollment_id INT NOT NULL,
        lesson_id INT NOT NULL,
        started_at TIMESTAMP NULL,
        completed_at TIMESTAMP NULL,
        time_spent_minutes INT DEFAULT 0,
        progress_percentage DECIMAL(5,2) DEFAULT 0.00,
        status ENUM('not_started', 'in_progress', 'completed', 'skipped') DEFAULT 'not_started',
        notes TEXT,
        last_position INT DEFAULT 0,
        FOREIGN KEY (enrollment_id) REFERENCES course_enrollments(id) ON DELETE CASCADE,
        FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
        UNIQUE KEY unique_progress (enrollment_id, lesson_id),
        INDEX idx_enrollment (enrollment_id),
        INDEX idx_lesson (lesson_id),
        INDEX idx_status (status)
    )", "إنشاء جدول تقدم الطلاب في الدروس");
    
    // ===================================
    // جداول الاختبارات والتقييم
    // ===================================
    
    // جدول الاختبارات
    executeQuery($pdo, "CREATE TABLE quizzes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200) NOT NULL,
        description TEXT,
        course_id INT NULL,
        lesson_id INT NULL,
        instructions TEXT,
        time_limit_minutes INT DEFAULT 0,
        max_attempts INT DEFAULT 1,
        passing_score DECIMAL(5,2) DEFAULT 60.00,
        show_results BOOLEAN DEFAULT TRUE,
        show_correct_answers BOOLEAN DEFAULT FALSE,
        randomize_questions BOOLEAN DEFAULT FALSE,
        status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
        FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
        FOREIGN KEY (created_by) REFERENCES users(id),
        INDEX idx_course (course_id),
        INDEX idx_lesson (lesson_id),
        INDEX idx_status (status)
    )", "إنشاء جدول الاختبارات");
    
    // جدول أسئلة الاختبارات
    executeQuery($pdo, "CREATE TABLE quiz_questions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        quiz_id INT NOT NULL,
        question_text TEXT NOT NULL,
        question_type ENUM('multiple_choice', 'true_false', 'short_answer', 'essay', 'fill_blank') NOT NULL,
        options JSON,
        correct_answer TEXT,
        explanation TEXT,
        points DECIMAL(5,2) DEFAULT 1.00,
        question_order INT DEFAULT 0,
        is_required BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE,
        INDEX idx_quiz (quiz_id),
        INDEX idx_order (question_order)
    )", "إنشاء جدول أسئلة الاختبارات");
    
    // جدول محاولات الاختبارات
    executeQuery($pdo, "CREATE TABLE quiz_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        quiz_id INT NOT NULL,
        student_id INT NOT NULL,
        started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        completed_at TIMESTAMP NULL,
        time_taken_minutes INT DEFAULT 0,
        score DECIMAL(5,2) DEFAULT 0.00,
        max_score DECIMAL(5,2) DEFAULT 0.00,
        percentage DECIMAL(5,2) DEFAULT 0.00,
        status ENUM('in_progress', 'completed', 'abandoned', 'expired') DEFAULT 'in_progress',
        attempt_number INT DEFAULT 1,
        answers JSON,
        FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
        INDEX idx_quiz (quiz_id),
        INDEX idx_student (student_id),
        INDEX idx_status (status)
    )", "إنشاء جدول محاولات الاختبارات");
    
    // ===================================
    // جداول الأحداث والفعاليات
    // ===================================
    
    // جدول الأحداث
    executeQuery($pdo, "CREATE TABLE events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200) NOT NULL,
        slug VARCHAR(200) UNIQUE NOT NULL,
        description TEXT,
        content LONGTEXT,
        category_id INT,
        event_type ENUM('lecture', 'workshop', 'conference', 'competition', 'social', 'religious', 'educational') NOT NULL,
        location VARCHAR(200),
        venue_details TEXT,
        organizer_id INT,
        speakers JSON,
        featured_image VARCHAR(255),
        gallery JSON,
        start_datetime DATETIME NOT NULL,
        end_datetime DATETIME NOT NULL,
        timezone VARCHAR(50) DEFAULT 'Asia/Riyadh',
        is_all_day BOOLEAN DEFAULT FALSE,
        registration_required BOOLEAN DEFAULT FALSE,
        registration_start DATETIME NULL,
        registration_end DATETIME NULL,
        max_attendees INT DEFAULT 0,
        current_attendees INT DEFAULT 0,
        registration_fee DECIMAL(10,2) DEFAULT 0.00,
        is_online BOOLEAN DEFAULT FALSE,
        meeting_link VARCHAR(500),
        meeting_password VARCHAR(100),
        status ENUM('draft', 'published', 'cancelled', 'completed') DEFAULT 'draft',
        is_featured BOOLEAN DEFAULT FALSE,
        is_recurring BOOLEAN DEFAULT FALSE,
        recurrence_pattern JSON,
        tags JSON,
        meta_title VARCHAR(200),
        meta_description TEXT,
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
        FOREIGN KEY (organizer_id) REFERENCES users(id),
        FOREIGN KEY (created_by) REFERENCES users(id),
        INDEX idx_slug (slug),
        INDEX idx_type (event_type),
        INDEX idx_status (status),
        INDEX idx_start (start_datetime),
        INDEX idx_featured (is_featured)
    )", "إنشاء جدول الأحداث");
    
    // جدول تسجيل الحضور في الأحداث
    executeQuery($pdo, "CREATE TABLE event_registrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        event_id INT NOT NULL,
        user_id INT NULL,
        attendee_name VARCHAR(100) NOT NULL,
        attendee_email VARCHAR(100) NOT NULL,
        attendee_phone VARCHAR(20),
        registration_data JSON,
        registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        attendance_status ENUM('registered', 'confirmed', 'attended', 'no_show', 'cancelled') DEFAULT 'registered',
        payment_status ENUM('pending', 'paid', 'refunded', 'waived') DEFAULT 'pending',
        payment_amount DECIMAL(10,2) DEFAULT 0.00,
        check_in_time TIMESTAMP NULL,
        check_out_time TIMESTAMP NULL,
        feedback_rating TINYINT NULL,
        feedback_comment TEXT,
        certificate_issued BOOLEAN DEFAULT FALSE,
        notes TEXT,
        FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_event (event_id),
        INDEX idx_user (user_id),
        INDEX idx_status (attendance_status)
    )", "إنشاء جدول تسجيل الحضور في الأحداث");
    
    // ===================================
    // جداول الحجوزات والمرافق
    // ===================================
    
    // جدول المرافق والقاعات
    executeQuery($pdo, "CREATE TABLE facilities (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        facility_type ENUM('hall', 'classroom', 'library', 'office', 'outdoor', 'equipment') NOT NULL,
        capacity INT DEFAULT 0,
        location VARCHAR(200),
        floor_plan VARCHAR(255),
        images JSON,
        amenities JSON,
        equipment JSON,
        hourly_rate DECIMAL(10,2) DEFAULT 0.00,
        daily_rate DECIMAL(10,2) DEFAULT 0.00,
        booking_rules TEXT,
        availability_schedule JSON,
        is_active BOOLEAN DEFAULT TRUE,
        requires_approval BOOLEAN DEFAULT FALSE,
        advance_booking_days INT DEFAULT 30,
        cancellation_policy TEXT,
        contact_person VARCHAR(100),
        contact_phone VARCHAR(20),
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id),
        INDEX idx_type (facility_type),
        INDEX idx_active (is_active)
    )", "إنشاء جدول المرافق والقاعات");
    
    // جدول الحجوزات
    executeQuery($pdo, "CREATE TABLE bookings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        facility_id INT NOT NULL,
        user_id INT NOT NULL,
        booking_reference VARCHAR(50) UNIQUE NOT NULL,
        title VARCHAR(200) NOT NULL,
        description TEXT,
        start_datetime DATETIME NOT NULL,
        end_datetime DATETIME NOT NULL,
        attendees_count INT DEFAULT 0,
        purpose ENUM('education', 'meeting', 'event', 'worship', 'social', 'other') NOT NULL,
        special_requirements TEXT,
        setup_requirements TEXT,
        contact_person VARCHAR(100),
        contact_phone VARCHAR(20),
        contact_email VARCHAR(100),
        booking_fee DECIMAL(10,2) DEFAULT 0.00,
        security_deposit DECIMAL(10,2) DEFAULT 0.00,
        total_amount DECIMAL(10,2) DEFAULT 0.00,
        payment_status ENUM('pending', 'partial', 'paid', 'refunded') DEFAULT 'pending',
        status ENUM('pending', 'confirmed', 'cancelled', 'completed', 'no_show') DEFAULT 'pending',
        approved_by INT NULL,
        approved_at TIMESTAMP NULL,
        cancellation_reason TEXT,
        cancelled_at TIMESTAMP NULL,
        check_in_time TIMESTAMP NULL,
        check_out_time TIMESTAMP NULL,
        damage_report TEXT,
        feedback_rating TINYINT NULL,
        feedback_comment TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (facility_id) REFERENCES facilities(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (approved_by) REFERENCES users(id),
        INDEX idx_facility (facility_id),
        INDEX idx_user (user_id),
        INDEX idx_status (status),
        INDEX idx_start (start_datetime),
        INDEX idx_reference (booking_reference)
    )", "إنشاء جدول الحجوزات");
    
    // ===================================
    // جداول المالية والتبرعات
    // ===================================
    
    // جدول أنواع التبرعات
    executeQuery($pdo, "CREATE TABLE donation_categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        target_amount DECIMAL(12,2) DEFAULT 0.00,
        current_amount DECIMAL(12,2) DEFAULT 0.00,
        is_zakat BOOLEAN DEFAULT FALSE,
        is_active BOOLEAN DEFAULT TRUE,
        sort_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )", "إنشاء جدول أنواع التبرعات");
    
    // جدول التبرعات
    executeQuery($pdo, "CREATE TABLE donations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        donor_name VARCHAR(100) NOT NULL,
        donor_email VARCHAR(100),
        donor_phone VARCHAR(20),
        donor_address TEXT,
        user_id INT NULL,
        category_id INT,
        amount DECIMAL(12,2) NOT NULL,
        currency VARCHAR(3) DEFAULT 'SAR',
        donation_type ENUM('one_time', 'monthly', 'yearly') DEFAULT 'one_time',
        payment_method ENUM('cash', 'bank_transfer', 'credit_card', 'online', 'check') NOT NULL,
        payment_reference VARCHAR(100),
        is_anonymous BOOLEAN DEFAULT FALSE,
        is_zakat BOOLEAN DEFAULT FALSE,
        message TEXT,
        receipt_number VARCHAR(50) UNIQUE,
        receipt_issued BOOLEAN DEFAULT FALSE,
        receipt_sent BOOLEAN DEFAULT FALSE,
        tax_deductible BOOLEAN DEFAULT TRUE,
        status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
        processed_by INT NULL,
        processed_at TIMESTAMP NULL,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
        FOREIGN KEY (category_id) REFERENCES donation_categories(id) ON DELETE SET NULL,
        FOREIGN KEY (processed_by) REFERENCES users(id),
        INDEX idx_donor_email (donor_email),
        INDEX idx_category (category_id),
        INDEX idx_status (status),
        INDEX idx_date (created_at),
        INDEX idx_receipt (receipt_number)
    )", "إنشاء جدول التبرعات");
    
    // جدول المصروفات
    executeQuery($pdo, "CREATE TABLE expenses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200) NOT NULL,
        description TEXT,
        category ENUM('utilities', 'maintenance', 'salaries', 'supplies', 'equipment', 'events', 'charity', 'other') NOT NULL,
        amount DECIMAL(12,2) NOT NULL,
        currency VARCHAR(3) DEFAULT 'SAR',
        expense_date DATE NOT NULL,
        payment_method ENUM('cash', 'bank_transfer', 'credit_card', 'check') NOT NULL,
        vendor_name VARCHAR(100),
        vendor_contact VARCHAR(100),
        receipt_number VARCHAR(50),
        receipt_image VARCHAR(255),
        is_recurring BOOLEAN DEFAULT FALSE,
        recurrence_pattern JSON,
        approved_by INT NULL,
        approved_at TIMESTAMP NULL,
        status ENUM('pending', 'approved', 'paid', 'rejected') DEFAULT 'pending',
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (approved_by) REFERENCES users(id),
        FOREIGN KEY (created_by) REFERENCES users(id),
        INDEX idx_category (category),
        INDEX idx_date (expense_date),
        INDEX idx_status (status)
    )", "إنشاء جدول المصروفات");
    
    // ===================================
    // جداول الإشعارات والرسائل
    // ===================================
    
    // جدول الإشعارات
    executeQuery($pdo, "CREATE TABLE notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        title VARCHAR(200) NOT NULL,
        message TEXT NOT NULL,
        notification_type ENUM('info', 'success', 'warning', 'error', 'announcement') DEFAULT 'info',
        category ENUM('system', 'course', 'event', 'payment', 'booking', 'message', 'reminder') DEFAULT 'system',
        action_url VARCHAR(500),
        action_text VARCHAR(50),
        icon VARCHAR(50),
        is_read BOOLEAN DEFAULT FALSE,
        is_important BOOLEAN DEFAULT FALSE,
        expires_at TIMESTAMP NULL,
        metadata JSON,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        read_at TIMESTAMP NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_user (user_id),
        INDEX idx_read (is_read),
        INDEX idx_type (notification_type),
        INDEX idx_category (category),
        INDEX idx_created (created_at)
    )", "إنشاء جدول الإشعارات");
    
    // جدول الرسائل
    executeQuery($pdo, "CREATE TABLE messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_id INT NULL,
        recipient_id INT NULL,
        sender_name VARCHAR(100) NOT NULL,
        sender_email VARCHAR(100) NOT NULL,
        sender_phone VARCHAR(20),
        recipient_name VARCHAR(100),
        recipient_email VARCHAR(100),
        subject VARCHAR(200) NOT NULL,
        message TEXT NOT NULL,
        message_type ENUM('contact', 'support', 'complaint', 'suggestion', 'inquiry', 'internal') DEFAULT 'contact',
        priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
        status ENUM('unread', 'read', 'replied', 'closed', 'spam') DEFAULT 'unread',
        is_internal BOOLEAN DEFAULT FALSE,
        attachments JSON,
        replied_by INT NULL,
        reply_message TEXT NULL,
        replied_at TIMESTAMP NULL,
        assigned_to INT NULL,
        assigned_at TIMESTAMP NULL,
        tags JSON,
        metadata JSON,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE SET NULL,
        FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE SET NULL,
        FOREIGN KEY (replied_by) REFERENCES users(id),
        FOREIGN KEY (assigned_to) REFERENCES users(id),
        INDEX idx_sender (sender_id),
        INDEX idx_recipient (recipient_id),
        INDEX idx_status (status),
        INDEX idx_type (message_type),
        INDEX idx_created (created_at)
    )", "إنشاء جدول الرسائل");
    
    // ===================================
    // جداول المحتوى الإسلامي
    // ===================================
    
    // جدول السور القرآنية
    executeQuery($pdo, "CREATE TABLE quran_surahs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        number INT UNIQUE NOT NULL,
        name_arabic VARCHAR(50) NOT NULL,
        name_english VARCHAR(50) NOT NULL,
        name_transliteration VARCHAR(50) NOT NULL,
        revelation_type ENUM('meccan', 'medinan') NOT NULL,
        verses_count INT NOT NULL,
        words_count INT DEFAULT 0,
        letters_count INT DEFAULT 0,
        audio_url VARCHAR(255),
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )", "إنشاء جدول السور القرآنية");
    
    // جدول الآيات القرآنية
    executeQuery($pdo, "CREATE TABLE quran_verses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        surah_id INT NOT NULL,
        verse_number INT NOT NULL,
        text_arabic TEXT NOT NULL,
        text_transliteration TEXT,
        text_translation TEXT,
        audio_url VARCHAR(255),
        page_number INT,
        juz_number INT,
        hizb_number INT,
        rub_number INT,
        sajda BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (surah_id) REFERENCES quran_surahs(id) ON DELETE CASCADE,
        UNIQUE KEY unique_verse (surah_id, verse_number),
        INDEX idx_surah (surah_id),
        INDEX idx_page (page_number),
        INDEX idx_juz (juz_number),
        FULLTEXT idx_search (text_arabic, text_transliteration, text_translation)
    )", "إنشاء جدول الآيات القرآنية");
    
    // جدول الأحاديث
    executeQuery($pdo, "CREATE TABLE hadiths (
        id INT AUTO_INCREMENT PRIMARY KEY,
        book VARCHAR(100) NOT NULL,
        chapter VARCHAR(200),
        hadith_number VARCHAR(20),
        narrator VARCHAR(200),
        text_arabic TEXT NOT NULL,
        text_translation TEXT,
        grade ENUM('sahih', 'hasan', 'daif', 'mawdu') DEFAULT 'sahih',
        source VARCHAR(100),
        reference VARCHAR(200),
        tags JSON,
        audio_url VARCHAR(255),
        explanation TEXT,
        is_featured BOOLEAN DEFAULT FALSE,
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id),
        INDEX idx_book (book),
        INDEX idx_grade (grade),
        INDEX idx_featured (is_featured),
        FULLTEXT idx_search (text_arabic, text_translation, narrator)
    )", "إنشاء جدول الأحاديث");
    
    // جدول الأدعية
    executeQuery($pdo, "CREATE TABLE duas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200) NOT NULL,
        category ENUM('morning', 'evening', 'prayer', 'eating', 'travel', 'sleep', 'general') NOT NULL,
        text_arabic TEXT NOT NULL,
        text_transliteration TEXT,
        text_translation TEXT,
        source VARCHAR(200),
        reference VARCHAR(200),
        audio_url VARCHAR(255),
        benefits TEXT,
        occasions TEXT,
        is_featured BOOLEAN DEFAULT FALSE,
        sort_order INT DEFAULT 0,
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id),
        INDEX idx_category (category),
        INDEX idx_featured (is_featured),
        INDEX idx_order (sort_order)
    )", "إنشاء جدول الأدعية");
    
    // ===================================
    // جداول أوقات الصلاة والتقويم
    // ===================================
    
    // جدول أوقات الصلاة
    executeQuery($pdo, "CREATE TABLE prayer_times (
        id INT AUTO_INCREMENT PRIMARY KEY,
        date DATE NOT NULL,
        city VARCHAR(100) DEFAULT 'Riyadh',
        country VARCHAR(100) DEFAULT 'Saudi Arabia',
        latitude DECIMAL(10, 8),
        longitude DECIMAL(11, 8),
        timezone VARCHAR(50) DEFAULT 'Asia/Riyadh',
        fajr TIME NOT NULL,
        sunrise TIME NOT NULL,
        dhuhr TIME NOT NULL,
        asr TIME NOT NULL,
        maghrib TIME NOT NULL,
        isha TIME NOT NULL,
        qiyam TIME,
        calculation_method VARCHAR(50) DEFAULT 'UmmAlQura',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_date_city (date, city),
        INDEX idx_date (date),
        INDEX idx_city (city)
    )", "إنشاء جدول أوقات الصلاة");
    
    // جدول التقويم الهجري
    executeQuery($pdo, "CREATE TABLE hijri_calendar (
        id INT AUTO_INCREMENT PRIMARY KEY,
        hijri_date DATE NOT NULL,
        gregorian_date DATE NOT NULL,
        hijri_year INT NOT NULL,
        hijri_month INT NOT NULL,
        hijri_day INT NOT NULL,
        month_name_arabic VARCHAR(50) NOT NULL,
        month_name_english VARCHAR(50) NOT NULL,
        day_name_arabic VARCHAR(50) NOT NULL,
        day_name_english VARCHAR(50) NOT NULL,
        is_weekend BOOLEAN DEFAULT FALSE,
        is_holiday BOOLEAN DEFAULT FALSE,
        holiday_name VARCHAR(100),
        special_events JSON,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_hijri (hijri_date),
        UNIQUE KEY unique_gregorian (gregorian_date),
        INDEX idx_hijri_year (hijri_year),
        INDEX idx_hijri_month (hijri_month)
    )", "إنشاء جدول التقويم الهجري");
    
    // ===================================
    // جداول الإحصائيات والتحليلات
    // ===================================
    
    // جدول إحصائيات الزوار
    executeQuery($pdo, "CREATE TABLE visitor_stats (
        id INT AUTO_INCREMENT PRIMARY KEY,
        visitor_ip VARCHAR(45) NOT NULL,
        user_id INT NULL,
        session_id VARCHAR(100),
        user_agent TEXT,
        page_url VARCHAR(500) NOT NULL,
        page_title VARCHAR(200),
        referer VARCHAR(500),
        search_query VARCHAR(200),
        country VARCHAR(50),
        city VARCHAR(50),
        device_type ENUM('desktop', 'mobile', 'tablet') DEFAULT 'desktop',
        browser VARCHAR(50),
        operating_system VARCHAR(50),
        screen_resolution VARCHAR(20),
        language VARCHAR(10),
        visit_duration INT DEFAULT 0,
        page_views INT DEFAULT 1,
        bounce BOOLEAN DEFAULT TRUE,
        conversion BOOLEAN DEFAULT FALSE,
        visit_date DATE NOT NULL,
        visit_time TIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_ip (visitor_ip),
        INDEX idx_user (user_id),
        INDEX idx_date (visit_date),
        INDEX idx_page (page_url),
        INDEX idx_session (session_id)
    )", "إنشاء جدول إحصائيات الزوار");
    
    // جدول الاستطلاعات
    executeQuery($pdo, "CREATE TABLE polls (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200) NOT NULL,
        description TEXT,
        question TEXT NOT NULL,
        poll_type ENUM('single_choice', 'multiple_choice', 'rating', 'text') DEFAULT 'single_choice',
        options JSON,
        min_rating INT DEFAULT 1,
        max_rating INT DEFAULT 5,
        target_audience ENUM('all', 'members', 'students', 'teachers') DEFAULT 'all',
        start_date DATE,
        end_date DATE,
        max_votes_per_user INT DEFAULT 1,
        allow_anonymous BOOLEAN DEFAULT TRUE,
        show_results BOOLEAN DEFAULT TRUE,
        results_after_vote BOOLEAN DEFAULT FALSE,
        is_featured BOOLEAN DEFAULT FALSE,
        status ENUM('draft', 'active', 'closed', 'archived') DEFAULT 'draft',
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id),
        INDEX idx_status (status),
        INDEX idx_dates (start_date, end_date),
        INDEX idx_featured (is_featured)
    )", "إنشاء جدول الاستطلاعات");
    
    // جدول أصوات الاستطلاعات
    executeQuery($pdo, "CREATE TABLE poll_votes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        poll_id INT NOT NULL,
        user_id INT NULL,
        voter_ip VARCHAR(45),
        voter_email VARCHAR(100),
        vote_data JSON NOT NULL,
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (poll_id) REFERENCES polls(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_poll (poll_id),
        INDEX idx_user (user_id),
        INDEX idx_ip (voter_ip)
    )", "إنشاء جدول أصوات الاستطلاعات");
    
    // ===================================
    // جداول النظام والأمان
    // ===================================
    
    // جدول سجل النشاطات
    executeQuery($pdo, "CREATE TABLE activity_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        action VARCHAR(100) NOT NULL,
        description TEXT,
        table_name VARCHAR(50),
        record_id INT,
        old_values JSON,
        new_values JSON,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_user (user_id),
        INDEX idx_action (action),
        INDEX idx_table (table_name),
        INDEX idx_created (created_at)
    )", "إنشاء جدول سجل النشاطات");
    
    // جدول جلسات المستخدمين
    executeQuery($pdo, "CREATE TABLE user_sessions (
        id VARCHAR(128) PRIMARY KEY,
        user_id INT NOT NULL,
        ip_address VARCHAR(45) NOT NULL,
        user_agent TEXT,
        payload LONGTEXT NOT NULL,
        last_activity INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        expires_at TIMESTAMP NOT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_user (user_id),
        INDEX idx_last_activity (last_activity),
        INDEX idx_expires (expires_at)
    )", "إنشاء جدول جلسات المستخدمين");
    
    // جدول محاولات تسجيل الدخول
    executeQuery($pdo, "CREATE TABLE login_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(100) NOT NULL,
        ip_address VARCHAR(45) NOT NULL,
        user_agent TEXT,
        success BOOLEAN DEFAULT FALSE,
        failure_reason VARCHAR(100),
        attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_email (email),
        INDEX idx_ip (ip_address),
        INDEX idx_attempted (attempted_at)
    )", "إنشاء جدول محاولات تسجيل الدخول");
    
    // جدول النسخ الاحتياطية
    executeQuery($pdo, "CREATE TABLE backups (
        id INT AUTO_INCREMENT PRIMARY KEY,
        filename VARCHAR(255) NOT NULL,
        file_path VARCHAR(500) NOT NULL,
        file_size BIGINT NOT NULL,
        backup_type ENUM('full', 'incremental', 'differential') DEFAULT 'full',
        compression_type VARCHAR(20),
        status ENUM('in_progress', 'completed', 'failed') DEFAULT 'in_progress',
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        completed_at TIMESTAMP NULL,
        FOREIGN KEY (created_by) REFERENCES users(id),
        INDEX idx_type (backup_type),
        INDEX idx_status (status),
        INDEX idx_created (created_at)
    )", "إنشاء جدول النسخ الاحتياطية");
    
    // ===================================
    // جداول القوائم والتنقل
    // ===================================
    
    // جدول عناصر القائمة
    executeQuery($pdo, "CREATE TABLE menu_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        menu_location ENUM('header', 'footer', 'sidebar', 'mobile') DEFAULT 'header',
        parent_id INT NULL,
        title VARCHAR(100) NOT NULL,
        url VARCHAR(500),
        page_id INT NULL,
        icon VARCHAR(50),
        css_class VARCHAR(100),
        target ENUM('_self', '_blank', '_parent', '_top') DEFAULT '_self',
        sort_order INT DEFAULT 0,
        is_active BOOLEAN DEFAULT TRUE,
        visibility ENUM('public', 'logged_in', 'logged_out', 'admin_only') DEFAULT 'public',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (parent_id) REFERENCES menu_items(id) ON DELETE CASCADE,
        FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE SET NULL,
        INDEX idx_location (menu_location),
        INDEX idx_parent (parent_id),
        INDEX idx_order (sort_order),
        INDEX idx_active (is_active)
    )", "إنشاء جدول عناصر القائمة");
    
    // ===================================
    // إنشاء المشاهدات (Views)
    // ===================================
    
    executeQuery($pdo, "CREATE VIEW active_users AS
    SELECT id, username, full_name, email, role, last_login, created_at
    FROM users 
    WHERE status = 'active'", "إنشاء مشاهدة المستخدمين النشطين");
    
    executeQuery($pdo, "CREATE VIEW published_pages AS
    SELECT p.*, c.name as category_name, u.full_name as author_name
    FROM pages p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN users u ON p.author_id = u.id
    WHERE p.status = 'published'", "إنشاء مشاهدة الصفحات المنشورة");
    
    executeQuery($pdo, "CREATE VIEW upcoming_events AS
    SELECT e.*, c.name as category_name
    FROM events e
    LEFT JOIN categories c ON e.category_id = c.id
    WHERE e.status = 'published' AND e.start_datetime > NOW()", "إنشاء مشاهدة الأحداث القادمة");
    
    executeQuery($pdo, "CREATE VIEW donation_summary AS
    SELECT 
        dc.name as category_name,
        COUNT(d.id) as total_donations,
        SUM(d.amount) as total_amount,
        AVG(d.amount) as average_amount
    FROM donation_categories dc
    LEFT JOIN donations d ON dc.id = d.category_id AND d.status = 'completed'
    GROUP BY dc.id, dc.name", "إنشاء مشاهدة ملخص التبرعات");
    
    // ===================================
    // إدراج البيانات الأساسية
    // ===================================
    
    // إدراج المستخدم الإداري
    executeQuery($pdo, "INSERT INTO users (username, email, password, full_name, role, status, email_verified) VALUES 
    ('admin', 'admin@mosque.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'مدير النظام', 'admin', 'active', TRUE)", "إدراج المستخدم الإداري");
    
    // إدراج الصلاحيات الأساسية
    executeQuery($pdo, "INSERT INTO permissions (name, display_name, description, module) VALUES 
    ('admin_access', 'الوصول للوحة التحكم', 'السماح بالوصول إلى لوحة التحكم', 'admin'),
    ('manage_users', 'إدارة المستخدمين', 'إضافة وتعديل وحذف المستخدمين', 'users'),
    ('manage_pages', 'إدارة الصفحات', 'إنشاء وتعديل ونشر الصفحات', 'pages'),
    ('manage_categories', 'إدارة التصنيفات', 'إنشاء وتعديل التصنيفات', 'categories'),
    ('manage_blocks', 'إدارة البلوكات', 'إنشاء وتعديل البلوكات الجانبية', 'blocks'),
    ('manage_events', 'إدارة الأحداث', 'إنشاء وإدارة الأحداث والفعاليات', 'events'),
    ('manage_courses', 'إدارة الدورات', 'إنشاء وإدارة الدورات التعليمية', 'courses'),
    ('manage_bookings', 'إدارة الحجوزات', 'إدارة حجوزات المرافق والقاعات', 'bookings'),
    ('manage_donations', 'إدارة التبرعات', 'إدارة التبرعات والمالية', 'donations'),
    ('manage_messages', 'إدارة الرسائل', 'قراءة والرد على الرسائل', 'messages'),
    ('manage_comments', 'إدارة التعليقات', 'الموافقة على التعليقات وإدارتها', 'comments'),
    ('manage_polls', 'إدارة الاستطلاعات', 'إنشاء وإدارة الاستطلاعات', 'polls'),
    ('manage_content', 'إدارة المحتوى الإسلامي', 'إدارة القرآن والأحاديث والأدعية', 'content'),
    ('manage_facilities', 'إدارة المرافق', 'إدارة المرافق والقاعات', 'facilities'),
    ('view_stats', 'عرض الإحصائيات', 'عرض إحصائيات الموقع والتحليلات', 'statistics'),
    ('manage_settings', 'إدارة الإعدادات', 'تعديل إعدادات النظام العامة', 'settings'),
    ('manage_menus', 'إدارة القوائم', 'إدارة قوائم التنقل', 'menus'),
    ('backup_system', 'النسخ الاحتياطي', 'إنشاء واستعادة النسخ الاحتياطية', 'backup')", "إدراج الصلاحيات الأساسية");
    
    // ربط المدير بجميع الصلاحيات
    executeQuery($pdo, "INSERT INTO user_permissions (user_id, permission_id, granted_by)
    SELECT 1, id, 1 FROM permissions", "ربط المدير بجميع الصلاحيات");
    
    // إدراج الإعدادات الأساسية
    executeQuery($pdo, "INSERT INTO settings (setting_key, setting_value, setting_type, category, display_name, description, is_public) VALUES 
    ('site_name', 'مسجد النور', 'text', 'general', 'اسم الموقع', 'اسم الموقع الذي يظهر في العنوان', TRUE),
    ('site_description', 'موقع مسجد النور للتعليم القرآني والخدمات الإسلامية', 'textarea', 'general', 'وصف الموقع', 'وصف مختصر للموق', TRUE),
    ('site_keywords', 'مسجد, قرآن, تعليم, إسلام, صلاة', 'text', 'seo', 'الكلمات المفتاحية', 'الكلمات المفتاحية للموقع', FALSE),
    ('admin_email', 'admin@mosque.com', 'text', 'general', 'بريد المدير', 'البريد الإلكتروني للمدير', FALSE),
    ('contact_phone', '+966501234567', 'text', 'contact', 'رقم الهاتف', 'رقم هاتف المسجد', TRUE),
    ('contact_address', 'الرياض، المملكة العربية السعودية', 'textarea', 'contact', 'العنوان', 'عنوان المسجد', TRUE),
    ('prayer_city', 'Riyadh', 'text', 'prayer', 'المدينة', 'المدينة لحساب أوقات الصلاة', TRUE),
    ('prayer_country', 'Saudi Arabia', 'text', 'prayer', 'الدولة', 'الدولة لحساب أوقات الصلاة', TRUE),
    ('prayer_method', 'UmmAlQura', 'text', 'prayer', 'طريقة الحساب', 'طريقة حساب أوقات الصلاة', TRUE),
    ('timezone', 'Asia/Riyadh', 'text', 'general', 'المنطقة الزمنية', 'المنطقة الزمنية للموقع', TRUE),
    ('currency', 'SAR', 'text', 'financial', 'العملة', 'العملة المستخدمة في التبرعات', TRUE),
    ('enable_registrations', '1', 'boolean', 'users', 'السماح بالتسجيل', 'السماح للمستخدمين الجدد بالتسجيل', FALSE),
    ('require_email_verification', '1', 'boolean', 'users', 'تأكيد البريد الإلكتروني', 'طلب تأكيد البريد الإلكتروني عند التسجيل', FALSE),
    ('enable_comments', '1', 'boolean', 'content', 'تفعيل التعليقات', 'السماح بالتعليقات على المحتوى', TRUE),
    ('moderate_comments', '1', 'boolean', 'content', 'مراجعة التعليقات', 'مراجعة التعليقات قبل النشر', FALSE),
    ('items_per_page', '10', 'number', 'display', 'عدد العناصر في الصفحة', 'عدد العناصر المعروضة في كل صفحة', FALSE),
    ('maintenance_mode', '0', 'boolean', 'system', 'وضع الصيانة', 'تفعيل وضع الصيانة للموقع', FALSE),
    ('google_analytics', '', 'text', 'analytics', 'Google Analytics', 'معرف Google Analytics', FALSE),
    ('facebook_url', '', 'text', 'social', 'رابط فيسبوك', 'رابط صفحة فيسبوك', TRUE),
    ('twitter_url', '', 'text', 'social', 'رابط تويتر', 'رابط حساب تويتر', TRUE),
    ('youtube_url', '', 'text', 'social', 'رابط يوتيوب', 'رابط قناة يوتيوب', TRUE),
    ('instagram_url', '', 'text', 'social', 'رابط إنستغرام', 'رابط حساب إنستغرام', TRUE)", "إدراج الإعدادات الأساسية");
    
    // إدراج التصنيفات الأساسية
    executeQuery($pdo, "INSERT INTO categories (name, slug, description, icon, color) VALUES 
    ('أخبار المسجد', 'mosque-news', 'أخبار وإعلانات المسجد', 'newspaper', '#007bff'),
    ('الدروس والمحاضرات', 'lessons-lectures', 'الدروس الدينية والمحاضرات', 'book-open', '#28a745'),
    ('الأحداث والفعاليات', 'events', 'الأحداث والفعاليات المختلفة', 'calendar', '#ffc107'),
    ('التعليم القرآني', 'quran-education', 'دورات وبرامج تعليم القرآن', 'book', '#17a2b8'),
    ('الخدمات الاجتماعية', 'social-services', 'الخدمات الاجتماعية والخيرية', 'heart', '#dc3545'),
    ('الإعلانات العامة', 'announcements', 'الإعلانات والتنبيهات العامة', 'megaphone', '#6f42c1')", "إدراج التصنيفات الأساسية");
    
    // إدراج أنواع التبرعات
    executeQuery($pdo, "INSERT INTO donation_categories (name, description, target_amount, is_zakat) VALUES 
    ('تبرعات عامة', 'تبرعات عامة لدعم أنشطة المسجد', 100000.00, FALSE),
    ('زكاة المال', 'زكاة الأموال والثروات', 50000.00, TRUE),
    ('صدقة جارية', 'الصدقات الجارية والأوقاف', 200000.00, FALSE),
    ('كفالة أيتام', 'كفالة الأيتام والمحتاجين', 75000.00, FALSE),
    ('مشاريع البناء', 'مشاريع توسعة وصيانة المسجد', 500000.00, FALSE),
    ('المساعدات الطارئة', 'المساعدات في الحالات الطارئة', 25000.00, FALSE)", "إدراج أنواع التبرعات");
    
    // إدراج المرافق الأساسية
    executeQuery($pdo, "INSERT INTO facilities (name, description, facility_type, capacity, location, hourly_rate, daily_rate) VALUES 
    ('القاعة الكبرى', 'القاعة الرئيسية للمحاضرات والفعاليات الكبيرة', 'hall', 200, 'الطابق الأول', 100.00, 500.00),
    ('قاعة الاجتماعات', 'قاعة صغيرة للاجتماعات والدروس', 'classroom', 30, 'الطابق الثاني', 50.00, 200.00),
    ('المكتبة', 'مكتبة المسجد للمطالعة والبحث', 'library', 50, 'الطابق الأول', 0.00, 0.00),
    ('الساحة الخارجية', 'الساحة الخارجية للفعاليات الكبيرة', 'outdoor', 500, 'خارج المبنى', 200.00, 1000.00)", "إدراج المرافق الأساسية");
    
    // إدراج عناصر القائمة الأساسية
    executeQuery($pdo, "INSERT INTO menu_items (menu_location, title, url, icon, sort_order, visibility) VALUES 
    ('header', 'الرئيسية', '/', 'home', 1, 'public'),
    ('header', 'عن المسجد', '/about', 'info', 2, 'public'),
    ('header', 'الأحداث', '/events', 'calendar', 3, 'public'),
    ('header', 'التعليم القرآني', '/courses', 'book', 4, 'public'),
    ('header', 'المكتبة', '/library', 'book-open', 5, 'public'),
    ('header', 'التبرعات', '/donations', 'heart', 6, 'public'),
    ('header', 'اتصل بنا', '/contact', 'phone', 7, 'public'),
    ('footer', 'سياسة الخصوصية', '/privacy', 'shield', 1, 'public'),
    ('footer', 'شروط الاستخدام', '/terms', 'file-text', 2, 'public'),
    ('footer', 'خريطة الموقع', '/sitemap', 'map', 3, 'public')", "إدراج عناصر القائمة الأساسية");
    
    // إدراج بعض السور القرآنية الأساسية
    executeQuery($pdo, "INSERT INTO quran_surahs (number, name_arabic, name_english, name_transliteration, revelation_type, verses_count) VALUES 
    (1, 'الفاتحة', 'The Opening', 'Al-Fatihah', 'meccan', 7),
    (2, 'البقرة', 'The Cow', 'Al-Baqarah', 'medinan', 286),
    (3, 'آل عمران', 'The Family of Imran', 'Aal-E-Imran', 'medinan', 200),
    (112, 'الإخلاص', 'The Sincerity', 'Al-Ikhlas', 'meccan', 4),
    (113, 'الفلق', 'The Daybreak', 'Al-Falaq', 'meccan', 5),
    (114, 'الناس', 'The Mankind', 'An-Nas', 'meccan', 6)", "إدراج السور القرآنية الأساسية");
    
    // إدراج بعض الأدعية الأساسية
    executeQuery($pdo, "INSERT INTO duas (title, category, text_arabic, text_transliteration, text_translation, source) VALUES 
    ('دعاء الاستيقاظ', 'morning', 'الحمد لله الذي أحيانا بعد ما أماتنا وإليه النشور', 'Alhamdu lillahi alladhi ahyana baada ma amatana wa ilayhi an-nushur', 'الحمد لله الذي أحيانا بعد أن أماتنا وإليه النشور', 'صحيح البخاري'),
    ('دعاء النوم', 'sleep', 'باسمك اللهم أموت وأحيا', 'Bismika Allahumma amutu wa ahya', 'باسمك اللهم أموت وأحيا', 'صحيح البخاري'),
    ('دعاء الطعام', 'eating', 'بسم الله', 'Bismillah', 'بسم الله', 'سنن أبي داود'),
    ('دعاء السفر', 'travel', 'سبحان الذي سخر لنا هذا وما كنا له مقرنين وإنا إلى ربنا لمنقلبون', 'Subhana alladhi sakhkhara lana hadha wa ma kunna lahu muqrineen wa inna ila rabbina la munqalibun', 'سبحان الذي سخر لنا هذا وما كنا له مقرنين وإنا إلى ربنا لمنقلبون', 'سنن الترمذي')", "إدراج الأدعية الأساسية");
    
    echo "</div>";
    
    echo "<div class='step success'>
            <strong>تم إنشاء قاعدة البيانات الكاملة بنجاح! 🎉</strong>
            <br><small>تم إنشاء جميع الجداول والبيانات الأساسية</small>
          </div>";
    
    echo "<div class='alert alert-info mt-4'>
            <h5>بيانات تسجيل الدخول:</h5>
            <p><strong>اسم المستخدم:</strong> admin</p>
            <p><strong>كلمة المرور:</strong> admin123</p>
          </div>";
    
    echo "<div class='alert alert-success mt-4'>
            <h5>الخطوات التالية:</h5>
            <ol>
                <li>قم بزيارة <a href='login.php'>صفحة تسجيل الدخول</a></li>
                <li>سجل الدخول باستخدام البيانات أعلاه</li>
                <li>ادخل إلى <a href='admin/'>لوحة التحكم</a></li>
                <li>ابدأ في تخصيص الموقع حسب احتياجاتك</li>
            </ol>
          </div>";
    
} catch (PDOException $e) {
    echo "<div class='step error'>
            <strong>خطأ في الاتصال بقاعدة البيانات:</strong><br>
            " . $e->getMessage() . "
          </div>";
}

echo "        </div>
    </div>
</body>
</html>";
?>
