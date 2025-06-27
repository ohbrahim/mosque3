<?php
/**
 * دوال رفع الملفات
 */

if (!function_exists('uploadFile')) {
    function uploadFile($file, $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'], $maxSize = 5242880) {
        global $db;
        
        // التحقق من وجود الملف
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'لم يتم رفع الملف بشكل صحيح'];
        }
        
        // التحقق من حجم الملف
        if ($file['size'] > $maxSize) {
            return ['success' => false, 'message' => 'حجم الملف كبير جداً'];
        }
        
        // التحقق من نوع الملف
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExtension, $allowedTypes)) {
            return ['success' => false, 'message' => 'نوع الملف غير مسموح'];
        }
        
        // إنشاء مجلد الرفع إذا لم يكن موجوداً
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // إنشاء اسم ملف فريد
        $filename = uniqid() . '_' . time() . '.' . $fileExtension;
        $filePath = $uploadDir . $filename;
        
        // رفع الملف
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            // حفظ معلومات الملف في قاعدة البيانات
            try {
                $db->insert('uploads', [
                    'filename' => $filename,
                    'original_name' => $file['name'],
                    'file_path' => $filePath,
                    'file_size' => $file['size'],
                    'file_type' => $file['type'],
                    'uploaded_by' => $_SESSION['user_id'] ?? null,
                    'created_at' => date('Y-m-d H:i:s')
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
        $filePath = 'uploads/' . $filename;
        if (file_exists($filePath)) {
            return unlink($filePath);
        }
        return false;
    }
}

if (!function_exists('getFileUrl')) {
    function getFileUrl($filename) {
        return 'uploads/' . $filename;
    }
}
?>
