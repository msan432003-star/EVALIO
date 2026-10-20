<?php
/* kb/examiner.php - answers for logged-in examiners (the bot only appears after login, so there is no account-approval content).
   Page names match the examiner sidebar: Certification Exam, Mock Exam, Grade Essays, Study References, Registered Users for Exam. EDIT to match your screens. */
$CONTACT = 'examinee/contact.php';
$SUGGEST = array('How do I create an exam?', 'How do I add questions?', 'Why is my exam not visible to students?', 'How do I grade essay answers?', 'Where can I see registered students?');

return array(
    'welcome'  => 'Hi! I am the Evalio examiner assistant. I can help with creating exams, adding questions, grading essays and study references. What would you like to know?',
    'fallback' => 'Sorry, I did not understand that. Try asking about creating exams, questions, grading, registered students or study references, or contact the Evalio team.',
    'fallback_links' => array(array('Contact us', $CONTACT)),
    'suggest'  => $SUGGEST,
    'entries'  => array(
        array('id' => 'create_exam', 'keywords' => array('create exam', 'create an exam', 'new exam', 'add exam', 'add new exam', 'how to create', 'set up exam', 'make exam'),
              'answer' => 'Use Certification Exam or Mock Exam in the sidebar. Fill in the title, description, image, course, exam type (MCQ, True/False, Essay or Hybrid), duration, price, total marks, registration deadline and start date and time. A new certification exam is sent for admin approval.',
              'links' => array(array('Certification Exam', 'examiner/addcertexam.php'), array('Mock Exam', 'examiner/addmockexam.php')),
              'suggest' => array('Why is my exam not visible to students?', 'How do I add questions?')),

        array('id' => 'exam_status', 'keywords' => array('exam not visible', 'exam pending', 'exam status', 'exam approval', 'exam approved', 'exam rejected', 'rejection reason', 'rejected exam', 'students cannot see', 'not showing to students', 'exam not showing'),
              'answer' => 'Certification exams are visible to students only after the admin approves them. If an exam is rejected, the rejection reason is saved with it. Fix the issue and resubmit. Mock exams appear to students automatically.',
              'links' => array(array('Certification Exam', 'examiner/addcertexam.php'))),

        array('id' => 'questions', 'keywords' => array('add question', 'add questions', 'create question', 'question bank', 'upload question', 'edit question', 'delete question', 'questions'),
              'answer' => 'Add questions to an exam from the Add Question page. Use question types that match the exam type: MCQ, True/False or Essay. Make sure the total of all question marks matches the exam total marks.',
              'links' => array(array('Add Questions', 'examiner/addquestion.php'))),

        array('id' => 'question_types', 'keywords' => array('mcq', 'multiple choice', 'true false', 'essay', 'hybrid', 'exam type', 'question type'),
              'answer' => 'MCQ and True/False answers are marked automatically. Essay answers need your review. A Hybrid exam mixes types, so results with essay answers stay Pending Review until you grade them.'),

        array('id' => 'grading', 'keywords' => array('grade', 'grading', 'evaluate', 'evaluation', 'essay answer', 'review answer', 'pending review', 'give marks', 'assign marks', 'check answer'),
              'answer' => 'Results containing essay answers are saved as Pending Review. Open Grade Essays, give marks for each essay answer and save. After you finish, the student sees the final score and Passed or Failed status.',
              'links' => array(array('Grade Essays', 'examiner/grade_essays.php'))),

        array('id' => 'results', 'keywords' => array('result', 'student result', 'who passed', 'scores', 'pass mark', 'passing mark', 'pass percentage', 'pass'),
              'answer' => 'Students pass with at least 60% of the total marks. Results are visible to the student after submission, and essay-based ones after you grade them.'),

        array('id' => 'registered', 'keywords' => array('registered student', 'registered user', 'who registered', 'students registered', 'enrolled', 'registrations', 'paid registration', 'student list'),
              'answer' => 'Registered Users for Exam in the sidebar lists the students who registered for your exams.',
              'links' => array(array('Registered Users', 'examiner/registered_students.php'))),

        array('id' => 'price', 'keywords' => array('price', 'fee', 'exam fee', 'free exam', 'mock exam', 'mock', 'concession'),
              'answer' => 'Certification exams use the price you set. Mock exams are free practice with price 0. At registration, students in SC, ST or PwD categories pay 50% of the price.'),

        array('id' => 'timing', 'keywords' => array('deadline', 'registration deadline', 'start time', 'duration', 'schedule', 'exam date', 'exam time'),
              'answer' => 'Set a registration deadline, a start date and time and a duration for each certification exam. Students can start only after the start time, and the exam closes at start time plus duration.'),

        array('id' => 'references', 'keywords' => array('reference', 'study material', 'add reference', 'resource', 'resources', 'link', 'pdf'),
              'answer' => 'You can attach study references to an exam, either as a web link or as a file. Students see them in the Study References page.',
              'links' => array(array('Study References', 'examiner/addreference.php'))),

        array('id' => 'contact', 'keywords' => array('contact', 'support', 'admin', 'help desk', 'complaint', 'talk to someone', 'human'),
              'answer' => 'For anything I cannot answer, contact the Evalio team from the Contact page.',
              'links' => array(array('Contact us', $CONTACT))),
    ),
);