-- قاعدة بيانات نظام إدارة المسجد الشاملة
-- إنشاء قاعدة البيانات
DROP DATABASE IF EXISTS mosque_management;
CREATE DATABASE mosque_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mosque_management;

-- ===================================
-- جداول النظام الأساسية
-- ===================================

-- جدول المستخدمين
CREATE TABLE users (
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
);

-- جدول الصلاحيات
CREATE TABLE permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    description TEXT,
    module VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- جدول ربط المستخدمين بالصلاحيات
CREATE TABLE user_permissions (
    user_id INT,
    permission_id INT,
    granted_by INT,
    granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    FOREIGN KEY (granted_by) REFERENCES users(id),
    PRIMARY KEY (user_id, permission_id)
);

-- جدول الإعدادات العامة
CREATE TABLE settings (
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
);

-- ===================================
-- جداول إدارة المحتوى
-- ===================================

-- جدول التصنيفات
CREATE TABLE categories (
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
);

-- جدول الصفحات
CREATE TABLE pages (
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
);

-- جدول المرفقات
CREATE TABLE attachments (
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
    used_in JSON, -- Array of table:id references
    download_count INT DEFAULT 0,
    is_public BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by) REFERENCES users(id),
    INDEX idx_type (file_type),
    INDEX idx_uploader (uploaded_by),
    INDEX idx_public (is_public)
);

-- ===================================
-- جداول التفاعل والمشاركة
-- ===================================

-- جدول التعليقات
CREATE TABLE comments (
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
);

-- جدول التقييمات
CREATE TABLE ratings (
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
);

-- جدول الإعجابات والمشاركات
CREATE TABLE interactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    content_type ENUM('page', 'post', 'comment', 'lesson', 'event') NOT NULL,
    content_id INT NOT NULL,
    user_id INT NULL,
    user_ip VARCHAR(45),
    interaction_type ENUM('like', 'dislike', 'share', 'bookmark', 'report') NOT NULL,
    platform VARCHAR(50), -- For shares: facebook, twitter, whatsapp, etc.
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_interaction (content_type, content_id, user_id, interaction_type),
    INDEX idx_content (content_type, content_id),
    INDEX idx_type (interaction_type),
    INDEX idx_user (user_id)
);

-- ===================================
-- جداول التعليم القرآني
-- ===================================

-- جدول المعلمين
CREATE TABLE teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE NOT NULL,
    specialization JSON, -- Array of specializations
    qualifications TEXT,
    experience_years INT DEFAULT 0,
    bio TEXT,
    teaching_style TEXT,
    available_times JSON, -- Schedule availability
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
);

-- جدول الطلاب
CREATE TABLE students (
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
);

-- جدول الدورات التعليمية
CREATE TABLE courses (
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
    materials JSON, -- Course materials and resources
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
);

-- جدول الدروس
CREATE TABLE lessons (
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
);

-- جدول التسجيل في الدورات
CREATE TABLE course_enrollments (
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
);

-- جدول تقدم الطلاب في الدروس
CREATE TABLE lesson_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    enrollment_id INT NOT NULL,
    lesson_id INT NOT NULL,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    time_spent_minutes INT DEFAULT 0,
    progress_percentage DECIMAL(5,2) DEFAULT 0.00,
    status ENUM('not_started', 'in_progress', 'completed', 'skipped') DEFAULT 'not_started',
    notes TEXT,
    last_position INT DEFAULT 0, -- For video/audio position
    FOREIGN KEY (enrollment_id) REFERENCES course_enrollments(id) ON DELETE CASCADE,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
    UNIQUE KEY unique_progress (enrollment_id, lesson_id),
    INDEX idx_enrollment (enrollment_id),
    INDEX idx_lesson (lesson_id),
    INDEX idx_status (status)
);

-- ===================================
-- جداول الاختبارات والتقييم
-- ===================================

-- جدول الاختبارات
CREATE TABLE quizzes (
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
);

-- جدول أسئلة الاختبارات
CREATE TABLE quiz_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT NOT NULL,
    question_text TEXT NOT NULL,
    question_type ENUM('multiple_choice', 'true_false', 'short_answer', 'essay', 'fill_blank') NOT NULL,
    options JSON, -- For multiple choice questions
    correct_answer TEXT,
    explanation TEXT,
    points DECIMAL(5,2) DEFAULT 1.00,
    question_order INT DEFAULT 0,
    is_required BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE,
    INDEX idx_quiz (quiz_id),
    INDEX idx_order (question_order)
);

