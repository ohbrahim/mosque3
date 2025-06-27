<?php
/**
 * جميع الدوال المطلوبة في ملف واحد - محدث
 */

// دوال رفع الملفات
if (!function_exists('uploadFile')) {
    function uploadFile($file, $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'], $maxSize = 5242880) {
        global $db;
        
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'لم يتم رفع الملف بشكل صحيح'];
        }
        
        if ($file['size'] > $maxSize) {
            return ['success' => false, 'message' => 'حجم الملف كبير جداً'];
        }
        
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExtension, $allowedTypes)) {
            return ['success' => false, 'message' => 'نوع الملف غير مسموح'];
        }
        
        $uploadDir = '../uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $filename = uniqid() . '_' . time() . '.' . $fileExtension;
        $filePath = $uploadDir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            try {
                $db->insert('uploads', [
                    'filename' => $filename,
                    'original_name' => $file['name'],
                    'file_path' => $filePath,
                    'file_size' => $file['size'],
                    'file_type' => $file['type'],
                    'uploaded_by' => $_SESSION['user_id'] ?? null
                ]);
            } catch (Exception $e) {
                // تجاهل خطأ قاعدة البيانات
            }
            
            return ['success' => true, 'filename' => $filename, 'path' => $filePath];
        } else {
            return ['success' => false, 'message' => 'فشل في رفع الملف'];
        }
    }
}

if (!function_exists('deleteFile')) {
    function deleteFile($filename) {
        $filePath = '../uploads/' . $filename;
        if (file_exists($filePath)) {
            return unlink($filePath);
        }
        return false;
    }
}

