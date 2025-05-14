
<!-- Font Awesome -->
<script src="/project/app/assets/fontawesome/js/fontawesome.js" crossorigin="anonymous"></script>
<!-- jQuery -->
<script src="/project/app/assets/DataTables/jquery.min.js"></script>
<!-- DataTables -->
<script src="/project/app/assets/DataTables/datatables.js"></script>
<!-- sweetAlerts -->
<script type="text/javascript" src="app\assets\sweetAlert\sweetalert2.all.min.js"></script>
<!-- Archivo dinámico de validaciones -->
<!-- <script type="module" src="/project/app/assets/js/validaciones.js"></script>
 <script type="module" src="/project/app/assets/js/exporthelpers.js"></script> -->

 <!-- Expresiones regulares -->
<!-- <script type="module" src="/project/app/assets/js/regex.js"></script> -->
<?php if (isset($data['page_functions_js'])): ?>
  <script  src="/project/app/assets/js/<?php echo $data['page_functions_js']; ?>"></script>
<?php endif; ?>

</div>
</body>

</html>