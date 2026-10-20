<?php
/* exam_report_helpers.php - shared helpers for the examiner exam report pages */

// Minimum percentage needed to pass (no pass mark column exists in the database, so change it here)
if (!defined('PASS_PERCENT')) {
    define('PASS_PERCENT', 40);
}

function er_h($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Every report page: examiner must be logged in. Returns the examiner id.
function er_require_examiner()
{
    if (!isset($_SESSION['examiner_id']) || $_SESSION['examiner_id'] == '') {
        echo "<script>alert('Unauthorized Access: Please login first.'); window.location.href='login.php';</script>";
        exit();
    }
    return (int)$_SESSION['examiner_id'];
}

// Returns 'Pass', 'Fail' or 'Pending' for one exam_results row
function er_status($row)
{
    if ($row['grading_status'] !== 'completed') {
        return 'Pending';
    }
    $total = (float)$row['total_marks'];
    if ($total <= 0) {
        return 'Fail';
    }
    return (((float)$row['marks_obtained'] / $total) * 100 >= PASS_PERCENT) ? 'Pass' : 'Fail';
}

function er_badge($status)
{
    $map = array('Pass' => 'label-success', 'Fail' => 'label-danger', 'Pending' => 'label-warning');
    $text = ($status === 'Pending') ? 'Pending review' : $status;
    return '<span class="label ' . (isset($map[$status]) ? $map[$status] : 'label-default') . '">' . er_h($text) . '</span>';
}

function er_rate($pass, $total)
{
    return $total > 0 ? round(($pass / $total) * 100, 1) : 0;
}

// Circle (donut) graph, pure CSS
function er_donut($pass, $fail, $size = 150)
{
    $pass = (int)$pass;
    $fail = (int)$fail;
    $total = $pass + $fail;
    $hole = (int)round($size * 0.58);
    $offset = (int)round(($size - $hole) / 2);

    if ($total > 0) {
        $deg = round(($pass / $total) * 360, 2);
        $bg = "conic-gradient(#16a34a 0deg {$deg}deg, #dc2626 {$deg}deg 360deg)";
        $center = er_rate($pass, $total) . '%';
    } else {
        $bg = '#9ca3af';
        $center = 'N/A';
    }

    $html  = '<div class="er-donut-wrap">';
    $html .= '<div class="er-donut" style="width:' . $size . 'px;height:' . $size . 'px;background:' . $bg . ';">';
    $html .= '<div class="er-donut-hole" style="width:' . $hole . 'px;height:' . $hole . 'px;top:' . $offset . 'px;left:' . $offset . 'px;">' . $center . '</div>';
    $html .= '</div>';
    $html .= '<div class="er-legend">';
    $html .= '<div><span class="er-dot" style="background:#16a34a"></span> Passed: <strong>' . $pass . '</strong></div>';
    $html .= '<div><span class="er-dot" style="background:#dc2626"></span> Failed: <strong>' . $fail . '</strong></div>';
    $html .= '</div></div>';
    return $html;
}

// Styles (use the examiner theme variables where they exist, so dark / light mode both work)
function er_styles()
{
    return '<style>
    .er-card { background:var(--card-bg,#fff); color:var(--card-text,#333); border:1px solid var(--table-border-color,#ddd); border-radius:10px; padding:18px 22px; margin-bottom:22px; }
    .er-card h4 { margin-top:0; color:var(--text-primary,#222); }
    .er-donut-wrap { display:flex; align-items:center; gap:22px; }
    .er-donut { position:relative; border-radius:50%; flex:0 0 auto; }
    .er-donut-hole { position:absolute; border-radius:50%; background:var(--card-bg,#fff); color:var(--text-primary,#222); display:flex; align-items:center; justify-content:center; font-weight:700; font-size:17px; }
    .er-legend div { margin:5px 0; font-size:14px; }
    .er-dot { display:inline-block; width:12px; height:12px; border-radius:50%; margin-right:6px; }
    .er-link { font-weight:600; }
    .er-q { border:1px solid var(--table-border-color,#ddd); border-radius:8px; padding:14px 18px; margin-bottom:14px; background:var(--card-bg,#fff); color:var(--card-text,#333); }
    .er-q-head { display:flex; justify-content:space-between; gap:10px; margin-bottom:8px; }
    .er-opt { padding:5px 10px; margin:4px 0; border-radius:5px; border:1px solid var(--table-border-color,#ddd); }
    .er-opt.correct { background:rgba(22,163,74,0.15); border-color:#16a34a; font-weight:600; }
    .er-blank { border-bottom:1px dashed #9ca3af; height:60px; margin-top:6px; }
    </style>';
}
