// ============================================================
// META CAPITAL — JavaScript Global
// BASE_URL es inyectada por PHP en el header (includes/header.php)
// ============================================================

// ---- PAGE LOADER (barra top desktop / overlay mobile) ----
var PageLoader = (function() {
    var bar      = null;
    var overlay  = null;
    var timer    = null;
    var progress = 0;

    function isMobile() {
        return window.innerWidth <= 900;
    }

    function getBar() {
        if (!bar) {
            bar = document.createElement('div');
            bar.id = 'page-loader-bar';
            document.body.appendChild(bar);
        }
        return bar;
    }

    function getOverlay() {
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'page-loader-overlay';

            // Detectar si hay logo cargado (buscar img en sidebar o login)
            var logoEl  = document.querySelector('.sidebar-logo-img, .login-logo-img');
            var logoHtml = '';
            if (logoEl) {
                logoHtml = '<img src="' + logoEl.src + '" alt="Logo">';
            } else {
                logoHtml =
                    '<div class="page-loader-logo-text">META</div>' +
                    '<div class="page-loader-logo-sub">Capital</div>';
            }

            overlay.innerHTML =
                '<div class="page-loader-logo">' + logoHtml + '</div>' +
                '<div class="page-loader-bar-mobile">' +
                  '<div class="page-loader-bar-mobile-fill"></div>' +
                '</div>';

            document.body.appendChild(overlay);
        }
        return overlay;
    }

    function start() {
        if (isMobile()) {
            // Mobile: overlay con spinner
            getOverlay().classList.add('active');
        } else {
            // Desktop: barra top
            var b = getBar();
            clearInterval(timer);
            progress = 0;
            b.style.transition = 'none';
            b.style.width = '0%';
            b.classList.add('active');
            setTimeout(function() {
                b.style.transition = 'width 0.3s ease';
                step();
            }, 10);
        }
    }

    function step() {
        timer = setInterval(function() {
            if (progress < 70) {
                progress += Math.random() * 15;
            } else if (progress < 90) {
                progress += Math.random() * 3;
            }
            if (progress > 90) progress = 90;
            getBar().style.width = progress + '%';
        }, 200);
    }

    function done() {
        // Siempre cerrar ambos por si acaso cambia el tamaño
        clearInterval(timer);
        var b = getBar();
        b.style.transition = 'width 0.15s ease';
        b.style.width = '100%';
        setTimeout(function() {
            b.classList.remove('active');
            b.style.width = '0%';
        }, 250);

        var ov = getOverlay();
        ov.classList.remove('active');
    }

    return { start: start, done: done };
})();

// Activar en clicks de navegación (links internos, no anchors ni externos)
document.addEventListener('click', function(e) {
    var a = e.target.closest('a[href]');
    if (!a) return;
    var href = a.getAttribute('href');
    if (!href) return;
    // Ignorar: anchors, javascript:, externos, target=_blank, nueva pestaña
    if (href.startsWith('#') || href.startsWith('javascript') ||
        href.startsWith('http') || href.startsWith('mailto') ||
        a.target === '_blank' || e.ctrlKey || e.metaKey || e.shiftKey) return;
    PageLoader.start();
});

// Activar en submit de formularios con navegación
document.addEventListener('submit', function(e) {
    var form = e.target;
    if (form.method && form.method.toLowerCase() === 'get' && form.action) {
        PageLoader.start();
    }
});

// Detener cuando la página termina de cargar
window.addEventListener('pageshow', function() {
    PageLoader.done();
});

// Por si acaso: detener al popstate (botón atrás)
window.addEventListener('popstate', function() {
    PageLoader.start();
});

// ---- FORMATO DE MONEDA ----
function fmt(n) {
    if (n === null || n === undefined || isNaN(n)) return '$0';
    return '$' + Math.round(n).toLocaleString('es-CO');
}

// ---- TOAST ----
function toast(msg, type) {
    type = type || 'success';
    var icons = { success: '✓', error: '✕', warning: '⚠' };
    var container = document.getElementById('toast-container');
    if (!container) return;
    var el = document.createElement('div');
    el.className = 'toast toast-' + type;
    el.innerHTML = '<span class="toast-icon">' + (icons[type] || '●') + '</span><span>' + msg + '</span>';
    container.appendChild(el);
    requestAnimationFrame(function() {
        el.classList.add('show');
        setTimeout(function() {
            el.classList.remove('show');
            setTimeout(function() { el.remove(); }, 300);
        }, 3500);
    });
}

// ---- MODAL ----
function openModal(id) {
    var el = document.getElementById(id);
    if (el) { el.classList.add('open'); document.body.style.overflow = 'hidden'; }
}
function closeModal(id) {
    var el = document.getElementById(id);
    if (el) { el.classList.remove('open'); document.body.style.overflow = ''; }
}
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.classList.remove('open');
        document.body.style.overflow = '';
    }
});

