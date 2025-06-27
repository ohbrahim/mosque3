<?php
/**
 * تحديث البلوكات تلقائياً
 */

function autoRefreshBlocks($db) {
    // التحقق من آخر تحديث للبلوكات
    $lastUpdate = getSetting($db, 'blocks_last_update', '');
    $currentTime = time();
    $lastUpdateTime = strtotime($lastUpdate);
    
    // تحديث البلوكات كل 5 دقائق
    if ($currentTime - $lastUpdateTime > 300) { // 300 ثانية = 5 دقائق
        try {
            // تضمين ملف البلوكات المحسنة
            require_once __DIR__ . '/enhanced_blocks_v2.php';
            
            // تحديث بلوك آية اليوم
            $quranBlocks = $db->fetchAll("SELECT * FROM blocks WHERE block_type = 'quran_verse' AND status = 'active'");
            foreach ($quranBlocks as $block) {
                $newContent = renderEnhancedQuranVerseBlock();
                $db->query("UPDATE blocks SET content = ?, updated_at = ? WHERE id = ?", [
                    $newContent,
                    date('Y-m-d H:i:s'),
                    $block['id']
                ]);
            }
            
            // تحديث بلوك حديث اليوم
            $hadithBlocks = $db->fetchAll("SELECT * FROM blocks WHERE block_type = 'hadith' AND status = 'active'");
            foreach ($hadithBlocks as $block) {
                $newContent = renderEnhancedHadithBlock();
                $db->query("UPDATE blocks SET content = ?, updated_at = ? WHERE id = ?", [
                    $newContent,
                    date('Y-m-d H:i:s'),
                    $block['id']
                ]);
            }
            
            // تحديث بلوك إحصائيات الزوار
            $statsBlocks = $db->fetchAll("SELECT * FROM blocks WHERE block_type = 'visitor_stats' AND status = 'active'");
            foreach ($statsBlocks as $block) {
                $newContent = renderEnhancedVisitorStatsBlock($db);
                $db->query("UPDATE blocks SET content = ?, updated_at = ? WHERE id = ?", [
                    $newContent,
                    date('Y-m-d H:i:s'),
                    $block['id']
                ]);
            }
            
            // تحديث وقت آخر تحديث
            $db->query("UPDATE settings SET setting_value = ? WHERE setting_key = 'blocks_last_update'", [date('Y-m-d H:i:s')]);
            
            // إذا لم يكن الإعداد موجوداً، أنشئه
            $exists = $db->fetchOne("SELECT COUNT(*) as count FROM settings WHERE setting_key = 'blocks_last_update'");
            if ($exists['count'] == 0) {
                $db->insert('settings', [
                    'setting_key' => 'blocks_last_update',
                    'setting_value' => date('Y-m-d H:i:s'),
                    'setting_type' => 'datetime',
                    'setting_group' => 'blocks',
                    'display_name' => 'آخر تحديث للبلوكات',
                    'description' => 'وقت آخر تحديث تلقائي للبلوكات'
                ]);
            }
            
        } catch (Exception $e) {
            error_log("Error auto-refreshing blocks: " . $e->getMessage());
        }
    }
}

// إضافة JavaScript لتحديث إحصائيات الزوار في الوقت الفعلي
function addVisitorStatsScript() {
    return '
    <script>
    // تحديث إحصائيات الزوار كل دقيقة
    setInterval(function() {
        // تحديث وقت آخر تحديث
        const lastUpdateElement = document.getElementById("lastUpdate");
        if (lastUpdateElement) {
            const now = new Date();
            const timeString = now.toLocaleTimeString("ar-SA", {
                hour: "2-digit",
                minute: "2-digit",
                second: "2-digit"
            });
            lastUpdateElement.textContent = timeString;
        }
        
        // إضافة تأثير دوران لأيقونة التحديث
        const refreshIcon = document.getElementById("refresh-icon");
        if (refreshIcon) {
            refreshIcon.style.animation = "spin 1s linear";
            setTimeout(() => {
                refreshIcon.style.animation = "spin 2s linear infinite";
            }, 1000);
        }
    }, 60000); // كل دقيقة
    </script>';
}
?>
