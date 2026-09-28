<?php

namespace App\Services;

use RuntimeException;

/**
 * Generic "save an uploaded image under a random name" helper. The file type
 * is taken from the content (getimagesize), never from the name or the
 * browser-supplied mime type: both of those can be faked.
 */
class ImageUpload
{
    private const TYPES = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_GIF => 'gif', IMAGETYPE_WEBP => 'webp'];

    public function __construct(private readonly int $maxMb = 8) {}

    /** @param  array|null  $file  a single entry from $_FILES */
    public function store(?array $file, string $directory): ?string
    {
        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
            throw new RuntimeException('De afbeelding is te groot (maximaal '.$this->maxMb.' MB).');
        }

        if ($file['error'] !== UPLOAD_ERR_OK || ! is_uploaded_file($file['tmp_name'])) {
            throw new RuntimeException('Het uploaden van de afbeelding is mislukt.');
        }

        if ($file['size'] > $this->maxMb * 1024 * 1024) {
            throw new RuntimeException('De afbeelding is te groot (maximaal '.$this->maxMb.' MB).');
        }

        $info = @getimagesize($file['tmp_name']);

        if ($info === false || ! isset(self::TYPES[$info[2]])) {
            throw new RuntimeException('Alleen JPG, PNG, GIF of WebP kunnen gebruikt worden.');
        }

        if (! is_dir($directory) && ! @mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new RuntimeException('De map voor afbeeldingen kon niet aangemaakt worden.');
        }

        $name = bin2hex(random_bytes(16)).'.'.self::TYPES[$info[2]];

        if (! move_uploaded_file($file['tmp_name'], $directory.'/'.$name)) {
            throw new RuntimeException('De afbeelding kon niet opgeslagen worden.');
        }

        return $name;
    }

    public function delete(string $directory, ?string $name): void
    {
        // basename() keeps paths like "../../.env" out.
        if ($name === null || $name === '' || $name !== basename($name)) {
            return;
        }

        $path = $directory.'/'.$name;
        if (is_file($path)) {
            @unlink($path);
        }
    }
}
