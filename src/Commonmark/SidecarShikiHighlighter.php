<?php

namespace Spatie\SidecarShiki\Commonmark;

use Exception;
use Spatie\SidecarShiki\SidecarShiki;

class SidecarShikiHighlighter
{
    public function __construct(private string $theme)
    {
    }

    public function highlight(string $codeBlock, ?string $infoLine = null): string
    {
        $codeBlockWithoutTags = strip_tags($codeBlock);

        $contents = htmlspecialchars_decode($codeBlockWithoutTags);

        [$contents, $addLines, $deleteLines] = $this->parseAddedAndDeletedLines($contents);

        $definition = $this->parseLangAndLines($infoLine);

        $language = $definition['lang'] ?? 'php';

        try {
            $highlightedContents = SidecarShiki::highlight(
                code: $contents,
                language: $language,
                theme: $this->theme,
                highlightLines: $definition['highlightLines'],
                addLines: $addLines,
                deleteLines: $deleteLines,
                focusLines: $definition['focusLines'],
            );
        } catch (Exception) {
            $highlightedContents = $contents;
        }

        return $highlightedContents;
    }

    protected function parseLangAndLines(?string $language): array
    {
        $parsed = [
            'lang' => $language,
            'highlightLines' => [],
            'focusLines' => [],
        ];

        if ($language === null) {
            return $parsed;
        }

        $bracePosition = strpos($language, '{');

        if ($bracePosition === false) {
            return $parsed;
        }

        preg_match_all('/{([^}]*)}/', $language, $matches);

        $parsed['lang'] = substr($language, 0, $bracePosition);
        $parsed['highlightLines'] = array_map('trim', explode(',', $matches[1][0] ?? ''));
        $parsed['focusLines'] = array_map('trim', explode(',', $matches[1][1] ?? ''));

        return $parsed;
    }

    private function parseAddedAndDeletedLines(string $contents): array
    {
        $addLines = [];
        $deleteLines = [];

        $contentLines = explode("\n", $contents);
        $contentLines = array_map(function (string $line, int $index) use (&$addLines, &$deleteLines) {
            if (str_starts_with($line, '+ ')) {
                $addLines[] = $index + 1;
                $line = substr($line, 2);
            }

            if (str_starts_with($line, '- ')) {
                $deleteLines[] = $index + 1;
                $line = substr($line, 2);
            }

            return $line;
        }, $contentLines, array_keys($contentLines));

        return [
            implode("\n", $contentLines),
            $addLines,
            $deleteLines,
        ];
    }
}
