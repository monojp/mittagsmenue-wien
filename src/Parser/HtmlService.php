<?php

namespace App\Parser;

class HtmlService extends TextService
{
    private $httpService;

    public function __construct(HttpService $httpService)
    {
        $this->httpService = $httpService;
    }

    /**
     * Tries to cleanup HTML
     * @param string $html
     * @param string|null $from_encoding
     * @return string
     */
    public function cleanHtml(string $html, ?string $from_encoding = null): string
    {
        $tmp = $this->stripInvalidChars($html);
        // fix unclean data by replacing tabs with spaces
        $tmp = str_replace(["\t", "\r"], ' ', $tmp);
        // adapt paragraphs with a line break to avoid being handled inline
        $tmp = str_ireplace(['</p>', '< /p>'], '</p><br />', $tmp);
        // strip html tags
        $tmp = strip_tags($tmp, '<br>');
        // remove unwanted stuff
        $tmp = str_replace('&nbsp;', ' ', $tmp);
        $tmp = str_ireplace(['<br />', '<br>', '<br/>'], "\r\n", $tmp);
        $tmp = preg_replace("/([a-z])\n([a-z])/i", '$1 $2', $tmp);
        // remove multiple spaces
        $tmp = preg_replace('/( )+/', ' ', $tmp);
        // remove multiple newlines
        $tmp = preg_replace("/(\n)+/i", "\n", $tmp);
        // convert data to utf-8
        if ($from_encoding !== null) {
            $tmp = mb_convert_encoding($tmp, 'UTF-8', $from_encoding);
        }
        // return trimmed data
        return trim($tmp);
    }

    /**
     * Downloads the HTML from the given URL and cleans it up
     * @param string $url
     * @param string|null $from_encoding
     * @return string|null
     */
    public function getCleanHtml(string $url, ?string $from_encoding = null): ?string
    {
        // download html
        $html = $this->httpService->getBodyContents($url);
        if (!$html) {
            return null;
        }
        // return clean data
        return $this->cleanHtml($html, $from_encoding);
    }

}