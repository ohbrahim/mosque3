<?php
/**
 * وظائف رفع الملفات
 */

/**
 * رفع ملف
 */
function uploadFile($file, $allowedTypes = [], $maxSize = 5242880) { // 5MB default
    $response = ['success' => false, 'filename' => '', 'message' => ''];
    
    // التحقق من وجود خطأ في الرفع
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $response['message'] = 'خطأ في رفع الملف';
        return $response;
    }
    
    // التحقق من حجم الملف
    if ($file['size'] > $maxSize) {
        $response['message'] = 'حجم الملف كبير جداً';
        return $response;
    }
    
    // التحقق من نوع الملف
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!empty($allowedTypes) && !in_array($fileExtension, $allowedTypes)) {
        $response['message'] = 'نوع الملف غير مسموح';
        return $response;
    }
    
    // إنشاء اسم ملف فريد
    $filename = uniqid() . '_' . time() . '.' . $fileExtension;
    $uploadPath = UPLOAD_PATH . $filename;
    
    // التأكد من وجود مجلد الرفع
    if (!is_dir(UPLOAD_PATH)) {
        mkdir(UPLOAD_PATH, 0755, true);
    }
    
    // نقل الملف
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        $response['success'] = true;
        $response['filename'] = $filename;
        $response['message'] = 'تم رفع الملف بنجاح';
    } else {
        $response['message'] = 'فشل في رفع الملف';
    }
    
    return $response;
}

/**
 * رفع صورة مع تغيير الحجم
 */
function uploadImage($file, $maxWidth = 800, $maxHeight = 600, $quality = 85) {
    $response = uploadFile($file, ['jpg', 'jpeg', 'png', 'gif'], 5242880);
    
    if (!$response['success']) {
        return $response;
    }
    
    $imagePath = UPLOAD_PATH . $response['filename'];
    
    // التحقق من أن الملف صورة
    $imageInfo = getimagesize($imagePath);
    if (!$imageInfo) {
        unlink($imagePath);
        $response['success'] = false;
        $response['message'] = 'الملف ليس صورة صحيحة';
        return $response;
    }
    
    $originalWidth = $imageInfo[0];
    $originalHeight = $imageInfo[1];
    $imageType = $imageInfo[2];
    
    // إذا كانت الصورة أصغر من الحد الأقصى، لا نحتاج لتغيير الحجم
    if ($originalWidth <= $maxWidth && $originalHeight <= $maxHeight) {
        return $response;
    }
    
    // حساب الأبعاد الجديدة
    $ratio = min($maxWidth / $originalWidth, $maxHeight / $originalHeight);
    $newWidth = round($originalWidth * $ratio);
    $newHeight = round($originalHeight * $ratio);
    
    // إنشاء صورة جديدة
    $newImage = imagecreatetruecolor($newWidth, $newHeight);
    
    // إنشاء الصورة الأصلية حسب النوع
    switch ($imageType) {
        case IMAGETYPE_JPEG:
            $sourceImage = imagecreatefromjpeg($imagePath);
            break;
        case IMAGETYPE_PNG:
            $sourceImage = imagecreatefrompng($imagePath);
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            break;
        case IMAGETYPE_GIF:
            $sourceImage = imagecreatefromgif($imagePath);
            break;
        default:
            imagedestroy($newImage);
            unlink($imagePath);
            $response['success'] = false;
            $response['message'] = 'نوع الصورة غير مدعوم';
            return $response;
    }
    
    // تغيير حجم الصورة
    imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);
    
    // حفظ الصورة الجديدة
    switch ($imageType) {
        case IMAGETYPE_JPEG:
            imagejpeg($newImage, $imagePath, $quality);
            break;
        case IMAGETYPE_PNG:
            imagepng($newImage, $imagePath);
            break;
        case IMAGETYPE_GIF:
            imagegif($newImage, $imagePath);
            break;
    }
    
    // تنظيف الذاكرة
    imagedestroy($newImage);
    imagedestroy($sourceImage);
    
    return $response;
}

/**
 * حذف ملف
 */
function deleteFile($filename) {
    $filePath = UPLOAD_PATH . $filename;
    if (file_exists($filePath)) {
        return unlink($filePath);
    }
    return true;
}

/**
 * التحقق من نوع الملف بناءً على محتواه
 */
function validateFileType($filePath, $allowedTypes) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $filePath);
    finfo_close($finfo);
    
    $allowedMimes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'txt' => 'text/plain'
    ];
    
    foreach ($allowedTypes as $type) {
        if (isset($allowedMimes[$type]) && $allowedMimes[$type] === $mimeType) {
            return true;
        }
    }
    
    return false;
}
?>
