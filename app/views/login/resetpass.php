<form id="formResetPass" method="POST" action="<?= base_url() ?>/login/resetPass">
    <label for="txtEmailReset">Correo electrónico:</label>
    <input type="email" id="txtEmailReset" name="txtEmailReset" required>
    <button type="submit">Recuperar contraseña</button>
</form>
<div id="resetPassMsg"></div>
<script>
document.getElementById('formResetPass').addEventListener('submit', function(e){
    e.preventDefault();
    let form = e.target;
    let formData = new FormData(form);
    fetch(form.action, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        document.getElementById('resetPassMsg').textContent = data.msg;
    });
});
</script>