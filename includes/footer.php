<?php

?>
                    <div class="content-backdrop fade"></div>
                </div>
                <!-- Content wrapper -->
            </div>
            <!-- / Layout page -->
        </div>

        <!-- Overlay -->
        <div class="layout-overlay layout-menu-toggle"></div>
    </div>
    <!-- / Layout wrapper -->

    <!-- Core JS -->
    <script src="<?php echo isset($assets_url) ? $assets_url : '../assets/'; ?>vendor/libs/jquery/jquery.js"></script>
    <script src="<?php echo isset($assets_url) ? $assets_url : '../assets/'; ?>vendor/libs/popper/popper.js"></script>
    <script src="<?php echo isset($assets_url) ? $assets_url : '../assets/'; ?>vendor/js/bootstrap.js"></script>
    <script src="<?php echo isset($assets_url) ? $assets_url : '../assets/'; ?>vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="<?php echo isset($assets_url) ? $assets_url : '../assets/'; ?>vendor/js/menu.js"></script>
    <script src="<?php echo isset($assets_url) ? $assets_url : '../assets/'; ?>js/main.js"></script>

    <?php if (isset($extra_js)): ?>
        <?php foreach ($extra_js as $js): ?>
            <script src="<?php echo $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if (isset($custom_js)): ?>
        <script><?php echo $custom_js; ?></script>
    <?php endif; ?>

</body>
</html>