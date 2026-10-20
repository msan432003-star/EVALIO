<?php
/* report_helpers.php - shared helpers for the admin examiner reports */

// Minimum percentage needed to pass (your database has no pass mark column, so change it here)
if (!defined('PASS_PERCENT')) {
    define('PASS_PERCENT', 40);
}

function rp_h($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// SQL condition for "this result is a pass" (alias r = exam_results)
function rp_pass_sql()
{
    return "(r.grading_status = 'completed' AND r.total_marks > 0 AND (r.marks_obtained / r.total_marks) * 100 >= " . (float)PASS_PERCENT . ")";
}

// SQL condition for "this result is a fail"
function rp_fail_sql()
{
    return "(r.grading_status = 'completed' AND (r.total_marks <= 0 OR (r.marks_obtained / r.total_marks) * 100 < " . (float)PASS_PERCENT . "))";
}

// Returns 'Pass', 'Fail' or 'Pending' for one exam_results row
function rp_status($row)
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

function rp_badge($status)
{
    $map = array('Pass' => 'label-success', 'Fail' => 'label-danger', 'Pending' => 'label-warning');
    $text = ($status === 'Pending') ? 'Pending review' : $status;
    return '<span class="label ' . (isset($map[$status]) ? $map[$status] : 'label-default') . '">' . rp_h($text) . '</span>';
}

function rp_rate($pass, $total)
{
    return $total > 0 ? round(($pass / $total) * 100, 1) : 0;
}

// Small donut chart (pure CSS, no library needed)
function rp_donut($pass, $fail, $size = 130)
{
    $pass = (int)$pass;
    $fail = (int)$fail;
    $total = $pass + $fail;
    $hole = (int)round($size * 0.58);
    $offset = (int)round(($size - $hole) / 2);

    if ($total > 0) {
        $deg = round(($pass / $total) * 360, 2);
        $bg = "conic-gradient(#10b981 0deg {$deg}deg, #ef4444 {$deg}deg 360deg)";
        $center = rp_rate($pass, $total) . '%';
    } else {
        $bg = 'var(--donut-empty-bg, #cbd5e1)';
        $center = 'N/A';
    }

    $html  = '<div class="rp-donut-wrap">';
    $html .= '<div class="rp-donut" style="width:' . $size . 'px;height:' . $size . 'px;background:' . $bg . ';">';
    $html .= '<div class="rp-donut-hole" style="width:' . $hole . 'px;height:' . $hole . 'px;top:' . $offset . 'px;left:' . $offset . 'px;">' . $center . '</div>';
    $html .= '</div>';
    $html .= '<div class="rp-legend">';
    $html .= '<div><span class="rp-dot" style="background:#10b981"></span> Passed: <strong>' . $pass . '</strong></div>';
    $html .= '<div><span class="rp-dot" style="background:#ef4444"></span> Failed: <strong>' . $fail . '</strong></div>';
    $html .= '</div></div>';
    return $html;
}

// Styles shared by all three report pages
function rp_styles()
{
    return '<style>
    .rp-donut-wrap { display:flex; align-items:center; gap:20px; }
    .rp-donut { position:relative; border-radius:50%; flex:0 0 auto; box-shadow: 0 2px 8px rgba(0,0,0,0.12); }
    .rp-donut-hole { position:absolute; border-radius:50%; background:var(--card-bg, #ffffff); color:var(--text-primary, #111827); display:flex; align-items:center; justify-content:center; font-weight:700; font-size:15px; border:1px solid var(--table-border-color, #e5e7eb); }
    .rp-legend div { margin:6px 0; font-size:14px; color:var(--text-primary, #111827); font-weight:500; }
    .rp-dot { display:inline-block; width:12px; height:12px; border-radius:50%; margin-right:8px; vertical-align:middle; }
    .rp-card { background:var(--card-bg, #ffffff); border:1px solid var(--table-border-color, #e5e7eb); border-radius:12px; padding:20px 24px; margin-bottom:24px; color:var(--text-primary, #111827); box-shadow:0 1px 4px rgba(0,0,0,0.04); transition:background-color 0.25s ease, border-color 0.25s ease; }
    .rp-card h4 { margin-top:0; margin-bottom:16px; color:var(--text-primary, #111827); font-weight:700; font-size:16px; letter-spacing:-0.2px; }
    .rp-link { font-weight:600; color:#4f46e5; text-decoration:none; }
    [data-theme="dark"] .rp-link { color:#a5b4fc; }
    .rp-link:hover { text-decoration:underline; }
    </style>';
}
