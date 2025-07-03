<script>
  const base_url = "<?= base_url(); ?>";
</script>

<!-- Font Awesome -->
<script src="/project/app/assets/fontawesome/js/fontawesome.js" crossorigin="anonymous"></script>
<!-- jQuery -->
<script src="/project/app/assets/DataTables/jquery.min.js"></script>
<!-- DataTables -->
<script src="/project/app/assets/DataTables/datatables.js"></script>

<!-- sweetAlerts -->
<script type="text/javascript" src="/project/app/assets/sweetAlert/sweetalert2.all.min.js"></script>
<!-- Shepherd.js JavaScript Local -->
<script src="/project/app/assets/shepherd.js/shepherd-simple.js"></script>
<!-- Chart.js para gráficos del dashboard -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<!-- Archivo dinámico de validaciones -->
 <script type="module" src="/project/app/assets/js/validaciones.js"></script>
 <script type="module" src="/project/app/assets/js/exporthelpers.js"></script>
 <!-- Expresiones regulares -->
<!-- <script type="module" src="/project/app/assets/js/regex.js"></script> -->
<?php if (isset($data['page_functions_js'])): ?>
  <script type="module" src="/project/app/assets/js/<?php echo $data['page_functions_js']; ?>"></script>
<?php endif; ?>
 <script type="module" src="/project/app/assets/js/functions_header.js"></script>

</div>
</body>

</html>