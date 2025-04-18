
<form method="POST" action="<?= BASE_URL ?>/login/login">
    <label for="usuario">Usuario:</label>
    <input type="text" name="usuario" id="usuario" required>

    <label for="clave">Contraseña:</label>
    <input type="password" name="clave" id="clave" required>

    <button type="submit">Iniciar Sesión</button>
</form>