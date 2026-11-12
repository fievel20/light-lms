<?php

namespace App\Support\Markdown;

use League\CommonMark\Node\Inline\AbstractInline;

/**
 * An icon of the library in the text, written ![label](icon:{id}).
 */
final class IconInline extends AbstractInline
{
    public function __construct(
        public readonly string $url,
        public readonly string $label,
        public readonly bool $monochrome,
    ) {
        parent::__construct();
    }
}
