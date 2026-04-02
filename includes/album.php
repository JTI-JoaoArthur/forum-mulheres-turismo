<?php
/**
 * Coleta imagens para o álbum de fotos.
 * Fontes: 1) featured_image de notícias com is_in_gallery=1
 *         2) uploads manuais na tabela gallery
 * Retorna array de caminhos de imagem.
 */
function getAlbumPhotos(): array {
    $photos = [];
    $realCount = 0; // quantas fotos são do CMS (não placeholder)

    if (!dbReady()) {
        // Fallback: imagens padrão
        for ($i = 1; $i <= 6; $i++) {
            $photos[] = "assets/img/galeria/gallery{$i}.svg";
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

    // Imagens do carrossel marcadas para o álbum
    $carouselPhotos = Database::fetchAll(
        "SELECT image FROM carousel WHERE is_in_gallery = 1 AND is_visible = 1 AND image IS NOT NULL AND image != '' ORDER BY display_order ASC"
    );
    foreach ($carouselPhotos as $c) {
        $photos[] = $c['image'];
    }

    // Fotos manuais da galeria
    $galleryPhotos = Database::fetchAll("SELECT image FROM gallery ORDER BY display_order ASC");
    foreach ($galleryPhotos as $g) {
        $photos[] = $g['image'];
    }

    $realCount = count($photos);

    // Completar com imagens padrão se houver poucas fotos (mínimo 6 para o grid + cycling)
    if (count($photos) < 6) {
        $defaults = [];
        for ($i = 1; $i <= 6; $i++) {
            $defaults[] = "assets/img/galeria/gallery{$i}.svg";
        }
        if (empty($photos)) {
            $photos = $defaults;
        } else {
            $di = 0;
            while (count($photos) < 6 && $di < count($defaults)) {
                if (!in_array($defaults[$di], $photos)) {
                    $photos[] = $defaults[$di];
                }
                $di++;
            }
        }
    }

    return ['photos' => $photos, 'realCount' => $realCount];
}
