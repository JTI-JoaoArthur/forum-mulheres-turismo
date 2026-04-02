<?php
/**
 * Instagram Feed — busca posts via Graph API com cache em SQLite.
 */

/**
 * Retorna array de posts do Instagram (cache de 60 min).
 * Cada post: [media_url, permalink, media_type, thumbnail_url]
 */
function getInstagramFeed(int $limit = 6, int $cacheTtlMinutes = 60): array
{
    if (!function_exists('dbReady') || !dbReady()) return [];

    $token = Database::getSetting('instagram_access_token');
    if (!$token) return [];

    // Verificar cache
    $cache = Database::getSetting('instagram_cache');
    $cacheTime = Database::getSetting('instagram_cache_updated_at');
    if ($cache && $cacheTime) {
        $age = time() - strtotime($cacheTime);
        if ($age < $cacheTtlMinutes * 60) {
            $posts = json_decode($cache, true);
            if (is_array($posts)) return array_slice($posts, 0, $limit);
        }
    }

    // Buscar da API
    $url = 'https://graph.instagram.com/me/media'
         . '?fields=id,media_type,media_url,thumbnail_url,permalink,timestamp'
         . '&limit=' . $limit
         . '&access_token=' . urlencode($token);

    $ctx = stream_context_create([
        'http' => ['timeout' => 5, 'ignore_errors' => true],
        'ssl'  => ['verify_peer' => true],
    ]);

    $response = @file_get_contents($url, false, $ctx);

    // Fallback para cURL se file_get_contents falhar
    if ($response === false && function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_FOLLOWLOCATION => true,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
    }

    if ($response === false) {
        // API falhou — retornar cache antigo se existir
        if ($cache) {
            $posts = json_decode($cache, true);
            return is_array($posts) ? array_slice($posts, 0, $limit) : [];
        }
        return [];
    }

    $data = json_decode($response, true);
    if (!isset($data['data']) || !is_array($data['data'])) {
        // Resposta inválida (token expirado, etc.) — retornar cache antigo
        if ($cache) {
            $posts = json_decode($cache, true);
            return is_array($posts) ? array_slice($posts, 0, $limit) : [];
        }
        return [];
    }

    // Mapear apenas os campos necessários
    $posts = [];
    foreach ($data['data'] as $item) {
        $posts[] = [
            'media_url'     => $item['media_url'] ?? '',
            'permalink'     => $item['permalink'] ?? '',
            'media_type'    => $item['media_type'] ?? 'IMAGE',
            'thumbnail_url' => $item['thumbnail_url'] ?? '',
        ];
    }

    // Salvar cache
    Database::setSetting('instagram_cache', json_encode($posts));
    Database::setSetting('instagram_cache_updated_at', date('Y-m-d H:i:s'));

    return array_slice($posts, 0, $limit);
}

/**
 * Retorna dias restantes até expiração do token (60 dias).
 * null se não há token salvo.
 */
function instagramTokenDaysLeft(): ?int
{
    if (!function_exists('dbReady') || !dbReady()) return null;
    $savedAt = Database::getSetting('instagram_token_saved_at');
    if (!$savedAt) return null;
    $expiresAt = strtotime($savedAt) + (60 * 86400);
    return max(0, (int) ceil(($expiresAt - time()) / 86400));
}
