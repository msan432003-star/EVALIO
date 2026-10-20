<?php 
/* index.php - Public Landing Page (Examinees & Examiners) */
require_once('../config/autoload.php'); 

$pageTitle = "Evalio - Online Exam & Certification Platform";
include('includes/header.php');
?>

<style>
    /* Keep "Why Choose" cards uniform: same layout, links pinned to the bottom */
    .grid-container .card-custom { display: flex; flex-direction: column; align-items: flex-start; }
    .grid-container .card-custom p { flex-grow: 1; }
    .grid-container .card-custom a { margin-top: auto !important; padding-top: 10px; }
</style>

    <!-- HOME BANNER (front image + get started) -->
    <section class="hero-banner">
        <img src="index/image/front.png" alt="Evalio online exam platform" class="banner-img">
        <div class="banner-overlay">
            <img src="index/image/logo.png" alt="Evalio Logo" class="banner-logo-img">
            <?php if (!empty($is_logged_in)): ?>
                <a href="<?= $dashboard_url; ?>" class="nav-btn hero-btn banner-btn get-started-glow"><i class="fa-solid fa-gauge-high"></i> Go to Dashboard</a>
            <?php else: ?>
                <a href="registration1.php" class="nav-btn hero-btn banner-btn get-started-glow" data-auth="signup">Get Started</a>
            <?php endif; ?>
        </div>
    </section>

    <!-- STATS BAR -->
<section class="stats-bar">
    <div class="stat-item">
        <h3>500+</h3>
        <p>Exams</p>
    </div>
    <div class="stat-item">
        <h3>10,000+</h3>
        <p>Candidates</p>
    </div>
    <div class="stat-item">
        <h3>200+</h3>
        <p>Examiners</p>
    </div>
    <div class="stat-item">
        <h3>50+</h3>
        <p>Certifications</p>
    </div>
    <div class="stat-item">
        <h3>98%</h3>
        <p>Pass Rate</p>
    </div>
</section>

    <!-- WELCOME / INTRO -->
    <section class="hero">
        <h1>Welcome to Evalio</h1>
        <p>Evalio is an all-in-one online exam and certification platform built for both sides of the exam room. Examiners can build question banks, design practice mock tests, author official certification exams, share study references, and see who has registered for their exams and how they performed. Candidates can browse exams, register and pay, practise with timed mock tests, and download certificates the moment they pass. With administrators overseeing courses, examiner approvals and platform settings, Evalio delivers a seamless, end-to-end evaluation experience for administrators, examiners, and candidates alike.</p>
    </section>
    <!-- HOW IT WORKS -->
