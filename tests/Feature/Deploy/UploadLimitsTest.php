<?php

use App\Http\Controllers\SequenceImageController;

/**
 * The server must accept what the application accepts: an image at the
 * size limit, plus the multipart overhead.
 */
function deployFile(string $name): string
{
    return (string) file_get_contents(base_path("deploy/{$name}"));
}

function megabytes(string $value): int
{
    return (int) $value * 1024 * 1024;
}

it('laisse passer une image à la taille maximale dans Apache', function () {
    preg_match('/^\s*LimitRequestBody\s+(\d+)/m', deployFile('apache.conf'), $match);

    expect((int) ($match[1] ?? 0))->toBeGreaterThan(SequenceImageController::MAX_SIZE * 1024);
});

it('laisse passer une image à la taille maximale dans PHP-FPM', function () {
    $script = deployFile('server-setup.sh');

    preg_match('/php_admin_value\[upload_max_filesize\]\s*=\s*(\d+)M/', $script, $upload);
    preg_match('/php_admin_value\[post_max_size\]\s*=\s*(\d+)M/', $script, $post);

    expect(megabytes($upload[1] ?? '0'))->toBeGreaterThanOrEqual(SequenceImageController::MAX_SIZE * 1024)
        ->and(megabytes($post[1] ?? '0'))->toBeGreaterThan(megabytes($upload[1] ?? '0'));
});

it('met à jour la limite d’Apache sur un serveur déjà installé', function () {
    preg_match('/^\s*LimitRequestBody\s+(\d+)/m', deployFile('apache.conf'), $match);

    // The site file is kept when it exists (certbot edits it): the limit is patched in place.
    expect(deployFile('server-setup.sh'))->toContain("LimitRequestBody {$match[1]}");
});
