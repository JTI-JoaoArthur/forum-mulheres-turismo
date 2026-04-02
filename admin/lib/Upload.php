<?php
/**
 * Upload — Validação e processamento seguro de uploads de imagens
 *
 * - Validação por MIME type real (finfo), não por extensão
 * - Nomes únicos (random_bytes) contra colisão e path traversal
 * - Limites configuráveis de tamanho
 */

class Upload
{
    private const ALLOWED_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    private const ALLOWED_VIDEO_TYPES = [
        'video/mp4'  => 'mp4',
        'video/webm' => 'webm',
    ];

    private const MAX_SIZE = 15 * 1024 * 1024; // 15 MB
    private const MAX_VIDEO_SIZE = 100 * 1024 * 1024; // 100 MB
    private const UPLOAD_DIR = __DIR__ . '/../uploads/';

    /**
     * Processar upload de imagem
     *
     * @param array $file — Elemento de $_FILES (ex: $_FILES['photo'])
     * @param string $subfolder — Subpasta dentro de uploads/ (ex: 'speakers')
     * @return array{success: bool, path?: string, error?: string}
     */
    public static function image(array $file, string $subfolder = ''): array
    {
        // Verificar erros de upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => self::uploadError($file['error'])];
        }

        // Verificar tamanho
        if ($file['size'] > self::MAX_SIZE) {
            $maxMB = self::MAX_SIZE / 1024 / 1024;
            return ['success' => false, 'error' => "Arquivo excede o limite de {$maxMB} MB."];
        }

        // Verificar MIME type real (não confia na extensão)
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        if (!isset(self::ALLOWED_TYPES[$mimeType])) {
            return ['success' => false, 'error' => 'Formato não permitido. Use JPG, PNG ou WebP.'];
        }

        // Gerar nome único
        $extension = self::ALLOWED_TYPES[$mimeType];
        $filename = bin2hex(random_bytes(16)) . '.' . $extension;

        // Criar diretório se necessário
        $dir = self::UPLOAD_DIR;
        if ($subfolder) {
            $subfolder = preg_replace('/[^a-zA-Z0-9_-]/', '', $subfolder);
            $dir .= $subfolder . '/';
        }
        if (!is_dir($dir)) {
            mkdir($dir, 0750, true);
        }

        $destination = $dir . $filename;

        // Mover arquivo
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            return ['success' => false, 'error' => 'Erro ao salvar o arquivo.'];
        }

        // Retornar caminho relativo (para salvar no banco)
        $relativePath = 'admin/uploads/';
        if ($subfolder) {
            $relativePath .= $subfolder . '/';
        }
        $relativePath .= $filename;

        return ['success' => true, 'path' => $relativePath];
    }

    /**
     * Processar upload de vídeo
     */
    public static function video(array $file, string $subfolder = ''): array
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => self::uploadError($file['error'])];
        }

        if ($file['size'] > self::MAX_VIDEO_SIZE) {
            $maxMB = self::MAX_VIDEO_SIZE / 1024 / 1024;
            return ['success' => false, 'error' => "Vídeo excede o limite de {$maxMB} MB."];
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        if (!isset(self::ALLOWED_VIDEO_TYPES[$mimeType])) {
            return ['success' => false, 'error' => 'Formato de vídeo não permitido. Use MP4 ou WebM.'];
        }

        $extension = self::ALLOWED_VIDEO_TYPES[$mimeType];
        $filename = bin2hex(random_bytes(16)) . '.' . $extension;

        $dir = self::UPLOAD_DIR;
        if ($subfolder) {
            $subfolder = preg_replace('/[^a-zA-Z0-9_-]/', '', $subfolder);
            $dir .= $subfolder . '/';
        }
        if (!is_dir($dir)) {
            mkdir($dir, 0750, true);
        }

        $destination = $dir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            return ['success' => false, 'error' => 'Erro ao salvar o vídeo.'];
        }

        $relativePath = 'admin/uploads/';
        if ($subfolder) {
            $relativePath .= $subfolder . '/';
        }
        $relativePath .= $filename;

        return ['success' => true, 'path' => $relativePath];
    }

    /**
     * Remover arquivo de upload
     */
    public static function delete(?string $relativePath): bool
    {
        if (empty($relativePath)) {
            return false;
        }

        $fullPath = __DIR__ . '/../../' . $relativePath;

        if (file_exists($fullPath) && str_starts_with(realpath($fullPath), realpath(self::UPLOAD_DIR))) {
            return unlink($fullPath);
        }

        return false;
    }

    private static function uploadError(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Arquivo muito grande.',
            UPLOAD_ERR_PARTIAL    => 'Upload incompleto. Tente novamente.',
            UPLOAD_ERR_NO_FILE    => 'Nenhum arquivo enviado.',
            UPLOAD_ERR_NO_TMP_DIR => 'Erro no servidor (diretório temporário).',
            UPLOAD_ERR_CANT_WRITE => 'Erro ao gravar arquivo.',
            default               => 'Erro desconhecido no upload.',
        };
    }
}
