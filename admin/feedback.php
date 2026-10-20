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

// 1. Process GET Filters
$search       = trim($_GET['search'] ?? '');
$examinerId   = (int)($_GET['examiner_id'] ?? 0);
$type         = trim($_GET['type'] ?? '');
$ratingFilter = (int)($_GET['rating'] ?? 0);

$allowedTypes = array('Certification', 'Mock');
if (!in_array($type, $allowedTypes, true)) {
    $type = '';
}

// 2. Fetch Examiners for Filter Dropdown
$examinersList = $dao->getData(array('id', 'name'), 'examiners', '1=1 ORDER BY name ASC');
if (!is_array($examinersList)) {
    $examinersList = array();
}

// 3. Overall Summary Statistics (Across All Feedback)
$statsQuery = $dao->query("SELECT 
    COUNT(f.id) AS total_ratings,
    AVG(f.rating) AS avg_rating,
    COUNT(DISTINCT f.exam_id) AS total_exams
FROM feedback f
WHERE f.is_deleted = 0");

$totalRatings = 0;
$overallAvg   = 0.0;
$totalExams   = 0;

if (!empty($statsQuery) && is_array($statsQuery)) {
    $totalRatings = (int)($statsQuery[0]['total_ratings'] ?? 0);
    $overallAvg   = (float)($statsQuery[0]['avg_rating'] ?? 0.0);
    $totalExams   = (int)($statsQuery[0]['total_exams'] ?? 0);
}

// 4. Construct Query for Per-Exam Feedback Summaries
$where = array('1=1', 'f.is_deleted = 0');

if ($examinerId > 0) {
    $where[] = "e.created_by = " . $examinerId;
}

if ($type !== '') {
    $escapedType = addslashes($type);
    $where[]     = "e.category = '$escapedType'";
}

if ($search !== '') {
    $escapedSearch = addslashes($search);
    $where[]       = "e.title LIKE '%$escapedSearch%'";
}

if ($ratingFilter >= 1 && $ratingFilter <= 5) {
    $where[] = "f.rating = " . $ratingFilter;
}

$whereClause = implode(' AND ', $where);

$sql = "SELECT 
            e.id, 
            e.title, 
            e.category, 
            x.name AS examiner_name,
            COUNT(f.id) AS total_ratings, 
            ROUND(AVG(f.rating), 1) AS avg_rating,
            MAX(f.created_at) AS last_rated
        FROM feedback f
        JOIN exams e ON e.id = f.exam_id
        LEFT JOIN examiners x ON x.id = e.created_by
        WHERE $whereClause
        GROUP BY e.id, e.title, e.category, x.name
        ORDER BY last_rated DESC";

$examSummaries = $dao->query($sql);
if (!is_array($examSummaries)) {
    $examSummaries = array();
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
    .exam-title-link {
        font-weight: 700;
        color: var(--accent-cyan, #0284c7);
        text-decoration: none;
        transition: color 0.2s ease;
    }
    .exam-title-link:hover {
        text-decoration: underline;
    }
</style>

<div class="row">
    <div class="col-md-12">
        <h2 style="font-weight: 700; margin-top: 0;">Exam Feedback</h2>
        <p class="text-muted">Summary of feedback and student ratings per exam. Click an exam name to view individual reviews.</p>
        <hr style="margin-top: 10px; margin-bottom: 25px; border-color: var(--border-color, #e2e8f0);" />
    </div>
</div>

<!-- Summary Cards -->
<div class="row" style="margin-bottom: 25px;">
    <div class="col-md-4 col-sm-6">
        <div class="panel-card" style="padding: 20px; display: flex; align-items: center; gap: 18px; margin-bottom: 15px;">
            <div style="width: 52px; height: 52px; border-radius: 12px; background: rgba(56, 189, 248, 0.15); color: #0284c7; display: flex; align-items: center; justify-content: center; font-size: 22px;">
                <i class="fa fa-comments"></i>
            </div>
            <div>
                <div style="font-size: 13px; color: var(--text-muted, #64748b); font-weight: 600; text-transform: uppercase;">Total Ratings</div>
                <div style="font-size: 26px; font-weight: 700; color: var(--text-primary, #0f172a);"><?= number_format($totalRatings); ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-sm-6">
        <div class="panel-card" style="padding: 20px; display: flex; align-items: center; gap: 18px; margin-bottom: 15px;">
            <div style="width: 52px; height: 52px; border-radius: 12px; background: rgba(245, 158, 11, 0.15); color: #d97706; display: flex; align-items: center; justify-content: center; font-size: 22px;">
                <i class="fa fa-star"></i>
            </div>
            <div>
                <div style="font-size: 13px; color: var(--text-muted, #64748b); font-weight: 600; text-transform: uppercase;">Overall Average</div>
                <div style="font-size: 26px; font-weight: 700; color: var(--text-primary, #0f172a);">
                    <?= number_format($overallAvg, 1); ?> <span style="font-size: 15px; color: var(--text-muted, #64748b); font-weight: 500;">/ 5.0</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-sm-12">
        <div class="panel-card" style="padding: 20px; display: flex; align-items: center; gap: 18px; margin-bottom: 15px;">
            <div style="width: 52px; height: 52px; border-radius: 12px; background: rgba(16, 185, 129, 0.15); color: #059669; display: flex; align-items: center; justify-content: center; font-size: 22px;">
                <i class="fa fa-book"></i>
            </div>
            <div>
                <div style="font-size: 13px; color: var(--text-muted, #64748b); font-weight: 600; text-transform: uppercase;">Rated Exams</div>
                <div style="font-size: 26px; font-weight: 700; color: var(--text-primary, #0f172a);"><?= number_format($totalExams); ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Filter Toolbar -->
<div class="panel-card" style="padding: 18px 20px; margin-bottom: 25px;">
    <form method="GET" action="feedback.php" class="row">
        <div class="col-md-4 col-sm-6" style="margin-bottom: 10px;">
            <label style="font-size: 13px; font-weight: 600;">Search Exam Title</label>
            <input type="text" name="search" class="form-control" placeholder="Search by exam title..." value="<?= htmlspecialchars($search); ?>">
        </div>
        <div class="col-md-3 col-sm-6" style="margin-bottom: 10px;">
            <label style="font-size: 13px; font-weight: 600;">Examiner</label>
            <select name="examiner_id" class="form-control">
                <option value="0">All Examiners</option>
                <?php foreach ($examinersList as $ex): ?>
                    <option value="<?= (int)$ex['id']; ?>" <?= $examinerId === (int)$ex['id'] ? 'selected' : ''; ?>>
                        <?= htmlspecialchars($ex['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 col-sm-6" style="margin-bottom: 10px;">
            <label style="font-size: 13px; font-weight: 600;">Exam Type</label>
            <select name="type" class="form-control">
                <option value="">All Types</option>
                <option value="Certification" <?= $type === 'Certification' ? 'selected' : ''; ?>>Certification</option>
                <option value="Mock" <?= $type === 'Mock' ? 'selected' : ''; ?>>Mock</option>
            </select>
        </div>
        <div class="col-md-3 col-sm-12" style="margin-bottom: 10px;">
            <label style="display: block; font-size: 13px; font-weight: 600;">&nbsp;</label>
            <button type="submit" class="btn btn-primary" style="margin-right: 5px;">
                <i class="fa fa-filter"></i> Filter
            </button>
            <a href="feedback.php" class="btn btn-default" title="Reset Filters">
                <i class="fa fa-refresh"></i> Reset
            </a>
        </div>
    </form>
</div>

<!-- Summary Table (One Row Per Exam) -->
<div class="panel-card" style="margin-bottom: 30px;">
    <div class="table-responsive">
        <table class="table table-striped table-hover table-feedback">
            <thead>
                <tr>
                    <th>Exam Name</th>
                    <th>Type</th>
                    <th>Created By</th>
                    <th style="text-align: center;">No. of Ratings</th>
                    <th>Average Rating</th>
                    <th>Last Rated</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($examSummaries)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted" style="padding: 36px 20px;">
                            <i class="fa fa-info-circle fa-2x" style="display: block; margin-bottom: 8px; color: #94a3b8;"></i>
                            No feedback submitted yet.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($examSummaries as $row): 
                        $examId     = (int)$row['id'];
                        $avgScore   = (float)($row['avg_rating'] ?? 0.0);
                        $isLow      = ($avgScore <= 2.0 && $avgScore > 0);
                        $category   = ucfirst(strtolower(trim($row['category'] ?? '')));
                        $badgeClass = ($category === 'Mock') ? 'label-info' : 'label-primary';
                        $examiner   = !empty(trim((string)$row['examiner_name'])) ? $row['examiner_name'] : 'Unknown';
                        $ratingsCnt = (int)($row['total_ratings'] ?? 0);
                    ?>
                        <tr class="<?= $isLow ? 'row-low-rating' : ''; ?>">
                            <td>
                                <a href="feedback_details.php?exam_id=<?= $examId; ?>" class="exam-title-link">
                                    <?= htmlspecialchars($row['title'] ?? 'Exam #' . $examId); ?>
                                </a>
                            </td>
                            <td>
                                <span class="label <?= $badgeClass; ?> badge-category">
                                    <?= htmlspecialchars($category ?: 'N/A'); ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($examiner); ?></td>
                            <td style="text-align: center;">
                                <span class="badge" style="background: var(--table-th-bg, #334155); color: var(--text-primary, #ffffff); font-size: 13px; padding: 5px 10px;">
                                    <?= number_format($ratingsCnt); ?>
                                </span>
                            </td>
                            <td>
                                <span class="star-display"><?= evalio_render_stars($avgScore); ?></span>
                                <strong style="margin-left: 6px; color: var(--text-primary, #0f172a);">
                                    <?= number_format($avgScore, 1); ?> / 5
                                </strong>
                            </td>
                            <td style="white-space: nowrap; color: var(--text-muted, #64748b);">
                                <?= !empty($row['last_rated']) ? date('d M Y, h:i A', strtotime($row['last_rated'])) : 'N/A'; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include('footer.php'); ?>
