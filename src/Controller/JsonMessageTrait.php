<?php

namespace App\Controller;

trait JsonMessageTrait
{
    private $jsMessageDefaultWidth = 50;
    private $jsMessageDefaultBreak = '<br/>';

    /**
     * prepare a html message for javascript output
     * @param string $message
     * @param int|null $width
     * @param string|null $break
     * @return string
     */
    private function jsMessagePrepare(string $message, ?int $width = null, ?string $break = null): string
    {
        if ($width === null) {
            $width = $this->jsMessageDefaultWidth;
        }

        if ($break === null) {
            $break = $this->jsMessageDefaultBreak;
        }

        $message = wordwrap($message, $width, $break);
        return $message;
    }

    /**
     * @param string $message
     * @param int|null $width
     * @param string|null $break
     * @return array
     */
    private function jsAlertMessage(string $message, ?int $width = null, ?string $break = null): array
    {
        return [
            'alert' => $this->jsMessagePrepare($message, $width, $break)
        ];
    }

}