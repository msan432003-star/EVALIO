</div><!-- /#page-inner -->
        </div><!-- /#page-wrapper -->
    </div><!-- /#wrapper -->

    <!-- Bootstrap Core JavaScript (jQuery is already loaded in header.php) -->
    <script src="assets/js/bootstrap.min.js"></script>
    <!-- Sidebar / mobile menu toggle -->
    <script src="assets/js/custom.js"></script>

    <!-- Evalio chatbot: guest pages get the public bot, logged-in students get the student bot -->
    <?php $chatbot_profile = !empty($is_guest) ? 'public' : 'examinee'; include __DIR__ . '/../chatbot/widget.php'; ?>
</body>
</html>