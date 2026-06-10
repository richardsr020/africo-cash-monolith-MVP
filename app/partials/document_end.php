  <div class="toast-notify" data-toast aria-live="polite" aria-atomic="true"></div>
  <script defer src="https://cdn.jsdelivr.net/npm/axios@1.7.9/dist/axios.min.js"></script>
  <script defer src="<?= asset_path('/assets/js/core/config.js') ?>"></script>
  <script defer src="<?= asset_path('/assets/js/core/auth-token.js') ?>"></script>
  <script defer src="<?= asset_path('/assets/js/core/api-client.js') ?>"></script>
  <script defer src="<?= asset_path('/assets/js/core/dom.js') ?>"></script>
  <script defer src="<?= asset_path('/assets/js/core/app.js') ?>"></script>
  <?php foreach (($currentPage['scripts'] ?? []) as $script): ?>
    <script defer src="<?= asset_path($script) ?>"></script>
  <?php endforeach; ?>
</body>
</html>