-- جدول محاولات الاختبارات
CREATE TABLE quiz_attempts (
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
);

-- ===================================
-- جداول الأحداث والفعاليات
-- ===================================

-- جدول الأحداث
CREATE TABLE events (
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
    speakers JSON, -- Array of speaker information
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
);

-- جدول تسجيل الحضور في الأحداث
CREATE TABLE event_registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    user_id INT NULL,
    attendee_name VARCHAR(100) NOT NULL,
    attendee_email VARCHAR(100) NOT NULL,
    attendee_phone VARCHAR(20),
    registration_data JSON, -- Additional form data
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
);

-- ===================================
-- جداول الحجوزات والمرافق
-- ===================================

-- جدول المرافق والقاعات
CREATE TABLE facilities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    facility_type ENUM('hall', 'classroom', 'library', 'office', 'outdoor', 'equipment') NOT NULL,
    capacity INT DEFAULT 0,
    location VARCHAR(200),
    floor_plan VARCHAR(255),
    images JSON,
    amenities JSON, -- Available amenities
    equipment JSON, -- Available equipment
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
);

-- جدول الحجوزات
CREATE TABLE bookings (
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
);

-- ===================================
-- جداول المالية والتبرعات
-- ===================================

-- جدول أنواع التبرعات
CREATE TABLE donation_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    target_amount DECIMAL(12,2) DEFAULT 0.00,
    current_amount DECIMAL(12,2) DEFAULT 0.00,
    is_zakat BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- جدول التبرعات
CREATE TABLE donations (
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
);

-- جدول المصروفات
CREATE TABLE expenses (
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
);

-- ===================================
-- جداول الإشعارات والرسائل
-- ===================================

-- جدول الإشعارات
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL, -- NULL for system-wide notifications
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
);

-- جدول الرسائل
CREATE TABLE messages (
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
);

-- ===================================
-- جداول المحتوى الإسلامي
-- ===================================

-- جدول السور القرآنية
CREATE TABLE quran_surahs (
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
);

-- جدول الآيات القرآنية
CREATE TABLE quran_verses (
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
);

-- جدول الأحاديث
CREATE TABLE hadiths (
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
);

-- جدول الأدعية
CREATE TABLE duas (
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
);

-- ===================================
-- جداول أوقات الصلاة والتقويم
-- ===================================

-- جدول أوقات الصلاة
CREATE TABLE prayer_times (
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
);

-- جدول التقويم الهجري
CREATE TABLE hijri_calendar (
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
);

-- ===================================
-- جداول الإحصائيات والتحليلات
-- ===================================

-- جدول إحصائيات الزوار
CREATE TABLE visitor_stats (
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
);

-- جدول الاستطلاعات
CREATE TABLE polls (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    question TEXT NOT NULL,
    poll_type ENUM('single_choice', 'multiple_choice', 'rating', 'text') DEFAULT 'single_choice',
    options JSON, -- Poll options for choice types
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
);

-- جدول أصوات الاستطلاعات
CREATE TABLE poll_votes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    poll_id INT NOT NULL,
    user_id INT NULL,
    voter_ip VARCHAR(45),
    voter_email VARCHAR(100),
    vote_data JSON NOT NULL, -- Stores the actual vote (option, rating, text)
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (poll_id) REFERENCES polls(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_poll (poll_id),
    INDEX idx_user (user_id),
    INDEX idx_ip (voter_ip)
);

-- ===================================
-- جداول النظام والأمان
-- ===================================

-- جدول سجل النشاطات
CREATE TABLE activity_logs (
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
);

-- جدول جلسات المستخدمين
CREATE TABLE user_sessions (
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
);

-- جدول محاولات تسجيل الدخول
CREATE TABLE login_attempts (
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
);

-- جدول النسخ الاحتياطية
CREATE TABLE backups (
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
);

-- ===================================
-- جداول القوائم والتنقل
-- ===================================

-- جدول عناصر القائمة
CREATE TABLE menu_items (
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
);

-- ===================================
-- إدراج البيانات الأساسية
-- ===================================

