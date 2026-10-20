<?php 
/* features.php - Portal Features (Examinees & Examiners) */
require_once('../config/autoload.php'); 

$pageTitle = "Evalio - Features";
include('includes/header.php');
?>

<style>
    /* ---------- Layout helpers ---------- */
    .section-subtitle {
        text-align: center;
        color: #999;
        max-width: 700px;
        margin: 0 auto 50px auto;
        font-size: 15px;
        line-height: 1.6;
    }

    /* ---------- Scroll reveal ---------- */
    .reveal {
        opacity: 0;
        transform: translateY(40px);
        transition: opacity 0.7s ease, transform 0.7s ease;
    }
    .reveal.is-visible {
        opacity: 1;
        transform: translateY(0);
    }
    /* stagger children of a revealed grid */
    .grid-container.is-visible .card-custom {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
    .grid-container .card-custom {
        opacity: 0;
        transform: translateY(30px) scale(0.97);
        transition: opacity 0.6s ease, transform 0.6s ease, border-color 0.3s, box-shadow 0.3s;
    }
    .grid-container.is-visible .card-custom:nth-child(1) { transition-delay: 0.05s; }
    .grid-container.is-visible .card-custom:nth-child(2) { transition-delay: 0.15s; }
    .grid-container.is-visible .card-custom:nth-child(3) { transition-delay: 0.25s; }
    .grid-container.is-visible .card-custom:nth-child(4) { transition-delay: 0.35s; }
    .grid-container.is-visible .card-custom:nth-child(5) { transition-delay: 0.45s; }
    .grid-container.is-visible .card-custom:nth-child(6) { transition-delay: 0.55s; }

    /* ---------- Feature cards: hover motion ---------- */
    .card-custom {
        position: relative;
        overflow: hidden;
        cursor: default;
    }
    .card-custom::before {
        content: "";
        position: absolute;
        inset: 0;
        background: radial-gradient(circle at 50% 0%, rgba(81, 36, 241, 0.18), transparent 65%);
        opacity: 0;
        transition: opacity 0.4s ease;
        pointer-events: none;
    }
    .card-custom:hover::before {
        opacity: 1;
    }
    .card-custom:hover {
        transform: translateY(-10px) scale(1.02);
    }
    .card-custom i {
        transition: transform 0.4s ease, color 0.3s ease;
        display: inline-block;
    }
    .card-custom:hover i {
        transform: translateY(-6px) scale(1.15) rotate(-6deg);
        color: #8a6bff;
    }
    .card-custom h3 {
        transition: transform 0.3s ease;
    }
    .card-custom:hover h3 {
        transform: translateX(4px);
    }

    /* ---------- Floating accent shapes for motion feel ---------- */
    .features-wrap {
        position: relative;
    }
    .floating-orb {
        position: absolute;
        border-radius: 50%;
        filter: blur(60px);
        opacity: 0.25;
        pointer-events: none;
        z-index: 0;
    }
    .floating-orb.orb-1 {
        width: 260px; height: 260px;
        background: #5124f1;
        top: -60px; left: -60px;
        animation: floatY 7s ease-in-out infinite;
    }
    .floating-orb.orb-2 {
        width: 200px; height: 200px;
        background: #8a6bff;
        bottom: -40px; right: -40px;
        animation: floatY 9s ease-in-out infinite reverse;
    }
    @keyframes floatY {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(30px); }
    }
    .section, .gallery-grid, .grid-container {
        position: relative;
        z-index: 1;
    }
    .glow-title {
    display: inline-block;
}

    @media (prefers-reduced-motion: reduce) {
        .reveal, .grid-container .card-custom,
        .card-custom, .card-custom i, .card-custom h3,
        .floating-orb {
            transition: none !important;
            animation: none !important;
        }
        .reveal, .grid-container .card-custom {
            opacity: 1 !important;
            transform: none !important;
        }
    }
</style>

<section class="section section-alt features-wrap">
    <span class="floating-orb orb-1"></span>
    <span class="floating-orb orb-2"></span>

    <h2 class="section-title reveal"><span class="glow-title">Portal Features</span></h2>
    <p class="section-subtitle reveal">
        Everything you need to create, manage, and take exams online &mdash; built for
        reliability, speed, and a smooth experience for both examiners and examinees.
    </p>
    <h3 class="reveal" style="text-align:center; color:#8b6df0; margin:0 0 30px;"><i class="fa-solid fa-chalkboard-user"></i> For Examiners</h3>
    <div class="grid-container reveal">
        <div class="card-custom">
            <i class="fa-solid fa-database"></i>
            <h3>Question Bank Management</h3>
            <p>Create, edit and organise MCQ, true/false and descriptive questions and reuse them across exams.</p>
        </div>
        <div class="card-custom">
            <i class="fa-solid fa-file-lines"></i>
            <h3>Exam &amp; Mock Test Builder</h3>
            <p>Author certification exams and practice mock tests with custom duration, marks and pass criteria.</p>
        </div>
        <div class="card-custom">
            <i class="fa-solid fa-folder"></i>
            <h3>Your Exams, Your Control</h3>
            <p>Each examiner sees and manages only their own exams, references and dashboard statistics.</p>
        </div>
        <div class="card-custom">
            <i class="fa-solid fa-user-check"></i>
            <h3>Registered Students</h3>
            <p>View the students who registered and paid for your exams, along with their registration dates.</p>
        </div>
        <div class="card-custom">
            <i class="fa-solid fa-book"></i>
            <h3>Study References</h3>
            <p>Upload and share study material so candidates can prepare for the exams you publish.</p>
        </div>
        <div class="card-custom">
            <i class="fa-solid fa-chart-pie"></i>
            <h3>Results &amp; Pass/Fail Insights</h3>
            <p>Review marks and pass/fail outcomes for every exam you run, student by student.</p>
        </div>
    </div>

    <h3 class="reveal" style="text-align:center; color:#8b6df0; margin:70px 0 30px;"><i class="fa-solid fa-user-graduate"></i> For Examinees</h3>
    <div class="grid-container reveal">
        <div class="card-custom">
            <i class="fa-solid fa-laptop-code"></i>
            <h3>Online Mock Tests</h3>
            <p>Simulate real exam scenarios with timed mock evaluations and automatic progress saving.</p>
        </div>
        <div class="card-custom">
            <i class="fa-solid fa-certificate"></i>
            <h3>Certification Exams</h3>
            <p>Gain accredited certifications upon successful completion of core requirements.</p>
        </div>
        <div class="card-custom">
            <i class="fa-solid fa-chart-line"></i>
            <h3>Instant Analytics</h3>
            <p>Review detailed performance breakdowns and scorecards immediately after test submission.</p>
        </div>
        <div class="card-custom">
            <i class="fa-solid fa-clock"></i>
            <h3>Timed &amp; Auto-Submit Exams</h3>
            <p>Set custom durations with live countdown timers and automatic submission when time runs out.</p>
        </div>
        <div class="card-custom">
            <i class="fa-solid fa-shield-halved"></i>
            <h3>Secure &amp; Role-Based Access</h3>
            <p>Separate dashboards and permissions for admins, examiners, and examinees keep data protected.</p>
        </div>
        <div class="card-custom">
            <i class="fa-solid fa-list-check"></i>
            <h3>Multiple Question Types</h3>
            <p>Support for MCQs, true/false, and descriptive questions to suit any exam format.</p>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var revealEls = document.querySelectorAll('.reveal');
    var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.15 });

    revealEls.forEach(function (el) {
        observer.observe(el);
    });
});
</script>

<?php include('includes/footer.php'); ?>