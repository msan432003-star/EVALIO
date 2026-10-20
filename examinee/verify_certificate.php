<?php
/**
 * Public Certificate Verification Portal
 * Allows third parties, employers, and students to authenticate certificate credentials.
 * Does not require student login session.
 */
require('../config/autoload.php');

$dao = new DataAccess();
$cert_input = trim($_GET['cert'] ?? '');
$searched = !empty($cert_input);

$is_valid = false;
$error_msg = '';
$cert_data = null;

if ($searched) {
    // Expected format: EVALIO-0001-00005-260928
    if (preg_match('/^EVALIO-(\d+)-(\d+)-(\d{6})$/i', $cert_input, $matches)) {
        $exam_id = (int)$matches[1];
        $student_id = (int)$matches[2];
        $date_code = $matches[3];

        // Query exam results
        $res = $dao->getData('*', 'exam_results', 'student_id = ' . $student_id . ' AND exam_id = ' . $exam_id);

        if (!empty($res)) {
            $row = $res[0];
            
            // Check grading status
            if (isset($row['grading_status']) && $row['grading_status'] === 'pending_review') {
                $error_msg = 'This credential is for an examination currently pending evaluation review.';
            } else {
                $total_m = (float)($row['total_marks'] > 0 ? $row['total_marks'] : $row['total_questions']);
                $obtained_m = (float)($row['marks_obtained'] ?? $row['score']);
                $percentage = ($total_m > 0) ? ($obtained_m / $total_m) * 100 : 0;

                if ($percentage >= 60) {
                    // Fetch student and exam details
                    $student_rows = $dao->getData(array('id', 'name', 'email'), 'examinees', 'id = ' . $student_id);
                    $exam_rows = $dao->getData(array('id', 'title', 'category', 'created_by'), 'exams', 'id = ' . $exam_id);

                    if (!empty($student_rows) && !empty($exam_rows)) {
                        $student = $student_rows[0];
                        $exam = $exam_rows[0];

                        // Examiner name
                        $examiner_name = 'Evalio Academic Board';
                        $created_by = (int)($exam['created_by'] ?? 0);
                        if ($created_by > 0) {
                            $examiner_rows = $dao->getData(array('name'), 'examiners', 'id = ' . $created_by);
                            if (!empty($examiner_rows) && !empty($examiner_rows[0]['name'])) {
                                $examiner_name = 'Prof. ' . $examiner_rows[0]['name'];
                            }
                        }

                        $is_valid = true;
                        $cert_data = array(
                            'cert_no' => $cert_input,
                            'student_name' => $student['name'],
                            'exam_title' => $exam['title'],
                            'percentage' => $percentage,
                            'obtained_marks' => $obtained_m,
                            'total_marks' => $total_m,
                            'issue_date' => date('d F Y', !empty($row['submitted_at']) ? strtotime($row['submitted_at']) : time()),
                            'examiner' => $examiner_name,
                            'status' => 'Verified Authentic'
                        );
                    } else {
                        $error_msg = 'Certificate records could not be verified.';
                    }
                } else {
                    $error_msg = 'This certificate ID does not meet passing criteria.';
                }
            }
        } else {
            $error_msg = 'No certificate record found for the provided Certificate ID.';
        }
    } else {
        $error_msg = 'Invalid certificate ID format. Expected format: EVALIO-XXXX-XXXXX-XXXXXX';
    }
}

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate Verification - Evalio Online Examination System</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f4f6fa;
            color: #333;
            min-height: 100vh;
        }
        .header-bar {
            background: linear-gradient(135deg, #2b1055, #7597de);
            color: #fff;
            padding: 35px 0 65px;
            text-align: center;
        }
        .header-bar h1 {
            font-weight: 700;
            font-size: 2rem;
            margin-bottom: 8px;
        }
        .verify-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            padding: 35px;
            margin-top: -35px;
            margin-bottom: 50px;
        }
        .badge-verified {
            background: #e8f8f0;
            color: #27ae60;
            border: 1px solid #c2ebd5;
            padding: 8px 18px;
            border-radius: 50px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95rem;
        }
        .credential-table th {
            width: 35%;
            color: #666;
            font-weight: 500;
            border-top: none;
        }
        .credential-table td {
            font-weight: 600;
            color: #1a1a2e;
            border-top: none;
        }
    </style>
