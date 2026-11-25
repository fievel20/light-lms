<?php

namespace App\Support;

use App\Models\Icon;
use App\Models\Sequence;
use App\Support\Markdown\IconInline;
use App\Support\Markdown\IconRenderer;
use App\Support\Markdown\QuizLauncher;
use App\Support\Markdown\QuizLauncherRenderer;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Extension\ExternalLink\ExternalLinkExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;
use League\CommonMark\Node\Block\Paragraph;
use League\CommonMark\Node\Inline\Text;

/**
 * Turns the Markdown of a sequence into HTML that is safe to display:
 * raw HTML is escaped, unsafe links are dropped, and links to other sites
 * open in a new tab (exercises, surveys…). Images written image:{id} point
 * to the protected route of that image, if it belongs to the sequence, and
 * links written fichier:{id} to the file to download, likewise;
 * icons written icon:{id} come from the shared library. A paragraph made of
 * [Quizz] alone marks where the quiz button goes (see splitAtQuiz).
 */
class MarkdownRenderer
{
    /** Left in the HTML where [Quizz] was; never produced by user input (raw HTML is escaped). */
    public const string QUIZ_MARKER = '<div data-quiz-launcher></div>';

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
        $environment->addEventListener(DocumentParsedEvent::class, $this->resolveAttachments(...));
        $environment->addEventListener(DocumentParsedEvent::class, $this->resolveIcons(...));
        $environment->addRenderer(IconInline::class, new IconRenderer);
        $environment->addEventListener(DocumentParsedEvent::class, $this->resolveQuizShortcode(...));
        $environment->addRenderer(QuizLauncher::class, new QuizLauncherRenderer);

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

    private function resolveAttachments(DocumentParsedEvent $event): void
    {
        $links = [];

        foreach ($event->getDocument()->iterator() as $node) {
            if ($node instanceof Link && str_starts_with($node->getUrl(), 'fichier:')) {
                $links[] = $node;
            }
        }

        if ($links === []) {
            return;
        }

        $ids = $this->sequence?->attachments()->pluck('id')->all() ?? [];

        foreach ($links as $link) {
            $id = (int) substr($link->getUrl(), strlen('fichier:'));

            if ($this->sequence !== null && in_array($id, $ids, true)) {
                $link->setUrl(route('sequences.attachments.show', [$this->sequence, $id]));
                // Read beside the sequence, like the exercises on other sites.
                $link->data->set('attributes/target', '_blank');
                $link->data->set('attributes/rel', 'noopener');
            } else {
                // Another sequence's file, or a deleted one: its text only.
                foreach (iterator_to_array($link->children()) as $child) {
                    $link->insertBefore($child);
                }

                $link->detach();
            }
        }
    }

    private function resolveIcons(DocumentParsedEvent $event): void
    {
        $nodes = [];

        foreach ($event->getDocument()->iterator() as $node) {
            if ($node instanceof Image && str_starts_with($node->getUrl(), 'icon:')) {
                $nodes[] = $node;
            }
        }

        if ($nodes === []) {
            return;
        }

        $ids = array_map(fn (Image $node) => (int) substr($node->getUrl(), strlen('icon:')), $nodes);
        $icons = Icon::query()->whereKey($ids)->get(['id', 'name', 'monochrome'])->keyBy('id');

        foreach ($nodes as $index => $node) {
            $icon = $icons->get($ids[$index]);

            if ($icon === null) {
                // A deleted icon: show nothing.
                $node->detach();

                continue;
            }

            $label = trim(implode('', array_map(
                fn ($child) => $child instanceof Text ? $child->getLiteral() : '',
                iterator_to_array($node->children()),
            )));

            $node->replaceWith(new IconInline(route('icons.show', $icon), $label !== '' ? $label : $icon->name, $icon->monochrome));
        }
    }

    /**
     * The HTML cut where the quiz button goes: one more part than buttons.
     *
     * @return list<string>
     */
    public static function splitAtQuiz(string $html): array
    {
        return explode(self::QUIZ_MARKER, $html);
    }

    /**
     * Only top-level paragraphs, so the HTML can be cut there without
     * breaking a list or a quote.
     */
    private function resolveQuizShortcode(DocumentParsedEvent $event): void
    {
        foreach ($event->getDocument()->children() as $node) {
            $child = $node->firstChild();

            if ($node instanceof Paragraph
                && $child instanceof Text
                && $child->next() === null
                && preg_match('/^\[quizz?\]$/i', trim($child->getLiteral())) === 1) {
                $node->replaceWith(new QuizLauncher);
            }
        }
    }
}
