</div><!-- /#page-inner -->
        </div><!-- /#page-wrapper -->
    </div><!-- /#wrapper -->

    <!-- jQuery -->
    <script src="assets/js/jquery-1.10.2.js"></script>
    <!-- Bootstrap Core JavaScript -->
    <script src="assets/js/bootstrap.min.js"></script>
    <!-- Metis Menu Plugin JavaScript -->
    <script src="assets/js/jquery.metisMenu.js"></script>
    <!-- Custom Scripts -->
    <script src="assets/js/custom.js"></script>

    <!-- Theme Toggle Controller -->
    <script>
        function updateThemeElements(theme) {
            var icon = document.getElementById('theme-toggle-icon');
            var label = document.getElementById('theme-toggle-label');
            if (icon) {
                if (theme === 'dark') {
                    icon.className = 'fa fa-sun-o';
                    if (label) label.textContent = 'Light Mode';
                } else {
                    icon.className = 'fa fa-moon-o';
                    if (label) label.textContent = 'Dark Mode';
                }
            }
        }

        function toggleExaminerTheme() {
            var currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
            var newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('examiner_theme', newTheme);
            updateThemeElements(newTheme);
        }

        // Initialize state on load
        (function() {
            var activeTheme = document.documentElement.getAttribute('data-theme') || localStorage.getItem('examiner_theme') || 'light';
            updateThemeElements(activeTheme);
        })();
    </script>

    <!-- Evalio chatbot (examiner profile): shown ONLY on the examiner dashboard.
         To show it on another examiner page later, add that file name to the list. -->
    <?php
    $cb_show_on = array('dashboard.php');
    if (in_array(basename($_SERVER['PHP_SELF']), $cb_show_on, true)) {
        $chatbot_profile = 'examiner';
        include __DIR__ . '/../chatbot/widget.php';
    }
    ?>
</body>
</html>