// دوال التعليقات
if (!function_exists('addComment')) {
    function addComment($db, $pageId, $content, $authorName = null, $authorEmail = null, $userId = null) {
        try {
            // التحقق من إعداد الموافقة التلقائية
            $autoApprove = getSetting($db, 'auto_approve_comments', '0');
            $status = $autoApprove === '1' ? 'approved' : 'pending';
            
            $data = [
                'page_id' => $pageId,
                'content' => sanitize($content),
                'status' => $status,
                'created_at' => date('Y-m-d H:i:s'),
                'author_ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
            ];
            
            if ($userId) {
                $data['user_id'] = $userId;
                $user = $db->fetchOne("SELECT full_name, email FROM users WHERE id = ?", [$userId]);
                if ($user) {
                    $data['author_name'] = $user['full_name'];
                    $data['author_email'] = $user['email'];
                }
            } else {
                $data['author_name'] = $authorName ?: 'زائر';
                $data['author_email'] = $authorEmail ?: 'guest@example.com';
            }
            
            return $db->insert('comments', $data);
        } catch (Exception $e) {
            error_log("Error adding comment: " . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('getPageComments')) {
    function getPageComments($db, $pageId) {
        try {
            return $db->fetchAll("
                SELECT c.*, u.full_name as user_name, u.avatar 
                FROM comments c 
                LEFT JOIN users u ON c.user_id = u.id 
                WHERE c.page_id = ? AND c.status = 'approved' 
                ORDER BY c.created_at DESC
            ", [$pageId]);
        } catch (Exception $e) {
            return [];
        }
    }
}

if (!function_exists('displayComments')) {
    function displayComments($db, $pageId) {
        $comments = getPageComments($db, $pageId);
        
        $html = '<div class="comments-section mt-5">';
        $html .= '<h4 class="mb-4">';
        $html .= '<i class="fas fa-comments"></i> ';
        $html .= 'التعليقات (' . convertToArabicNumbers(count($comments)) . ')';
        $html .= '</h4>';
        
        // نموذج إضافة تعليق
        $html .= '<div class="add-comment-form mb-4">';
        $html .= '<div class="card">';
        $html .= '<div class="card-body">';
        $html .= '<form method="POST" action="submit_comment.php">';
        $html .= '<input type="hidden" name="csrf_token" value="' . generateCSRFToken() . '">';
        $html .= '<input type="hidden" name="page_id" value="' . $pageId . '">';
        $html .= '<input type="hidden" name="return_url" value="' . $_SERVER['REQUEST_URI'] . '">';
        
        if (isLoggedIn()) {
            $html .= '<div class="mb-3">';
            $html .= '<label for="comment_content" class="form-label">أضف تعليقك</label>';
            $html .= '<textarea class="form-control" id="comment_content" name="content" rows="4" placeholder="شاركنا رأيك..." required></textarea>';
            $html .= '</div>';
            $html .= '<button type="submit" class="btn btn-primary">';
            $html .= '<i class="fas fa-paper-plane"></i> إرسال التعليق';
            $html .= '</button>';
            
            // إضافة ملاحظة حول الموافقة
            $autoApprove = getSetting($db, 'auto_approve_comments', '0');
            if ($autoApprove !== '1') {
                $html .= '<div class="form-text mt-2"><i class="fas fa-info-circle"></i> سيتم مراجعة التعليق من قبل المشرف قبل نشره.</div>';
            }
        } else {
            $html .= '<div class="row">';
            $html .= '<div class="col-md-6 mb-3">';
            $html .= '<label for="author_name" class="form-label">الاسم</label>';
            $html .= '<input type="text" class="form-control" id="author_name" name="author_name" required>';
            $html .= '</div>';
            $html .= '<div class="col-md-6 mb-3">';
            $html .= '<label for="author_email" class="form-label">البريد الإلكتروني</label>';
            $html .= '<input type="email" class="form-control" id="author_email" name="author_email" required>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '<div class="mb-3">';
            $html .= '<label for="comment_content" class="form-label">التعليق</label>';
            $html .= '<textarea class="form-control" id="comment_content" name="content" rows="4" placeholder="شاركنا رأيك..." required></textarea>';
            $html .= '</div>';
            $html .= '<button type="submit" class="btn btn-primary">';
            $html .= '<i class="fas fa-paper-plane"></i> إرسال التعليق';
            $html .= '</button>';
            
            // إضافة ملاحظة حول الموافقة
            $autoApprove = getSetting($db, 'auto_approve_comments', '0');
            if ($autoApprove !== '1') {
                $html .= '<div class="form-text mt-2"><i class="fas fa-info-circle"></i> سيتم مراجعة التعليق من قبل المشرف قبل نشره.</div>';
            }
        }
        
        $html .= '</form>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        
        // عرض التعليقات
        if (empty($comments)) {
            $html .= '<div class="text-center py-4">';
            $html .= '<i class="fas fa-comments fa-3x text-muted mb-3"></i>';
            $html .= '<p class="text-muted">لا توجد تعليقات بعد. كن أول من يعلق!</p>';
            $html .= '</div>';
        } else {
            foreach ($comments as $comment) {
                $html .= '<div class="comment-item card mb-3">';
                $html .= '<div class="card-body">';
                
                $html .= '<div class="d-flex align-items-start">';
                $html .= '<div class="comment-avatar me-3">';
                if ($comment['avatar']) {
                    $html .= '<img src="uploads/' . $comment['avatar'] . '" alt="صورة المعلق" class="rounded-circle" width="50" height="50">';
                } else {
                    $html .= '<div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">';
                    $html .= '<i class="fas fa-user"></i>';
                    $html .= '</div>';
                }
                $html .= '</div>';
                
                $html .= '<div class="flex-grow-1">';
                $html .= '<div class="comment-header mb-2">';
                $html .= '<h6 class="mb-1">' . htmlspecialchars($comment['user_name'] ?: $comment['author_name']) . '</h6>';
                $html .= '<small class="text-muted">' . formatArabicDate($comment['created_at']) . '</small>';
                $html .= '</div>';
                
                $html .= '<div class="comment-content">';
                $html .= '<p class="mb-0">' . nl2br(htmlspecialchars($comment['content'])) . '</p>';
                $html .= '</div>';
                $html .= '</div>';
                
                $html .= '</div>';
                $html .= '</div>';
                $html .= '</div>';
            }
        }
        
        $html .= '</div>';
        
        return $html;
    }
}

// دوال البلوكات
if (!function_exists('renderBlocks')) {
    function renderBlocks($db, $position) {
        try {
            $blocks = $db->fetchAll("
                SELECT * FROM blocks 
                WHERE position = ? AND status = 'active' 
                ORDER BY display_order ASC
            ", [$position]);
            
            $output = '';
            
            foreach ($blocks as $block) {
                $output .= renderSingleBlock($block);
            }
            
            return $output;
        } catch (Exception $e) {
            return '';
        }
    }
}

if (!function_exists('renderSingleBlock')) {
    function renderSingleBlock($block) {
        global $db;
        
        $cssClass = 'block-widget mb-4 ' . ($block['css_class'] ?: '');
        
        $html = '<div class="' . $cssClass . '" id="block-' . $block['id'] . '">';
        
        // عرض العنوان إذا كان مطلوباً
        if ($block['show_title'] && !empty($block['title'])) {
            $html .= '<div class="card mb-3">';
            $html .= '<div class="card-header bg-primary text-white">';
            $html .= '<h5 class="card-title mb-0">' . htmlspecialchars($block['title']) . '</h5>';
            $html .= '</div>';
        }
        
        // عرض المحتوى حسب نوع البلوك
        switch ($block['block_type']) {
            case 'custom':
            case 'html':
                // التحقق من إعداد السماح بـ HTML مخصص
                $allowCustomHtml = getSetting($db, 'allow_custom_html', '1');
                if ($allowCustomHtml === '1') {
                    $html .= $block['content'];
                } else {
                    $html .= '<div class="alert alert-warning">HTML مخصص غير مسموح</div>';
                }
                break;
                
            case 'iframe':
                // السماح بـ iframe بشكل آمن
                $html .= $block['content'];
                break;
                
            case 'marquee':
                // السماح بـ marquee
                $html .= $block['content'];
                break;
                
            case 'text':
                $html .= '<div class="card-body">';
                $html .= '<p>' . nl2br(htmlspecialchars($block['content'])) . '</p>';
                $html .= '</div>';
                break;
                
            case 'announcement':
                $html .= '<div class="alert alert-info">' . $block['content'] . '</div>';
                break;
                
            case 'prayer_times':
                $html .= renderPrayerTimesBlock();
                break;
                
            case 'weather':
                $html .= renderWeatherBlock();
                break;
                
            default:
                $html .= $block['content'];
                break;
        }
        
        // إغلاق العنوان إذا كان مطلوباً
        if ($block['show_title'] && !empty($block['title'])) {
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        // إضافة CSS مخصص إذا كان موجوداً
        if (!empty($block['custom_css'])) {
            $html .= '<style>' . $block['custom_css'] . '</style>';
        }
        
        return $html;
    }
}

if (!function_exists('renderPrayerTimesBlock')) {
    function renderPrayerTimesBlock() {
        return '<div class="text-center"><i class="fas fa-mosque fa-2x text-primary mb-2"></i><p>أوقات الصلاة</p></div>';
    }
}

if (!function_exists('renderWeatherBlock')) {
    function renderWeatherBlock() {
        return '<div class="text-center"><i class="fas fa-cloud-sun fa-2x text-info mb-2"></i><p>حالة الطقس</p></div>';
    }
}

// دوال التقييم
if (!function_exists('displayRatingStars')) {
    function displayRatingStars($pageId, $currentRating = 0) {
        $html = '<div class="rating-widget mt-3">';
        $html .= '<h6>قيم هذه الصفحة:</h6>';
        $html .= '<div class="rating-stars" data-page-id="' . $pageId . '">';
        
        for ($i = 1; $i <= 5; $i++) {
            $class = $i <= $currentRating ? 'fas fa-star text-warning' : 'far fa-star text-muted';
            $html .= '<i class="' . $class . ' rating-star" data-rating="' . $i . '" style="cursor: pointer; font-size: 1.5rem; margin: 0 2px;"></i>';
        }
        
        $html .= '</div>';
        $html .= '<div class="rating-message mt-2"></div>';
        $html .= '</div>';
        
        $html .= '<script>
        document.addEventListener("DOMContentLoaded", function() {
            const stars = document.querySelectorAll(".rating-star");
            const pageId = document.querySelector(".rating-stars").dataset.pageId;
            
            stars.forEach(star => {
                star.addEventListener("click", function() {
                    const rating = this.dataset.rating;
                    submitRating(pageId, rating);
                });
                
                star.addEventListener("mouseover", function() {
                    const rating = this.dataset.rating;
                    highlightStars(rating);
                });
            });
            
            document.querySelector(".rating-stars").addEventListener("mouseleave", function() {
                resetStars();
            });
        });
        
        function highlightStars(rating) {
            const stars = document.querySelectorAll(".rating-star");
            stars.forEach((star, index) => {
                if (index < rating) {
                    star.className = "fas fa-star text-warning rating-star";
                } else {
                    star.className = "far fa-star text-muted rating-star";
                }
            });
        }
        
        function resetStars() {
            // إعادة تعيين النجوم للحالة الأصلية
        }
        
        function submitRating(pageId, rating) {
            fetch("submit_rating.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    page_id: pageId,
                    rating: rating,
                    csrf_token: "' . generateCSRFToken() . '"
                })
            })
            .then(response => response.json())
            .then(data => {
                const messageDiv = document.querySelector(".rating-message");
                if (data.success) {
                    messageDiv.innerHTML = "<div class=\"alert alert-success\">" + data.message + "</div>";
                    highlightStars(rating);
                } else {
                    messageDiv.innerHTML = "<div class=\"alert alert-danger\">" + data.message + "</div>";
                }
            })
            .catch(error => {
                console.error("Error:", error);
                document.querySelector(".rating-message").innerHTML = "<div class=\"alert alert-danger\">حدث خطأ أثناء التقييم</div>";
            });
        }
        </script>';
        
        return $html;
    }
}

// دالة عرض البانر
if (!function_exists('displayWelcomeBanner')) {
    function displayWelcomeBanner($db) {
        $showBanner = getSetting($db, 'show_welcome_banner', '0');
        
        if ($showBanner === '1') {
            $title = getSetting($db, 'welcome_banner_title', 'مرحباً بكم');
            $subtitle = getSetting($db, 'welcome_banner_subtitle', 'أهلاً وسهلاً');
            $content = getSetting($db, 'welcome_banner_content', 'مرحباً بكم في موقع مسجد النور');
            
            return '
            <div class="welcome-banner bg-primary text-white py-4 mb-4">
                <div class="container text-center">
                    <h2>' . htmlspecialchars($title) . '</h2>
                    <h4>' . htmlspecialchars($subtitle) . '</h4>
                    <p class="lead">' . htmlspecialchars($content) . '</p>
                </div>
            </div>';
        }
        
        return '';
    }
}

// دالة الحصول على متوسط تقييم الصفحة
if (!function_exists('getPageRating')) {
    function getPageRating($db, $pageId) {
        try {
            $result = $db->fetchOne("SELECT AVG(rating) as avg_rating, COUNT(*) as count FROM ratings WHERE page_id = ?", [$pageId]);
            return [
                'average' => round($result['avg_rating'] ?? 0, 1),
                'count' => $result['count'] ?? 0
            ];
        } catch (Exception $e) {
            return ['average' => 0, 'count' => 0];
        }
    }
}

// دالة تحديث متوسط تقييم الصفحة
if (!function_exists('updatePageRating')) {
    function updatePageRating($db, $pageId) {
        try {
            $rating = getPageRating($db, $pageId);
            return $db->update('pages', [
                'average_rating' => $rating['average'],
                'ratings_count' => $rating['count']
            ], 'id = ?', [$pageId]);
        } catch (Exception $e) {
            return false;
        }
    }
}
?>
