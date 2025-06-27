<?php
/**
 * دوال البلوكات المبسطة - بدون أي تعقيدات
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
    
    // المحتوى - بدون أي تنظيف أو تعديل
    $html .= '<div class="block-content">';
    $html .= $block['content']; // هذا هو المفتاح - عرض المحتوى كما هو تماماً
    $html .= '</div>';
    
    // نهاية HTML
    $html .= '</div>';
    
    return $html;
}
?>
