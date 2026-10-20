<?php 
/* exams.php - public page (same look as index.php): browse only, no register / no mock */
require_once('../config/autoload.php'); 

// e() fallback in case it lives in auth_helper.php (not loaded on this public page)
if(!function_exists('e')) {
    function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}
$login_page = 'login.php'; // <-- change to your student login page name (same as the Login button in the top menu)

$dao = new DataAccess(); 
$current_time = time();

// Exams this student already registered for
$registered_ids = array(); // guests have no registrations

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

// Helper: default placeholder image if exam has no uploaded image
function exam_card_image($ex) {
    if (!empty($ex['image'])) {
        return '../uploads/exams/' . htmlspecialchars((string)$ex['image'], ENT_QUOTES, 'UTF-8');
    }
    return 'login/images/img2.jpg';
}

// Merge both lists into one array (tagged) so we can render a single searchable/filterable grid
$combined_exams = array();
foreach($certification_exams as $ex) { $combined_exams[] = array('data' => $ex, 'is_mock' => false); }
foreach($mock_exams as $ex) { $combined_exams[] = array('data' => $ex, 'is_mock' => true); }

$pageTitle = "Evalio - Available Exams & Certifications";
include('includes/header.php');
?>

<style>
    /* Only styles the exam cards / search bar / filter tabs; everything else comes from your existing site css */

    .exams-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 16px;
        max-width: 1100px;
        margin: 0 auto 30px auto;
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
        stroke: #7c4dff;
        pointer-events: none;
    }
    .exams-search-input {
        width: 100%;
        background: #000000;
        border: 1px solid #2a2a2a;
        color: #fff;
        border-radius: 10px;
        padding: 12px 16px 12px 42px;
        font-size: 14px;
        outline: none;
        transition: border-color .15s ease, box-shadow .15s ease;
    }
    .exams-search-input::placeholder { color: #8a8a8a; }
    .exams-search-input:focus {
        border-color: #7c4dff;
        box-shadow: 0 0 0 3px rgba(124, 77, 255, 0.25);
    }

    .exams-filter-tabs {
        display: flex;
        background: #000000;
        border: 1px solid #2a2a2a;
        border-radius: 10px;
        padding: 4px;
        gap: 4px;
        flex: 0 0 auto;
    }
    .exams-filter-tab {
        border: none;
        background: transparent;
        color: #ccc;
        font-size: 13px;
        font-weight: 600;
        padding: 8px 16px;
        border-radius: 8px;
        cursor: pointer;
        transition: background .15s ease, color .15s ease;
        white-space: nowrap;
    }
    .exams-filter-tab:hover { color: #fff; }
    .exams-filter-tab.active { background: #7c4dff; color: #fff; }

    .exams-empty-search {
        text-align: center;
        color: #ccc;
        padding: 30px 0;
        display: none;
    }
    

    /* Force 3 cards per row (on this page only) and keep them narrow/vertical */
    .section .grid-container {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
    }

    .exam-public-card {
        text-align: left;
        padding: 14px;
        max-width: 100%;
    }
    .exam-public-img { width: 100%; height: 110px; object-fit: cover; display: block; border-radius: 8px; margin-bottom: 10px; }
    .exam-public-card h3 { margin: 0 0 6px 0; font-size: 17px; }
    .exam-public-desc { color: #ccc; font-size: 13px; margin: 6px 0 8px 0; }
    .exam-public-desc a { color: #7c4dff; font-weight: 600; text-decoration: none; }
    .exam-public-meta { font-size: 13px; margin: 3px 0; }
    .exam-public-badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; color: #fff; background: #7c4dff; }
    .exam-public-badge.mock { background: #f0ad4e; }
    .exam-public-btn { display: block; text-align: center; margin-top: 12px; padding: 8px 0; font-size: 14px; }
    .exam-public-empty { text-align: center; color: #ccc; }

    /* Stack to fewer columns on smaller screens so cards don't get too cramped */
    @media (max-width: 900px) {
        .section .grid-container { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 600px) {
        .section .grid-container { grid-template-columns: 1fr; }
        .exams-filter-tabs { width: 100%; }
        .exams-filter-tab { flex: 1; text-align: center; }
    }
    
   

</style>
<style>
    .glow-title {
        font-size: 50px; /* increase as needed */
    }
</style>


<section class="section">
    <h2 class="section-title glow-title">Exams</h2>

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
    <div class="grid-container" id="examsGrid">
        <?php foreach($combined_exams as $item) {
            $ex = $item['data'];
            $is_mock = $item['is_mock'];
            $title = $ex['title'] ?? '';
        ?>
            <div class="card-custom exam-public-card" data-category="<?= $is_mock ? 'mock' : 'certification'; ?>" data-title="<?= e(mb_strtolower($title)); ?>">
                <?php if(!$is_mock) { ?>
                    <img src="<?= exam_card_image($ex); ?>" class="exam-public-img" alt="<?= e($title); ?>">
                <?php } ?>

                <h3><a href="exam_info.php?exam_id=<?= (int)$ex['id']; ?>" style="color: inherit; text-decoration: none;" title="View Exam Details"><?= e($title); ?></a></h3>

                <?php if(!$is_mock) { ?>
                    <p class="exam-public-desc">
                        <?= !empty($ex['description']) ? e(mb_strimwidth($ex['description'], 0, 100, '...')) : 'No description provided for this exam.'; ?>
                        <?php if(!empty($ex['description']) && mb_strwidth($ex['description']) > 100) { ?>
                            <a href="exam_info.php?exam_id=<?= (int)$ex['id']; ?>">Show More</a>
                        <?php } ?>
                    </p>
                <?php } ?>

                <p class="exam-public-meta"><strong>Course:</strong> <?= e($courses_lookup[(int)$ex['course_id']] ?? 'N/A'); ?></p>
                <?php if(!$is_mock) { ?>
                    <p class="exam-public-meta"><strong>By:</strong> Prof <?= e($examiners_lookup[(int)$ex['created_by']] ?? 'N/A'); ?></p>
                <?php } ?>
                <p class="exam-public-meta"><strong>Category:</strong> <span class="exam-public-badge<?= $is_mock ? ' mock' : ''; ?>"><?= e($ex['category']); ?></span></p>
                <p class="exam-public-meta"><strong>Duration:</strong> <?= (int)$ex['duration_minutes']; ?> Minutes</p>
                <?php if(!$is_mock) { ?>
                    <p class="exam-public-meta"><strong>Starts At:</strong> <?= date('d M Y, h:i A', strtotime($ex['exam_start_datetime'])); ?></p>
                    <p class="exam-public-meta"><strong>Fee:</strong> &#8377;<?= number_format($ex['price'], 2); ?></p>
                <?php } ?>

                <div style="margin-top: 14px; text-align: right;">
                    <a href="exam_info.php?exam_id=<?= (int)$ex['id']; ?>" style="color: #8b6df0; text-decoration: none; font-size: 13.5px; font-weight: 600;">View Details &rarr;</a>
                </div>
            </div>
        <?php } ?>
    </div>
    <p class="exams-empty-search" id="examsEmptyMsg">No exams match your search.</p>
    <?php } else { ?>
        <p class="exam-public-empty">No exams available right now.</p>
    <?php } ?>
</section>

<script>
(function() {
    var searchInput = document.getElementById('examSearchInput');
    var tabs = document.querySelectorAll('.exams-filter-tab');
    var cards = document.querySelectorAll('#examsGrid .exam-public-card');
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

        if (emptyMsg) {
            emptyMsg.style.display = visibleCount === 0 ? 'block' : 'none';
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', applyFilters);
    }

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

<?php include('includes/footer.php'); ?>