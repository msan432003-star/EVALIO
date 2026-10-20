<?php
require('../config/autoload.php');
require('auth.php');

$dao = new DataAccess();

// Helper for text star rendering (avoids missing glyphs in icon fonts)
if (!function_exists('evalio_render_stars')) {
    function evalio_render_stars($rating) {
        $rounded = (int)round((float)$rating);
        $rounded = max(0, min(5, $rounded));
        $stars = '';
        for ($i = 1; $i <= 5; $i++) {
            $stars .= ($i <= $rounded) ? '★' : '☆';
        }
        return $stars;
    }
}

// 1. Validate Exam ID
$examId = (int)($_GET['exam_id'] ?? 0);

// 2. Fetch Exam & Examiner Details
$exam = null;
if ($examId > 0) {
    $examQuery = $dao->query("SELECT 
        e.id, 
        e.title, 
        e.category, 
        e.created_by, 
        x.name AS examiner_name
    FROM exams e
    LEFT JOIN examiners x ON x.id = e.created_by
    WHERE e.id = " . $examId);

    if (!empty($examQuery) && is_array($examQuery)) {
        $exam = $examQuery[0];
    }
}

// 3. If Exam Exists, Fetch All Feedback & Breakdown
$feedbackList = array();
$totalRatings = 0;
$avgRating    = 0.0;
$breakdown    = array(5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0);

if ($exam !== null) {
    $feedbackQuery = $dao->query("SELECT 
        f.id, 
        f.student_id, 
        f.rating, 
        f.comments, 
        f.created_at, 
        s.name AS examinee_name
    FROM feedback f
    LEFT JOIN examinees s ON s.id = f.student_id
    WHERE f.exam_id = " . $examId . " AND f.is_deleted = 0
    ORDER BY f.created_at DESC");

    if (is_array($feedbackQuery)) {
        $feedbackList = $feedbackQuery;
        $totalRatings = count($feedbackList);
        $sumRatings   = 0;
        foreach ($feedbackList as $fb) {
            $r = (int)$fb['rating'];
            $sumRatings += $r;
            if (isset($breakdown[$r])) {
                $breakdown[$r]++;
            }
        }
        if ($totalRatings > 0) {
            $avgRating = round($sumRatings / $totalRatings, 1);
        }
    }
}

include('header.php');
?>

<style>
    .panel-card {
        background: var(--card-bg, #ffffff);
        border: 1px solid var(--border-color, #e2e8f0);
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.04);
        overflow: hidden;
    }
    .table-feedback {
        margin-bottom: 0;
        width: 100%;
    }
    .table-feedback th {
        background: var(--table-th-bg, #f8fafc);
        color: var(--text-primary, #0f172a);
        font-weight: 700;
        font-size: 13px;
        border-bottom: 2px solid var(--border-color, #e2e8f0) !important;
        white-space: nowrap;
        padding: 12px 16px;
    }
    .table-feedback td {
        vertical-align: middle !important;
        border-top: 1px solid var(--border-color, #e2e8f0) !important;
        font-size: 13.5px;
        color: var(--text-primary, #1e293b);
        padding: 12px 16px;
    }
    .row-low-rating {
        border-left: 4px solid #ef4444 !important;
        background: rgba(239, 68, 68, 0.04);
    }
    .star-display {
        color: #f59e0b;
        font-size: 15px;
        letter-spacing: 1px;
        white-space: nowrap;
    }
    .badge-category {
        font-size: 11px;
        padding: 4px 9px;
        border-radius: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        display: inline-block;
    }
    .rating-row {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 9px;
        font-size: 13px;
    }
    .rating-row-label {
        width: 48px;
        font-weight: 600;
        color: var(--text-primary, #0f172a);
        white-space: nowrap;
    }
    .rating-bar-wrap {
        flex: 1;
        height: 10px;
        background: var(--bg-hover, #e2e8f0);
        border-radius: 10px;
        overflow: hidden;
    }
    .rating-bar-fill {
        height: 100%;
        background: linear-gradient(90deg, #f59e0b, #d97706);
        border-radius: 10px;
        transition: width 0.3s ease;
    }
    .rating-row-count {
        width: 85px;
        text-align: right;
        color: var(--text-muted, #64748b);
        font-weight: 500;
        white-space: nowrap;
    }
</style>

<div class="row">
    <div class="col-md-12">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 15px;">
            <div>
                <h2 style="font-weight: 700; margin-top: 0; margin-bottom: 6px;">Exam Feedback Details</h2>
                <p class="text-muted" style="margin-bottom: 0;">Comprehensive review breakdown and student feedback log.</p>
            </div>
            <div>
                <a href="feedback.php" class="btn btn-default">
                    <i class="fa fa-arrow-left"></i> Back to Exam Feedback
                </a>
            </div>
        </div>
        <hr style="margin-top: 0; margin-bottom: 25px; border-color: var(--border-color, #e2e8f0);" />
    </div>
</div>

<?php if ($exam === null): ?>
    <!-- Invalid / Not Found Exam Notice -->
    <div class="panel-card" style="padding: 36px 24px; text-align: center; margin-bottom: 30px;">
        <i class="fa fa-exclamation-triangle fa-3x" style="color: #ef4444; margin-bottom: 12px; display: block;"></i>
        <h4 style="font-weight: 700; color: var(--text-primary, #0f172a); margin-bottom: 8px;">Exam Not Found</h4>
        <p class="text-muted" style="margin-bottom: 20px;">The requested exam does not exist or has been removed.</p>
        <a href="feedback.php" class="btn btn-primary">
            <i class="fa fa-arrow-left"></i> Return to Feedback List
        </a>
    </div>
<?php else: 
    $category   = ucfirst(strtolower(trim($exam['category'] ?? '')));
    $badgeClass = ($category === 'Mock') ? 'label-info' : 'label-primary';
    $examiner   = !empty(trim((string)$exam['examiner_name'])) ? $exam['examiner_name'] : 'Unknown';
?>
    <!-- Top Details Card -->
    <div class="panel-card" style="padding: 24px; margin-bottom: 25px;">
        <div class="row">
            <div class="col-md-7 col-sm-12" style="border-right: 1px solid var(--border-color, #e2e8f0);">
                <div style="margin-bottom: 10px;">
                    <span class="label <?= $badgeClass; ?> badge-category" style="margin-right: 8px;">
                        <?= htmlspecialchars($category ?: 'N/A'); ?>
                    </span>
                    <span class="text-muted" style="font-size: 13px;">Exam ID: #<?= (int)$exam['id']; ?></span>
                </div>
                <h3 style="margin-top: 0; margin-bottom: 14px; font-weight: 700; color: var(--text-primary, #0f172a);">
                    <?= htmlspecialchars($exam['title'] ?? 'Exam #' . (int)$exam['id']); ?>
                </h3>
                <p style="margin-bottom: 16px; font-size: 14px; color: var(--text-muted, #64748b);">
                    <strong>Created By:</strong> <?= htmlspecialchars($examiner); ?>
                </p>
                <div style="display: flex; align-items: center; gap: 20px; flex-wrap: wrap;">
                    <div style="display: flex; align-items: baseline; gap: 8px;">
                        <span style="font-size: 32px; font-weight: 800; color: var(--text-primary, #0f172a); line-height: 1;">
                            <?= number_format($avgRating, 1); ?>
                        </span>
                        <span style="font-size: 16px; color: var(--text-muted, #64748b); font-weight: 600;">/ 5.0</span>
                    </div>
                    <div>
                        <div class="star-display" style="font-size: 18px; margin-bottom: 4px;">
                            <?= evalio_render_stars($avgRating); ?>
                        </div>
                        <div style="font-size: 13px; color: var(--text-muted, #64748b); font-weight: 600;">
                            Based on <?= number_format($totalRatings); ?> <?= $totalRatings === 1 ? 'rating' : 'ratings'; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rating Breakdown Chart -->
            <div class="col-md-5 col-sm-12" style="padding-left: 25px;">
                <h5 style="margin-top: 5px; margin-bottom: 15px; font-weight: 700; color: var(--text-primary, #0f172a);">
                    Rating Breakdown
                </h5>
                <?php for ($star = 5; $star >= 1; $star--): 
                    $starCount = $breakdown[$star] ?? 0;
                    $percent   = ($totalRatings > 0) ? round(($starCount / $totalRatings) * 100, 1) : 0;
                ?>
                    <div class="rating-row">
                        <span class="rating-row-label"><?= $star; ?> ★</span>
                        <div class="rating-bar-wrap">
                            <div class="rating-bar-fill" style="width: <?= $percent; ?>%;"></div>
                        </div>
                        <span class="rating-row-count"><?= number_format($starCount); ?> (<?= $percent; ?>%)</span>
                    </div>
                <?php endfor; ?>
            </div>
        </div>
    </div>

    <!-- Individual Ratings Table -->
    <div class="panel-card" style="margin-bottom: 30px;">
        <div style="padding: 16px 20px; border-bottom: 1px solid var(--border-color, #e2e8f0); background: var(--table-th-bg, #f8fafc);">
            <h4 style="margin: 0; font-size: 15px; font-weight: 700; color: var(--text-primary, #0f172a);">
                <i class="fa fa-list" style="margin-right: 6px;"></i> All Student Reviews (<?= number_format($totalRatings); ?>)
            </h4>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-hover table-feedback">
                <thead>
                    <tr>
                        <th style="width: 40px; text-align: center;">#</th>
                        <th>Examinee Name</th>
                        <th>Rating</th>
                        <th>Comment</th>
                        <th style="white-space: nowrap;">Date &amp; Time Rated</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($feedbackList)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted" style="padding: 36px 20px;">
                                <i class="fa fa-info-circle fa-2x" style="display: block; margin-bottom: 8px; color: #94a3b8;"></i>
                                No feedback submitted for this exam yet.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        $idx = 1;
                        foreach ($feedbackList as $row): 
                            $ratingVal = (int)($row['rating'] ?? 0);
                            $isLow     = ($ratingVal <= 2 && $ratingVal > 0);
                            $examinee  = !empty(trim((string)$row['examinee_name'])) ? $row['examinee_name'] : ('Student #' . (int)$row['student_id']);
                            $comment   = trim((string)($row['comments'] ?? ''));
                        ?>
                            <tr class="<?= $isLow ? 'row-low-rating' : ''; ?>">
                                <td style="text-align: center; color: var(--text-muted, #94a3b8); font-weight: 600;">
                                    <?= $idx++; ?>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($examinee); ?></strong>
                                </td>
                                <td>
                                    <span class="star-display"><?= evalio_render_stars($ratingVal); ?></span>
                                    <strong style="margin-left: 6px; color: var(--text-primary, #0f172a);">
                                        <?= $ratingVal; ?> / 5
                                    </strong>
                                </td>
                                <td style="max-width: 420px;">
                                    <?php if ($comment !== ''): ?>
                                        <?= nl2br(htmlspecialchars($comment)); ?>
                                    <?php else: ?>
                                        <span class="text-muted">No comment</span>
                                    <?php endif; ?>
                                </td>
                                <td style="white-space: nowrap; color: var(--text-muted, #64748b);">
                                    <?= !empty($row['created_at']) ? date('d M Y, h:i A', strtotime($row['created_at'])) : 'N/A'; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<script>
// Highlight 'Exam Feedback' in admin sidebar when viewing details
document.addEventListener("DOMContentLoaded", function() {
    var link = document.querySelector('a[href="feedback.php"]');
    if (link) {
        link.classList.add('active-menu');
    }
});
</script>

<?php include('footer.php'); ?>
