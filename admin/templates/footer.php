        </main>
    </div>
</div>
<script src="/assets/js/vendor/jquery-3.7.1.min.js"></script>
<script src="/assets/js/bootstrap.min.js"></script>
<script src="/admin/assets/js/admin.js"></script>
<script src="https://cdn.jsdelivr.net/npm/quill@2/dist/quill.js"></script>
<script>
(function() {
    var editorEl = document.getElementById('editor-container');
    if (!editorEl) return;

    var quill = new Quill('#editor-container', {
        theme: 'snow',
        modules: {
            toolbar: '#editor-toolbar'
        },
        placeholder: 'Escreva o conteúdo da notícia...'
    });

    // Sincronizar conteúdo com o textarea hidden ao submeter
    var form = editorEl.closest('form');
    var hiddenField = document.getElementById('body');
    if (form && hiddenField) {
        form.addEventListener('submit', function() {
            var html = quill.root.innerHTML;
            hiddenField.value = (html === '<p><br></p>') ? '' : html;
        });
    }
})();

// Galeria: acumular arquivos, preview, drag & drop, remoção, seleção de destaque
(function() {
    var zone = document.getElementById('gallery-drop-zone');
    var picker = document.getElementById('gallery-input-picker');
    var realInput = document.getElementById('gallery-input-real');
    var preview = document.getElementById('gallery-preview');
    var placeholder = document.getElementById('gallery-placeholder');
    if (!zone || !picker || !realInput || !preview) return;

    var files = [];
    var featuredIdx = -1;

    placeholder.addEventListener('click', function() { picker.click(); });

    picker.addEventListener('change', function() {
        var hadNone = files.length === 0;
        Array.from(this.files).forEach(function(f) {
            if (f.type.startsWith('image/')) files.push(f);
        });
        if (hadNone && files.length > 0 && featuredIdx < 0) featuredIdx = 0;
        this.value = '';
        syncAndRender();
    });

    zone.addEventListener('dragover', function(e) {
        e.preventDefault();
        placeholder.style.borderColor = '#64428c';
        placeholder.style.background = 'rgba(100,66,140,0.04)';
    });
    zone.addEventListener('dragleave', function() {
        placeholder.style.borderColor = '#ccc';
        placeholder.style.background = '';
    });
    zone.addEventListener('drop', function(e) {
        e.preventDefault();
        placeholder.style.borderColor = '#ccc';
        placeholder.style.background = '';
        var hadNone = files.length === 0;
        Array.from(e.dataTransfer.files).forEach(function(f) {
            if (f.type.startsWith('image/')) files.push(f);
        });
        if (hadNone && files.length > 0 && featuredIdx < 0) featuredIdx = 0;
        syncAndRender();
    });

    function syncAndRender() {
        // Sincronizar o input real com todos os arquivos acumulados
        try {
            var dt = new DataTransfer();
            files.forEach(function(f) { dt.items.add(f); });
            realInput.files = dt.files;
        } catch(e) {
            // Fallback: browsers antigos
        }
        render();
    }

    function render() {
        preview.innerHTML = '';
        placeholder.style.display = files.length ? 'none' : '';

        var sidebarFeatured = document.getElementById('featuredPreview');
        var hasSidebarFeatured = sidebarFeatured && sidebarFeatured.src && !sidebarFeatured.src.endsWith('/');
        var checkedType = (document.querySelector('input[name="featured_type"]:checked') || {}).value;
        if (checkedType === 'video_file' || checkedType === 'video_url') hasSidebarFeatured = true;

        var showStars = !hasSidebarFeatured && files.length > 0;

        files.forEach(function(file, idx) {
            var col = document.createElement('div');
            col.className = 'col-4 col-md-2 mb-2 text-center';

            var wrap = document.createElement('div');
            wrap.style.cssText = 'position:relative;';

            var img = document.createElement('img');
            img.className = 'img-thumbnail';
            img.style.cssText = 'height:80px;width:100%;object-fit:cover;' + (showStars && idx === featuredIdx ? 'border-color:#64428c;border-width:3px;' : '');
            var reader = new FileReader();
            reader.onload = function(e) { img.src = e.target.result; };
            reader.readAsDataURL(file);
            wrap.appendChild(img);

            if (showStars && idx === featuredIdx) {
                var badge = document.createElement('span');
                badge.style.cssText = 'position:absolute;top:4px;left:4px;background:#64428c;color:#fff;font-size:9px;padding:2px 6px;border-radius:3px;font-weight:600;';
                badge.textContent = 'DESTAQUE';
                wrap.appendChild(badge);
            }

            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'btn btn-danger btn-sm';
            btn.style.cssText = 'position:absolute;top:2px;right:2px;width:20px;height:20px;padding:0;font-size:10px;line-height:20px;border-radius:50%;opacity:0.85;';
            btn.innerHTML = '&times;';
            btn.title = 'Remover';
            (function(i) {
                btn.addEventListener('click', function() {
                    files.splice(i, 1);
                    if (featuredIdx === i) featuredIdx = files.length > 0 ? 0 : -1;
                    else if (featuredIdx > i) featuredIdx--;
                    syncAndRender();
                });
            })(idx);
            wrap.appendChild(btn);
            col.appendChild(wrap);

            if (showStars) {
                var label = document.createElement('label');
                label.style.cssText = 'font-size:10px;cursor:pointer;display:block;margin-top:2px;color:' + (idx === featuredIdx ? '#64428c;font-weight:700;' : '#999;');
                var radio = document.createElement('input');
                radio.type = 'radio';
                radio.name = '_gallery_featured_pick';
                radio.style.cssText = 'margin-right:3px;accent-color:#64428c;';
                radio.checked = idx === featuredIdx;
                (function(i) {
                    radio.addEventListener('change', function() { featuredIdx = i; render(); });
                })(idx);
                label.appendChild(radio);
                label.appendChild(document.createTextNode(idx === featuredIdx ? 'Destaque' : 'Usar'));
                col.appendChild(label);
            } else {
                var name = document.createElement('small');
                name.className = 'text-muted d-block text-truncate';
                name.style.fontSize = '10px';
                name.textContent = file.name;
                col.appendChild(name);
            }

            preview.appendChild(col);
        });

        if (files.length) {
            var addCol = document.createElement('div');
            addCol.className = 'col-4 col-md-2 mb-2 text-center';
            var addBtn = document.createElement('div');
            addBtn.style.cssText = 'height:80px;border:2px dashed #ccc;border-radius:6px;display:flex;align-items:center;justify-content:center;cursor:pointer;';
            addBtn.innerHTML = '<i class="fas fa-plus text-muted"></i>';
            addBtn.title = 'Adicionar mais';
            addBtn.addEventListener('click', function() { picker.click(); });
            addCol.appendChild(addBtn);
            var countLabel = document.createElement('small');
            countLabel.className = 'text-muted';
            countLabel.style.fontSize = '10px';
            countLabel.textContent = files.length + ' imagem' + (files.length > 1 ? 'ns' : '');
            addCol.appendChild(countLabel);
            preview.appendChild(addCol);
        }
    }

    // No submit: garantir sync + passar índice do destaque
    var form = zone.closest('form');
    if (form) {
        form.addEventListener('submit', function() {
            syncAndRender();
            var existing = form.querySelector('input[name="gallery_featured_index"]');
            if (existing) existing.remove();
            if (featuredIdx >= 0 && files.length > 0) {
                var hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'gallery_featured_index';
                hidden.value = featuredIdx;
                form.appendChild(hidden);
            }
        });
    }
})();

