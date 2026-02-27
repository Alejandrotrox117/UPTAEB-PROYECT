<script nonce="<?= generateCSPNonce(); ?>">
  window.base_url = "<?= base_url(); ?>";

  // Variables de sesión para WebSocket
  window.SESSION_USER_ID = <?= $_SESSION['usuario_id'] ?? 0; ?>;
  window.SESSION_ROL_ID = <?= $_SESSION['rol_id'] ?? 0; ?>;
  window.SESSION_ROL_NOMBRE = '<?= $_SESSION['user']['rol_nombre'] ?? 'Usuario'; ?>';
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
<!-- DEBUG: Footer.php cargado correctamente -->
<!-- Sistema de Notificaciones WebSocket -->
<script>console.log('DEBUG: Intentando cargar notificaciones_websocket.js desde:', '<?= base_url('app/assets/js/notificaciones_websocket.js'); ?>');</script>
<script src="<?= base_url('app/assets/js/notificaciones_websocket.js'); ?>"></script>
<!-- Herramienta Global de Ayuda (FAQ) -->
<script src="<?= base_url('app/assets/js/global_help.js'); ?>"></script>

</div>
</body>

</html>