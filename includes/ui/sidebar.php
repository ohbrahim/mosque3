<?php
// تم إزالة عرض الاستطلاع من هنا لتجنب التكرار
// الاستطلاع يُعرض الآن فقط في index.php
?>

<!-- إعلانات الشريط الجانبي -->
<?php foreach ($sidebarAds as $ad): ?>
    <div class="sidebar-widget text-center">
        <?php if ($ad['link_url']): ?>
            <a href="<?php echo htmlspecialchars($ad['link_url']); ?>" target="_blank" 
               onclick="trackAdClick(<?php echo $ad['id']; ?>)">
        <?php endif; ?>
        
        <?php if ($ad['image']): ?>
            <img src="uploads/<?php echo htmlspecialchars($ad['image']); ?>" 
                 alt="<?php echo htmlspecialchars($ad['title']); ?>" 
                 class="img-fluid rounded">
        <?php else: ?>
            <h6><?php echo htmlspecialchars($ad['title']); ?></h6>
            <?php if ($ad['content']): ?>
                <p><?php echo htmlspecialchars($ad['content']); ?></p>
            <?php endif; ?>
        <?php endif; ?>
        
        <?php if ($ad['link_url']): ?>
            </a>
        <?php endif; ?>
    </div>
<?php endforeach; ?>

<!-- أوقات الصلاة -->
<div class="sidebar-widget">
    <h5>
        <i class="fas fa-clock text-primary me-2"></i>
        أوقات الصلاة
    </h5>
    <?php echo renderPrayerTimesBlock(); ?>
</div>

<!-- آخر الصفحات -->
<?php if (!empty($recentPages)): ?>
    <div class="sidebar-widget">
        <h5>
            <i class="fas fa-newspaper text-primary me-2"></i>
            آخر الصفحات
        </h5>
        <div class="list-group list-group-flush">
            <?php foreach ($recentPages as $page): ?>
                <a href="?page=<?php echo htmlspecialchars($page['slug']); ?>" 
                   class="list-group-item list-group-item-action border-0 px-0">
                    <div class="d-flex w-100 justify-content-between">
                        <h6 class="mb-1"><?php echo htmlspecialchars($page['title']); ?></h6>
                        <small><?php echo formatArabicDate($page['created_at']); ?></small>
                    </div>
                    <?php if ($page['excerpt']): ?>
                        <p class="mb-1 text-muted small"><?php echo truncateText($page['excerpt'], 80); ?></p>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<!-- البلوكات الجانبية -->
<?php foreach ($sidebarBlocks as $block): ?>
    <div class="sidebar-widget">
        <?php echo renderBlock($block); ?>
    </div>
<?php endforeach; ?>

<!-- روابط التواصل الاجتماعي -->
<div class="sidebar-widget text-center">
    <h5>
        <i class="fas fa-share-alt text-primary me-2"></i>
        تابعونا
    </h5>
    <div class="d-flex justify-content-center gap-3">
        <?php if (getSetting('facebook_url')): ?>
            <a href="<?php echo htmlspecialchars(getSetting('facebook_url')); ?>" target="_blank" 
               class="btn btn-outline-primary btn-sm">
                <i class="fab fa-facebook"></i>
            </a>
        <?php endif; ?>
        
        <?php if (getSetting('twitter_url')): ?>
            <a href="<?php echo htmlspecialchars(getSetting('twitter_url')); ?>" target="_blank" 
               class="btn btn-outline-info btn-sm">
                <i class="fab fa-twitter"></i>
            </a>
        <?php endif; ?>
        
        <?php if (getSetting('instagram_url')): ?>
            <a href="<?php echo htmlspecialchars(getSetting('instagram_url')); ?>" target="_blank" 
               class="btn btn-outline-danger btn-sm">
                <i class="fab fa-instagram"></i>
            </a>
        <?php endif; ?>
        
        <?php if (getSetting('youtube_url')): ?>
            <a href="<?php echo htmlspecialchars(getSetting('youtube_url')); ?>" target="_blank" 
               class="btn btn-outline-danger btn-sm">
                <i class="fab fa-youtube"></i>
            </a>
        <?php endif; ?>
    </div>
</div>
