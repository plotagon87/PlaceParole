<?php
/**
 * templates/footer.php
 * Close the main content wrapper and body
 * Include at the end of every page: require_once '../../templates/footer.php';
 */
?>
</main>

<!-- Footer -->
<footer class="text-center text-gray-400 text-sm py-6 mt-16 border-t border-gray-200 bg-gray-100">
    &copy; <?= date('Y') ?> <strong><?= $t['app_name'] ?? 'PlaceParole' ?></strong> — Built for Cameroon's Market Communities
</footer>

</body>
</html>
