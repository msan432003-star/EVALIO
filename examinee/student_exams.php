<?php 
/* student_exams.php */
require('../config/autoload.php'); 
require('auth_helper.php');
$student_id = require_login();

$dao = new DataAccess(); 
$current_time = time();

// Exams this student already registered for
$registrations = $dao->getData('*', 'registrations', 'student_id = ' . $student_id);
$registered_ids = !empty($registrations) ? array_map('intval', array_column($registrations, 'exam_id')) : array();

// Fetch all active exams ordered by start time (both Certification & Mock exams must be status = 'approved')
$all_exams = $dao->getData('*', 'exams', "status = 'approved' ORDER BY exam_start_datetime ASC");

$certification_exams = array();
$mock_exams = array();

if(!empty($all_exams)) {
    foreach($all_exams as $ex) {
        $category = strtolower(trim($ex['category'] ?? ''));
        $type = strtolower(trim($ex['exam_type'] ?? ''));

        // Check if the exam is marked as a mock exam (by category or exam_type column)
        $is_mock = ($category === 'mock' || $type === 'mock' || strpos($category, 'mock') !== false);

        if ($is_mock) {
            // Mock exams are always available for practice
            $mock_exams[] = $ex;
        } else {
            // Certification exams check if end time hasn't passed
            $start_timestamp = strtotime($ex['exam_start_datetime']);
            $end_time = $start_timestamp + ($ex['duration_minutes'] * 60);

            if ($current_time <= $end_time) {
                $certification_exams[] = $ex;
            }
        }
    }
}

// Build lookup: course_id -> course_name
$courses_lookup = array();
$all_courses = $dao->getData('*', 'courses', '1');
if(!empty($all_courses)) {
    foreach($all_courses as $c) {
        $cid = $c['id'] ?? ($c['course_id'] ?? 0);
        $courses_lookup[(int)$cid] = $c['course_name'] ?? ($c['name'] ?? '');
    }
}

// Build lookup: examiner_id -> examiner name (professor who created the exam)
$examiners_lookup = array();
$all_examiners = $dao->getData('*', 'examiners', '1');
if(!empty($all_examiners)) {
    foreach($all_examiners as $ex2) {
        $eid = $ex2['id'] ?? ($ex2['examiner_id'] ?? 0);
        $examiners_lookup[(int)$eid] = $ex2['name'] ?? ($ex2['examiner_name'] ?? ($ex2['fullname'] ?? ''));
    }
}

// Helper: default placeholder image if exam has no uploaded image.
// IMPORTANT: the folder path comes FIRST, the filename comes SECOND.
// If images ever break again, View Source and check this exact order
// in the rendered <img src="..."> tag.
function exam_card_image($ex) {
    if (!empty($ex['image'])) {
        return '../uploads/exams/' . htmlspecialchars((string)$ex['image'], ENT_QUOTES, 'UTF-8');
    }
    return 'login/images/img2.jpg';
}

// Merge both lists into one array (tagged) so we can render a single searchable/filterable grid,
// same approach as the public exams.php page.
$combined_exams = array();
foreach($certification_exams as $ex) { $combined_exams[] = array('data' => $ex, 'is_mock' => false); }
foreach($mock_exams as $ex) { $combined_exams[] = array('data' => $ex, 'is_mock' => true); }

$page_title = 'Available Exams';
include("header.php"); 
?>
<!-- VERSION MARKER: student_exams.php v2026-09-26-01
     If you View Source and DO NOT see this exact comment near the top
     of the page, the browser/server is serving a different, stale copy
     of this file from somewhere else on disk. Find and remove the
     duplicate before troubleshooting anything else. -->

