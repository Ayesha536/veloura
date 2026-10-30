<?php /* VELOURA — Admin panel footer */ ?>
    </div><!-- /.admin-content -->
  </div><!-- /.admin-main -->
</div><!-- /.admin-wrapper -->

<div class="admin-overlay" id="adminOverlay"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php if (!empty($loadChartJs)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<?php endif; ?>
<script src="<?= url('assets/js/admin.js') ?>?v=1.0"></script>
</body>
</html>
