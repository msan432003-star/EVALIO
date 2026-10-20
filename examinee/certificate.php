<?php 
require('../config/autoload.php'); 
require('auth_helper.php');
$student_id = require_login(); // logged-in student only

$dao = new DataAccess(); 
$exam_id = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;

// 1. Verify exam result existence
$result_info = $dao->getData('*', 'exam_results', 'student_id = ' . $student_id . ' AND exam_id = ' . $exam_id);
if(empty($result_info)) {
    echo "<script>alert('No result found for this exam.'); window.location.href='results.php';</script>";
    exit();
}

$res = $result_info[0];

// 2. If still pending review, certificate is not available
if(isset($res['grading_status']) && $res['grading_status'] === 'pending_review') {
    echo "<script>alert('This exam has descriptive/essay questions awaiting examiner grading. Certificates are issued once evaluation is finalized.'); window.location.href='results.php';</script>";
    exit();
}

// 3. Compute score & percentage
$total_m = (float)(isset($res['total_marks']) && $res['total_marks'] > 0 ? $res['total_marks'] : $res['total_questions']);
$obtained_m = (float)(isset($res['marks_obtained']) ? $res['marks_obtained'] : $res['score']);
$percentage = ($total_m > 0) ? ($obtained_m / $total_m) * 100 : 0;

if($percentage < 60) {
    echo "<script>alert('You did not pass this exam (requires 60% or higher), so a certificate is not available.'); window.location.href='results.php';</script>";
    exit();
}

// 4. Fetch Exam Title & Examiner Name
$exam_info = $dao->getData('*', 'exams', 'id = ' . $exam_id);
$exam_title = !empty($exam_info) ? $exam_info[0]['title'] : 'Online Examination';
$examiner_id = !empty($exam_info) ? (int)$exam_info[0]['created_by'] : 0;

$examiner_name = 'Authorized Examiner';
if ($examiner_id > 0) {
    $examiner_data = $dao->getData('*', 'examiners', 'id = ' . $examiner_id);
    if (!empty($examiner_data)) {
        $raw_name = $examiner_data[0]['name'] ?? ($examiner_data[0]['fullname'] ?? $examiner_data[0]['email'] ?? '');
        if (!empty($raw_name)) {
            $examiner_name = $raw_name;
        }
    }
}

// Prefix Prof. if not already present
$examiner_display = (stripos($examiner_name, 'Prof') === false && stripos($examiner_name, 'Dr') === false) 
    ? 'Prof. ' . $examiner_name 
    : $examiner_name;

// 5. Fetch the real student name
$student_info = $dao->getData('name', 'examinees', 'id = ' . $student_id);
$student_name = !empty($student_info) ? $student_info[0]['name'] : 'Student';

