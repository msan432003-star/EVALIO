<?php
require('../config/autoload.php');
require('auth_helper.php');
$student_id = require_login();

$dao = new DataAccess();

// Fetch all available references with exam title and course name
$query = "SELECT r.*, e.title AS exam_title, c.course_name 
          FROM exam_references r
          LEFT JOIN exams e ON e.id = r.exam_id
          LEFT JOIN courses c ON c.id = r.course_id
          ORDER BY r.id DESC";

$refs = $dao->query($query);
if (!is_array($refs)) {
    $refs = array();
}

include('header.php');

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
?>

<div class="container" style="margin-top: 30px; margin-bottom: 60px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="font-weight-bold" style="color: #2b1055;"><i class="fa fa-book"></i> Study References & Materials</h2>
            <p class="text-muted">Curated textbooks, syllabus PDFs, and external references uploaded by your examiners.</p>
        </div>
    </div>

    <div class="row">
        <?php if (!empty($refs)) { ?>
            <?php foreach ($refs as $ref) { 
                $isPdf = ($ref['ref_type'] === 'PDF');
                $link = $isPdf ? '../uploads/' . $ref['file_path'] : $ref['reference_url'];
                $icon = $isPdf ? 'fa-file-pdf-o text-danger' : 'fa-external-link text-primary';
                $btnText = $isPdf ? 'Download / View PDF' : 'Visit Resource';
                $btnClass = $isPdf ? 'btn-outline-danger' : 'btn-outline-primary';
            ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 shadow-sm" style="border-radius: 10px; border: 1px solid #e2e8f0;">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex align-items-center mb-3">
                                <i class="fa <?= $icon; ?> fa-2x mr-3"></i>
                                <div>
                                    <h5 class="card-title font-weight-bold mb-1" style="font-size: 1.1rem; color: #1a202c;"><?= h($ref['title']); ?></h5>
                                    <span class="badge badge-light" style="border: 1px solid #cbd5e0; font-size: 0.75rem;"><?= h($ref['ref_type']); ?></span>
                                </div>
                            </div>
                            
                            <p class="text-muted small mb-2">
                                <i class="fa fa-graduation-cap"></i> <strong>Course:</strong> <?= h($ref['course_name'] ?? 'General'); ?><br>
                                <i class="fa fa-pencil-square-o"></i> <strong>Exam:</strong> <?= h($ref['exam_title'] ?? 'All Candidates'); ?>
                            </p>
                            
                            <div class="mt-auto pt-3">
                                <a href="<?= h($link); ?>" target="_blank" class="btn <?= $btnClass; ?> btn-sm btn-block font-weight-bold" style="border-radius: 6px;">
                                    <i class="fa <?= $isPdf ? 'fa-download' : 'fa-external-link'; ?>"></i> <?= $btnText; ?>
                                </a>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent border-0 text-muted small py-2">
                            <i class="fa fa-clock-o"></i> Added <?= date('d M Y', strtotime($ref['created_at'])); ?>
                        </div>
                    </div>
                </div>
            <?php } ?>
        <?php } else { ?>
            <div class="col-12">
                <div class="alert alert-info text-center py-5" style="border-radius: 8px;">
                    <i class="fa fa-info-circle fa-3x mb-3 text-info"></i>
                    <h4>No Study References Available Yet</h4>
                    <p class="text-muted mb-0">Your instructors have not attached references or study guides yet. Check back soon!</p>
                </div>
            </div>
        <?php } ?>
    </div>
</div>

<?php include('footer.php'); ?>