<?php
/**
 * تحميل الكتب من المكتبة
 */
require_once 'config/config.php';

// التحقق من وجود معرف الكتاب
if (!isset($_GET['book']) || !is_numeric($_GET['book'])) {
    header('HTTP/1.0 404 Not Found');
    die('الكتاب غير موجود');
}

$bookId = (int)$_GET['book'];

// جلب معلومات الكتاب
$book = $db->fetchOne("SELECT * FROM library_books WHERE id = ? AND status = 'published'", [$bookId]);

if (!$book) {
    header('HTTP/1.0 404 Not Found');
    die('الكتاب غير موجود');
}

// التحقق من وجود الملف
$filePath = UPLOAD_PATH . $book['file_path'];
if (!file_exists($filePath)) {
    header('HTTP/1.0 404 Not Found');
    die('ملف الكتاب غير موجود');
}

// تحديث عداد التحميل
$db->query("UPDATE library_books SET download_count = download_count + 1 WHERE id = ?", [$bookId]);

// تسجيل عملية التحميل
logVisitor($db, "download_book_{$bookId}");

// إعداد headers للتحميل
$fileName = $book['title'] . '.' . pathinfo($book['file_path'], PATHINFO_EXTENSION);
$fileSize = filesize($filePath);
$mimeType = mime_content_type($filePath);

header('Content-Type: ' . $mimeType);
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Content-Length: ' . $fileSize);
header('Cache-Control: must-revalidate');
header('Pragma: public');

// قراءة وإرسال الملف
readfile($filePath);
exit;
?>
