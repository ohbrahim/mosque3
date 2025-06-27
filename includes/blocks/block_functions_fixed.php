<?php
/**
 * دوال البلوكات المحسنة لحل مشكلة iframe
 */

function renderBlock($block) {
    // التأكد من وجود المحتوى
    if (empty($block['content'])) {
        return '';
    }
    
    // بناء CSS class
    $cssClass = 'block-widget mb-4';
    if (!empty($block['css_class'])) {
        $cssClass .= ' ' . $block['css_class'];
    }
    
    // بداية HTML
    $html = '<div class="' . $cssClass . '" id="block-' . $block['id'] . '">';
    
    // CSS مخصص
    if (!empty($block['custom_css'])) {
        $html .= '<style>' . $block['custom_css'] . '</style>';
    }
    
    // العنوان
    if (!empty($block['show_title']) && !empty($block['title'])) {
        $html .= '<div class="block-header mb-3">';
        $html .= '<h5 class="block-title text-primary border-bottom pb-2">';
        $html .= '<i class="fas fa-cube me-2"></i>';
        $html .= htmlspecialchars($block['title']);
        $html .= '</h5>';
        $html .= '</div>';
    }
    
    // المحتوى
    $html .= '<div class="block-content">';
    
    // معالجة خاصة لـ iframe
    if (strpos($block['content'], '<iframe') !== false) {
        // التأكد من أن iframe يحتوي على الخصائص المطلوبة
        $content = $block['content'];
        
        // إضافة خصائص أمان لـ iframe إذا لم تكن موجودة
        if (strpos($content, 'loading=') === false) {
            $content = str_replace('<iframe', '<iframe loading="lazy"', $content);
        }
        
        // التأكد من وجود sandbox للأمان
        if (strpos($content, 'sandbox=') === false) {
            $content = str_replace('<iframe', '<iframe sandbox="allow-scripts allow-same-origin allow-forms"', $content);
        }
        
        $html .= $content;
    } else {
        // للمحتوى العادي
        $html .= $block['content'];
    }
    
    $html .= '</div>';
    $html .= '</div>';
    
    return $html;
}

// دالة مساعدة لتنظيف iframe
function sanitizeIframe($content) {
    // إزالة أي JavaScript خطير
    $content = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $content);
    
    // التأكد من أن src يحتوي على https
    $content = preg_replace('/src=["\']http:\/\//', 'src="https://', $content);
    
    return $content;
}
?>
