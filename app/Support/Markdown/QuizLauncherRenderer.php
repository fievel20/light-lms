<?php

namespace App\Support\Markdown;

use App\Support\MarkdownRenderer;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;

/**
 * A marker the pages split the HTML on, to put the button component there.
 */
final class QuizLauncherRenderer implements NodeRendererInterface
{
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
    {
        return MarkdownRenderer::QUIZ_MARKER;
    }
}
