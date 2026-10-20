<?php 

require('../config/autoload.php'); 
require('auth_helper.php');
$student_id = require_login();

$dao = new DataAccess(); 

$results = $dao->getData('*', 'exam_results', 'student_id = ' . $student_id . ' ORDER BY submitted_at DESC');

$titles = array();
$categories = array();
if(!empty($results)) {
    $exam_ids = array_map('intval', array_column($results, 'exam_id'));
    $id_list = !empty($exam_ids) ? implode(',', $exam_ids) : '0';
    $exams = $dao->getData('*', 'exams', 'id IN (' . $id_list . ')');
    if(!empty($exams)) {
        foreach($exams as $ex) {
            $titles[$ex['id']] = $ex['title'];
            $categories[$ex['id']] = $ex['category'];
        }
    }
}

$results = array_values(array_filter($results, function($r) use ($categories) {
    $cat = isset($categories[$r['exam_id']]) ? strtolower(trim($categories[$r['exam_id']])) : '';
    return $cat !== 'mock';
}));

$page_title = 'My Results';
include("header.php"); 
?>

<div class="row">
    <div class="col-md-12">
        <h2>My Exam Results</h2>
        <p class="text-muted">View your performance, scores, provisional evaluations, and generated certificates.</p>
        <hr>
        <?php if(!empty($results)) { ?>
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Exam Title</th>
                            <th>Score / Total Marks</th>
                            <th>Percentage</th>
                            <th>Status &amp; Result</th>
                            <th>Submitted On</th>
                            <th>Certificate</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach($results as $r) { 
                        $is_pending = (isset($r['grading_status']) && $r['grading_status'] === 'pending_review');
                        $total_m = (float)(isset($r['total_marks']) && $r['total_marks'] > 0 ? $r['total_marks'] : $r['total_questions']);
                        $obtained_m = (float)(isset($r['marks_obtained']) ? $r['marks_obtained'] : $r['score']);
                        $percentage = ($total_m > 0) ? ($obtained_m / $total_m) * 100 : 0;
                        $passed = (!$is_pending && $percentage >= 60);
                    ?>
                        <tr>
                            <td>
                                <strong><?= e(isset($titles[$r['exam_id']]) ? $titles[$r['exam_id']] : 'Exam #' . (int)$r['exam_id']); ?></strong>
                            </td>
                            <td>
                                <?php if($is_pending) { ?>
                                    <span style="font-weight: 700; color: #b78103;">
                                        <?= number_format($obtained_m, 2); ?> / <?= number_format($total_m, 2); ?>
                                    </span>
                                    <small class="text-muted">(Provisional)</small>
                                <?php } else { ?>
                                    <strong><?= number_format($obtained_m, 2); ?></strong> / <?= number_format($total_m, 2); ?>
                                <?php } ?>
                            </td>
                            <td>
                                <?php if($is_pending) { ?>
                                    <span class="text-muted"><em>Pending Review</em></span>
                                <?php } else { ?>
                                    <strong><?= number_format($percentage, 2); ?>%</strong>
                                <?php } ?>
                            </td>
                            <td>
                                <?php if($is_pending) { ?>
                                    <span class="label label-warning" style="background:#f39c12;"><i class="fa fa-clock-o"></i> Pending Review</span>
                                <?php } elseif($passed) { ?>
                                    <span class="label label-success" style="background:#27ae60;"><i class="fa fa-check"></i> Passed</span>
                                <?php } else { ?>
                                    <span class="label label-danger" style="background:#e74c3c;"><i class="fa fa-times"></i> Failed</span>
                                <?php } ?>
                            </td>
                            <td><?= date('d M Y, h:i A', strtotime($r['submitted_at'])); ?></td>
                            <td>
                                <?php if($is_pending) { ?>
                                    <span class="text-muted"><i class="fa fa-clock-o"></i> Available after grading</span>
                                <?php } elseif($passed) { ?>
                                    <a href="certificate.php?exam_id=<?= (int)$r['exam_id']; ?>" class="btn btn-primary btn-xs" target="_blank" style="background: linear-gradient(135deg, #0284c7, #38bdf8); border: none; font-weight: 600; padding: 5px 12px; border-radius: 6px; box-shadow: 0 2px 8px rgba(56, 189, 248, 0.25);">
                                        <i class="fa fa-certificate"></i> View Certificate
                                    </a>
                                <?php } else { ?>
                                    <span class="text-muted">Not available</span>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
        <?php } else { ?>
            <div class="alert alert-info">You have not written any exams yet.</div>
        <?php } ?>
    </div>
</div>

<?php include('footer.php'); ?>