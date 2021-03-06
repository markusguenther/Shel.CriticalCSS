<?php
declare(strict_types=1);

namespace Shel\CriticalCSS\Http;

/*
 * This file is part of the Shel.CriticalCSS package.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\Component\ComponentContext;
use Neos\Flow\Http\Component\ComponentInterface;
use Neos\Flow\Http\ContentStream;

/**
 * The HTTP component to collect all inline styles and merge them into the html head
 */
class StyleComponent implements ComponentInterface
{

    /**
     * @var boolean
     * @Flow\InjectConfiguration(path="mergeStylesComponent.enabled")
     */
    protected $enabled;

    /**
     * @inheritDoc
     */
    public function handle(ComponentContext $componentContext)
    {
        if (!$this->enabled) {
            return;
        }

        $request = $componentContext->getHttpRequest();

        if (strpos($request->getUri()->getPath(), '/neos/') === 0) {
            return;
        }

        $response = $componentContext->getHttpResponse();
        $content = $response->getBody()->getContents();

        // Retrieve all inline style tags
        preg_match_all('/<style data-inline>(.*?)<\/style>/', $content, $matches);

        if (!$matches) {
            return;
        }

        // Remove duplicates
        $styles = array_unique($matches[1]);

        if (count($styles) > 0) {
            // Remove inline style tags from content
            $content = preg_replace('/<style data-inline>.*?<\/style>/', '', $content);

            // Add merged styles into one new style tag to head
            $styleTag = '<style data-merged>' . join('', $styles) . '</style>';
            $content = str_replace('</head>', $styleTag . '</head>', $content);

            $componentContext->replaceHttpResponse($response->withBody(ContentStream::fromContents($content)));
        } else {
            $response->getBody()->rewind();
        }
    }
}
