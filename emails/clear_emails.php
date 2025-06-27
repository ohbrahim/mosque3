<?php
/**
 * مسح جميع رسائل البريد المحفوظة
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailDir = __DIR__;
    
    // مسح جميع ملفات HTML
    $files = glob($emailDir . '/email_*.html');
    foreach ($files as $file) {
        unlink($file);
    }
    
    // مسح فهرس الرسائل
    $indexFile = $emailDir . '/index.json';
    if (file_exists($indexFile)) {
        unlink($indexFile);
    }
    
    echo json_encode(['success' => true]);
} else {
    header('Location: view_emails.php');
}
?>