<section class="section">
    <h2 class="section-title">How It Works</h2>
    <h3 style="text-align:center; color:#8b6df0; margin:0 0 40px;"><i class="fa-solid fa-user-graduate"></i> For Candidates</h3>
    <div class="steps-container">
        <?php if (!empty($is_logged_in)): ?>
        <a href="<?= $dashboard_url; ?>" class="step-item">
            <div class="step-number">1</div>
            <i class="fa-solid fa-user-check"></i>
            <h3>Active Account</h3>
            <p>You are logged in as <?= htmlspecialchars($_SESSION['name'] ?? $user_role_label); ?>. Access your portal directly.</p>
            <span class="step-cta">Go to dashboard &rarr;</span>
        </a>
        <?php else: ?>
        <a href="registration1.php" class="step-item" data-auth="signup">
            <div class="step-number">1</div>
            <i class="fa-solid fa-user-plus"></i>
            <h3>Register</h3>
            <p>Create your free account in seconds and set up your candidate profile.</p>
            <span class="step-cta">Sign up now &rarr;</span>
        </a>
        <?php endif; ?>
        <a href="exams.php" class="step-item">
            <div class="step-number">2</div>
            <i class="fa-solid fa-magnifying-glass"></i>
            <h3>Choose Exam</h3>
            <p>Browse our library and pick the certification or mock test that fits your goals.</p>
            <span class="step-cta">Browse exams &rarr;</span>
        </a>
        <a href="features.php" class="step-item">
            <div class="step-number">3</div>
            <i class="fa-solid fa-book-open"></i>
            <h3>Practice/Prepare</h3>
            <p>Sharpen your skills with practice tests and topic-wise performance feedback.</p>
            <span class="step-cta">See features &rarr;</span>
        </a>
        <a href="login.php" class="step-item" data-auth="login">
            <div class="step-number">4</div>
            <i class="fa-solid fa-award"></i>
            <h3>Get Certified</h3>
            <p>Pass your exam and instantly download your official certificate.</p>
            <span class="step-cta">Go to dashboard &rarr;</span>
        </a>
    </div>
    <h3 style="text-align:center; color:#8b6df0; margin:90px 0 40px;"><i class="fa-solid fa-chalkboard-user"></i> For Examiners</h3>
    <div class="steps-container">
        <a href="../examiner/login.php" class="step-item">
            <div class="step-number">1</div>
            <i class="fa-solid fa-user-tie"></i>
            <h3>Join &amp; Get Approved</h3>
            <p>Sign up as an examiner and get verified by the administrator to start creating exams.</p>
            <span class="step-cta">Become an examiner &rarr;</span>
        </a>
        <a href="../examiner/login.php" class="step-item">
            <div class="step-number">2</div>
            <i class="fa-solid fa-pen-to-square"></i>
            <h3>Create Exams</h3>
            <p>Build certification exams and mock tests with your own questions, duration and pass marks.</p>
            <span class="step-cta">Start creating &rarr;</span>
        </a>
        <a href="../examiner/login.php" class="step-item">
            <div class="step-number">3</div>
            <i class="fa-solid fa-book"></i>
            <h3>Share References</h3>
            <p>Upload study references so candidates can prepare for the exams you publish.</p>
            <span class="step-cta">Add references &rarr;</span>
        </a>
        <a href="../examiner/login.php" class="step-item">
            <div class="step-number">4</div>
            <i class="fa-solid fa-chart-pie"></i>
            <h3>Track Results</h3>
            <p>See who registered and paid, review marks, and monitor pass/fail across your exams.</p>
            <span class="step-cta">View dashboard &rarr;</span>
        </a>
    </div>
</section>
    

    <!-- QUICK HIGHLIGHTS -->
    <section class="section">
        <h2 class="section-title">Why Choose Evalio?</h2>
        <p style="text-align:center; color:#999; max-width:700px; margin:-10px auto 40px;">Built for candidates who want to prepare and certify, and for examiners who want to create and manage exams with confidence.</p>
        <div class="grid-container">

            <div class="card-custom">
                <img src="index/image/examcategory.jpg" alt="Diverse Exam Categories" class="card-img">
                <i class="fa-solid fa-graduation-cap"></i>
                <h3>Diverse Exam Categories</h3>
                <p>From IT certifications to academic and professional assessments, Evalio hosts hundreds of structured exam sets spanning multiple domains — so you can find and practice for exactly the exam you need, all in one place.</p>
                <a href="exams.php" style="color: #5124f1; text-decoration: none; font-weight: 600; display: inline-block; margin-top: 10px;">Explore Exams &rarr;</a>
            </div>

            <div class="card-custom">
                <img src="index/image/testing.jpg" alt="Interactive Testing" class="card-img">
                <i class="fa-solid fa-laptop-code"></i>
                <h3>Interactive Testing</h3>
                <p>Sit realistic, timed mock and certification exams that mirror the actual test environment, complete with instant scoring, detailed performance analytics, and instant submission feedback the moment you finish.</p>
                <a href="features.php" style="color: #5124f1; text-decoration: none; font-weight: 600; display: inline-block; margin-top: 10px;">View Features &rarr;</a>
            </div>

            <div class="card-custom">
                <img src="index/image/support.jpg" alt="Dedicated Support" class="card-img">
                <i class="fa-solid fa-headset"></i>
                <h3>Dedicated Support</h3>
                <p>Run into an issue or have a question about your exam, account, or results? Our dedicated support team is available around the clock, ready to help candidates, examiners, and administrators alike.</p>
                <a href="contact.php" style="color: #5124f1; text-decoration: none; font-weight: 600; display: inline-block; margin-top: 10px;">Contact Us &rarr;</a>
            </div>

            <div class="card-custom">
                <img src="index/image/instant.jpg" alt="Instant Result" class="card-img">
    <i class="fa-solid fa-bolt"></i>
    <h3>Instant Results</h3>
    <p>Get your score, correct answers, and performance breakdown the moment you submit — no waiting.</p>
</div>

