
<!-- Font Awesome -->
<script src="/project/app/assets/fontawesome/js/fontawesome.js" crossorigin="anonymous"></script>
<!-- jQuery -->
<script src="/project/app/assets/DataTables/jquery.min.js"></script>
<!-- DataTables -->
<script src="/project/app/assets/DataTables/datatables.js"></script>
<!-- sweetAlerts -->
<script src="/project/app/assets/sweetAlert/sweetalert2.all.min.js"></script>
<!-- Archivo dinámico -->
<?php if (isset($data['page_functions_js'])): ?>
  <script src="/project/app/assets/js/<?php echo $data['page_functions_js']; ?>"></script>
<?php endif; ?>

</div>
</body>

</html>