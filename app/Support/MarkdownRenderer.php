<?php

namespace App\Support;

use App\Models\Sequence;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\Extension\ExternalLink\ExternalLinkExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;

/**
 * Turns the Markdown of a sequence into HTML that is safe to display:
 * raw HTML is escaped, unsafe links are dropped, and links to other sites
 * open in a new tab (exercises, surveys…). Images written image:{id} point
 * to the protected route of that image, if it belongs to the sequence.
 */
class MarkdownRenderer
{
    private MarkdownConverter $converter;

    /** The sequence being rendered, whose images may be shown. */
    private ?Sequence $sequence = null;

    public function __construct()
    {
        $environment = new Environment([
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 20,
            'external_link' => [
                'internal_hosts' => [(string) parse_url((string) config('app.url'), PHP_URL_HOST)],
                'open_in_new_window' => true,
                'noopener' => 'external',
                'noreferrer' => 'external',
            ],
        ]);

        $environment->addExtension(new CommonMarkCoreExtension);
        // Tables, strikethrough, task lists and bare URLs turned into links.
        $environment->addExtension(new GithubFlavoredMarkdownExtension);
        $environment->addExtension(new ExternalLinkExtension);
        $environment->addEventListener(DocumentParsedEvent::class, $this->resolveImages(...));

        $this->converter = new MarkdownConverter($environment);
    }

    public function toHtml(string $markdown, ?Sequence $sequence = null): string
    {
        $this->sequence = $sequence;

        try {
            return $this->converter->convert($markdown)->getContent();
        } finally {
            $this->sequence = null;
        }
    }

    private function resolveImages(DocumentParsedEvent $event): void
    {
        $images = [];

        foreach ($event->getDocument()->iterator() as $node) {
            if ($node instanceof Image && str_starts_with($node->getUrl(), 'image:')) {
                $images[] = $node;
            }
        }

        if ($images === []) {
            return;
        }

        $ids = $this->sequence?->images()->pluck('id')->all() ?? [];

        foreach ($images as $image) {
            $id = (int) substr($image->getUrl(), strlen('image:'));

            if ($this->sequence !== null && in_array($id, $ids, true)) {
                $image->setUrl(route('sequences.images.show', [$this->sequence, $id]));
            } else {
                // Another sequence's image, or a deleted one: show nothing.
                $image->detach();
            }
        }
    }
}
