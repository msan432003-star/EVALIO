<?php 
require('../config/autoload.php'); 

if(!isset($_SESSION['examiner_id']) || $_SESSION['examiner_id'] == '') {
    echo "<script>alert('Unauthorized Access: Please login first.'); window.location.href='login.php';</script>";
    exit();
} 

$dao = new DataAccess(); 
$exam_id = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;
$examiner_id = (int)$_SESSION['examiner_id'];

// Verify exam existence and examiner ownership
$exam_data = $dao->getData('*', 'exams', 'id = ' . $exam_id . ' AND created_by = ' . $examiner_id);
if(empty($exam_data)) {
    echo "<script>alert('Exam not found or access denied.'); window.location.href='dashboard.php';</script>";
    exit();
}

$exam = $exam_data[0];

// Mock exams are fully auto-graded, so they can never use the Descriptive / Essay type
$is_mock = (stripos($exam['category'] ?? '', 'mock') !== false);

$current_type = !empty($exam['exam_type']) ? $exam['exam_type'] : 'hybrid';
if($is_mock && $current_type === 'essay') {
    $current_type = 'hybrid';
}

// Handle format submission
if(isset($_POST['btn_save_format'])) {
    $selected_format = isset($_POST['exam_type']) ? trim($_POST['exam_type']) : 'hybrid';
    $valid_formats = array('hybrid', 'mcq', 'essay', 'short_answer', 'true_false', 'fill_blank', 'matching');
    if($is_mock) {
        $valid_formats = array_diff($valid_formats, array('essay'));
    }
    if(!in_array($selected_format, $valid_formats)) {
        $selected_format = 'hybrid';
    }

    $update_data = array(
        'exam_type' => $selected_format
    );

    if($dao->update($update_data, 'exams', 'id = ' . $exam_id)) {
        echo "<script>
            alert('Exam format set successfully! Proceeding to add questions.');
            window.location.href = 'addquestion.php?exam_id=" . $exam_id . "';
        </script>";
        exit();
    } else {
        echo "<script>alert('Failed to update exam format. Please try again.');</script>";
    }
}

include("header.php"); 
?>

