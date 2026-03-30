/* home.js — Carrossel, Countdown, Álbum, Social (Fórum de Mulheres no Turismo) */

// ── Carrossel (Swiper) ──────────────────────────────────────────────
var carrosselEl = document.querySelector("#carrossel-destaques"),
    slides = carrosselEl ? carrosselEl.querySelectorAll(".swiper-slide") : [];

if (slides.length > 0) {
    var swiper = new Swiper("#carrossel-destaques", {
        slidesPerView: 1,
        watchOverflow: true,
        loop: slides.length > 1,
        autoplay: { delay: 7000, disableOnInteraction: false },
        navigation: {
            nextEl: "#carrossel-destaques .swiper-button-next",
            prevEl: "#carrossel-destaques .swiper-button-prev"
        },
        pagination: { el: "#carrossel-destaques .swiper-pagination", clickable: true },
        on: {
            slideChangeTransitionEnd: function() {
                var active = this.slides[this.activeIndex];
                if (active && active.querySelector("iframe")) {
                    this.autoplay.stop();
                } else {
                    this.autoplay.start();
                }
            }
        }
    });
    slides.forEach(function(e) {
        var a = e.getAttribute("data-href");
        if (a) {
            e.style.cursor = "pointer";
            e.addEventListener("click", function(ev) {
                if (!ev.target.closest(".swiper-button-next, .swiper-button-prev, iframe")) {
                    window.location.href = a;
                }
            });
        }
    });
} else if (carrosselEl) {
    carrosselEl.style.display = "none";
}

// ── Ocultar ícones sociais sem link ─────────────────────────────────
document.querySelectorAll(".team-social li[data-social]").forEach(function(li) {
    var link = li.querySelector("a");
    if (!link || !link.getAttribute("href") || link.getAttribute("href") === "#") {
        li.style.display = "none";
    }
});

// ── Countdown ───────────────────────────────────────────────────────
// Usa global `dataDoEvento` injetado pelo PHP; fallback hardcoded.
if (typeof dataDoEvento === "undefined") {
    var dataDoEvento = new Date("June 3, 2026 09:00:00").getTime();
}

function renderContador(m, d, h, s) {
    return '<div class="countdown-item"><span class="countdown-number">' + m +
        '</span><span class="countdown-label">Meses</span></div>' +
        '<div class="countdown-item"><span class="countdown-number">' + d +
        '</span><span class="countdown-label">Dias</span></div>' +
        '<div class="countdown-item"><span class="countdown-number">' + h +
        '</span><span class="countdown-label">Horas</span></div>' +
        '<div class="countdown-item"><span class="countdown-number">' + s +
        '</span><span class="countdown-label">Seg</span></div>';
}

var timerInterval = setInterval(function() {
    var now = (new Date()).getTime();
    var diff = dataDoEvento - now;
    var el = document.getElementById("contador");
    if (!el) return;
    if (diff < 0) { clearInterval(timerInterval); el.style.display = "none"; return; }
    var meses = Math.floor(diff / 2630016000);
    var dias  = Math.floor((diff % 2630016000) / 86400000);
    var horas = Math.floor((diff % 86400000) / 3600000);
    var segs  = Math.floor((diff % 60000) / 1000);
    el.innerHTML = renderContador(meses, dias, horas, segs);
}, 1000);

// ── Álbum de Fotos: rotação em cascata ──────────────────────────────
// Usa global `albumFotos` injetado pelo PHP; fallback hardcoded.
if (typeof albumFotos === "undefined") {
    var albumFotos = [
        "assets/img/galeria/gallery1.png", "assets/img/galeria/gallery2.png",
        "assets/img/galeria/gallery3.png", "assets/img/galeria/gallery4.png",
        "assets/img/galeria/gallery5.png", "assets/img/galeria/gallery6.png"
    ];
}

(function initAlbumSequencial() {
    var slots = document.querySelectorAll(".gallery-slot");
    if (!slots.length || albumFotos.length < 2) return;
    var slotAtual = 0, indices = [];
    for (var i = 0; i < slots.length; i++) indices.push(i % albumFotos.length);
    setInterval(function() {
        indices[slotAtual] = (indices[slotAtual] + 1) % albumFotos.length;
        var novaImg = albumFotos[indices[slotAtual]];
        slots[slotAtual].style.backgroundImage = "url(" + novaImg + ")";
        var link = slots[slotAtual].closest(".album-popup");
        if (link) link.setAttribute("href", novaImg);
        slotAtual = (slotAtual + 1) % slots.length;
    }, 3000);
})();

// ── Magnific Popup para álbum ───────────────────────────────────────
$(document).ready(function() {
    $(".album-popup").magnificPopup({
        type: "image",
        gallery: { enabled: true, tCounter: "%curr% de %total%" },
        image: { titleSrc: function() { return "Álbum de Fotos — Fórum de Mulheres no Turismo"; } }
    });
});
