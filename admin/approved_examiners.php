<?php
require('../config/autoload.php');
require('auth.php');

$dao = new DataAccess();

include("header.php");
if (!function_exists('h')) {
    function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}

// All approved examiners with category, profile and number of exams they created
$rows = $dao->query(
    "SELECT ex.id, ex.name, ex.email, ex.phone, ex.profile_photo, ex.created_at, ex.account_status,
            c.name AS category_name,
            p.degree_name, p.designation, p.experience_years,
            (SELECT COUNT(*) FROM exams e WHERE e.created_by = ex.id) AS exam_count
     FROM examiners ex
     LEFT JOIN examiner_profiles p ON p.examiner_id = ex.id
     LEFT JOIN categories c ON c.id = ex.category_id
     WHERE ex.status = 'approved'
     ORDER BY ex.name ASC"
);
if (!is_array($rows)) { $rows = array(); }
?>

<div class="container mt-5">
    <h2>Approved Examiners <small>(<?= count($rows); ?>)</small></h2>
    <p class="text-muted">Click an examiner's name or the View button to see their complete profile photo and details.</p>
    <hr>

    <?php
    // distinct categories for the dropdown
    $catList = array();
    foreach ($rows as $r) { if (!empty($r['category_name'])) { $catList[$r['category_name']] = true; } }
    ksort($catList);
    ?>
    <style>
        .ex-toolbar { display:flex; flex-wrap:wrap; gap:12px; align-items:center; margin-bottom:20px; }
        .ex-search { position:relative; flex:1 1 260px; max-width:380px; }
        .ex-search i.fa-search { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:var(--text-muted); pointer-events:none; }
        .ex-search input { width:100%; height:40px; padding:0 36px 0 38px; border:1px solid var(--input-border); border-radius:20px; background:var(--input-bg); color:var(--input-text); outline:none; transition:border-color .2s, box-shadow .2s; font-size:13.5px; }
        .ex-search input:focus { border-color:var(--input-focus-border); box-shadow:0 0 0 3px var(--input-focus-glow); }
        .ex-clear { position:absolute; right:12px; top:50%; transform:translateY(-50%); cursor:pointer; color:var(--text-muted); display:none; }
        .ex-clear:hover { color:var(--text-primary); }
        .ex-select { height:40px; padding:0 14px; border:1px solid var(--input-border); border-radius:20px; background:var(--input-bg); color:var(--input-text); outline:none; min-width:180px; font-size:13.5px; }
        .ex-select:focus { border-color:var(--input-focus-border); box-shadow:0 0 0 3px var(--input-focus-glow); }
        .ex-count { color:var(--text-muted); font-size:13px; margin-left:auto; }
        #examinerTable tbody tr:hover { background:var(--table-row-hover); }
    </style>
    <div class="ex-toolbar">
        <div class="ex-search">
            <i class="fa fa-search"></i>
            <input type="text" id="searchBox" placeholder="Search name or email" autocomplete="off">
            <i class="fa fa-times-circle ex-clear" id="clearSearch" title="Clear"></i>
        </div>
        <select id="catFilter" class="ex-select">
            <option value="">All categories</option>
            <?php foreach ($catList as $cn => $_) { ?>
                <option value="<?= h(strtolower($cn)); ?>"><?= h($cn); ?></option>
            <?php } ?>
        </select>
        <span class="ex-count" id="resultCount"></span>
    </div>

    <?php if (!empty($rows)) { ?>
    <div class="table-responsive">
        <table class="table table-bordered table-hover" id="examinerTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Category</th>
                    <th>Degree</th>
                    <th>Experience</th>
                    <th>Exams</th>
                    <th>Account</th>
                    <th style="width:90px; text-align:center;">Action</th>
                </tr>
            </thead>
            <tbody>
            <?php $i = 1; foreach ($rows as $r) { 
                $thumb = 'assets/img/find_user.png';
                if (!empty($r['profile_photo'])) {
                    if (is_file(__DIR__ . '/../uploads/profile/' . $r['profile_photo'])) {
                        $thumb = '../uploads/profile/' . rawurlencode($r['profile_photo']);
                    } elseif (is_file(__DIR__ . '/../examinee/uploads/profile/' . $r['profile_photo'])) {
                        $thumb = '../examinee/uploads/profile/' . rawurlencode($r['profile_photo']);
                    }
                }
            ?>
                <tr data-cat="<?= h(strtolower((string)$r['category_name'])); ?>">
                    <td><?= $i++; ?></td>
                    <td>
                        <a href="examiner_details.php?id=<?= (int)$r['id']; ?>" style="display:inline-flex; align-items:center; gap:8px; text-decoration:none;">
                            <img src="<?= $thumb; ?>" alt="" style="width:28px; height:28px; border-radius:50%; object-fit:cover; border:1px solid #d9d9e3;" onerror="this.src='assets/img/find_user.png'">
                            <strong><?= h($r['name']); ?></strong>
                        </a>
                    </td>
                    <td><?= h($r['email']); ?></td>
                    <td><?= !empty($r['category_name']) ? '<span class="label label-info">' . h($r['category_name']) . '</span>' : '-'; ?></td>
                    <td><?= !empty($r['degree_name']) ? h($r['degree_name']) : '-'; ?></td>
                    <td><?= isset($r['experience_years']) ? (int)$r['experience_years'] . ' yr(s)' : '-'; ?></td>
                    <td><?= (int)$r['exam_count']; ?></td>
                    <td>
                        <?php if ($r['account_status'] === 'disabled') { ?>
                            <span class="label label-danger">Disabled</span>
                        <?php } else { ?>
                            <span class="label label-success">Active</span>
                        <?php } ?>
                    </td>
                    <td style="text-align:center;">
                        <a href="examiner_details.php?id=<?= (int)$r['id']; ?>" class="btn btn-info btn-xs" title="View Details">
                            <i class="fa fa-eye"></i> View
                        </a>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
    <?php } else { ?>
        <div class="alert alert-info">No approved examiners yet.</div>
    <?php } ?>
    <div id="noResults" class="alert alert-info" style="display:none;">No examiners match your search.</div>
</div>

<script>
(function () {
    var box = document.getElementById('searchBox'),
        cat = document.getElementById('catFilter'),
        clr = document.getElementById('clearSearch'),
        cnt = document.getElementById('resultCount'),
        none = document.getElementById('noResults'),
        rows = document.querySelectorAll('#examinerTable tbody tr');

    function apply() {
        var q = box.value.toLowerCase().trim(), c = cat.value, shown = 0;
        clr.style.display = box.value ? 'block' : 'none';
        for (var i = 0; i < rows.length; i++) {
            // search only name (col 2) and email (col 3)
            var cells = rows[i].cells;
            var text = (cells[1].textContent + ' ' + cells[2].textContent).toLowerCase();
            var ok = text.indexOf(q) > -1 && (c === '' || rows[i].getAttribute('data-cat') === c);
            rows[i].style.display = ok ? '' : 'none';
            if (ok) shown++;
        }
        cnt.textContent = 'Showing ' + shown + ' of ' + rows.length;
        none.style.display = shown === 0 ? 'block' : 'none';
    }
    box.addEventListener('input', apply);
    cat.addEventListener('change', apply);
    clr.addEventListener('click', function () { box.value = ''; box.focus(); apply(); });
    if (rows.length) apply();
})();
</script>

<?php include('footer.php'); ?>