// 6. Unique certificate number
$issued_ts = !empty($res['submitted_at']) ? strtotime($res['submitted_at']) : time();
$cert_no = sprintf('EVALIO-%04d-%05d-%s', $exam_id, $student_id, date('ymd', $issued_ts));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate of Achievement - <?= e($student_name); ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;600;700;800;900&family=Cormorant+Garamond:ital,wght@0,500;0,600;0,700;1,500;1,600&family=Montserrat:wght@400;500;600;700&family=Pinyon+Script&family=Alex+Brush&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    
    <style>
        :root {
            --ink: #1b0d2a;
            --violet-deep: #2c124d;
            --violet-primary: #5029D6;
            --violet-soft: #7c4dff;
            --gold-deep: #9d7628;
            --gold: #c5a059;
            --gold-light: #f5e4b8;
            --paper: #fffefb;
        }

        body {
            background: #e5e1eb;
            font-family: 'Cormorant Garamond', Georgia, serif;
            color: var(--ink);
            margin: 0;
            padding: 0;
        }

        .toolbar {
            margin: 25px auto 10px;
            text-align: center;
        }
        .toolbar .btn {
            font-family: 'Montserrat', sans-serif;
            font-size: 14px;
            font-weight: 600;
            letter-spacing: 0.5px;
            padding: 10px 24px;
            border-radius: 30px;
            margin: 0 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        }
        .btn-print {
            background: #28a745;
            border-color: #28a745;
            color: #fff;
        }
        .btn-print:hover { background: #218838; color: #fff; }
        .btn-back {
            background: #0284c7;
            border-color: #0284c7;
            color: #fff;
        }
        .btn-back:hover { background: #0369a1; color: #fff; }

        /* Container & Aspect Ratio */
        .sheet-wrap {
            width: min(1080px, 95vw);
            margin: 20px auto 50px;
            container-type: inline-size;
        }

        .certificate-box {
            position: relative;
            aspect-ratio: 297 / 210;
            background: var(--paper);
            border: 1.4cqw solid var(--violet-deep);
            box-shadow: 0 15px 50px rgba(27, 13, 42, 0.28);
            text-align: center;
            color: var(--ink);
            box-sizing: border-box;
            background-image: 
                radial-gradient(circle at 50% 50%, rgba(245, 228, 184, 0.08) 0%, transparent 60%),
                linear-gradient(135deg, rgba(80, 41, 214, 0.02) 0%, rgba(197, 160, 89, 0.03) 100%);
        }

        /* Outer Double Gold Frame */
        .certificate-box::before {
            content: "";
            position: absolute;
            inset: 1.1cqw;
            border: 0.22cqw solid var(--gold);
            pointer-events: none;
        }
        .certificate-box::after {
            content: "";
            position: absolute;
            inset: 1.7cqw;
            border: 0.1cqw solid var(--gold-deep);
            opacity: 0.65;
            pointer-events: none;
        }

        /* Intricate Corner Accents */
        .corner {
            position: absolute;
            width: 4.8cqw;
            height: 4.8cqw;
            border: 0.3cqw solid var(--gold);
            pointer-events: none;
        }
        .corner.tl { top: 2.3cqw; left: 2.3cqw; border-right: 0; border-bottom: 0; }
        .corner.tr { top: 2.3cqw; right: 2.3cqw; border-left: 0; border-bottom: 0; }
        .corner.bl { bottom: 2.3cqw; left: 2.3cqw; border-right: 0; border-top: 0; }
        .corner.br { bottom: 2.3cqw; right: 2.3cqw; border-left: 0; border-top: 0; }

        .inner-content {
            position: absolute;
            inset: 0;
            padding: 3.2cqw 6.5cqw 3cqw;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            box-sizing: border-box;
        }

        /* Header with Crest */
        .crest-header {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .crest-logo {
            width: 7.2cqw;
            height: auto;
            max-height: 7.2cqw;
            object-fit: contain;
            filter: drop-shadow(0 2px 5px rgba(0,0,0,0.15));
            margin-bottom: 0.6cqw;
        }
        .academy-name {
            font-family: 'Cinzel', serif;
            font-size: 1.8cqw;
            font-weight: 700;
            letter-spacing: 0.32em;
            color: var(--violet-deep);
            margin: 0;
            text-transform: uppercase;
        }
        .cert-title {
            font-family: 'Cinzel', Georgia, serif;
            font-size: 4.8cqw;
            font-weight: 800;
            line-height: 1.05;
            margin: 0.4cqw 0 0;
            letter-spacing: 0.04em;
            color: var(--ink);
            text-transform: uppercase;
            text-shadow: 0 1px 2px rgba(0,0,0,0.06);
        }
        .gold-divider {
            width: 22cqw;
            height: 0.22cqw;
            margin: 0.8cqw auto 0;
            background: linear-gradient(90deg, transparent, var(--gold), var(--gold-deep), var(--gold), transparent);
        }

        /* Presentation & Recipient */
        .presentation-block {
            margin: 0.4cqw 0;
        }
        .lead-text {
            font-family: 'Cormorant Garamond', serif;
            font-size: 1.85cqw;
            font-style: italic;
            font-weight: 500;
            margin: 0;
            color: #63536d;
            letter-spacing: 0.02em;
        }
        .student-name {
            font-family: 'Pinyon Script', 'Alex Brush', cursive;
            font-size: 6.8cqw;
            line-height: 1.15;
            color: var(--violet-primary);
            margin: 0.2cqw 0 0;
            padding: 0 3.5cqw 0.2cqw;
            border-bottom: 0.16cqw solid var(--gold);
            display: inline-block;
            text-shadow: 0 1px 3px rgba(80, 41, 214, 0.12);
        }

        /* Description / Performance */
        .description-block {
            font-size: 1.85cqw;
            line-height: 1.45;
            margin: 0;
            color: #3b2d45;
            max-width: 90%;
        }
        .exam-name {
            font-family: 'Cinzel', serif;
            font-size: 2.6cqw;
            font-weight: 700;
            color: var(--violet-deep);
            display: block;
            margin: 0.3cqw 0;
            letter-spacing: 0.02em;
        }
        .score-pill {
            display: inline-block;
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            font-size: 1.4cqw;
            color: var(--violet-primary);
            background: #f4efff;
            border: 0.12cqw solid #d8c7f8;
            padding: 0.1cqw 0.9cqw;
            border-radius: 20px;
            margin-left: 0.3cqw;
        }

        /* Footer: Date, Seal, Examiner Signature */
        .cert-footer {
            width: 100%;
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            padding: 0 1.5cqw;
            margin-top: 0.5cqw;
        }
        .footer-slot {
            width: 25cqw;
            text-align: center;
        }
        .footer-slot .sig-value {
            font-family: 'Montserrat', sans-serif;
            font-size: 1.5cqw;
            font-weight: 600;
            color: var(--ink);
            min-height: 2.4cqw;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .footer-slot .examiner-sign {
            font-family: 'Alex Brush', cursive;
            font-size: 3.2cqw;
            color: var(--violet-deep);
            line-height: 1;
            font-weight: 400;
        }
        .footer-slot .sig-line {
            border-top: 0.15cqw solid var(--ink);
            padding-top: 0.4cqw;
            font-family: 'Montserrat', sans-serif;
            font-size: 1.25cqw;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #554460;
        }

        /* Center Official Seal */
        .seal-slot {
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
        }
        .official-seal-img {
            width: 10.5cqw;
            height: 10.5cqw;
            object-fit: contain;
            filter: drop-shadow(0 3px 8px rgba(0,0,0,0.22));
            margin-bottom: -0.8cqw;
            transition: transform 0.2s ease;
        }

        /* Certificate Meta & Accreditation */
        .cert-meta {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 1.4cqw;
            font-family: 'Montserrat', sans-serif;
            font-size: 0.95cqw;
            font-weight: 600;
            letter-spacing: 0.16em;
            color: #83728f;
            text-transform: uppercase;
        }

        /* Print Settings */
        @page {
            size: A4 landscape;
            margin: 0;
        }
        @media print {
            .no-print { display: none !important; }
            body {
                background: #fff;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .sheet-wrap {
                width: 297mm;
                height: 210mm;
                margin: 0 auto;
                box-shadow: none;
            }
            .certificate-box {
                box-shadow: none;
            }
        }
    </style>
</head>
<body>

<!-- Toolbar -->
<div class="container toolbar no-print">
    <button onclick="window.print();" class="btn btn-print">
        <i class="fa fa-print"></i> Print / Save as PDF
    </button>
    <a href="results.php" class="btn btn-back">
        <i class="fa fa-arrow-left"></i> Back to Results
    </a>
</div>

<!-- Main Certificate Container -->
<div class="sheet-wrap">
    <div class="certificate-box">
        <!-- Corner Ornaments -->
        <span class="corner tl"></span>
        <span class="corner tr"></span>
        <span class="corner bl"></span>
        <span class="corner br"></span>

        <div class="inner-content">
            <!-- Header with Crest -->
            <div class="crest-header">
                <img src="../assets/images/evalio_crest.png" class="crest-logo" alt="Evalio Crest">
                <p class="academy-name">EVALIO ACADEMY &bull; EXAMINATION PORTAL</p>
                <h1 class="cert-title">Certificate of Achievement</h1>
                <div class="gold-divider"></div>
            </div>

            <!-- Recipient Name -->
            <div class="presentation-block">
                <p class="lead-text">This is proudly and formally conferred upon</p>
                <div class="student-name"><?= e($student_name); ?></div>
            </div>

            <!-- Exam Details & Performance -->
            <div class="description-block">
                for successfully meeting all academic standards and passing the examination in
                <span class="exam-name"><?= e($exam_title); ?></span>
                with a final performance score of 
                <span class="score-pill"><?= number_format($percentage, 2); ?>%</span>
                <span style="font-size: 1.4cqw; color: #666; margin-left: 0.2cqw;">
                    (<?= number_format($obtained_m, 2); ?> / <?= number_format($total_m, 2); ?> Marks)
                </span>
            </div>

            <!-- Footer: Date, Official Seal, Authorized Examiner -->
            <div class="cert-footer">
                <!-- Date of Issue -->
                <div class="footer-slot">
                    <div class="sig-value">
                        <i class="fa fa-calendar-check-o" style="color: var(--gold); margin-right: 0.4cqw;"></i>
                        <?= date('d F Y', $issued_ts); ?>
                    </div>
                    <div class="sig-line">Date of Issue</div>
                </div>

                <!-- Official Seal of Evalio Academy -->
                <div class="seal-slot">
                    <img src="../assets/images/evalio_seal.png" class="official-seal-img" alt="Official Seal of Evalio Academy">
                </div>

                <!-- Authorized Examiner -->
                <div class="footer-slot">
                    <div class="sig-value examiner-sign">
                        <?= e($examiner_display); ?>
                    </div>
                    <div class="sig-line">Authorized Examiner</div>
                </div>
            </div>
        </div>

        <!-- Verification Footnote -->
        <div class="cert-meta">
            <a href="verify_certificate.php?cert=<?= urlencode($cert_no); ?>" target="_blank" style="color: inherit; text-decoration: underline;" title="Verify Credential Authenticity">
                CERTIFICATE ID: <?= e($cert_no); ?> &bull; SECURE DIGITAL CREDENTIAL VERIFIED <i class="fa fa-external-link" style="font-size: 0.85em;"></i>
            </a>
        </div>
    </div>
</div>

</body>
</html>