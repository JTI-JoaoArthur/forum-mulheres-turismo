/* home.js — Carrossel, Countdown, Album, Social (Forum de Mulheres no Turismo) */

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
        pagination: { el: "#carrossel-destaques .swiper-pagination", clickable: true }
    });

    // Hover pausa o autoplay
    carrosselEl.addEventListener("mouseenter", function() {
        swiper.autoplay.stop();
    });
    carrosselEl.addEventListener("mouseleave", function() {
        if (!carrosselEl.querySelector("iframe.slide-video-iframe") &&
            !carrosselEl.querySelector("video.slide-video-playing")) {
            swiper.autoplay.start();
        }
    });

    // Click em slides com link
    slides.forEach(function(e) {
        var a = e.getAttribute("data-href");
        if (a) {
            e.style.cursor = "pointer";
            e.addEventListener("click", function(ev) {
                if (!ev.target.closest(".swiper-button-next, .swiper-button-prev, .video-play-btn")) {
                    window.location.href = a;
                }
            });
        }
    });

    // Play de video no carrossel (arquivo local ou embed externo)
    carrosselEl.addEventListener("click", function(ev) {
        var btn = ev.target.closest(".video-play-btn");
        if (!btn) return;
        ev.preventDefault();
        var slide = btn.closest(".swiper-slide");
        var wrap = slide ? slide.querySelector(".slide-video-wrap") : null;
        if (!wrap) return;

        var videoFile = slide.getAttribute("data-video-file");
        var videoEmbed = slide.getAttribute("data-video");

        if (videoFile) {
            // Video local: usar <video> nativo
            var nativeVideo = wrap.querySelector("video.slide-video-native");
            if (nativeVideo) {
                // Ja tem o elemento video, so dar play
                btn.style.display = "none";
                var thumbImg = wrap.querySelector(".slide-thumb");
                if (thumbImg) thumbImg.style.display = "none";
                nativeVideo.style.display = "block";
                nativeVideo.classList.add("slide-video-playing");
                nativeVideo.controls = true;
                nativeVideo.play();
            } else {
                // Criar video element
                var ext = videoFile.split(".").pop();
                var video = document.createElement("video");
                video.className = "slide-video-native slide-video-playing";
                video.controls = true;
                video.autoplay = true;
                video.style.cssText = "width:100%;height:100%;object-fit:cover;display:block;";
                var source = document.createElement("source");
                source.src = videoFile;
                source.type = "video/" + ext;
                video.appendChild(source);
                wrap.innerHTML = "";
                wrap.appendChild(video);
            }
            swiper.autoplay.stop();
        } else if (videoEmbed) {
            // Video externo: iframe
            var separator = videoEmbed.indexOf("?") !== -1 ? "&" : "?";
            var iframe = document.createElement("iframe");
            iframe.className = "slide-video-iframe";
            iframe.src = videoEmbed + separator + "autoplay=1";
            iframe.setAttribute("frameborder", "0");
            iframe.setAttribute("allow", "autoplay; encrypted-media; picture-in-picture");
            iframe.setAttribute("allowfullscreen", "");
            wrap.innerHTML = "";
            wrap.appendChild(iframe);
            swiper.autoplay.stop();
        }
    });

    // Mostrar countdown apenas no primeiro slide
    var countdownWrap = carrosselEl.querySelector(".carousel-countdown");
    function toggleCountdown() {
        if (countdownWrap) {
            countdownWrap.style.opacity = swiper.realIndex === 0 ? "1" : "0";
        }
    }
    toggleCountdown();

    // Ao mudar de slide, parar videos e restaurar thumbnails
    swiper.on("slideChange", function() {
        toggleCountdown();
        // Parar iframes
        carrosselEl.querySelectorAll("iframe.slide-video-iframe").forEach(function(iframe) {
            var wrap = iframe.closest(".slide-video-wrap");
            var slide = iframe.closest(".swiper-slide");
            var videoUrl = slide ? slide.getAttribute("data-video") : "";
            var thumbSrc = "";
            if (videoUrl) {
                var ytMatch = videoUrl.match(/youtube\.com\/embed\/([\w-]+)/);
                if (ytMatch) thumbSrc = "https://img.youtube.com/vi/" + ytMatch[1] + "/maxresdefault.jpg";
            }
            iframe.remove();
            if (wrap) {
                if (thumbSrc) {
                    var img = document.createElement("img");
                    img.src = thumbSrc;
                    img.alt = "Video";
                    img.className = "slide-thumb";
                    wrap.appendChild(img);
                }
                var playBtn = document.createElement("button");
                playBtn.className = "video-play-btn";
                playBtn.type = "button";
                playBtn.setAttribute("aria-label", "Reproduzir video");
                playBtn.innerHTML = '<i class="fas fa-play"></i>';
                wrap.appendChild(playBtn);
            }
        });

        // Parar videos nativos
        carrosselEl.querySelectorAll("video.slide-video-playing").forEach(function(video) {
            video.pause();
            video.currentTime = 0;
            video.classList.remove("slide-video-playing");
            video.controls = false;
            video.style.display = "none";
            var wrap = video.closest(".slide-video-wrap");
            if (wrap) {
                var thumbImg = wrap.querySelector(".slide-thumb");
                if (thumbImg) thumbImg.style.display = "";
                var playBtn = wrap.querySelector(".video-play-btn");
                if (playBtn) {
                    playBtn.style.display = "";
                } else {
                    playBtn = document.createElement("button");
                    playBtn.className = "video-play-btn";
                    playBtn.type = "button";
                    playBtn.setAttribute("aria-label", "Reproduzir video");
                    playBtn.innerHTML = '<i class="fas fa-play"></i>';
                    wrap.appendChild(playBtn);
                }
            }
        });

        if (!carrosselEl.querySelector("iframe.slide-video-iframe") &&
            !carrosselEl.querySelector("video.slide-video-playing")) {
            swiper.autoplay.start();
        }
    });
} else if (carrosselEl) {
    carrosselEl.style.display = "none";
}

