<?php

namespace App\Support\Markdown;

use InvalidArgumentException;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;

/**
 * A coloured icon is a plain image; a one-colour icon is a mask filled with
 * the colour of the text, so it stays readable in dark mode. Both are sized
 * by the .icon class (see app.css).
 */
final class IconRenderer implements NodeRendererInterface
{
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): HtmlElement
    {
        if (! $node instanceof IconInline) {
            throw new InvalidArgumentException('Incompatible node type: '.$node::class);
        }

        if (! $node->monochrome) {
            return new HtmlElement('img', ['class' => 'icon', 'src' => $node->url, 'alt' => $node->label], '', true);
        }

        return new HtmlElement('span', [
            'class' => 'icon icon-mono',
            'role' => 'img',
            'aria-label' => $node->label,
            'style' => "--icon: url(\"{$node->url}\")",
        ], '');
    }
}
