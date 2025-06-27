<?php
/**
 * دوال البلوكات النهائية مع إصلاح HTML entities
 */

function renderBlock($block) {
    // التأكد من وجود المحتوى
    if (empty($block['content'])) {
        return '';
    }
    
    // فك تشفير HTML entities إذا كان المحتوى مُرمزاً
    $content = decodeBlockContent($block['content']);
    
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
    $html .= $content; // المحتوى بعد فك التشفير
    $html .= '</div>';
    $html .= '</div>';
    
    return $html;
}

// دالة لفك تشفير محتوى البلوك
function decodeBlockContent($content) {
    // التحقق من وجود HTML entities
    if (strpos($content, '&lt;') !== false || strpos($content, '&gt;') !== false || strpos($content, '&nbsp;') !== false) {
        // فك تشفير HTML entities
        $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // إصلاحات إضافية
        $content = str_replace('&nbsp;', ' ', $content);
        $content = str_replace('&amp;', '&', $content);
        
        // إزالة <br> الزائدة التي قد تكون من التحرير
        $content = preg_replace('/<br\s*\/?>\s*&lt;/', '<', $content);
        $content = preg_replace('/&gt;\s*<br\s*\/?>/', '>', $content);
        
        // إصلاح العلامات المكسورة
        $content = preg_replace('/&lt;(\/?[a-zA-Z][^&]*?)&gt;/', '<$1>', $content);
    }
    
    return $content;
}

// دالة لتنظيف المحتوى عند الحفظ
function sanitizeBlockContent($content) {
    // إزالة العلامات الخطيرة
    $content = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $content);
    $content = preg_replace('/<iframe[^>]*src=["\'](?!https:\/\/www\.islamicfinder\.org)[^"\']*["\'][^>]*>/i', '', $content);
    
    return $content;
}
?>