<style>
    .format-cards-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 18px;
        margin-bottom: 24px;
    }
    .format-card {
        border: 1.5px solid var(--table-border-color);
        border-radius: 14px;
        padding: 20px 22px;
        cursor: pointer;
        transition: all 0.25s ease;
        background: var(--card-bg);
        color: var(--card-text);
        position: relative;
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
        height: 100%;
        min-height: 140px;
        margin-bottom: 0;
        box-sizing: border-box;
    }
    .format-card:hover {
        border-color: #38bdf8;
        box-shadow: 0 0 16px rgba(56, 189, 248, 0.35);
        transform: translateY(-2px);
    }
    .format-card input[type="radio"] {
        position: absolute;
        top: 20px;
        right: 20px;
        transform: scale(1.3);
        accent-color: #38bdf8;
        cursor: pointer;
    }
    .format-card.selected {
        border-color: #38bdf8;
        background: var(--active-menu-bg);
        box-shadow: 0 0 18px rgba(56, 189, 248, 0.45);
    }
    [data-theme="dark"] .format-card.selected {
        border-color: #00d2ff;
        background: rgba(0, 210, 255, 0.12);
        box-shadow: 0 0 18px rgba(0, 210, 255, 0.45);
    }
    .format-icon {
        font-size: 26px;
        color: #38bdf8;
        margin-bottom: 10px;
    }
    .format-title {
        font-size: 17px;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 6px;
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 6px;
    }
    .format-desc {
        font-size: 13px;
        color: var(--text-muted);
        line-height: 1.5;
        margin: 0;
    }
    .badge-hybrid {
        background-color: #0284c7;
        color: #fff;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 600;
    }
    .badge-single {
        background-color: rgba(148, 163, 184, 0.25);
        color: var(--text-muted);
        border: 1px solid var(--table-border-color);
        padding: 3px 8px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 600;
    }

    @media (max-width: 767px) {
        .format-cards-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="row">
    <div class="col-md-12">
        <h2 style="margin-top: 0; color: var(--text-primary);">Select Question Type Mode</h2>
        <p class="text-muted" style="font-size: 15px; margin-bottom: 8px;">
            Exam: <strong style="color: var(--text-primary);"><?= htmlspecialchars($exam['title']); ?></strong> 
            (<?= htmlspecialchars($exam['category']); ?> &bull; <?= (int)$exam['duration_minutes']; ?> Mins)
        </p>
        <p class="text-info" style="font-size: 14px;">
            <i class="fa fa-info-circle"></i> Choose whether this exam contains a mixture of different question types (Hybrid) or a single fixed question type throughout.
        </p>
        <hr style="margin-bottom: 24px;">

        <form method="POST" action="">
            <!-- 1. Hybrid Mode (Recommended) Full Width Box -->
            <div style="margin-bottom: 24px;">
                <label class="format-card <?= ($current_type === 'hybrid' ? 'selected' : ''); ?>" style="border-width: 2px;">
                    <input type="radio" name="exam_type" value="hybrid" <?= ($current_type === 'hybrid' ? 'checked' : ''); ?>>
                    <div class="format-icon"><i class="fa fa-cubes"></i></div>
                    <div class="format-title">
                        Hybrid / Custom Exam 
                        <span class="badge-hybrid">Recommended &bull; Mixed Types</span>
                    </div>
                    <p class="format-desc">
                        <?php if($is_mock) { ?>
                            Create a versatile exam combining multiple auto-graded question formats: <strong>Multiple Choice, Short Answer, True / False, Fill in the Blanks, and Matching Type</strong>. You can choose the question type dynamically for each question as you build the exam.
                        <?php } else { ?>
                            Create a rich, versatile exam combining multiple question formats: <strong>Multiple Choice, Descriptive / Essay, Short Answer, True / False, Fill in the Blanks, and Matching Type</strong>. You can choose the question type dynamically for each question as you build the exam.
                        <?php } ?>
                    </p>
                </label>
            </div>

            <!-- Single Type Options Heading -->
            <h4 style="margin: 0 0 16px 0; color: var(--text-primary); font-weight: 600;">
                <i class="fa fa-lock" style="color: var(--text-muted); margin-right: 6px;"></i> Or Choose a Single Fixed Question Type:
            </h4>

            <!-- Evenly Aligned 2-Column Grid for Single Type Cards -->
            <div class="format-cards-grid">
                <!-- 2. MCQ Only -->
                <label class="format-card <?= ($current_type === 'mcq' ? 'selected' : ''); ?>">
                    <input type="radio" name="exam_type" value="mcq" <?= ($current_type === 'mcq' ? 'checked' : ''); ?>>
                    <div class="format-icon"><i class="fa fa-check-circle-o"></i></div>
                    <div class="format-title">Multiple Choice Only (MCQ) <span class="badge-single">Single Type</span></div>
                    <p class="format-desc">4 choices with one correct option. 100% automatically graded upon submission.</p>
                </label>

                <!-- 3. Essay Only (certification exams only) -->
                <?php if(!$is_mock) { ?>
                <label class="format-card <?= ($current_type === 'essay' ? 'selected' : ''); ?>">
                    <input type="radio" name="exam_type" value="essay" <?= ($current_type === 'essay' ? 'checked' : ''); ?>>
                    <div class="format-icon"><i class="fa fa-file-text-o"></i></div>
                    <div class="format-title">Descriptive / Essay Only <span class="badge-single">Single Type</span></div>
                    <p class="format-desc">In-depth prompts evaluated and scored manually by the examiner with feedback.</p>
                </label>
                <?php } ?>

                <!-- 4. Short Answer Only -->
                <label class="format-card <?= ($current_type === 'short_answer' ? 'selected' : ''); ?>">
                    <input type="radio" name="exam_type" value="short_answer" <?= ($current_type === 'short_answer' ? 'checked' : ''); ?>>
                    <div class="format-icon"><i class="fa fa-align-left"></i></div>
                    <div class="format-title">Short Answer Only <span class="badge-single">Single Type</span></div>
                    <p class="format-desc">Brief text answers matched against keyword variants. Automatically graded.</p>
                </label>

                <!-- 5. True / False Only -->
                <label class="format-card <?= ($current_type === 'true_false' ? 'selected' : ''); ?>">
                    <input type="radio" name="exam_type" value="true_false" <?= ($current_type === 'true_false' ? 'checked' : ''); ?>>
                    <div class="format-icon"><i class="fa fa-toggle-on"></i></div>
                    <div class="format-title">True / False Only <span class="badge-single">Single Type</span></div>
                    <p class="format-desc">Binary statement verification. 100% automatically graded.</p>
                </label>

                <!-- 6. Fill in the Blanks Only -->
                <label class="format-card <?= ($current_type === 'fill_blank' ? 'selected' : ''); ?>">
                    <input type="radio" name="exam_type" value="fill_blank" <?= ($current_type === 'fill_blank' ? 'checked' : ''); ?>>
                    <div class="format-icon"><i class="fa fa-pencil-square-o"></i></div>
                    <div class="format-title">Fill in the Blanks Only <span class="badge-single">Single Type</span></div>
                    <p class="format-desc">Sentence completion with smart synonym/variant tolerance. Automatically graded.</p>
                </label>

                <!-- 7. Matching Type Only -->
                <label class="format-card <?= ($current_type === 'matching' ? 'selected' : ''); ?>">
                    <input type="radio" name="exam_type" value="matching" <?= ($current_type === 'matching' ? 'checked' : ''); ?>>
                    <div class="format-icon"><i class="fa fa-random"></i></div>
                    <div class="format-title">Matching Type Only <span class="badge-single">Single Type</span></div>
                    <p class="format-desc">Interactive Left &amp; Right pair matching with proportional scoring.</p>
                </label>
            </div>

            <div style="margin-top: 20px; margin-bottom: 40px;">
                <button type="submit" name="btn_save_format" class="btn btn-primary btn-lg" style="padding: 10px 28px; font-weight: 700;">
                    <i class="fa fa-arrow-right"></i> Proceed to Add Questions
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.querySelectorAll('.format-card input[type="radio"]').forEach(function(radio) {
    radio.addEventListener('change', function() {
        document.querySelectorAll('.format-card').forEach(function(c) {
            c.classList.remove('selected');
        });
        if(this.checked) {
            this.closest('.format-card').classList.add('selected');
        }
    });
});
</script>

<?php include('footer.php'); ?>