// ---- DROPDOWN ----
document.addEventListener('click', function(e) {
    var trigger = e.target.closest('[data-dropdown]');
    var dropdowns = document.querySelectorAll('.user-dropdown');
    if (trigger) {
        var target = document.getElementById(trigger.dataset.dropdown);
        dropdowns.forEach(function(d) { if (d !== target) d.classList.remove('open'); });
        if (target) target.classList.toggle('open');
    } else if (!e.target.closest('.user-dropdown')) {
        dropdowns.forEach(function(d) { d.classList.remove('open'); });
    }
});

// ---- TOGGLE CARD ----
function toggleCard(id) {
    var el = document.getElementById(id);
    if (el) el.classList.toggle('open');
}

// ---- SIDEBAR MOBILE ----
function toggleSidebar() {
    var sb  = document.querySelector('.sidebar');
    var ov  = document.getElementById('sidebar-overlay');
    if (!sb) return;
    var isOpen = sb.classList.toggle('mobile-open');
    if (ov) ov.classList.toggle('active', isOpen);
    document.body.style.overflow = isOpen ? 'hidden' : '';
}

function closeSidebar() {
    var sb = document.querySelector('.sidebar');
    var ov = document.getElementById('sidebar-overlay');
    if (sb) sb.classList.remove('mobile-open');
    if (ov) ov.classList.remove('active');
    document.body.style.overflow = '';
}

// ---- FETCH HELPERS ----
async function apiPost(url, data) {
    var fullUrl = BASE_URL + url;
    try {
        var res = await fetch(fullUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(data)
        });
        var text = await res.text();
        try {
            return JSON.parse(text);
        } catch(e) {
            console.error('API no devolvio JSON (' + fullUrl + '):', text.substring(0, 300));
            return { ok: false, msg: 'Error del servidor. Ver consola.' };
        }
    } catch (e) {
        console.error('Fetch error:', e);
        return { ok: false, msg: 'Error de red: ' + e.message };
    }
}

async function apiGet(url) {
    var fullUrl = BASE_URL + url;
    try {
        var res = await fetch(fullUrl, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        var text = await res.text();
        try {
            return JSON.parse(text);
        } catch(e) {
            console.error('API no devolvio JSON:', text.substring(0, 300));
            return { ok: false, msg: 'Error del servidor' };
        }
    } catch (e) {
        return { ok: false, msg: 'Error de red' };
    }
}

// ---- CAMBIAR COBRO ----
async function cambiarCobro(id) {
    var res = await apiPost('/api/set_cobro.php', { cobro_id: id });
    if (res.ok) { window.location.reload(); }
    else { toast(res.msg || 'Error al cambiar cobro', 'error'); }
}

// ---- CALCULO PRESTAMO ----
function calcularPrestamo(opts) {
    var monto        = parseFloat(opts.monto)        || 0;
    var interesValor = parseFloat(opts.interesValor) || 0;
    var numCuotas    = parseInt(opts.numCuotas)      || 1;
    var tipoInteres  = opts.tipoInteres || 'porcentaje';
    var interesCalc  = tipoInteres === 'porcentaje' ? monto * interesValor / 100 : interesValor;
    var total        = monto + interesCalc;
    return { interesCalc: interesCalc, total: total, valorCuota: total / numCuotas };
}

// ---- FECHA FIN PRESTAMO ----
function calcFechaFin(fechaInicio, frecuencia, numCuotas) {
    if (!fechaInicio || !numCuotas) return '';
    var diasMap = { diario: 1, semanal: 7, quincenal: 15, mensual: 30 };
    var dias = diasMap[frecuencia] || 30;
    var d = new Date(fechaInicio + 'T00:00:00');
    d.setDate(d.getDate() + dias * parseInt(numCuotas));
    return d.toISOString().split('T')[0];
}

function formatFecha(str) {
    if (!str) return '—';
    var d = new Date(str + 'T00:00:00');
    return d.toLocaleDateString('es-CO', { day: '2-digit', month: 'short', year: 'numeric' });
}

// ---- NAV ACTIVO ----
document.addEventListener('DOMContentLoaded', function() {
    var path = window.location.pathname;
    document.querySelectorAll('.nav-item[href]').forEach(function(a) {
        var href = a.getAttribute('href');
        if (href && path.indexOf(href.split('?')[0]) !== -1) {
            a.classList.add('active');
        }
    });

    // Cerrar sidebar al hacer click en un nav-item (mobile)
    document.querySelectorAll('.nav-item').forEach(function(a) {
        a.addEventListener('click', function() {
            if (window.innerWidth <= 900) closeSidebar();
        });
    });
});