// ── Ocultar icones sociais sem link ─────────────────────────────────
document.querySelectorAll(".team-social li[data-social]").forEach(function(li) {
    var link = li.querySelector("a");
    if (!link || !link.getAttribute("href") || link.getAttribute("href") === "#") {
        li.style.display = "none";
    }
});

// ── Countdown ───────────────────────────────────────────────────────
if (typeof dataDoEvento === "undefined") {
    var dataDoEvento = new Date("2026-06-03T09:00:00").getTime();
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
    var el = document.getElementById("contador") || document.getElementById("contador-fallback");
    if (!el) return;
    if (diff < 0) { clearInterval(timerInterval); el.style.display = "none"; return; }
    var meses = Math.floor(diff / 2630016000);
    var dias  = Math.floor((diff % 2630016000) / 86400000);
    var horas = Math.floor((diff % 86400000) / 3600000);
    var segs  = Math.floor((diff % 60000) / 1000);
    el.innerHTML = renderContador(meses, dias, horas, segs);
}, 1000);

// ── Album de Fotos: rotacao em cascata ──────────────────────────────
if (typeof albumFotos === "undefined") {
    var albumFotos = [
        "assets/img/galeria/gallery1.svg", "assets/img/galeria/gallery2.svg",
        "assets/img/galeria/gallery3.svg", "assets/img/galeria/gallery4.svg",
        "assets/img/galeria/gallery5.svg", "assets/img/galeria/gallery6.svg"
    ];
}

