<?php
/* my_exams.php - STEP 1: exams created by the logged-in examiner (search, category, date filter, sort) */
require('../config/autoload.php');
require_once('exam_report_helpers.php');
$examiner_id = er_require_examiner();

$dao = new DataAccess();

// ---------- Filters ----------
$search   = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';
$from     = isset($_GET['from']) ? trim($_GET['from']) : '';
$to       = isset($_GET['to']) ? trim($_GET['to']) : '';
$sort     = isset($_GET['sort']) ? $_GET['sort'] : 'created';
$order    = (isset($_GET['order']) && $_GET['order'] === 'asc') ? 'asc' : 'desc';

if (!in_array($category, array('Certification', 'Mock'))) { $category = ''; }
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) { $from = ''; }
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to))   { $to = ''; }

$sortCols = array('created' => 'e.created_at', 'title' => 'e.title', 'category' => 'e.category');
if (!isset($sortCols[$sort])) { $sort = 'created'; }

$where = "e.created_by = " . $examiner_id;
if ($search !== '')   { $where .= " AND e.title LIKE '%" . addslashes($search) . "%'"; }
if ($category !== '') { $where .= " AND e.category = '" . $category . "'"; }
if ($from !== '')     { $where .= " AND DATE(e.created_at) >= '" . $from . "'"; }
if ($to !== '')       { $where .= " AND DATE(e.created_at) <= '" . $to . "'"; }

$exams = $dao->query(
    "SELECT e.id, e.title, e.category, e.status, e.total_marks, e.created_at
     FROM exams e
     WHERE " . $where . "
     ORDER BY " . $sortCols[$sort] . " " . strtoupper($order)
);
if (!is_array($exams)) { $exams = array(); }

include("header.php");
echo er_styles();
?>

<div class="row">
    <div class="col-md-12">
        <h2>My Exams</h2>
        <p class="text-muted">Click a certification exam for its pass/fail report. Click a mock exam to see its questions and answers.</p>
        <hr>

        <form method="GET" class="form-inline" style="margin-bottom:20px;">
            <div class="form-group" style="margin-right:8px;">
                <input type="text" name="search" class="form-control" placeholder="Search exam name" value="<?= er_h($search); ?>">
            </div>
            <div class="form-group" style="margin-right:8px;">
                <select name="category" class="form-control">
                    <option value="">All Types</option>
                    <option value="Certification" <?= $category === 'Certification' ? 'selected' : ''; ?>>Certification</option>
                    <option value="Mock" <?= $category === 'Mock' ? 'selected' : ''; ?>>Mock</option>
                </select>
            </div>
            <div class="form-group" style="margin-right:8px;">
                <label for="from">Created from</label>
                <input type="date" name="from" id="from" class="form-control" value="<?= er_h($from); ?>">
            </div>
            <div class="form-group" style="margin-right:8px;">
                <label for="to">to</label>
                <input type="date" name="to" id="to" class="form-control" value="<?= er_h($to); ?>">
            </div>
            <div class="form-group" style="margin-right:8px;">
                <select name="sort" class="form-control">
                    <option value="created" <?= $sort === 'created' ? 'selected' : ''; ?>>Sort: Created date</option>
                    <option value="title" <?= $sort === 'title' ? 'selected' : ''; ?>>Sort: Exam name</option>
                    <option value="category" <?= $sort === 'category' ? 'selected' : ''; ?>>Sort: Type</option>
                </select>
                <select name="order" class="form-control">
                    <option value="desc" <?= $order === 'desc' ? 'selected' : ''; ?>>Newest / Z-A</option>
                    <option value="asc" <?= $order === 'asc' ? 'selected' : ''; ?>>Oldest / A-Z</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Search</button>
            <a href="my_exams.php" class="btn btn-default">Reset</a>
        </form>

        <p><strong>Showing:</strong> <?= count($exams); ?> exam(s)</p>

        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Exam</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Total Marks</th>
                        <th>Created On</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($exams)) { $i = 1; foreach ($exams as $x) {
                    $isMock = (strcasecmp($x['category'], 'Mock') === 0);
                    $href = $isMock ? 'exam_detail.php?exam_id=' . (int)$x['id'] : 'exam_report.php?exam_id=' . (int)$x['id'];
                    ?>
                    <tr>
                        <td><?= $i++; ?></td>
                        <td><a class="er-link" href="<?= $href; ?>"><?= er_h($x['title']); ?></a></td>
                        <td><?= er_h($x['category']); ?></td>
                        <td><?= er_h(ucfirst($x['status'])); ?></td>
                        <td><?= er_h(number_format((float)$x['total_marks'], 2)); ?></td>
                        <td><?= !empty($x['created_at']) ? er_h(date('d M Y, h:i A', strtotime($x['created_at']))) : '&mdash;'; ?></td>
                    </tr>
                <?php } } else { ?>
                    <tr><td colspan="6" class="text-center">No exams found.</td></tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>