<style>
    /* ---------- Search + filter toolbar (matches the Evalio Theme System) ---------- */
    .exams-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 16px;
        margin: 0 0 28px 0;
    }
    .exams-search-wrap {
        position: relative;
        flex: 1 1 320px;
    }
    .exams-search-wrap svg {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        width: 18px;
        height: 18px;
        stroke: var(--input-icon, #8b6df0);
        pointer-events: none;
        transition: stroke 0.3s ease;
    }
    .exams-search-input {
        width: 100%;
        background: var(--input-bg, #222222);
        border: 1px solid var(--input-border, #333333);
        color: var(--input-color, #ffffff);
        border-radius: 10px;
        padding: 12px 16px 12px 42px;
        font-size: 14px;
        outline: none;
        box-sizing: border-box;
        transition: border-color .15s ease, box-shadow .15s ease, background-color .3s ease, color .3s ease;
    }
    .exams-search-input::placeholder {
        color: var(--input-placeholder, #888888);
    }
    .exams-search-input:focus {
        border-color: var(--input-focus-border, #38bdf8);
        box-shadow: 0 0 0 3px var(--input-focus-glow, rgba(56, 189, 248, 0.3));
    }
    .exams-filter-tabs {
        display: flex;
        background: var(--tabs-bg, #0d1527);
        border: 1px solid var(--tabs-border, #1e293b);
        border-radius: 10px;
        padding: 4px;
        gap: 4px;
        flex: 0 0 auto;
        box-sizing: border-box;
        transition: background-color .3s ease, border-color .3s ease;
    }
    .exams-filter-tab {
        border: none;
        background: transparent;
        color: var(--tab-color, #94a3b8);
        font-size: 13px;
        font-weight: 600;
        padding: 8px 16px;
        border-radius: 8px;
        cursor: pointer;
        transition: background .15s ease, color .15s ease;
        white-space: nowrap;
    }
    .exams-filter-tab:hover {
        color: var(--tab-hover-color, #ffffff);
    }
    .exams-filter-tab.active {
        background: var(--tab-active-bg, #0284c7);
        color: var(--tab-active-color, #ffffff);
    }
    .exams-empty-search {
        text-align: center;
        color: var(--text-muted, #94a3b8);
        padding: 40px 0;
        font-size: 15px;
        display: none;
    }

    /* ---------- Exam cards grid ---------- */
    .exams-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 22px;
        align-items: stretch;
    }
    @media (max-width: 992px) {
        .exams-grid { grid-template-columns: repeat(2, 1fr); gap: 18px; }
    }
    @media (max-width: 600px) {
        .exams-grid { grid-template-columns: 1fr; gap: 16px; }
        .exams-filter-tabs { width: 100%; }
        .exams-filter-tab { flex: 1; text-align: center; padding: 8px 10px; }
    }

    .exam-card {
        background: var(--card-bg, #0d1527);
        border: 1px solid var(--card-border, rgba(255, 255, 255, 0.08));
        border-radius: 16px;
        overflow: hidden;
        box-shadow: var(--card-shadow, 0 4px 20px rgba(0,0,0,0.5));
        display: flex;
        flex-direction: column;
        height: 100%;
        box-sizing: border-box;
        transition: transform 0.25s ease, border-color 0.25s ease, box-shadow 0.25s ease, background-color 0.3s ease;
    }
    .exam-card:hover {
        transform: translateY(-5px);
        border-color: var(--card-hover-border, #38bdf8);
        box-shadow: var(--card-hover-shadow, 0 0 20px rgba(56, 189, 248, 0.35));
    }
    .exam-card-img {
        width: 100%;
        height: 160px;
        object-fit: cover;
        display: block;
        border-top-left-radius: 15px;
        border-top-right-radius: 15px;
    }
    .exam-card-body {
        padding: 18px;
        display: flex;
        flex-direction: column;
        flex: 1;
    }
    .exam-card-title {
        margin: 0 0 8px 0;
        font-weight: 700;
        font-size: 17px;
        color: var(--text-primary, #0f172a);
        line-height: 1.35;
        transition: color 0.3s ease;
    }
    [data-theme="light"] .exam-card-title { color: #0f172a; }
    [data-theme="dark"]  .exam-card-title { color: #ffffff; }
    .exam-card-desc {
        color: var(--card-desc-color, #94a3b8);
        font-size: 13px;
        line-height: 1.5;
        margin: 0 0 12px 0;
        transition: color 0.3s ease;
    }
    .exam-card-desc a {
        color: var(--card-link-color, #38bdf8);
        font-weight: 600;
        text-decoration: none;
        transition: color 0.2s ease;
    }
    .exam-card-desc a:hover {
        text-decoration: underline;
        color: #00e5ff;
    }
    .exam-card-meta {
        font-size: 13px;
        line-height: 1.4;
        margin: 0 0 7px 0;
        color: var(--card-meta-label, #94a3b8);
        transition: color 0.3s ease;
    }
    .exam-card-meta strong {
        color: var(--card-meta-label, #94a3b8);
        font-weight: 600;
        display: inline-block;
        min-width: 74px;
        transition: color 0.3s ease;
    }
    .exam-card-badge {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
        color: var(--badge-cert-color, #ffffff);
        background: var(--badge-cert-bg, #0284c7);
        letter-spacing: 0.3px;
        vertical-align: middle;
    }
    .exam-card-badge.mock {
        background: var(--badge-mock-bg, #ff2a8d);
        color: var(--badge-mock-color, #ffffff);
    }
    .exam-card .text-danger {
        color: var(--card-starts-color, #38bdf8) !important;
        font-weight: 500;
        transition: color 0.3s ease;
    }
    .exam-card-footer {
        margin-top: auto;
        padding-top: 14px;
    }
    .exam-card-footer .btn {
        border-radius: 8px;
        font-weight: 600;
        font-size: 13.5px;
        padding: 10px 16px;
        transition: all 0.2s ease;
    }
    .exam-card-footer .btn-primary {
        background: var(--btn-primary-bg, #5124f1) !important;
        border-color: var(--btn-primary-border, #5124f1) !important;
        color: #ffffff !important;
    }
    .exam-card-footer .btn-primary:hover {
        background: var(--btn-primary-hover, #6338f3) !important;
        transform: translateY(-1px);
        box-shadow: 0 4px 14px rgba(81, 36, 241, 0.4);
    }
    .exam-card-footer .btn-success {
        background: var(--btn-success-bg, #059669) !important;
        border-color: var(--btn-success-bg, #059669) !important;
        color: #ffffff !important;
    }
    .exam-card-footer .btn-success:hover {
        background: var(--btn-success-hover, #10b981) !important;
        transform: translateY(-1px);
        box-shadow: 0 4px 14px rgba(5, 150, 105, 0.4);
    }
    .exam-card-footer .btn-default {
        background: var(--btn-registered-bg, #333333) !important;
        border-color: var(--btn-registered-border, #444444) !important;
        color: var(--btn-registered-color, #ffffff) !important;
    }
    .exam-card-footer .btn-default:hover {
        background: #444444 !important;
        transform: translateY(-1px);
    }
</style>

<div class="row">
    <div class="col-md-12">
        <h2 class="page-header-title">All Exams</h2>
        <hr class="header-divider">
    </div>
</div>

<div class="exams-toolbar">
    <div class="exams-search-wrap">
        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="11" cy="11" r="7" stroke-width="2"></circle>
            <path d="M21 21L16.65 16.65" stroke-width="2" stroke-linecap="round"></path>
        </svg>
        <input type="text" id="examSearchInput" class="exams-search-input" placeholder="Search all exams...">
    </div>

    <div class="exams-filter-tabs">
        <button type="button" class="exams-filter-tab active" data-filter="all">All Exams</button>
        <button type="button" class="exams-filter-tab" data-filter="certification">Certification Exams</button>
        <button type="button" class="exams-filter-tab" data-filter="mock">Mock Exams</button>
    </div>
</div>

<?php if(!empty($combined_exams)) { ?>
<div class="exams-grid" id="examsGrid">
    <?php foreach($combined_exams as $item) {
        $ex = $item['data'];
        $is_mock = $item['is_mock'];
        $title = $ex['title'] ?? '';
        $is_registered = !$is_mock && in_array((int)$ex['id'], $registered_ids);
    ?>
        <div class="exam-card" data-category="<?= $is_mock ? 'mock' : 'certification'; ?>" data-title="<?= e(mb_strtolower($title)); ?>">
            <?php if(!$is_mock) { ?>
                <!-- DEBUG: raw path visible via View Source so you can confirm the order is correct -->
                <!-- <?= exam_card_image($ex); ?> -->
                <img src="<?= exam_card_image($ex); ?>" class="exam-card-img" alt="<?= e($title); ?>">
            <?php } ?>
            <div class="exam-card-body">
                <h4 class="exam-card-title"><?= e($title); ?></h4>

                <?php if(!$is_mock) { ?>
                    <p class="exam-card-desc">
                        <?= !empty($ex['description']) ? e(mb_strimwidth($ex['description'], 0, 100, '...')) : 'No description provided for this exam.'; ?>
                        <?php if(!empty($ex['description']) && mb_strwidth($ex['description']) > 100) { ?>
                            <a href="exam_details.php?exam_id=<?= (int)$ex['id']; ?>">Show More</a>
                        <?php } ?>
                    </p>
                <?php } ?>

                <p class="exam-card-meta"><strong>Course:</strong> <?= e($courses_lookup[(int)$ex['course_id']] ?? 'N/A'); ?></p>
                <?php if(!$is_mock) { ?>
                    <p class="exam-card-meta"><strong>By:</strong> Prof <?= e($examiners_lookup[(int)$ex['created_by']] ?? 'N/A'); ?></p>
                <?php } ?>
                <p class="exam-card-meta"><strong>Category:</strong> <span class="exam-card-badge<?= $is_mock ? ' mock' : ''; ?>"><?= e($ex['category']); ?></span></p>
                <p class="exam-card-meta"><strong>Duration:</strong> <?= (int)$ex['duration_minutes']; ?> Minutes</p>
                <?php if(!$is_mock) { ?>
                    <p class="exam-card-meta"><strong>Starts At:</strong> <span class="text-danger"><?= date('d M Y, h:i A', strtotime($ex['exam_start_datetime'])); ?></span></p>
                    <p class="exam-card-meta"><strong>Fee:</strong> &#8377;<?= number_format($ex['price'], 2); ?></p>
                <?php } ?>

                <div class="exam-card-footer">
                    <?php if($is_mock) { ?>
                        <a href="takeexam.php?exam_id=<?= (int)$ex['id']; ?>" class="btn btn-success btn-block">Take Mock Exam Now</a>
                    <?php } elseif($is_registered) { ?>
                        <a href="my_exams.php" class="btn btn-default btn-block">Registered - Go to My Exams</a>
                    <?php } else { ?>
                        <a href="register_exam.php?exam_id=<?= (int)$ex['id']; ?>" class="btn btn-primary btn-block">Register</a>
                    <?php } ?>
                </div>
            </div>
        </div>
    <?php } ?>
</div>
<p class="exams-empty-search" id="examsEmptyMsg">No exams match your search.</p>
<?php } else { ?>
    <p class="text-muted">No exams available right now.</p>
<?php } ?>

<script>
(function() {
    var searchInput = document.getElementById('examSearchInput');
    var tabs = document.querySelectorAll('.exams-filter-tab');
    var cards = document.querySelectorAll('#examsGrid .exam-card');
    var emptyMsg = document.getElementById('examsEmptyMsg');
    var activeFilter = 'all';

    function applyFilters() {
        if (!cards.length) return;
        var query = (searchInput ? searchInput.value : '').trim().toLowerCase();
        var visibleCount = 0;

        cards.forEach(function(card) {
            var matchesCategory = (activeFilter === 'all' || card.getAttribute('data-category') === activeFilter);
            var matchesSearch = card.getAttribute('data-title').indexOf(query) !== -1;
            var show = matchesCategory && matchesSearch;
            card.style.display = show ? '' : 'none';
            if (show) visibleCount++;
        });

        if (emptyMsg) { emptyMsg.style.display = visibleCount === 0 ? 'block' : 'none'; }
    }

    if (searchInput) { searchInput.addEventListener('input', applyFilters); }

    tabs.forEach(function(tab) {
        tab.addEventListener('click', function() {
            tabs.forEach(function(t) { t.classList.remove('active'); });
            tab.classList.add('active');
            activeFilter = tab.getAttribute('data-filter');
            applyFilters();
        });
    });
})();
</script>

<?php include('footer.php'); ?>