<?php
/* kb/public.php - answers for visitors who are not logged in.
   EDIT THIS FILE to change what the bot says. Each entry: id, keywords (words or phrases, lower case),
   answer (plain text), links (label + path relative to the project root), suggest (optional follow-up questions).
   Tip: put longer phrases in keywords for specific answers; longer phrases score higher than single words. */
$CONTACT ='examinee/contact.php'; // change if your Contact page is somewhere else
$SUGGEST =array('How do I register for an exam?', 'What is the pass mark?', 'How do I pay the fee?', 'How do I get my certificate?', 'What are mock exams?');

return array('welcome'=> 'Hi! I am the Evalio assistant. I can help with registration, exams, fees, payments, results and certificates. What would you like to know?',
    'fallback'=> 'Sorry, I did not understand that. Try asking about registration, fees, payment, results, certificates or mock exams, or contact our team.',
    'fallback_links'=> array(array('Contact us', $CONTACT)),
    'suggest'=> $SUGGEST,
    'entries'=> array(array('id'=> 'about', 'keywords'=> array('what is evalio', 'about evalio', 'what is this', 'what is this website', 'platform'),
            'answer'=> 'Evalio is an online exam and certification platform. You can register for certification exams, practise with free mock exams, take exams online, see your results and download certificates when you pass.',
            'links'=> array(array('Browse exams', 'examinee/exams.php')), 'suggest'=> $SUGGEST),

        array('id'=> 'signup', 'keywords'=> array('sign up', 'signup', 'create account', 'create an account', 'new account', 'open account', 'new user', 'register account', 'registration page'),
            'answer'=> 'To create a student account, use the Sign Up page, fill in your details and submit. After that you can log in and register for exams.',
            'links'=> array(array('Sign up', 'examinee/registration1.php'), array('Login', 'examinee/login.php'))),

        array('id'=> 'exam_register', 'keywords'=> array('register for exam', 'register for an exam', 'exam registration', 'how to register', 'how do i register', 'enroll', 'enrol', 'apply for exam', 'apply for an exam', 'join exam'),
            'answer'=> 'To register for an exam: 1) log in to your student account, 2) open All Exams and pick an exam, 3) click Register, 4) choose your category and payment method, 5) pay. Free mock exams need no payment. After payment the exam appears in My Exams.',
            'links'=> array(array('Login', 'examinee/login.php'), array('Browse exams', 'examinee/exams.php')),
            'suggest'=> array('How do I pay the fee?', 'Is there any fee concession?', 'What is the registration deadline?')),

        array('id'=> 'login', 'keywords'=> array('login', 'log in', 'sign in', 'cannot login', 'unable to login', 'login problem', 'login issue', 'not able to login'),
            'answer'=> 'Log in with your registered email and password on the Login page. If it fails, check the email and password. After 5 wrong attempts login is locked for 5 minutes. Blocked or inactive accounts cannot log in.',
            'links'=> array(array('Login', 'examinee/login.php'))),

        array('id'=> 'forgot_password', 'keywords'=> array('forgot password', 'forgot my password', 'forgotten password', 'reset password', 'lost password', 'password reset', 'forgot'),
            'answer'=> 'Automatic password reset is not available yet. Please contact the Evalio team from the Contact page and they will help you. Once you can log in, you can change your password from My Profile.',
            'links'=> array(array('Contact us', $CONTACT))),

        array('id'=> 'locked', 'keywords'=> array('too many attempts', 'too many failed', 'account locked', 'account is locked', 'is locked', 'locked out', 'account blocked', 'account inactive', 'blocked'),
            'answer'=> 'After 5 wrong login attempts the login is locked for 5 minutes. Please wait and try again. If your account is blocked or inactive, contact the Evalio team.',
            'links'=> array(array('Contact us', $CONTACT))),

        array('id'=> 'exam_types', 'keywords'=> array('types of exam', 'exam types', 'what exams', 'which exams', 'available exam', 'list of exam', 'exam list', 'certification exam'),
            'answer'=> 'Evalio has two kinds of exams: Certification exams (paid, scheduled, give a certificate when you pass) and Mock exams (free practice, always available). Certification exams are listed after they are approved.',
            'links'=> array(array('Browse exams', 'examinee/exams.php'))),

        array('id'=> 'mock', 'keywords'=> array('mock', 'mock exam', 'mock test', 'practice exam', 'practice test', 'practice', 'free exam'),
            'answer'=> 'Mock exams are free practice tests that are always available. They need no payment, and they are for practice only, so they do not appear in My Results and give no certificate.'),

        array('id'=> 'fee', 'keywords'=> array('fee', 'exam fee', 'price', 'cost', 'how much', 'charge', 'amount', 'how much is the fee'),
            'answer'=> 'Each certification exam has its own fee, shown on the exam card and on the registration page before you pay. Mock exams are free.',
            'suggest'=> array('Is there any fee concession?', 'How do I pay the fee?')),

        array('id'=> 'concession', 'keywords'=> array('concession', 'discount', 'category', 'quota', 'reservation', 'sc st', 'pwd', 'obc', 'ews', 'half fee', 'fee concession'),
            'answer'=> 'On the registration page you choose a category. General, OBC and EWS pay the full fee. SC, ST and PwD get a 50% concession. The payable amount updates on the page before you pay.'),

        array('id'=> 'payment', 'keywords'=> array('pay', 'payment', 'how to pay', 'how do i pay', 'payment method', 'payment mode', 'upi', 'net banking', 'netbanking', 'wallet', 'debit card', 'credit card'),
            'answer'=> 'You can pay by UPI, Net Banking, Credit or Debit Card, or Wallet on the registration page. After a successful payment you get a transaction ID and the exam is added to My Exams.',
            'suggest'=> array('Is my card data safe?', 'What if my payment failed?')),

        array('id'=> 'card_safe', 'keywords'=> array('card details', 'is my data safe', 'is it safe', 'secure', 'privacy', 'card data', 'card safe'),
            'answer'=> 'Card details are only checked on the payment form and are not stored by Evalio. Passwords are stored in encrypted form.'),

        array('id'=> 'payment_failed', 'keywords'=> array('payment failed', 'money deducted', 'amount deducted', 'payment not showing', 'payment pending', 'paid but', 'payment problem', 'payment issue'),
            'answer'=> 'If your payment failed or the money was deducted but the exam is not in My Exams, please contact the Evalio team with your transaction ID and registered email.',
            'links'=> array(array('Contact us', $CONTACT))),

        array('id'=> 'deadline', 'keywords'=> array('deadline', 'last date', 'registration closed', 'registration close', 'exam closed', 'too late', 'closing date'),
            'answer'=> 'Each exam has a registration deadline and a start time. Registration closes once the exam window is over. The dates are shown on the exam card.'),

        array('id'=> 'schedule', 'keywords'=> array('exam date', 'exam time', 'when is the exam', 'start time', 'schedule', 'timing', 'what time', 'when does the exam'),
            'answer'=> 'The start date and time of each certification exam is shown on its exam card. You can start only after the start time, and you must finish within the exam duration.'),

        array('id'=> 'duration', 'keywords'=> array('duration', 'how long', 'time limit', 'exam duration', 'how many minutes'),
            'answer'=> 'The duration (in minutes) is shown on each exam card. The timer runs from the moment you start the exam.'),

        array('id'=> 'question_types', 'keywords'=> array('question type', 'types of question', 'mcq', 'multiple choice', 'true false', 'essay', 'hybrid'),
            'answer'=> 'Exams can be MCQ (multiple choice), True/False, Essay, or Hybrid (a mix). MCQ and True/False are marked automatically. Essay answers are reviewed by the examiner, so those results stay provisional until graded.'),

        array('id'=> 'pass_mark', 'keywords'=> array('pass mark', 'passing mark', 'pass percentage', 'pass percent', 'minimum mark', 'cutoff', 'cut off', 'how much to pass', 'passing score', 'pass', 'passed', 'fail', 'failed'),
            'answer'=> 'You pass an exam by scoring at least 60%. Below that the result shows as Failed.'),

        array('id'=> 'results', 'keywords'=> array('result', 'my result', 'score', 'mark', 'see result', 'check result', 'when result', 'scorecard'),
            'answer'=> 'After you submit an exam your result appears in My Results with your score, percentage and Passed or Failed status. Exams with essay questions show Pending Review until the examiner grades them.',
            'links'=> array(array('Login', 'examinee/login.php'))),

        array('id'=> 'pending_review', 'keywords'=> array('pending review', 'pending evaluation', 'provisional', 'evaluated', 'evaluation', 'grading', 'graded', 'result pending', 'result not came', 'result not shown'),
            'answer'=> 'Results with essay questions are provisional (Pending Review) until the examiner grades them. When grading is done the final score and Passed or Failed status are shown, and a certificate becomes available if you passed.'),

        array('id'=> 'certificate', 'keywords'=> array('certificate', 'download certificate', 'get certificate', 'my certificate', 'certification letter'),
            'answer'=> 'When you pass a certification exam (60% or more) a View Certificate button appears next to that result in My Results. Mock exams do not give certificates.',
            'links'=> array(array('Login', 'examinee/login.php'))),

        array('id'=> 'refund', 'keywords'=> array('refund', 'money back', 'cancel registration', 'cancel exam', 'cancellation', 'cancel'),
            'answer'=> 'I do not have information about refunds or cancellations. Please contact the Evalio team and they will guide you.',
            'links'=> array(array('Contact us', $CONTACT))),

        array('id'=> 'examiner', 'keywords'=> array('examiner', 'become examiner', 'create exam', 'teacher', 'professor', 'conduct exam'),
            'answer'=> 'Examiners register separately and their account must be approved before they can log in. Approved examiners can create exams and grade answers.'),

        array('id'=> 'technical', 'keywords'=> array('not working', 'error', 'bug', 'page not loading', 'blank page', 'website slow', 'site not working'),
            'answer'=> 'Sorry about that. Please refresh the page, clear your browser cache or try another browser. If the problem continues, tell us what you were doing through the Contact page.',
            'links'=> array(array('Contact us', $CONTACT))),

        array('id'=> 'contact', 'keywords'=> array('contact', 'support', 'customer care', 'complaint', 'reach you', 'talk to someone', 'human', 'email address', 'phone number'),
            'answer'=> 'You can reach the Evalio team through the Contact page. Send your name, email and message and we will reply.',
            'links'=> array(array('Contact us', $CONTACT))),
    ),
);