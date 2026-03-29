<?php
/**
 * Coleta imagens para o álbum de fotos.
 * Fontes: 1) featured_image de notícias com is_in_gallery=1
 *         2) uploads manuais na tabela gallery
 * Retorna array de caminhos de imagem.
 */
function getAlbumPhotos(): array {
    $photos = [];

    if (!dbReady()) {
        // Fallback: imagens padrão
        for ($i = 1; $i <= 6; $i++) {
            $photos[] = "assets/img/galeria/gallery{$i}.png";
        }
        return $photos;
    }

    // Fotos de notícias marcadas para o álbum
    $newsPhotos = Database::fetchAll(
        "SELECT featured_image FROM news WHERE is_in_gallery = 1 AND is_visible = 1 AND featured_image IS NOT NULL AND featured_image != '' ORDER BY published_at DESC"
    );
    foreach ($newsPhotos as $n) {
        $photos[] = $n['featured_image'];
    }

    // Fotos manuais da galeria
    $galleryPhotos = Database::fetchAll("SELECT image FROM gallery ORDER BY display_order ASC");
    foreach ($galleryPhotos as $g) {
        $photos[] = $g['image'];
    }

    // Se não houver fotos no CMS, usar padrão
    if (empty($photos)) {
        for ($i = 1; $i <= 6; $i++) {
            $photos[] = "assets/img/galeria/gallery{$i}.png";
        }
    }

    return $photos;
}
