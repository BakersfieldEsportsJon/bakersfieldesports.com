
<?php if (isset($extra_scripts)): ?>
    <?php foreach ((array)$extra_scripts as $script): ?>
    <script src="<?= $script ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>
<?php if (isset($extra_footer_content)) echo $extra_footer_content; ?>
</body>
</html>
