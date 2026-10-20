            </div><!-- /#page-inner -->
        </div><!-- /#page-wrapper -->
    </div><!-- /#wrapper -->

    <!-- jQuery -->
    <script src="assets/js/jquery-1.10.2.js"></script>
    <!-- Bootstrap Core JavaScript -->
    <script src="assets/js/bootstrap.min.js"></script>
    <!-- Sidebar / Custom JS -->
    <script src="assets/js/custom.js"></script>

    <!-- Theme Toggle Controller -->
    <script>
        function updateAdminThemeUI(theme) {
            var icon = document.getElementById('theme-toggle-icon');
            var label = document.getElementById('theme-toggle-label');
            if (icon) {
                if (theme === 'dark') {
                    icon.className = 'fa fa-sun-o';
                    if (label) label.textContent = 'Light';
                } else {
                    icon.className = 'fa fa-moon-o';
                    if (label) label.textContent = 'Dark';
                }
            }
        }

        function toggleAdminTheme() {
            var currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
            var newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('admin_theme', newTheme);
            updateAdminThemeUI(newTheme);
        }

        // Initialize state on page load
        (function() {
            var activeTheme = document.documentElement.getAttribute('data-theme') || localStorage.getItem('admin_theme') || 'light';
            updateAdminThemeUI(activeTheme);
        })();
    </script>
</body>
</html>