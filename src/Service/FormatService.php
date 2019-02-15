<?php

namespace App\Service;

use /** @noinspection PhpUndefinedClassInspection */
    NumberFormatter;
use Symfony\Component\HttpFoundation\RequestStack;

class FormatService
{

    private $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * @param float $value
     * @param string $currency
     * @param string|null $locale
     * @return string
     */
    public function formatMoney(float $value, string $currency = 'EUR', ?string $locale = null)
    {
        $locale = $locale ?: $this->requestStack->getCurrentRequest()->getLocale();
        /** @noinspection PhpUndefinedClassInspection */
        return (new NumberFormatter($locale, NumberFormatter::CURRENCY))
            ->formatCurrency($value, $currency);
    }

}