<?php 
/* about.php - About Us */
require_once('../config/autoload.php'); 

$pageTitle = "Evalio - About Us";
include('includes/header.php');

// Pull images straight from the exams upload folder so this page always
// shows real, current images without hardcoding filenames.
$examImagesDir = '../uploads/exams/';
$examImages = glob($examImagesDir . '*.{jpg,jpeg,png,webp,gif}', GLOB_BRACE);
sort($examImages);

function valio_about_image($images, $index, $alt) {
    echo '<div class="ab-img-wrap">';
    if (isset($images[$index])) {
        echo '<img src="' . htmlspecialchars($images[$index]) . '" alt="' . htmlspecialchars($alt) . '" loading="lazy">';
    } else {
        echo '<div class="ab-img-fallback"></div>';
    }
    echo '</div>';
}

// Find the "wall" image specifically (any extension), falling back to the
// first image in the folder if it isn't found.
$wallMatches = glob($examImagesDir . 'wall.*');
$heroImagePath = isset($wallMatches[0]) ? $wallMatches[0] : (isset($examImages[0]) ? $examImages[0] : null);

function valio_about_image_path($path, $alt) {
    echo '<div class="ab-img-wrap">';
    if ($path) {
        echo '<img src="' . htmlspecialchars($path) . '" alt="' . htmlspecialchars($alt) . '" loading="lazy">';
    } else {
        echo '<div class="ab-img-fallback"></div>';
    }
    echo '</div>';
}
?>
<style>
    :root {
        --ab-bg: #0b0a12;
        --ab-panel: #14121e;
        --ab-line: rgba(255,255,255,0.08);
        --ab-text: #eae7f5;
        --ab-muted: #9d97b3;
        --ab-violet: #8b5cf6;
        --ab-magenta: #c026d3;
    }

    .glow-title {
        font-size: 45px;
    }

    .ab-wrap {
        max-width: 1140px;
        margin: 0 auto;
        padding: 0 clamp(20px, 4vw, 40px);
        color: var(--ab-text);
    }

    .ab-hero {
        text-align: center;
        max-width: 720px;
        margin: 0 auto;
    }

    .ab-hero-title {
        padding: 56px 0 32px;
    }

    .ab-hero-text {
        padding: 32px 0 8px;
    }

    .ab-hero p {
        margin-top: 18px;
        line-height: 1.75;
        color: var(--ab-muted);
        font-size: 17px;
    }

    .ab-hero-img {
        margin-top: 40px;
        width: 100%;
    }

    .ab-hero-img .ab-img-wrap {
        aspect-ratio: 21 / 9;
        border-radius: 0;
        border: none;
        width: 100%;
    }

    @media (min-width: 761px) {
        .ab-hero-img .ab-img-wrap {
            aspect-ratio: 21 / 8;
        }
    }

    .ab-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 56px;
        align-items: center;
        padding: 56px 0;
        border-top: 1px solid var(--ab-line);
    }

    .ab-row.reverse .ab-row-media { order: 2; }
    .ab-row.reverse .ab-row-text { order: 1; }

    .ab-row-text h3 {
        font-size: 26px;
        margin: 0 0 16px;
        letter-spacing: -0.01em;
    }

    .ab-row-text p {
        line-height: 1.75;
        color: var(--ab-muted);
        margin: 0 0 14px;
        font-size: 15.5px;
    }

    .ab-row-text p:last-child { margin-bottom: 0; }

    .ab-img-wrap {
        position: relative;
        border-radius: 18px;
        overflow: hidden;
        aspect-ratio: 4 / 3;
        background: var(--ab-panel);
        border: 1px solid var(--ab-line);
    }

    .ab-img-wrap img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .ab-img-fallback {
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, rgba(139,92,246,0.18), rgba(192,38,211,0.12));
    }

    .ab-team {
        padding: 56px 0 72px;
        border-top: 1px solid var(--ab-line);
    }

    .ab-team h3 {
        text-align: center;
        font-size: 26px;
        margin: 0 0 40px;
    }

    .ab-team-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 24px;
    }

    .ab-card {
        background: var(--ab-panel);
        border: 1px solid var(--ab-line);
        border-radius: 16px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    .ab-card .ab-img-wrap {
        border-radius: 0;
        border: none;
        aspect-ratio: 4 / 3;
    }

    .ab-card-body {
        padding: 22px;
    }

    .ab-card-body p.quote {
        font-size: 14px;
        line-height: 1.65;
        color: var(--ab-muted);
        margin: 0 0 16px;
    }

    .ab-card-body p.name {
        margin: 0;
        font-weight: 600;
        font-size: 15px;
    }

    .ab-card-body p.role {
        margin: 2px 0 0;
        font-size: 13px;
        color: var(--ab-muted);
    }

    @media (max-width: 760px) {
        .ab-row {
            grid-template-columns: 1fr;
            gap: 28px;
            padding: 40px 0;
        }
        .ab-row.reverse .ab-row-media,
        .ab-row.reverse .ab-row-text {
            order: initial;
        }
        .ab-team-grid {
            grid-template-columns: 1fr;
        }
        .ab-hero-img .ab-img-wrap {
            aspect-ratio: 4 / 3;
        }
    }
</style>

<div class="ab-wrap">

    <section class="ab-hero ab-hero-title">
        <h2 class="section-title glow-title">About Us</h2>
    </section>

</div>

<div class="ab-hero-img">
    <?php valio_about_image_path($heroImagePath, 'Students taking an exam on our platform'); ?>
</div>

<div class="ab-wrap">

    <section class="ab-hero ab-hero-text">
        <p>
            Our platform gives examiners the tools to create and manage exams, and gives examinees a reliable,
            secure, and accessible way to sit them online. Whether you're building a question bank or taking
            an official certification, the system is built for smooth administration and immediate progress
            tracking — for every exam we run.
        </p>
    </section>

    <section class="ab-row">
        <div class="ab-row-text">
            <h3>How it all started</h3>
            <p>
                It began with a simple idea: exams should be easy to run, easy to take, and impossible to
                tamper with. Early versions covered the basics — letting examiners upload question sets and schedule test
                windows, and giving examinees a clean, distraction-free screen to work on.
            </p>
            <p>
                As more institutions came on board, we kept adding what they actually needed: auto-grading,
                live progress tracking, and dashboards that give administrators a clear view of every exam
                in real time.
            </p>
        </div>
        <div class="ab-row-media">
            <?php valio_about_image($examImages, 1, 'Team discussing the platform'); ?>
        </div>
    </section>

    <section class="ab-row reverse">
        <div class="ab-row-text">
            <h3>Our mission and values</h3>
            <p>
                We believe examiners should stay in control of how their exams are run. The platform is
                built to adapt to your process, not the other way around — whatever your subject, format,
                or grading style, it stays out of your way so you can focus on the exam itself.
            </p>
            <p>
                Security and fairness sit behind every feature we ship, because a result only means
                something if people can trust the process behind it.
            </p>
        </div>
        <div class="ab-row-media">
            <?php valio_about_image($examImages, 2, 'Our mission and values'); ?>
        </div>
    </section>

    <section class="ab-row">
        <div class="ab-row-text">
            <h3>Built for examiners and examinees</h3>
            <p>
                Examiners get a dedicated panel to build question banks, create mock tests and certification
                exams, share study references, and see which students registered and how they performed —
                with every examiner seeing only their own exams.
            </p>
            <p>
                Examinees get a focused space to find exams, register, practise, sit timed tests and receive
                instant results and certificates, while administrators oversee approvals, courses and reports.
            </p>
        </div>
        <div class="ab-row-media">
            <?php valio_about_image($examImages, 6, 'Examiners and examinees on one platform'); ?>
        </div>
    </section>

    <section class="ab-team">
        <h3>Meet some of our team members</h3>
        <div class="ab-team-grid">
            <div class="ab-card">
                <?php valio_about_image($examImages, 3, 'Team member'); ?>
                <div class="ab-card-body">
                    <p class="quote">"Building tools that take the stress out of exam day, for students and administrators alike, is what gets me up in the morning."</p>
                    <p class="name">Team Member Name</p>
                    <p class="role">Full-stack Developer</p>
                </div>
            </div>
            <div class="ab-card">
                <?php valio_about_image($examImages, 4, 'Team member'); ?>
                <div class="ab-card-body">
                    <p class="quote">"Every feature request comes from a real exam room somewhere. We build with that in mind."</p>
                    <p class="name">Team Member Name</p>
                    <p class="role">Product & Support</p>
                </div>
            </div>
            <div class="ab-card">
                <?php valio_about_image($examImages, 5, 'Team member'); ?>
                <div class="ab-card-body">
                    <p class="quote">"Reliability isn't a feature, it's the whole job when you're powering someone's official exam."</p>
                    <p class="name">Team Member Name</p>
                    <p class="role">Platform Reliability</p>
                </div>
            </div>
        </div>
    </section>

</div>

<?php include('includes/footer.php'); ?>