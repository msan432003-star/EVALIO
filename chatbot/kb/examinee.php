<?php
/* kb/examinee.php - answers for logged-in students.
   Starts from the public answers and replaces / adds student-specific ones. EDIT freely. */
$pub = require __DIR__ . '/public.php';   // note: this file also sets $CONTACT / $SUGGEST, so we set ours AFTER it
$CONTACT = 'examinee/contact.php';
$SUGGEST = array('How do I take my exam?', 'Where can I see my results?', 'How do I get my certificate?', 'Where is my payment receipt?', 'How do I change my password?');

// Replace some public answers with student-specific ones (same id = same keywords, new answer)
$override = array(
    'exam_register' => array('answer' => 'Open All Exams, choose an exam and click Register. Pick your category, choose a payment method and pay. After payment the exam appears in My Exams. Mock exams are free and need no registration payment.',
        'links' => array(array('All Exams', 'examinee/student_exams.php'), array('My Exams', 'examinee/my_exams.php'))),
    'results' => array('answer' => 'Go to My Results to see your score, percentage and Passed or Failed status for each exam. Results with essay questions show Pending Review until the examiner grades them. Mock exams are practice only and are not listed there.',
        'links' => array(array('My Results', 'examinee/results.php'))),
    'certificate' => array('answer' => 'In My Results, every passed certification exam (60% or more) has a View Certificate button. If your result is Pending Review, the certificate becomes available after grading. Mock exams do not give certificates.',
        'links' => array(array('My Results', 'examinee/results.php'))),
    'login' => array('answer' => 'You are already logged in. If you want to log out, use the Logout button at the top or at the bottom of the sidebar.'),
    'signup' => array('answer' => 'You already have an account. You can update your details from My Profile.', 'links' => array(array('My Profile', 'examinee/profile.php'))),
    'forgot_password' => array('answer' => 'You are logged in, so you can change your password any time from My Profile, under Change Password. You need your current password. It must be at least 8 characters.',
        'links' => array(array('My Profile', 'examinee/profile.php'))),
);

$entries = array();
foreach ($pub['entries'] as $e) {
    if (isset($override[$e['id']])) { $e = array_merge($e, $override[$e['id']]); }
    $entries[] = $e;
}

$extra = array(
    array('id' => 'take_exam', 'keywords' => array('take exam', 'take the exam', 'start exam', 'start the exam', 'begin exam', 'attempt exam', 'write exam', 'how do i take', 'exam not starting', 'starts soon', 'exam locked', 'how to take'),
          'answer' => 'Open My Exams. When the start time arrives the button changes to Take Exam Now. Before that it shows Starts Soon (Locked). You must finish within the exam duration, and the exam closes at start time plus duration.',
          'links' => array(array('My Exams', 'examinee/my_exams.php')), 'suggest' => array('What if I miss the exam?')),

    array('id' => 'missed', 'keywords' => array('miss exam', 'miss the exam', 'missed exam', 'missed the exam', 'exam over', 'exam ended', 'time over', 'exam window', 'late for exam'),
          'answer' => 'Once the exam window (start time plus duration) is over, the exam moves to the past list in My Exams and cannot be started. For help with a missed exam, please contact the Evalio team.',
          'links' => array(array('Contact us', $CONTACT))),

    array('id' => 'my_exams', 'keywords' => array('my exam', 'registered exam', 'where are my exam', 'upcoming exam', 'exam list not showing', 'exam not in my exam'),
          'answer' => 'My Exams shows every exam you have paid for. Upcoming ones have the start time and a Take Exam Now button, past ones are those you submitted or whose window is over. If an exam is missing right after payment, refresh the page or contact us with your transaction ID.',
          'links' => array(array('My Exams', 'examinee/my_exams.php'))),

    array('id' => 'cant_see_exam', 'keywords' => array('cannot see exam', 'exam is not showing', 'exam not showing', 'exam missing', 'not listed', 'not visible', 'exam disappeared', 'exam not available'),
          'answer' => 'Certification exams are listed once they are approved and disappear when their end time has passed. Mock exams are always listed. Use the search box and filters on All Exams to find an exam.',
          'links' => array(array('All Exams', 'examinee/student_exams.php'))),

    array('id' => 'already_registered', 'keywords' => array('already registered', 'register again', 'registered twice', 'duplicate', 'register twice'),
          'answer' => 'You can register for an exam only once. If you are already registered, the exam card shows Registered and you can find it in My Exams.',
          'links' => array(array('My Exams', 'examinee/my_exams.php'))),

    array('id' => 'payment_history', 'keywords' => array('payment history', 'receipt', 'invoice', 'transaction id', 'transaction', 'my payment', 'paid history', 'print receipt'),
          'answer' => 'Payment History lists all your payments with date, exam, amount, status and transaction ID. Paid exams have a Print receipt link. Free mock exams have no payment, so they have no receipt.',
          'links' => array(array('Payment History', 'examinee/payment_history.php'))),

    array('id' => 'profile', 'keywords' => array('profile', 'my profile', 'change name', 'change phone', 'phone number change', 'profile photo', 'photo', 'picture', 'avatar', 'update profile', 'change email'),
          'answer' => 'In My Profile you can change your full name, phone number and profile photo (JPG, PNG or WEBP, up to 2 MB). Your email address cannot be changed.',
          'links' => array(array('My Profile', 'examinee/profile.php'))),

    array('id' => 'change_password', 'keywords' => array('change password', 'new password', 'update password', 'change my password', 'password'),
          'answer' => 'Go to My Profile and use the Change Password card. Enter your current password, then a new password of at least 8 characters, and confirm it.',
          'links' => array(array('My Profile', 'examinee/profile.php'))),

    array('id' => 'dashboard', 'keywords' => array('dashboard', 'overview', 'statistics', 'average score', 'my progress', 'progress'),
          'answer' => 'The Dashboard shows your registered, completed and passed exams, your average score, certificates earned, upcoming exams and recent results.',
          'links' => array(array('Dashboard', 'examinee/dashboard.php'))),

    array('id' => 'references', 'keywords' => array('reference', 'study reference', 'study material', 'study materials', 'notes', 'material', 'resources', 'syllabus'),
          'answer' => 'Study References has the reference links and materials added for your exams. Open it from the sidebar.',
          'links' => array(array('Study References', 'examinee/references.php'))),

    array('id' => 'logout', 'keywords' => array('logout', 'log out', 'sign out'),
          'answer' => 'Use the Logout button at the top right, or Logout at the bottom of the sidebar. It ends your session.'),
);

return array(
    'welcome'  => 'Hi! I am the Evalio assistant. I can help with your exams, payments, results, certificates and profile. What would you like to know?',
    'fallback' => $pub['fallback'],
    'fallback_links' => $pub['fallback_links'],
    'suggest'  => $SUGGEST,
    'entries'  => array_merge($extra, $entries),   // student-specific entries first so they win ties
);