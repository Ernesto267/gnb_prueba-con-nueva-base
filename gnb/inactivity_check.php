<?php
$nombre_usuario = $_SESSION['usuario_nombre'] ?? $_SESSION['usuario'] ?? 'Funcionario';
?>

<!-- Modal de Advertencia de Inactividad Profesional y Estilizado -->
<div class="modal fade" id="modalInactividad" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0 rounded-4" style="background: linear-gradient(145deg, #1e293b, #0f172a); color: #f8fafc; border: 1px solid rgba(59, 130, 246, 0.3) !important;">
            <div class="modal-body text-center p-4 p-md-5">
                
                <!-- Logo Institucional del Laboratorio -->
                <div class="mb-4 position-relative d-inline-block">
                    <div class="position-absolute top-50 start-50 translate-middle rounded-circle bg-primary opacity-25" style="width: 85px; height: 85px; filter: blur(10px);"></div>
                    <div class="rounded-circle bg-dark d-flex align-items-center justify-content-center mx-auto position-relative shadow-sm border border-primary border-opacity-50 p-2" style="width: 75px; height: 75px;">
                        <img src="imagen/logo_gnb.png" alt="Logo Institucional" class="img-fluid rounded-circle" style="max-height: 55px; object-fit: contain;">
                    </div>
                </div>

                <!-- Título Personalizado -->
                <h3 class="fw-bold mb-3 text-white">Atención, <?php echo htmlspecialchars($nombre_usuario); ?></h3>

                <p class="mb-4" style="color: #cbd5e1; font-size: 0.95rem; line-height: 1.5;">
                    Su sesión está a punto de expirar por inactividad prolongada en el puesto de trabajo.
                </p>

                <!-- Caja de Cuenta Regresiva Estilizada -->
                <div class="p-3 rounded-3 mb-4 border border-warning border-opacity-25 d-flex align-items-center justify-content-center gap-2" style="background: rgba(245, 158, 11, 0.08);">
                    <i class="bi bi-clock-history text-warning fs-5"></i>
                    <span style="color: #e2e8f0; font-size: 0.95rem;">Cierre automático en:</span>
                    <span id="contadorRegresivo" class="fw-bold text-warning fs-4 font-monospace">10</span>
                    <span style="color: #e2e8f0; font-size: 0.95rem;">seg</span>
                </div>

                <!-- Botón de Acción Principal Moderno -->
                <div class="d-grid">
                    <button type="button" class="btn btn-primary btn-lg fw-bold py-3 rounded-pill shadow-lg d-flex align-items-center justify-content-center gap-2 border-0" style="background: linear-gradient(135deg, #3b82f6, #1d4ed8); transition: all 0.3s ease;" onclick="continuarSesion()">
                        <i class="bi bi-check-circle-fill fs-5"></i>
                        <span>Continuar en el Sistema</span>
                    </button>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Lógica JavaScript del Temporizador -->
<script>
    let inactivityTimer;
    let countdownTimer;
    let segundosRestantes = 10;
    let modalInactividadObj = null;
    let modalVisible = false;

    function getMinutosLimite() {
        // 1. Prioridad: Preferencia guardada por el administrador
        const savedTime = localStorage.getItem('gnb_inactivity_time');
        if (savedTime !== null) {
            return parseFloat(savedTime);
        }
        
        // 2. Si se encuentra en la pantalla de configuración, leer el valor actual
        const selectEl = document.getElementById('tiempoInactividad');
        if (selectEl) {
            return parseFloat(selectEl.value);
        }
        
        // 3. Valor de producción predeterminado (15 minutos)
        return 15; 
    }

    function iniciarTemporizadorInactividad() {
        if (modalVisible) return; // Ignorar movimiento si el modal ya está visible

        clearTimeout(inactivityTimer);
        clearInterval(countdownTimer);

        let minutos = getMinutosLimite();
        if (minutos > 0) {
            let milisegundosTotales = (minutos * 60 * 1000) - (segundosRestantes * 1000);
            if (milisegundosTotales < 0) milisegundosTotales = 1000;

            inactivityTimer = setTimeout(mostrarAdvertencia, milisegundosTotales);
        }
    }

    function mostrarAdvertencia() {
        modalVisible = true;
        segundosRestantes = 10;
        document.getElementById('contadorRegresivo').textContent = segundosRestantes;

        const modalElement = document.getElementById('modalInactividad');
        if (!modalInactividadObj) {
            modalInactividadObj = new bootstrap.Modal(modalElement);
        }
        modalInactividadObj.show();

        countdownTimer = setInterval(() => {
            segundosRestantes--;
            document.getElementById('contadorRegresivo').textContent = segundosRestantes;

            if (segundosRestantes <= 0) {
                clearInterval(countdownTimer);
                window.location.href = 'logout.php';
            }
        }, 1000);
    }

    function continuarSesion() {
        modalVisible = false;
        if (modalInactividadObj) {
            modalInactividadObj.hide();
        }
        clearInterval(countdownTimer);
        iniciarTemporizadorInactividad();
    }

    // Escuchadores de eventos para detectar actividad operativa
    window.onload = iniciarTemporizadorInactividad;
    window.onmousemove = iniciarTemporizadorInactividad;
    window.onmousedown = iniciarTemporizadorInactividad;
    window.onclick = iniciarTemporizadorInactividad;
    window.onkeypress = iniciarTemporizadorInactividad;
    window.addEventListener('scroll', iniciarTemporizadorInactividad, true);
</script>