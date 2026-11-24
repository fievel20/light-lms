<?php

namespace App\Support;

/**
 * Draws a text as a PNG image, so the participants' browsers have no text
 * to translate (quiz express). Needs GD with FreeType and a TrueType font:
 * the one set in config('express.font'), or one usually installed with
 * the system (DejaVu Sans on Linux, Arial on Windows and macOS).
 */
class TextImage
{
    private const array FONTS = [
        '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
        '/usr/share/fonts/dejavu/DejaVuSans-Bold.ttf',
        '/usr/share/fonts/TTF/DejaVuSans-Bold.ttf',
        'C:\\Windows\\Fonts\\arialbd.ttf',
        '/System/Library/Fonts/Supplemental/Arial Bold.ttf',
    ];

    public function available(): bool
    {
        return function_exists('imagettftext') && $this->font() !== null;
    }

    public function font(): ?string
    {
        $configured = config('express.font');

        foreach ([...(is_string($configured) && $configured !== '' ? [$configured] : []), ...self::FONTS] as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * The text wrapped to the width at most, on a transparent background;
     * the image is no wider than its longest line, so pages can show all
     * the images at one scale (same text size, short answers included).
     *
     * @param  string  $color  hexadecimal, e.g. #0e0926
     */
    public function png(string $text, int $width, int $size, string $color): string
    {
        $font = $this->font() ?? throw new \RuntimeException('Aucune police TrueType trouvée.');
        // The font has no emoji: they would show as empty boxes.
        $text = trim(preg_replace('/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}\x{FE0F}\x{200D}]/u', '', $text) ?? $text);
        $lines = $this->wrap($text === '' ? ' ' : $text, $font, $size, $width);
        $lineHeight = (int) ceil($size * 1.35);
        $height = $lineHeight * count($lines) + (int) ceil($size * 0.4);
        $used = max(0, ...array_map(fn (string $line) => $this->measure($line, $font, $size), $lines));
        $width = min($width, $used + (int) ceil($size * 0.2));

        $image = imagecreatetruecolor(max(1, $width), max(1, $height));
        imagesavealpha($image, true);
        imagealphablending($image, false);
        imagefill($image, 0, 0, (int) imagecolorallocatealpha($image, 0, 0, 0, 127));
        imagealphablending($image, true);

        [$red, $green, $blue] = $this->rgb($color);
        $ink = (int) imagecolorallocate($image, $red, $green, $blue);

        foreach ($lines as $index => $line) {
            imagettftext($image, $size * 0.75, 0, 0, (int) ($size + $index * $lineHeight), $ink, $font, $line);
        }

        ob_start();
        imagepng($image);

        return (string) ob_get_clean();
    }

    /**
     * @return array{0: int<0, 255>, 1: int<0, 255>, 2: int<0, 255>}
     */
    private function rgb(string $color): array
    {
        $hex = str_pad(substr(ltrim($color, '#'), 0, 6), 6, '0');
        $channel = fn (int $offset): int => max(0, min(255, (int) hexdec(substr($hex, $offset, 2))));

        return [$channel(0), $channel(2), $channel(4)];
    }

    /**
     * Words on lines no wider than the width (a word too long is cut).
     *
     * @return list<string>
     */
    private function wrap(string $text, string $font, int $size, int $width): array
    {
        $lines = [];

        foreach (preg_split('/\R/u', $text) ?: [$text] as $paragraph) {
            $line = '';

            foreach (preg_split('/\s+/u', trim($paragraph)) ?: [] as $word) {
                $candidate = $line === '' ? $word : "{$line} {$word}";

                if ($this->measure($candidate, $font, $size) <= $width) {
                    $line = $candidate;

                    continue;
                }

                if ($line !== '') {
                    $lines[] = $line;
                }

                // A word wider than the line is cut where it no longer fits.
                while ($this->measure($word, $font, $size) > $width && mb_strlen($word) > 1) {
                    $cut = mb_strlen($word) - 1;

                    while ($cut > 1 && $this->measure(mb_substr($word, 0, $cut), $font, $size) > $width) {
                        $cut--;
                    }

                    $lines[] = mb_substr($word, 0, $cut);
                    $word = mb_substr($word, $cut);
                }

                $line = $word;
            }

            $lines[] = $line;
        }

        return $lines;
    }

    private function measure(string $text, string $font, int $size): int
    {
        $box = imagettfbbox($size * 0.75, 0, $font, $text);

        return $box === false ? 0 : abs($box[2] - $box[0]);
    }
}