// Galeria admin (gallery.php): acumular, preview, drag & drop, remoção
(function() {
    var zone = document.getElementById('gal-drop-zone');
    var picker = document.getElementById('gal-picker');
    var realInput = document.getElementById('gal-real');
    var preview = document.getElementById('gal-preview');
    var placeholder = document.getElementById('gal-placeholder');
    var submitBtn = document.getElementById('gal-submit-btn');
    var countLabel = document.getElementById('gal-count-label');
    if (!zone || !picker || !realInput || !preview) return;

    var files = [];

    placeholder.addEventListener('click', function() { picker.click(); });

    picker.addEventListener('change', function() {
        Array.from(this.files).forEach(function(f) {
            if (f.type.startsWith('image/')) files.push(f);
        });
        this.value = '';
        syncAndRender();
    });

    zone.addEventListener('dragover', function(e) {
        e.preventDefault();
        placeholder.style.borderColor = '#64428c';
        placeholder.style.background = 'rgba(100,66,140,0.04)';
    });
    zone.addEventListener('dragleave', function() {
        placeholder.style.borderColor = '#ccc';
        placeholder.style.background = '';
    });
    zone.addEventListener('drop', function(e) {
        e.preventDefault();
        placeholder.style.borderColor = '#ccc';
        placeholder.style.background = '';
        Array.from(e.dataTransfer.files).forEach(function(f) {
            if (f.type.startsWith('image/')) files.push(f);
        });
        syncAndRender();
    });

    function syncAndRender() {
        try {
            var dt = new DataTransfer();
            files.forEach(function(f) { dt.items.add(f); });
            realInput.files = dt.files;
        } catch(e) {}
        render();
    }

    function render() {
        preview.innerHTML = '';
        placeholder.style.display = files.length ? 'none' : '';
        submitBtn.style.display = files.length ? '' : 'none';
        countLabel.textContent = files.length > 0 ? '(' + files.length + ' imagem' + (files.length > 1 ? 'ns' : '') + ')' : '';

        files.forEach(function(file, idx) {
            var col = document.createElement('div');
            col.className = 'col-4 col-md-2 mb-2 text-center';
            var wrap = document.createElement('div');
            wrap.style.cssText = 'position:relative;';
            var img = document.createElement('img');
            img.className = 'img-thumbnail';
            img.style.cssText = 'height:80px;width:100%;object-fit:cover;';
            var reader = new FileReader();
            reader.onload = function(e) { img.src = e.target.result; };
            reader.readAsDataURL(file);
            wrap.appendChild(img);

            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'btn btn-danger btn-sm';
            btn.style.cssText = 'position:absolute;top:2px;right:2px;width:20px;height:20px;padding:0;font-size:10px;line-height:20px;border-radius:50%;opacity:0.85;';
            btn.innerHTML = '&times;';
            btn.title = 'Remover';
            (function(i) {
                btn.addEventListener('click', function() {
                    files.splice(i, 1);
                    syncAndRender();
                });
            })(idx);
            wrap.appendChild(btn);
            col.appendChild(wrap);

            var name = document.createElement('small');
            name.className = 'text-muted d-block text-truncate';
            name.style.fontSize = '10px';
            name.textContent = file.name;
            col.appendChild(name);
            preview.appendChild(col);
        });

        if (files.length) {
            var addCol = document.createElement('div');
            addCol.className = 'col-4 col-md-2 mb-2 text-center';
            var addBtn = document.createElement('div');
            addBtn.style.cssText = 'height:80px;border:2px dashed #ccc;border-radius:6px;display:flex;align-items:center;justify-content:center;cursor:pointer;';
            addBtn.innerHTML = '<i class="fas fa-plus text-muted"></i>';
            addBtn.title = 'Adicionar mais';
            addBtn.addEventListener('click', function() { picker.click(); });
            addCol.appendChild(addBtn);
            preview.appendChild(addCol);
        }
    }

    var form = document.getElementById('gallery-upload-form');
    if (form) {
        form.addEventListener('submit', function() { syncAndRender(); });
    }
})();

// Máscara de hora (HH:MM) para inputs .time-input
document.querySelectorAll('.time-input').forEach(function(input) {
    // Só permitir dígitos e ":"
    input.addEventListener('input', function() {
        var v = this.value.replace(/[^\d]/g, '');
        if (v.length >= 3) {
            this.value = v.substring(0, 2) + ':' + v.substring(2, 4);
        } else if (v.length >= 1) {
            this.value = v;
        }
    });
    // Ao sair: completar formato
    input.addEventListener('blur', function() {
        var v = this.value.replace(/[^\d]/g, '');
        if (!v) return;
        if (v.length === 1) v = '0' + v;
        if (v.length === 2) v = v + '00';
        if (v.length === 3) v = '0' + v;
        var h = Math.min(23, parseInt(v.substring(0, 2)));
        var m = Math.min(59, parseInt(v.substring(2, 4)));
        this.value = String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0');
    });
});
</script>
</body>
</html>
