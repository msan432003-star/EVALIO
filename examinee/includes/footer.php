<?php
/* includes/footer.php */
?>
    <!-- FOOTER -->
    <footer>
        <p>&copy; <?= date('Y'); ?> Evalio - Online Exam and Certification Platform. All rights reserved.</p>
    </footer>

    <!-- ROLE SELECTION MODAL -->
    <div class="role-modal-backdrop" id="roleModal" role="dialog" aria-modal="true" aria-labelledby="roleModalTitle" aria-describedby="roleModalDesc" aria-hidden="true">
        <div class="role-modal-container">
            <!-- Close Button -->
            <button type="button" class="role-modal-close" id="roleModalClose" aria-label="Close dialog">
                <i class="fa-solid fa-xmark"></i>
            </button>

            <!-- Modal Header -->
            <div class="role-modal-header">
                <h2 class="role-modal-title" id="roleModalTitle">Login as</h2>
                <p class="role-modal-desc" id="roleModalDesc">Select your role to continue</p>
            </div>

            <!-- Role Cards -->
            <div class="role-modal-grid">
                <!-- Examinee Card -->
                <a href="login.php" class="role-modal-card" id="roleCardExaminee">
                    <div class="role-card-icon-wrap">
                        <i class="fa-solid fa-graduation-cap"></i>
                    </div>
                    <h3 class="role-card-name">Examinee</h3>
                    <p class="role-card-text">Take exams and earn certifications</p>
                    <span class="role-card-arrow"><i class="fa-solid fa-arrow-right"></i></span>
                </a>

                <!-- Examiner Card -->
                <a href="../examiner/login.php" class="role-modal-card" id="roleCardExaminer">
                    <div class="role-card-icon-wrap">
                        <i class="fa-solid fa-chalkboard-user"></i>
                    </div>
                    <h3 class="role-card-name">Examiner</h3>
                    <p class="role-card-text">Create exams and evaluate candidates</p>
                    <span class="role-card-arrow"><i class="fa-solid fa-arrow-right"></i></span>
                </a>
            </div>
        </div>
    </div>

    <!-- Role Selection Modal Controller -->
    <script>
    (function() {
        var roleModal = document.getElementById('roleModal');
        if (!roleModal) return;

        var modalTitle = document.getElementById('roleModalTitle');
        var roleCardExaminee = document.getElementById('roleCardExaminee');
        var roleCardExaminer = document.getElementById('roleCardExaminer');
        var closeBtn = document.getElementById('roleModalClose');
        var lastActiveElement = null;

        var authConfig = {
            login: {
                title: 'Login as',
                examineeHref: 'login.php',
                examinerHref: '../examiner/login.php'
            },
            signup: {
                title: 'Sign up as',
                examineeHref: 'registration1.php',
                examinerHref: '../examiner/registration1.php'
            }
        };

        function openRoleModal(mode) {
            var config = authConfig[mode] || authConfig.login;
            if (modalTitle) {
                modalTitle.textContent = config.title;
            }
            if (roleCardExaminee) {
                roleCardExaminee.setAttribute('href', config.examineeHref);
            }
            if (roleCardExaminer) {
                roleCardExaminer.setAttribute('href', config.examinerHref);
            }

            lastActiveElement = document.activeElement;
            roleModal.classList.add('active');
            roleModal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('modal-open');

            setTimeout(function() {
                if (roleCardExaminee) {
                    roleCardExaminee.focus();
                } else if (closeBtn) {
                    closeBtn.focus();
                }
            }, 50);
        }

        function closeRoleModal() {
            if (!roleModal.classList.contains('active')) return;
            roleModal.classList.remove('active');
            roleModal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('modal-open');

            if (lastActiveElement && typeof lastActiveElement.focus === 'function') {
                lastActiveElement.focus();
            }
        }

        // Intercept clicks on any element with data-auth
        document.querySelectorAll('[data-auth]').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                var mode = this.getAttribute('data-auth') || 'login';
                openRoleModal(mode);
            });
        });

        // Close button click
        if (closeBtn) {
            closeBtn.addEventListener('click', function(e) {
                e.preventDefault();
                closeRoleModal();
            });
        }

        // Overlay click outside container
        roleModal.addEventListener('click', function(e) {
            if (e.target === roleModal) {
                closeRoleModal();
            }
        });

        // Keyboard handler: Escape to close and Tab focus trap
        document.addEventListener('keydown', function(e) {
            if (!roleModal.classList.contains('active')) return;

            if (e.key === 'Escape' || e.key === 'Esc') {
                e.preventDefault();
                closeRoleModal();
                return;
            }

            if (e.key === 'Tab') {
                var focusables = roleModal.querySelectorAll('a[href], button:not([disabled])');
                if (focusables.length === 0) return;

                var firstFocusable = focusables[0];
                var lastFocusable = focusables[focusables.length - 1];

                if (e.shiftKey) {
                    if (document.activeElement === firstFocusable) {
                        e.preventDefault();
                        lastFocusable.focus();
                    }
                } else {
                    if (document.activeElement === lastFocusable) {
                        e.preventDefault();
                        firstFocusable.focus();
                    }
                }
            }
        });
    })();
    </script>

    <!-- Evalio chatbot (public profile). This file is in examinee/includes/, so the chatbot folder is two levels up. -->
    <?php $chatbot_profile = 'public'; include __DIR__ . '/../../chatbot/widget.php'; ?>
</body>
</html>