<?php

namespace App\Parser;

use Psr\Log\LoggerInterface;
use Smalot\PdfParser\Parser;

class ConverterService
{
    private $logger;
    private $textService;
    private $httpService;

    /**
     * ConverterService constructor.
     * @param LoggerInterface $logger
     * @param TextService $textService
     * @param HttpService $httpService
     */
    public function __construct(LoggerInterface $logger, TextService $textService, HttpService $httpService)
    {
        $this->logger = $logger;
        $this->textService = $textService;
        $this->httpService = $httpService;
    }

    private function cleanupText(string $text)
    {
        // Tabs seem to be always incorrect
        return str_replace("\t", '', $text);
    }

    /**
     * @param string $file
     * @return string|null
     */
    public function pdfToText(string $file): ?string
    {
        // Download URLs to a temporary file to avoid downloads in the PdfParser
        $tmpFile = null;
        if ($this->textService->startsWith($file, 'http')) {
            $tmpFile = (string)tempnam(sys_get_temp_dir(), __CLASS__);
            if (!$tmpFile) {
                $this->logger->error('Could not create temporary file');
                return null;
            }

            file_put_contents($tmpFile, $this->httpService->getBodyContents($file));
            $file = $tmpFile;
        }

        $parser = new Parser();
        try {
            $text = $parser->parseFile($file)->getText();
            $text = $this->cleanupText($text);
            return $text;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), [
                'exception' => $e,
            ]);
            return null;
        } finally {
            if ($tmpFile) {
                unlink($tmpFile);
            }
        }
    }
}