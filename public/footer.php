<script nonce="<?= generateCSPNonce(); ?>">
  const base_url = "<?= base_url(); ?>";
</script>

<!-- Font Awesome -->
<script src="<?= base_url('app/assets/fontawesome/js/fontawesome.js'); ?>" crossorigin="anonymous"></script>
<!-- jQuery -->
<script src="<?= base_url('app/assets/DataTables/jquery.min.js'); ?>"></script>
<!-- DataTables -->
<script src="<?= base_url('app/assets/DataTables/datatables.js'); ?>"></script>

<!-- sweetAlerts -->
<script type="text/javascript" src="<?= base_url('app/assets/sweetAlert/sweetalert2.all.min.js'); ?>"></script>
<!-- Shepherd.js JavaScript Local -->
<script src="<?= base_url('app/assets/shepherd.js/shepherd-simple.js'); ?>"></script>
<!-- Chart.js para gráficos del dashboard -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<!-- Archivo dinámico de validaciones -->
<script type="module" src="<?= base_url('app/assets/js/validaciones.js'); ?>"></script>
<script type="module" src="<?= base_url('app/assets/js/exporthelpers.js'); ?>"></script>
<?php if (isset($data['page_functions_js'])): ?>
  <script type="module" src="<?= base_url('app/assets/js/' . $data['page_functions_js']); ?>"></script>
<?php endif; ?>
<script type="module" src="<?= base_url('app/assets/js/functions_header.js'); ?>"></script>
<!-- WebSocket Notifications Client -->\n
<script type="module" src="<?= base_url('app/assets/js/notifications-websocket.js'); ?>"></script>

</div>
</body>

</html>