(function initAlbumSequencial() {
    var allSlots = document.querySelectorAll(".gallery-slot");
    if (!allSlots.length || albumFotos.length < 2) return;
    // Filtrar apenas slots visíveis (não display:none)
    var slots = [];
    for (var i = 0; i < allSlots.length; i++) {
        if (allSlots[i].offsetParent !== null) slots.push(allSlots[i]);
    }
    if (!slots.length) return;
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

// ── Magnific Popup para album ───────────────────────────────────────
$(document).ready(function() {
    $(".album-popup").magnificPopup({
        type: "image",
        gallery: { enabled: true, tCounter: "%curr% de %total%" },
        image: { titleSrc: function() { return "Album de Fotos — Forum de Mulheres no Turismo"; } }
    });
});

// ── Viewer Fullscreen do Album (Mosaico) ──────────────────────────
(function() {
    var btnOpen = document.getElementById("album-fullscreen-btn");
    var viewer = document.getElementById("album-viewer");
    if (!btnOpen || !viewer) return;

    var btnClose = document.getElementById("album-viewer-close");

    // ── Crossfade dinâmico: troca fotos aleatórias a cada 4s ──────
    var allPhotos = window.albumViewerPhotos || (typeof albumFotos !== "undefined" ? albumFotos : []);
    var swapInterval = null;
    var lastSwapped = -1;

    function startSwapping() {
        if (swapInterval || allPhotos.length < 2) return;
        var mTiles = viewer.querySelectorAll(".album-viewer__tile");

        swapInterval = setInterval(function() {
            if (viewer.hidden) { clearInterval(swapInterval); swapInterval = null; return; }

            var idx;
            do { idx = Math.floor(Math.random() * mTiles.length); } while (idx === lastSwapped && mTiles.length > 1);
            lastSwapped = idx;

            var tile = mTiles[idx];
            var mainImg = tile.querySelector("img:first-child");
            var currentSrc = mainImg.src;
            var newSrc;
            do {
                newSrc = allPhotos[Math.floor(Math.random() * allPhotos.length)];
            } while (allPhotos.length > 1 && currentSrc.indexOf(newSrc) !== -1);

            if (newSrc.indexOf("://") === -1 && newSrc.indexOf("/") !== 0) {
                newSrc = window.location.origin + "/" + newSrc;
            }

            var nextImg = tile.querySelector(".tile-next");
            if (!nextImg) {
                nextImg = document.createElement("img");
                nextImg.className = "tile-next";
                nextImg.draggable = false;
                tile.appendChild(nextImg);
            }

            nextImg.onload = function() {
                nextImg.classList.add("tile-active");
                setTimeout(function() {
                    mainImg.src = newSrc;
                    nextImg.classList.remove("tile-active");
                    nextImg.src = "";
                }, 1300);
            };
            nextImg.src = newSrc;
        }, 4000);
    }

    function openViewer() {
        // Reiniciar animações dos tiles
        var tiles = viewer.querySelectorAll(".album-viewer__tile");
        tiles.forEach(function(t) { t.style.animation = "none"; });
        void viewer.offsetHeight;
        tiles.forEach(function(t) { t.style.animation = ""; });

        viewer.hidden = false;
        viewer.scrollTop = 0;
        resetIdle();
        startSwapping();
        // Fullscreen API nativo
        var rfs = viewer.requestFullscreen || viewer.webkitRequestFullscreen || viewer.msRequestFullscreen;
        if (rfs) rfs.call(viewer);
    }

    function closeViewer() {
        viewer.hidden = true;
        clearTimeout(idleTimer);
        clearInterval(swapInterval);
        swapInterval = null;
        viewer.classList.remove("album-viewer--idle");
        // Fechar Magnific Popup se estiver aberto
        if ($.magnificPopup && $.magnificPopup.instance && $.magnificPopup.instance.isOpen) {
            $.magnificPopup.close();
        }
        if (document.fullscreenElement || document.webkitFullscreenElement) {
            var efs = document.exitFullscreen || document.webkitExitFullscreen || document.msExitFullscreen;
            if (efs) efs.call(document);
        }
    }

    // Idle timer: esconder controles e cursor após 2.5s sem movimento
    var idleTimer = null;
    function resetIdle() {
        viewer.classList.remove("album-viewer--idle");
        clearTimeout(idleTimer);
        idleTimer = setTimeout(function() {
            if (!viewer.hidden) viewer.classList.add("album-viewer--idle");
        }, 2500);
    }
    viewer.addEventListener("mousemove", resetIdle);
    viewer.addEventListener("scroll", resetIdle, { passive: true });
    viewer.addEventListener("touchstart", resetIdle, { passive: true });

    btnOpen.addEventListener("click", openViewer);
    if (btnClose) btnClose.addEventListener("click", closeViewer);

    // Esc fecha
    document.addEventListener("keydown", function(e) {
        if (viewer.hidden) return;
        if (e.key === "Escape") closeViewer();
    });

    // Fechar ao sair do fullscreen
    document.addEventListener("fullscreenchange", onFsChange);
    document.addEventListener("webkitfullscreenchange", onFsChange);
    function onFsChange() {
        if (!document.fullscreenElement && !document.webkitFullscreenElement && !viewer.hidden) {
            viewer.hidden = true;
        }
    }

    // Mosaico já é fullscreen — sem popup adicional

})();
