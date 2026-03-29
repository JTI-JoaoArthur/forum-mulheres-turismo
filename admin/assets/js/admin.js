/**
 * Painel Administrativo — JavaScript
 * Vanilla JS + jQuery (já disponível via Bootstrap)
 */

(function() {
    'use strict';

    // Preview de imagem antes do upload
    document.querySelectorAll('input[type="file"][data-preview]').forEach(function(input) {
        input.addEventListener('change', function() {
            var previewId = this.getAttribute('data-preview');
            var preview = document.getElementById(previewId);
            if (!preview) return;

            if (this.files && this.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    });

    // Confirmação de exclusão
    document.querySelectorAll('[data-confirm]').forEach(function(el) {
        el.addEventListener('click', function(e) {
            var msg = this.getAttribute('data-confirm') || 'Tem certeza que deseja excluir?';
            if (!confirm(msg)) {
                e.preventDefault();
            }
        });
    });

    // Auto-hide alerts após 5 segundos
    document.querySelectorAll('.alert-dismissible').forEach(function(alert) {
        setTimeout(function() {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(function() { alert.remove(); }, 500);
        }, 5000);
    });
})();