<div class="card-custom">
    <img src="index/image/Proctoring.jpg" alt="Secure Proctoring" class="card-img">
    <i class="fa-solid fa-shield-halved"></i>
    <h3>Secure Proctoring</h3>
    <p>Evalio ensures test integrity with built-in anti-cheating safeguards that keep exams secure, fair, and credible. By enforcing browser lockdown, restricting tab switching, and preventing unauthorized navigation, the platform creates a controlled environment where candidates are evaluated strictly on their own knowledge. These protective measures safeguard the value of your certifications, giving institutions full confidence in the authenticity of every result..</p>
</div>

<div class="card-custom">
    <img src="index/image/img2.jpg" alt="Question Bank Management" class="card-img">
    <i class="fa-solid fa-database"></i>
    <h3>Question Bank for Examiners</h3>
    <p>Create, organise and reuse MCQ, true/false and descriptive questions, then pull them into any exam you build.</p>
    <a href="../examiner/login.php" style="color: #5124f1; text-decoration: none; font-weight: 600; display: inline-block; margin-top: 10px;">Examiner Portal &rarr;</a>
</div>

<div class="card-custom">
    <img src="index/image/img4.jpg" alt="Exam Builder" class="card-img">
    <i class="fa-solid fa-file-lines"></i>
    <h3>Flexible Exam Builder</h3>
    <p>Author official certification exams and practice mock tests with your own timing, marking and schedule, and stay in full control of your own exams.</p>
</div>

<div class="card-custom">
    <img src="index/image/img3.jpg" alt="Registrations and Results" class="card-img">
    <i class="fa-solid fa-users"></i>
    <h3>Registrations &amp; Results</h3>
    <p>See which candidates registered and paid for your exams, review their marks, and track pass/fail performance in one place.</p>
</div>


        </div>
    </section>

    <!-- TESTIMONIALS -->
<section class="section section-alt">
    <h2 class="section-title">What Our Candidates &amp; Examiners Say</h2>
    <div class="grid-container">

        <div class="testimonial-card">
            <div class="stars">
                <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
            </div>
            <p class="testimonial-text">"Evalio made my certification prep so much easier. The mock tests felt exactly like the real exam."</p>
            <div class="testimonial-author">
                <h4>Riya Sharma</h4>
                <span>PMP Certified</span>
            </div>
        </div>

        <div class="testimonial-card">
            <div class="stars">
                <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
            </div>
            <p class="testimonial-text">"The instant results and detailed analytics helped me pinpoint exactly where I needed to improve."</p>
            <div class="testimonial-author">
                <h4>Arjun Menon</h4>
                <span>IT Certification</span>
            </div>
        </div>

        <div class="testimonial-card">
            <div class="stars">
                <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star-half-stroke"></i>
            </div>
            <p class="testimonial-text">"Simple to use, reliable, and the support team responded within minutes when I had an issue."</p>
            <div class="testimonial-author">
                <h4>Priya Nair</h4>
                <span>Academic Exam</span>
            </div>
        </div>


        <div class="testimonial-card">
            <div class="stars">
                <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
            </div>
            <p class="testimonial-text">"Creating a certification exam and a mock test from my own question bank took minutes, not days."</p>
            <div class="testimonial-author">
                <h4>Dr. Anil Kumar</h4>
                <span>Examiner</span>
            </div>
        </div>

        <div class="testimonial-card">
            <div class="stars">
                <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
            </div>
            <p class="testimonial-text">"I can see exactly who registered and paid for my exams, and how each candidate performed."</p>
            <div class="testimonial-author">
                <h4>Meera Thomas</h4>
                <span>Examiner</span>
            </div>
        </div>

        <div class="testimonial-card">
            <div class="stars">
                <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star-half-stroke"></i>
            </div>
            <p class="testimonial-text">"My exams stay under my control, and the pass/fail reports save me hours of manual checking."</p>
            <div class="testimonial-author">
                <h4>Joseph Varghese</h4>
                <span>Examiner</span>
            </div>
        </div>
    </div>
</section>

    <!-- TRUSTED BY -->
<section class="section partners-section">
    <h2 class="section-title">Trusted By</h2>
    <div class="partners-row">
        <div class="partner-logo">TechCert University</div>
        <div class="partner-logo">Global Skills Board</div>
        <div class="partner-logo">ProMetrics Institute</div>
        <div class="partner-logo">Cambridge Learning Group</div>
        <div class="partner-logo">National IT Council</div>
    </div>
</section>

<?php include('includes/footer.php'); ?>