</head>
<body>

<div class="header-bar">
    <div class="container">
        <h1><i class="fas fa-certificate"></i> Evalio Credential Verification</h1>
        <p class="mb-0 text-white-50">Official Certificate Authentication Registry</p>
    </div>
</div>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="verify-card">
                <!-- Search Form -->
                <form method="GET" action="verify_certificate.php" class="mb-4">
                    <div class="input-group input-group-lg">
                        <input type="text" name="cert" class="form-control" placeholder="Enter Certificate ID (e.g. EVALIO-0001-00005-260928)" value="<?= h($cert_input); ?>" required>
                        <div class="input-group-append">
                            <button class="btn btn-primary px-4" type="submit" style="background:#2b1055;border-color:#2b1055;">
                                <i class="fas fa-search"></i> Verify
                            </button>
                        </div>
                    </div>
                </form>

                <?php if ($searched && $is_valid && $cert_data) { ?>
                    <!-- Verified Result -->
                    <div class="text-center mb-4">
                        <div class="badge-verified mb-3">
                            <i class="fas fa-check-circle"></i> Official Certificate Verified
                        </div>
                        <h4 class="text-dark font-weight-bold mb-1"><?= h($cert_data['student_name']); ?></h4>
                        <p class="text-muted"><?= h($cert_data['exam_title']); ?></p>
                    </div>

                    <table class="table credential-table mb-4">
                        <tbody>
                            <tr>
                                <th>Certificate Number</th>
                                <td><code><?= h($cert_data['cert_no']); ?></code></td>
                            </tr>
                            <tr>
                                <th>Recipient</th>
                                <td><?= h($cert_data['student_name']); ?></td>
                            </tr>
                            <tr>
                                <th>Examination</th>
                                <td><?= h($cert_data['exam_title']); ?></td>
                            </tr>
                            <tr>
                                <th>Final Score</th>
                                <td><span class="badge badge-success px-2 py-1"><?= number_format($cert_data['percentage'], 2); ?>%</span> (<?= number_format($cert_data['obtained_marks'], 2); ?> / <?= number_format($cert_data['total_marks'], 2); ?>)</td>
                            </tr>
                            <tr>
                                <th>Date of Issuance</th>
                                <td><?= h($cert_data['issue_date']); ?></td>
                            </tr>
                            <tr>
                                <th>Authorized Authority</th>
                                <td><?= h($cert_data['examiner']); ?></td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="text-center pt-2">
                        <a href="index.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-home"></i> Return to Home</a>
                    </div>

                <?php } elseif ($searched && !$is_valid) { ?>
                    <!-- Failed Result -->
                    <div class="alert alert-danger text-center p-4">
                        <i class="fas fa-times-circle fa-3x mb-3 text-danger"></i>
                        <h5 class="font-weight-bold">Verification Failed</h5>
                        <p class="mb-0"><?= h($error_msg); ?></p>
                    </div>
                    <div class="text-center pt-2">
                        <a href="verify_certificate.php" class="btn btn-outline-secondary btn-sm">Try Another Search</a>
                    </div>
                <?php } else { ?>
                    <!-- Instructions -->
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-shield-alt fa-3x mb-3 text-secondary"></i>
                        <p>Enter a certificate identifier above to authenticate completion records, verify academic validity, and check candidate scores.</p>
                        <a href="index.php" class="btn btn-link btn-sm"><i class="fas fa-arrow-left"></i> Back to Main Portal</a>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

</body>
</html>
