<?php
/**
 * مدير البلوكات المحسن
 */

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
        $html .= '<div class="card h-100">';
        
        // عرض العنوان
        if ($block['show_title'] && !empty($block['title'])) {
            $html .= '<div class="card-header bg-primary text-white">';
            $html .= '<h5 class="card-title mb-0">' . htmlspecialchars($block['title']) . '</h5>';
            $html .= '</div>';
        }
        
        $html .= '<div class="card-body">';
        
        // عرض المحتوى حسب نوع البلوك
        switch ($block['block_type']) {
            case 'custom':
            case 'html':
                $html .= $block['content'];
                break;
                
            case 'prayer_times':
                $html .= renderPrayerTimesBlock();
                break;
                
            case 'weather':
                $html .= renderWeatherBlock();
                break;
                
            case 'recent_pages':
                $html .= renderRecentPagesBlock($db);
                break;
                
            case 'visitor_stats':
                $html .= renderVisitorStatsBlock($db);
                break;
                
            case 'quran_verse':
                $html .= renderQuranVerseBlock();
                break;
                
            case 'hadith':
                $html .= renderHadithBlock();
                break;
                
            case 'social_links':
                $html .= renderSocialLinksBlock($db);
                break;
                
            case 'quick_links':
                $html .= renderQuickLinksBlock($db);
                break;
                
            case 'text':
                $html .= '<p>' . nl2br(htmlspecialchars($block['content'])) . '</p>';
                break;
                
            case 'announcement':
                $html .= '<div class="alert alert-info">' . $block['content'] . '</div>';
                break;
                
            default:
                $html .= $block['content'];
                break;
        }
        
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        
        // إضافة CSS مخصص إذا كان موجوداً
        if (!empty($block['custom_css'])) {
            $html .= '<style>' . $block['custom_css'] . '</style>';
        }
        
        return $html;
    }
}
?>
