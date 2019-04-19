<?php

declare(strict_types=1);

namespace Bolt\Snippet;

use Symfony\Component\HttpFoundation\Response;
use Tightenco\Collect\Support\Collection;

/**
 * Snippet queue processor.
 */
class QueueProcessor
{
    /** @var HtmlInjector */
    protected $injector;

    /** @var array */
    private $matchedComments = [];

    private $matchedCommentsCount = 0;

    public function __construct(HtmlInjector $injector)
    {
        $this->injector = $injector;
    }

    public function process(Response $response, Collection $queue, string $zone): Response
    {
        // First, gather all html <!-- comments -->, because they shouldn't be
        // considered for replacements. We use a callback, so we can fill our
        // $this->matchedComments array
        preg_replace_callback('/<!--(.*)-->/Uis', [$this, 'pregCallback'], $response->getContent());

        foreach ($queue as $widget) {
            if ($widget->getZone() === $zone) {
                $this->injector->inject($widget, $response);
            }
            // unset($this->queue[$key]);
        }

        // Finally, replace back ###comment### with its original comment.
        if (! empty($this->matchedComments)) {
            $html = preg_replace(array_keys($this->matchedComments), $this->matchedComments, $response->getContent(), 1);
            $response->setContent($html);
        }

        return $response;
    }

    /**
     * Callback method to identify comments and store them in the
     * matchedComments array.
     *
     * These will be put back after the replacements on the HTML are finished.
     */
    public function pregCallback(array $c): string
    {
        $key = '###bolt-comment-' . $this->matchedCommentsCount++ . '###';
        // Add it to the array of matched comments.
        $this->matchedComments['/' . $key . '/'] = $c[0];

        return $key;
    }
}
