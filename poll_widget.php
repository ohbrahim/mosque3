<?php
/**
 * ويدجت الاستطلاعات للموقع الرئيسي
 */
require_once 'config/config.php';

// جلب الاستطلاع النشط
$activePoll = $db->fetchOne("
    SELECT * FROM polls 
    WHERE status = 'active' 
    AND (start_date IS NULL OR start_date <= CURDATE()) 
    AND (end_date IS NULL OR end_date >= CURDATE()) 
    ORDER BY created_at DESC 
    LIMIT 1
");

if (!$activePoll) {
    return; // لا يوجد استطلاع نشط
}

// جلب خيارات الاستطلاع مع عدد الأصوات
$pollOptions = $db->fetchAll("
    SELECT po.*, 
           COUNT(pv.id) as votes_count
    FROM poll_options po 
    LEFT JOIN poll_votes pv ON po.id = pv.option_id 
    WHERE po.poll_id = ? 
    GROUP BY po.id 
    ORDER BY po.display_order
", [$activePoll['id']]);

// إجمالي الأصوات
$totalVotes = array_sum(array_column($pollOptions, 'votes_count'));

// التحقق من تصويت المستخدم
$userVoted = false;
$userVote = null;

if (isset($_SESSION['user_id'])) {
    $userVote = $db->fetchOne("
        SELECT option_id FROM poll_votes 
        WHERE poll_id = ? AND user_id = ?
    ", [$activePoll['id'], $_SESSION['user_id']]);
    $userVoted = (bool)$userVote;
} elseif (isset($_COOKIE['poll_' . $activePoll['id']])) {
    $userVoted = true;
}

// معالجة التصويت
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vote_option']) && !$userVoted) {
    $optionId = (int)$_POST['vote_option'];
    
    // التحقق من صحة الخيار
    $validOption = false;
    foreach ($pollOptions as $option) {
        if ($option['id'] == $optionId) {
            $validOption = true;
            break;
        }
    }
    
    if ($validOption && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $voteData = [
            'poll_id' => $activePoll['id'],
            'option_id' => $optionId,
            'user_id' => $_SESSION['user_id'] ?? null,
            'voter_ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
        ];
        
        if ($db->insert('poll_votes', $voteData)) {
            // تعيين كوكي للزوار غير المسجلين
            if (!isset($_SESSION['user_id'])) {
                setcookie('poll_' . $activePoll['id'], '1', time() + (30 * 24 * 60 * 60), '/');
            }
            
            // إعادة تحميل الصفحة لإظهار النتائج
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
    }
}

// إعادة جلب البيانات بعد التصويت
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pollOptions = $db->fetchAll("
        SELECT po.*, 
               COUNT(pv.id) as votes_count
        FROM poll_options po 
        LEFT JOIN poll_votes pv ON po.id = pv.option_id 
        WHERE po.poll_id = ? 
        GROUP BY po.id 
        ORDER BY po.display_order
    ", [$activePoll['id']]);
    
    $totalVotes = array_sum(array_column($pollOptions, 'votes_count'));
}
?>

<div class="poll-widget">
    <h5 class="poll-title">
        <i class="fas fa-poll text-primary"></i>
        <?php echo htmlspecialchars($activePoll['title']); ?>
    </h5>
    
    <?php if ($activePoll['description']): ?>
        <p class="poll-description"><?php echo htmlspecialchars($activePoll['description']); ?></p>
    <?php endif; ?>
    
    <?php if (!$userVoted): ?>
        <!-- نموذج التصويت -->
        <form method="POST" class="poll-form">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <?php foreach ($pollOptions as $option): ?>
                <div class="poll-option">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="vote_option" 
                               id="option_<?php echo $option['id']; ?>" value="<?php echo $option['id']; ?>" required>
                        <label class="form-check-label" for="option_<?php echo $option['id']; ?>">
                            <?php echo htmlspecialchars($option['option_text']); ?>
                        </label>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <div class="poll-actions">
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="fas fa-vote-yea"></i> صوّت
                </button>
            </div>
        </form>
        
    <?php else: ?>
        <!-- عرض النتائج -->
        <div class="poll-results">
            <?php foreach ($pollOptions as $option): ?>
                <?php 
                $percentage = $totalVotes > 0 ? ($option['votes_count'] / $totalVotes) * 100 : 0;
                $isUserChoice = $userVote && $userVote['option_id'] == $option['id'];
                ?>
                <div class="poll-result-item <?php echo $isUserChoice ? 'user-choice' : ''; ?>">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="option-text">
                            <?php echo htmlspecialchars($option['option_text']); ?>
                            <?php if ($isUserChoice): ?>
                                <i class="fas fa-check text-success ms-1"></i>
                            <?php endif; ?>
                        </span>
                        <span class="vote-count">
                            <?php echo convertToArabicNumbers($option['votes_count']); ?>
                            (<?php echo convertToArabicNumbers(number_format($percentage, 1)); ?>%)
                        </span>
                    </div>
                    <div class="progress poll-progress">
                        <div class="progress-bar" style="width: <?php echo $percentage; ?>%"></div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <div class="poll-stats mt-3">
                <small class="text-muted">
                    <i class="fas fa-users"></i>
                    إجمالي الأصوات: <?php echo convertToArabicNumbers($totalVotes); ?>
                </small>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.poll-widget {
    background: white;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.poll-title {
    color: #2c3e50;
    margin-bottom: 15px;
    font-weight: 600;
}

.poll-description {
    color: #6c757d;
    font-size: 0.9rem;
    margin-bottom: 15px;
}

.poll-option {
    margin-bottom: 10px;
}

.poll-option .form-check-label {
    cursor: pointer;
    padding: 8px 0;
}

.poll-actions {
    margin-top: 15px;
}

.poll-result-item {
    margin-bottom: 15px;
}

.poll-result-item.user-choice {
    background: #e8f5e8;
    padding: 10px;
    border-radius: 5px;
}

.option-text {
    font-weight: 500;
}

.vote-count {
    font-size: 0.9rem;
    color: #6c757d;
}

.poll-progress {
    height: 8px;
    background: #e9ecef;
    border-radius: 4px;
}

.poll-progress .progress-bar {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 4px;
}

.poll-stats {
    text-align: center;
    padding-top: 10px;
    border-top: 1px solid #e9ecef;
}
</style>