-- إدراج المستخدم الإداري
INSERT INTO users (username, email, password, full_name, role, status, email_verified) VALUES 
('admin', 'admin@mosque.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'مدير النظام', 'admin', 'active', TRUE);

-- إدراج الصلاحيات الأساسية
INSERT INTO permissions (name, display_name, description, module) VALUES 
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
('backup_system', 'النسخ الاحتياطي', 'إنشاء واستعادة النسخ الاحتياطية', 'backup');

-- ربط المدير بجميع الصلاحيات
INSERT INTO user_permissions (user_id, permission_id, granted_by)
SELECT 1, id, 1 FROM permissions;

-- إدراج الإعدادات الأساسية
INSERT INTO settings (setting_key, setting_value, setting_type, category, display_name, description, is_public) VALUES 
-- إعدادات عامة
('site_name', 'مسجد النور', 'text', 'general', 'اسم الموقع', 'اسم الموقع الذي يظهر في العنوان', TRUE),
('site_description', 'موقع مسجد النور للتعليم القرآني والخدمات الإسلامية', 'textarea', 'general', 'وصف الموقع', 'وصف مختصر للموقع', TRUE),
('site_keywords', 'مسجد، قرآن، تعليم، إسلام، دروس', 'text', 'general', 'الكلمات المفتاحية', 'كلمات مفتاحية للموقع', FALSE),
('site_logo', '', 'image', 'general', 'شعار الموقع', 'شعار الموقع الرئيسي', TRUE),
('site_favicon', '', 'image', 'general', 'أيقونة الموقع', 'أيقونة صغيرة تظهر في المتصفح', TRUE),
('site_language', 'ar', 'text', 'general', 'لغة الموقع', 'اللغة الافتراضية للموقع', TRUE),
('site_timezone', 'Asia/Riyadh', 'text', 'general', 'المنطقة الزمنية', 'المنطقة الزمنية للموقع', FALSE),

-- إعدادات الاتصال
('contact_email', 'info@mosque.com', 'text', 'contact', 'بريد التواصل', 'البريد الإلكتروني للتواصل', TRUE),
('contact_phone', '+966123456789', 'text', 'contact', 'هاتف التواصل', 'رقم الهاتف للتواصل', TRUE),
('contact_whatsapp', '+966123456789', 'text', 'contact', 'واتساب', 'رقم الواتساب للتواصل', TRUE),
('contact_address', 'الرياض، المملكة العربية السعودية', 'textarea', 'contact', 'عنوان المسجد', 'العنوان الكامل للمسجد', TRUE),
('contact_map_embed', '', 'textarea', 'contact', 'خريطة جوجل', 'كود تضمين خريطة جوجل', TRUE),

-- إعدادات أوقات الصلاة
('prayer_city', 'Riyadh', 'text', 'prayer', 'مدينة أوقات الصلاة', 'المدينة لحساب أوقات الصلاة', TRUE),
('prayer_calculation_method', 'UmmAlQura', 'text', 'prayer', 'طريقة الحساب', 'طريقة حساب أوقات الصلاة', FALSE),
('prayer_auto_update', '1', 'boolean', 'prayer', 'التحديث التلقائي', 'تحديث أوقات الصلاة تلقائياً', FALSE),

-- إعدادات الميزات
('enable_comments', '1', 'boolean', 'features', 'تفعيل التعليقات', 'السماح بالتعليقات على الصفحات', FALSE),
('enable_ratings', '1', 'boolean', 'features', 'تفعيل التقييمات', 'السماح بتقييم الصفحات', FALSE),
('enable_registrations', '1', 'boolean', 'features', 'تفعيل التسجيل', 'السماح بتسجيل مستخدمين جدد', FALSE),
('enable_bookings', '1', 'boolean', 'features', 'تفعيل الحجوزات', 'تفعيل نظام حجز المرافق', FALSE),
('enable_donations', '1', 'boolean', 'features', 'تفعيل التبرعات', 'تفعيل نظام التبرعات', FALSE),
('enable_courses', '1', 'boolean', 'features', 'تفعيل الدورات', 'تفعيل نظام الدورات التعليمية', FALSE),

-- إعدادات العرض
('items_per_page', '10', 'number', 'display', 'عدد العناصر في الصفحة', 'عدد العناصر المعروضة في كل صفحة', FALSE),
('date_format', 'Y-m-d', 'text', 'display', 'تنسيق التاريخ', 'تنسيق عرض التاريخ', FALSE),
('time_format', 'H:i', 'text', 'display', 'تنسيق الوقت', 'تنسيق عرض الوقت', FALSE),

-- إعدادات الأمان
('max_login_attempts', '5', 'number', 'security', 'محاولات تسجيل الدخول', 'عدد محاولات تسجيل الدخول المسموحة', FALSE),
('session_timeout', '3600', 'number', 'security', 'انتهاء الجلسة', 'مدة انتهاء الجلسة بالثواني', FALSE),
('password_min_length', '8', 'number', 'security', 'طول كلمة المرور', 'الحد الأدنى لطول كلمة المرور', FALSE),
('require_email_verification', '0', 'boolean', 'security', 'تأكيد البريد الإلكتروني', 'طلب تأكيد البريد عند التسجيل', FALSE),

-- إعدادات البريد الإلكتروني
('smtp_host', 'smtp.gmail.com', 'text', 'email', 'خادم SMTP', 'عنوان خادم البريد الإلكتروني', FALSE),
('smtp_port', '587', 'number', 'email', 'منفذ SMTP', 'منفذ خادم البريد الإلكتروني', FALSE),
('smtp_username', '', 'text', 'email', 'اسم مستخدم SMTP', 'اسم المستخدم لخادم البريد', FALSE),
('smtp_password', '', 'text', 'email', 'كلمة مرور SMTP', 'كلمة المرور لخادم البريد', FALSE),
('smtp_encryption', 'tls', 'text', 'email', 'تشفير SMTP', 'نوع التشفير المستخدم', FALSE),

-- إعدادات الهيدر والفوتر
('header_style', 'default', 'text', 'appearance', 'نمط الهيدر', 'نمط تصميم الهيدر', FALSE),
('footer_style', 'default', 'text', 'appearance', 'نمط الفوتر', 'نمط تصميم الفوتر', FALSE),
('header_background_color', '#ffffff', 'color', 'appearance', 'لون خلفية الهيدر', 'لون خلفية الهيدر', FALSE),
('footer_background_color', '#333333', 'color', 'appearance', 'لون خلفية الفوتر', 'لون خلفية الفوتر', FALSE),
('primary_color', '#007bff', 'color', 'appearance', 'اللون الأساسي', 'اللون الأساسي للموقع', FALSE),
('secondary_color', '#6c757d', 'color', 'appearance', 'اللون الثانوي', 'اللون الثانوي للموقع', FALSE),

-- إعدادات وسائل التواصل الاجتماعي
('facebook_url', '', 'text', 'social', 'فيسبوك', 'رابط صفحة الفيسبوك', TRUE),
('twitter_url', '', 'text', 'social', 'تويتر', 'رابط حساب تويتر', TRUE),
('instagram_url', '', 'text', 'social', 'إنستغرام', 'رابط حساب إنستغرام', TRUE),
('youtube_url', '', 'text', 'social', 'يوتيوب', 'رابط قناة اليوتيوب', TRUE),
('telegram_url', '', 'text', 'social', 'تليغرام', 'رابط قناة التليغرام', TRUE),

-- إعدادات النسخ الاحتياطي
('backup_frequency', 'weekly', 'text', 'backup', 'تكرار النسخ الاحتياطي', 'تكرار إنشاء النسخ الاحتياطية', FALSE),
('backup_retention_days', '30', 'number', 'backup', 'مدة الاحتفاظ', 'عدد أيام الاحتفاظ بالنسخ الاحتياطية', FALSE),
('auto_backup', '1', 'boolean', 'backup', 'النسخ التلقائي', 'تفعيل النسخ الاحتياطي التلقائي', FALSE);

-- إدراج التصنيفات الأساسية
INSERT INTO categories (name, slug, description, icon, color, sort_order, created_by) VALUES 
('أخبار المسجد', 'mosque-news', 'آخر أخبار وفعاليات المسجد', 'fas fa-newspaper', '#007bff', 1, 1),
('الدروس والمحاضرات', 'lessons-lectures', 'الدروس الدينية والمحاضرات', 'fas fa-chalkboard-teacher', '#28a745', 2, 1),
('الأحداث والفعاليات', 'events', 'الأحداث والفعاليات القادمة', 'fas fa-calendar-alt', '#ffc107', 3, 1),
('التعليم القرآني', 'quran-education', 'دورات وبرامج تعليم القرآن الكريم', 'fas fa-quran', '#17a2b8', 4, 1),
('الخدمات الاجتماعية', 'social-services', 'الخدمات الاجتماعية والخيرية', 'fas fa-hands-helping', '#6f42c1', 5, 1),
('المكتبة الإسلامية', 'islamic-library', 'الكتب والمراجع الإسلامية', 'fas fa-book', '#fd7e14', 6, 1);

-- إدراج أنواع التبرعات
INSERT INTO donation_categories (name, description, target_amount, is_zakat, is_active, sort_order) VALUES 
('زكاة المال', 'زكاة الأموال والمدخرات', 100000.00, TRUE, TRUE, 1),
('صدقة عامة', 'التبرعات العامة لدعم أنشطة المسجد', 50000.00, FALSE, TRUE, 2),
('كفالة يتيم', 'كفالة الأيتام والمحتاجين', 30000.00, FALSE, TRUE, 3),
('بناء وصيانة', 'تبرعات لبناء وصيانة المسجد', 200000.00, FALSE, TRUE, 4),
('إفطار صائم', 'توفير وجبات الإفطار في رمضان', 20000.00, FALSE,
('بناء وصيانة', 'تبرعات لبناء وصيانة المسجد', 200000.00, FALSE, TRUE, 4),
('إفطار صائم', 'توفير وجبات الإفطار في رمضان', 20000.00, FALSE, TRUE, 5),
('مشاريع تعليمية', 'دعم البرامج والدورات التعليمية', 40000.00, FALSE, TRUE, 6),
('مساعدات طارئة', 'مساعدة الأسر المتضررة والحالات الطارئة', 25000.00, FALSE, TRUE, 7);

-- إدراج المرافق الأساسية
INSERT INTO facilities (name, description, facility_type, capacity, location, hourly_rate, daily_rate, is_active, created_by) VALUES 
('القاعة الكبرى', 'القاعة الرئيسية للمحاضرات والفعاليات الكبيرة', 'hall', 200, 'الطابق الأول', 100.00, 500.00, TRUE, 1),
('قاعة الاجتماعات', 'قاعة متوسطة للاجتماعات والدروس', 'hall', 50, 'الطابق الثاني', 50.00, 250.00, TRUE, 1),
('فصل تحفيظ القرآن', 'فصل مخصص لحلقات تحفيظ القرآن الكريم', 'classroom', 20, 'الطابق الأول', 30.00, 150.00, TRUE, 1),
('المكتبة', 'مكتبة المسجد للمطالعة والبحث', 'library', 30, 'الطابق الثاني', 0.00, 0.00, TRUE, 1),
('الساحة الخارجية', 'الساحة الخارجية للفعاليات والأنشطة', 'outdoor', 500, 'خارج المبنى', 200.00, 800.00, TRUE, 1);

-- إدراج عناصر القائمة الأساسية
INSERT INTO menu_items (menu_location, title, url, icon, sort_order, is_active, visibility) VALUES 
('header', 'الرئيسية', '/', 'fas fa-home', 1, TRUE, 'public'),
('header', 'عن المسجد', '/about', 'fas fa-mosque', 2, TRUE, 'public'),
('header', 'القرآن الكريم', '/quran', 'fas fa-quran', 3, TRUE, 'public'),
('header', 'المكتبة الإسلامية', '/library', 'fas fa-book', 4, TRUE, 'public'),
('header', 'الدورات التعليمية', '/courses', 'fas fa-graduation-cap', 5, TRUE, 'public'),
('header', 'الأحداث والفعاليات', '/events', 'fas fa-calendar-alt', 6, TRUE, 'public'),
('header', 'التبرعات', '/donations', 'fas fa-hand-holding-heart', 7, TRUE, 'public'),
('header', 'اتصل بنا', '/contact', 'fas fa-envelope', 8, TRUE, 'public'),

('footer', 'سياسة الخصوصية', '/privacy', 'fas fa-shield-alt', 1, TRUE, 'public'),
('footer', 'شروط الاستخدام', '/terms', 'fas fa-file-contract', 2, TRUE, 'public'),
('footer', 'الأسئلة الشائعة', '/faq', 'fas fa-question-circle', 3, TRUE, 'public'),
('footer', 'خريطة الموقع', '/sitemap', 'fas fa-sitemap', 4, TRUE, 'public');

-- إدراج بعض السور القرآنية الأساسية
INSERT INTO quran_surahs (number, name_arabic, name_english, name_transliteration, revelation_type, verses_count) VALUES 
(1, 'الفاتحة', 'The Opening', 'Al-Fatihah', 'meccan', 7),
(2, 'البقرة', 'The Cow', 'Al-Baqarah', 'medinan', 286),
(3, 'آل عمران', 'The Family of Imran', 'Aal-E-Imran', 'medinan', 200),
(4, 'النساء', 'The Women', 'An-Nisa', 'medinan', 176),
(5, 'المائدة', 'The Table', 'Al-Maidah', 'medinan', 120),
(112, 'الإخلاص', 'The Sincerity', 'Al-Ikhlas', 'meccan', 4),
(113, 'الفلق', 'The Daybreak', 'Al-Falaq', 'meccan', 5),
(114, 'الناس', 'The Mankind', 'An-Nas', 'meccan', 6);

-- إدراج بعض الآيات من سورة الفاتحة
INSERT INTO quran_verses (surah_id, verse_number, text_arabic, text_transliteration, text_translation) VALUES 
(1, 1, 'بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ', 'Bismillahi r-rahmani r-raheem', 'In the name of Allah, the Most Gracious, the Most Merciful'),
(1, 2, 'الْحَمْدُ لِلَّهِ رَبِّ الْعَالَمِينَ', 'Alhamdu lillahi rabbi l-alameen', 'Praise be to Allah, Lord of all the worlds'),
(1, 3, 'الرَّحْمَٰنِ الرَّحِيمِ', 'Ar-rahmani r-raheem', 'The Most Gracious, the Most Merciful'),
(1, 4, 'مَالِكِ يَوْمِ الدِّينِ', 'Maliki yawmi d-deen', 'Master of the Day of Judgment'),
(1, 5, 'إِيَّاكَ نَعْبُدُ وَإِيَّاكَ نَسْتَعِينُ', 'Iyyaka na\'budu wa iyyaka nasta\'een', 'You alone we worship, and You alone we ask for help'),
(1, 6, 'اهْدِنَا الصِّرَاطَ الْمُسْتَقِيمَ', 'Ihdina s-sirata l-mustaqeem', 'Guide us on the straight path'),
(1, 7, 'صِرَاطَ الَّذِينَ أَنْعَمْتَ عَلَيْهِمْ غَيْرِ الْمَغْضُوبِ عَلَيْهِمْ وَلَا الضَّالِّينَ', 'Sirata l-ladhina an\'amta \'alayhim ghayri l-maghdubi \'alayhim wa la d-dalleen', 'The path of those You have blessed, not of those who have incurred Your wrath, nor of those who have gone astray');

-- إدراج بعض الأحاديث الأساسية
INSERT INTO hadiths (book, narrator, text_arabic, text_translation, grade, source, created_by) VALUES 
('صحيح البخاري', 'عمر بن الخطاب رضي الله عنه', 'إنما الأعمال بالنيات وإنما لكل امرئ ما نوى', 'Actions are but by intention and every man shall have but that which he intended', 'sahih', 'البخاري', 1),
('صحيح مسلم', 'أبو هريرة رضي الله عنه', 'من كان يؤمن بالله واليوم الآخر فليقل خيراً أو ليصمت', 'Whoever believes in Allah and the Last Day should speak good or keep silent', 'sahih', 'مسلم', 1),
('سنن الترمذي', 'عبدالله بن عمرو رضي الله عنهما', 'المسلم من سلم المسلمون من لسانه ويده', 'The Muslim is the one from whose tongue and hand the Muslims are safe', 'sahih', 'الترمذي', 1);

-- إدراج بعض الأدعية الأساسية
INSERT INTO duas (title, category, text_arabic, text_transliteration, text_translation, source, created_by) VALUES 
('دعاء الاستيقاظ', 'morning', 'الحمد لله الذي أحيانا بعد ما أماتنا وإليه النشور', 'Alhamdu lillahi alladhi ahyana ba\'da ma amatana wa ilayhi n-nushur', 'Praise be to Allah who gave us life after having taken it from us and unto Him is the resurrection', 'البخاري', 1),
('دعاء النوم', 'sleep', 'باسمك اللهم أموت وأحيا', 'Bismika Allahumma amutu wa ahya', 'In Your name O Allah, I live and die', 'البخاري', 1),
('دعاء الطعام', 'eating', 'بسم الله', 'Bismillah', 'In the name of Allah', 'أبو داود', 1),
('دعاء السفر', 'travel', 'سبحان الذي سخر لنا هذا وما كنا له مقرنين وإنا إلى ربنا لمنقلبون', 'Subhana alladhi sakhkhara lana hadha wa ma kunna lahu muqrineen wa inna ila rabbina la munqalibun', 'Glory be to Him who has subjected this to us, and we could never have it (by our efforts). And verily, to our Lord we indeed are to return', 'الترمذي', 1);

-- إدراج أوقات الصلاة لبعض الأيام (مثال)
INSERT INTO prayer_times (date, city, fajr, sunrise, dhuhr, asr, maghrib, isha) VALUES 
(CURDATE(), 'Riyadh', '05:30:00', '06:45:00', '12:15:00', '15:30:00', '18:00:00', '19:30:00'),
(DATE_ADD(CURDATE(), INTERVAL 1 DAY), 'Riyadh', '05:31:00', '06:46:00', '12:15:00', '15:31:00', '18:01:00', '19:31:00'),
(DATE_ADD(CURDATE(), INTERVAL 2 DAY), 'Riyadh', '05:32:00', '06:47:00', '12:16:00', '15:32:00', '18:02:00', '19:32:00');

-- إنشاء الفهارس الإضافية لتحسين الأداء
CREATE INDEX idx_users_role_status ON users(role, status);
CREATE INDEX idx_pages_status_featured ON pages(status, is_featured);
CREATE INDEX idx_events_start_status ON events(start_datetime, status);
CREATE INDEX idx_donations_date_status ON donations(created_at, status);
CREATE INDEX idx_bookings_facility_date ON bookings(facility_id, start_datetime);
CREATE INDEX idx_course_enrollments_student_status ON course_enrollments(student_id, status);
CREATE INDEX idx_notifications_user_read ON notifications(user_id, is_read);
CREATE INDEX idx_messages_status_type ON messages(status, message_type);
CREATE INDEX idx_visitor_stats_date_page ON visitor_stats(visit_date, page_url);

-- إنشاء المشاهدات (Views) المفيدة
CREATE VIEW active_users AS
SELECT id, username, full_name, email, role, last_login, created_at
FROM users 
WHERE status = 'active';

CREATE VIEW published_pages AS
SELECT p.*, c.name as category_name, u.full_name as author_name
FROM pages p
LEFT JOIN categories c ON p.category_id = c.id
LEFT JOIN users u ON p.author_id = u.id
WHERE p.status = 'published';

CREATE VIEW upcoming_events AS
SELECT e.*, c.name as category_name
FROM events e
LEFT JOIN categories c ON e.category_id = c.id
WHERE e.status = 'published' AND e.start_datetime > NOW();

CREATE VIEW donation_summary AS
SELECT 
    dc.name as category_name,
    COUNT(d.id) as total_donations,
    SUM(d.amount) as total_amount,
    AVG(d.amount) as average_amount
FROM donation_categories dc
LEFT JOIN donations d ON dc.id = d.category_id AND d.status = 'completed'
GROUP BY dc.id, dc.name;

-- إنشاء الإجراءات المخزنة (Stored Procedures)
DELIMITER //

CREATE PROCEDURE GetUserStats(IN user_id INT)
BEGIN
    SELECT 
        (SELECT COUNT(*) FROM course_enrollments WHERE student_id = user_id) as enrolled_courses,
        (SELECT COUNT(*) FROM event_registrations WHERE user_id = user_id) as registered_events,
        (SELECT COUNT(*) FROM bookings WHERE user_id = user_id) as total_bookings,
        (SELECT SUM(amount) FROM donations WHERE user_id = user_id AND status = 'completed') as total_donations;
END //

CREATE PROCEDURE GetSiteStats()
BEGIN
    SELECT 
        (SELECT COUNT(*) FROM users WHERE status = 'active') as active_users,
        (SELECT COUNT(*) FROM pages WHERE status = 'published') as published_pages,
        (SELECT COUNT(*) FROM events WHERE status = 'published' AND start_datetime > NOW()) as upcoming_events,
        (SELECT COUNT(*) FROM courses WHERE status = 'published') as active_courses,
        (SELECT SUM(amount) FROM donations WHERE status = 'completed') as total_donations,
        (SELECT COUNT(DISTINCT visitor_ip) FROM visitor_stats WHERE visit_date = CURDATE()) as today_visitors;
END //

DELIMITER ;

-- إنشاء المحفزات (Triggers) للتحديث التلقائي
DELIMITER //

CREATE TRIGGER update_page_views 
AFTER INSERT ON visitor_stats
FOR EACH ROW
BEGIN
    IF NEW.page_url LIKE '/page/%' THEN
        UPDATE pages 
        SET views_count = views_count + 1 
        WHERE CONCAT('/page/', slug) = NEW.page_url;
    END IF;
END //

CREATE TRIGGER update_donation_totals
AFTER INSERT ON donations
FOR EACH ROW
BEGIN
    IF NEW.status = 'completed' THEN
        UPDATE donation_categories 
        SET current_amount = current_amount + NEW.amount 
        WHERE id = NEW.category_id;
    END IF;
END //

CREATE TRIGGER update_course_students
AFTER INSERT ON course_enrollments
FOR EACH ROW
BEGIN
    UPDATE courses 
    SET current_students = current_students + 1 
    WHERE id = NEW.course_id;
END //

CREATE TRIGGER update_event_attendees
AFTER INSERT ON event_registrations
FOR EACH ROW
BEGIN
    UPDATE events 
    SET current_attendees = current_attendees + 1 
    WHERE id = NEW.event_id;
END //

DELIMITER ;

-- إنشاء وظائف مخصصة
DELIMITER //

CREATE FUNCTION GetArabicDate(input_date DATE) 
RETURNS VARCHAR(100)
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE arabic_date VARCHAR(100);
    DECLARE day_name VARCHAR(20);
    DECLARE month_name VARCHAR(20);
    
    SET day_name = CASE DAYOFWEEK(input_date)
        WHEN 1 THEN 'الأحد'
        WHEN 2 THEN 'الاثنين'
        WHEN 3 THEN 'الثلاثاء'
        WHEN 4 THEN 'الأربعاء'
        WHEN 5 THEN 'الخميس'
        WHEN 6 THEN 'الجمعة'
        WHEN 7 THEN 'السبت'
    END;
    
    SET month_name = CASE MONTH(input_date)
        WHEN 1 THEN 'يناير'
        WHEN 2 THEN 'فبراير'
        WHEN 3 THEN 'مارس'
        WHEN 4 THEN 'أبريل'
        WHEN 5 THEN 'مايو'
        WHEN 6 THEN 'يونيو'
        WHEN 7 THEN 'يوليو'
        WHEN 8 THEN 'أغسطس'
        WHEN 9 THEN 'سبتمبر'
        WHEN 10 THEN 'أكتوبر'
        WHEN 11 THEN 'نوفمبر'
        WHEN 12 THEN 'ديسمبر'
    END;
    
    SET arabic_date = CONCAT(day_name, ' ', DAY(input_date), ' ', month_name, ' ', YEAR(input_date));
    
    RETURN arabic_date;
END //

DELIMITER ;

-- تحسين إعدادات قاعدة البيانات
SET GLOBAL innodb_buffer_pool_size = 268435456; -- 256MB
SET GLOBAL query_cache_size = 67108864; -- 64MB
SET GLOBAL query_cache_type = 1;
SET GLOBAL slow_query_log = 1;
SET GLOBAL long_query_time = 2;

-- إنشاء مستخدم قاعدة بيانات مخصص للتطبيق
CREATE USER IF NOT EXISTS 'mosque_user'@'localhost' IDENTIFIED BY 'mosque_secure_password_2024';
GRANT SELECT, INSERT, UPDATE, DELETE ON mosque_management.* TO 'mosque_user'@'localhost';
GRANT EXECUTE ON mosque_management.* TO 'mosque_user'@'localhost';
FLUSH PRIVILEGES;

-- إضافة تعليقات على الجداول الرئيسية
ALTER TABLE users COMMENT = 'جدول المستخدمين - يحتوي على بيانات جميع مستخدمي النظام';
ALTER TABLE pages COMMENT = 'جدول الصفحات - يحتوي على محتوى الموقع والمقالات';
ALTER TABLE events COMMENT = 'جدول الأحداث - يحتوي على الفعاليات والأحداث';
ALTER TABLE courses COMMENT = 'جدول الدورات - يحتوي على الدورات التعليمية';
ALTER TABLE donations COMMENT = 'جدول التبرعات - يحتوي على سجل التبرعات والزكاة';
ALTER TABLE bookings COMMENT = 'جدول الحجوزات - يحتوي على حجوزات المرافق والقاعات';
ALTER TABLE messages COMMENT = 'جدول الرسائل - يحتوي على رسائل التواصل والاستفسارات';
ALTER TABLE notifications COMMENT = 'جدول الإشعارات - يحتوي على إشعارات النظام للمستخدمين';

-- إنهاء إنشاء قاعدة البيانات
SELECT 'تم إنشاء قاعدة البيانات بنجاح!' as status;
