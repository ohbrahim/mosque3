<?php
/**
 * دوال البلوكات المحدثة
 */

// دالة لفك تشفير HTML entities
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

// دالة لعرض البلوكات
function renderBlock($block) {
    global $db;
    
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
    
    // عرض المحتوى حسب نوع البلوك
    switch ($block['block_type']) {
        case 'iframe':
        case 'marquee':
        case 'html':
        case 'custom':
            // عرض المحتوى مباشرة
            $html .= $content;
            break;
            
        case 'quran_verse':
            // استخدام دالة آية قرآنية محسنة إذا كانت موجودة
            if (function_exists('renderEnhancedQuranVerseBlock')) {
                $html .= renderEnhancedQuranVerseBlock();
            } else {
                $html .= $content;
            }
            break;
            
        case 'hadith':
            // استخدام دالة حديث محسنة إذا كانت موجودة
            if (function_exists('renderEnhancedHadithBlock')) {
                $html .= renderEnhancedHadithBlock();
            } else {
                $html .= $content;
            }
            break;
            
        case 'visitor_stats':
            // استخدام دالة إحصائيات الزوار المحسنة إذا كانت موجودة
            if (function_exists('renderEnhancedVisitorStatsBlock')) {
                $html .= renderEnhancedVisitorStatsBlock($db);
            } else {
                $html .= $content;
            }
            break;
            
        case 'recent_pages':
            // استخدام دالة الصفحات الحديثة المحسنة إذا كانت موجودة
            if (function_exists('renderEnhancedRecentPagesBlock')) {
                $html .= renderEnhancedRecentPagesBlock($db);
            } else {
                $html .= $content;
            }
            break;
            
        case 'social_links':
            // استخدام دالة روابط التواصل الاجتماعي المحسنة إذا كانت موجودة
            if (function_exists('renderEnhancedSocialLinksBlock')) {
                $html .= renderEnhancedSocialLinksBlock($db);
            } else {
                $html .= $content;
            }
            break;
            
        default:
            // عرض المحتوى مباشرة لأنواع البلوكات الأخرى
            $html .= $content;
            break;
    }
    
    $html .= '</div>';
    $html .= '</div>';
    
    return $html;
}

// تضمين ملف البلوكات المحسنة إذا كان موجوداً
$enhanced_blocks_file = __DIR__ . '/enhanced_blocks.php';
if (file_exists($enhanced_blocks_file)) {
    require_once $enhanced_blocks_file;
}
?>
