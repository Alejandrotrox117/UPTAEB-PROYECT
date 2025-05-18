<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio de Sesión</title>
    <!-- Añadimos Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="shortcut icon" href="/project/app/assets/img/favicon.svg" type="image/x-icon">
    <style>
    body {
    margin: 0;
    padding: 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #f0f2f5;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    overflow: hidden; /* Evita el desbordamiento */
}

.login-container {
    background: white;
    padding: 40px 30px;
    border-radius: 15px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    text-align: center;
    width: 100%;
    max-width: 400px;
    box-sizing: border-box; /* Esto asegura que el padding no afecte el tamaño total del contenedor */
    margin: 0 15px; /* Espacio para que el contenedor no se toque con los bordes */
    overflow: hidden; /* Evita que el contenido se desborde */
}

.login-container img {
    width: 100px;
    margin-bottom: 20px;
}

.login-container h2 {
    margin-bottom: 20px;
    color: #333;
}

.login-container label {
    display: block;
    text-align: left;
    margin-top: 15px;
    font-weight: bold;
}

.login-container input {
    width: 100%;
    padding: 10px;
    margin-top: 5px;
    border: 1px solid #ccc;
    border-radius: 8px;
    box-sizing: border-box; /* Asegura que el padding no afecte el ancho total */
}

.login-container button {
    margin-top: 25px;
    padding: 12px;
    width: 100%;
    background-color: #4CAF50;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    cursor: pointer;
}

.login-container a {
    display: block;
    margin-top: 15px;
    font-size: 14px;
    color: #555;
}

.password-container {
    position: relative;
    width: 100%;
    margin-top: 10px; /* Espaciado para no pegarse al label anterior */
}

.password-container input {
    padding-right: 35px; /* Para hacer espacio para el ícono */
    width: 100%; /* Asegura que ocupe todo el ancho */
    box-sizing: border-box; /* Asegura que el padding no afecte el tamaño total */
}

.password-container .eye-icon {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    font-size: 18px; /* Tamaño del ícono */
    color: #888; /* Color suave */
}

/* Media queries para hacer la página responsiva */
@media (max-width: 600px) {
    .login-container {
        padding: 20px 15px;  /* Menos padding en dispositivos pequeños */
        max-width: 90%;  /* Permite que el contenedor ocupe un 90% del ancho disponible */
        margin: 0 10px;  /* Deja un pequeño margen lateral */
    }

    .login-container img {
        width: 80px; /* Logo más pequeño en dispositivos pequeños */
    }

    .login-container h2 {
        font-size: 1.5rem; /* Reducir el tamaño del título en pantallas pequeñas */
    }

    .login-container input {
        font-size: 14px;  /* Ajustar tamaño de fuente en inputs */
    }

    .login-container button {
        font-size: 14px;  /* Ajustar tamaño de fuente en el botón */
    }
}

/* Media query para pantallas muy pequeñas como teléfonos en modo retrato */
@media (max-width: 400px) {
    .login-container {
        padding: 15px 10px;  /* Aún menos padding para pantallas muy pequeñas */
        max-width: 95%; /* Asegura que el contenedor ocupe casi todo el ancho */
    }

    .login-container h2 {
        font-size: 1.25rem;  /* Asegura que el título no sea demasiado grande */
    }

    .login-container input, .login-container button {
        font-size: 12px;  /* Ajustar tamaño de texto para dispositivos más pequeños */
    }

    .password-container .eye-icon {
        font-size: 16px;  /* Reducir tamaño del ícono en dispositivos pequeños */
    }
}

    </style>
</head>
<body>
    <div class="login-container">
        <img src="/project/app/assets/img/favicon.svg" alt="Logo Empresa">
        <h2>Bienvenido</h2>
        <form action="app/controllers/procesar_login.php" method="POST">
            <label for="email">Correo Electrónico:</label>
            <input type="email" name="email" id="email" required>
            
            <label for="password">Contraseña:</label>
            <div class="password-container">
                <input type="password" name="password" id="password" required>
                <span class="eye-icon" onclick="togglePassword()">
                    <i class="fas fa-eye"></i>  <!-- Ojo abierto de Font Awesome -->
                </span>
            </div>
            <button name="ingresar" type="submit">Iniciar Sesión</button>
        </form>
        <a href="recuperar_contraseña.php">¿Olvidaste tu contraseña?</a>
    </div>

    <script>
        function togglePassword() {
            var passwordField = document.getElementById("password");
            var eyeIcon = document.querySelector(".eye-icon i");

            if (passwordField.type === "password") {
                passwordField.type = "text";
                eyeIcon.classList.remove("fa-eye");
                eyeIcon.classList.add("fa-eye-slash"); // Ojo tachado cuando la contraseña está visible
            } else {
                passwordField.type = "password";
                eyeIcon.classList.remove("fa-eye-slash");
                eyeIcon.classList.add("fa-eye"); // Ojo abierto cuando la contraseña está oculta
            }
        }
    </script>
</body>
</html>
 