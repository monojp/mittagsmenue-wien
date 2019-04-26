<?php

namespace App\Parser;

use DateTime;
use Exception;
use Psr\Log\LoggerInterface;

class TimeService
{
    public const DATE_FORMAT = 'Y-m-d';
    public const TIME_FORMAT = 'Y-m-d H:i:s';

    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param int $week
     * @param int $year
     * @param string $format
     * @return array
     */
    public function getStartAndEndDate(int $week, int $year, string $format = self::DATE_FORMAT): array
    {
        try {
            $dto = new DateTime();
        } catch (Exception $e) {
            $this->logger->error($e->getMessage(), [
                'exception' => $e,
            ]);
            return [];
        }

        $dto->setISODate($year, $week);
        $ret[] = $dto->format($format);
        $dto->modify('+6 days');
        $ret[] = $dto->format($format);
        return $ret;
    }

    /**
     * @param $time
     * @param $format
     * @param null $timestamp
     * @return false|int
     */
    public function strtotimep($time, $format, $timestamp = null)
    {
        if (empty($timestamp)) {
            $timestamp = time();
        }

        $date = strptime($time, $format);

        $hour = empty($date['tm_hour']) ? date('H', $timestamp) : $date['tm_hour'];
        $min = empty($date['tm_min']) ? date('i', $timestamp) : $date['tm_min'];
        $sec = empty($date['tm_sec']) ? date('s', $timestamp) : $date['tm_sec'];
        $day = empty($date['tm_mday']) ? date('j', $timestamp) : $date['tm_mday'];
        $month = empty($date['tm_mon']) ? date('n', $timestamp) : ($date['tm_mon'] + 1);
        // we need the offset because "tm_year" are the years since 1900 as of definition
        $year = 1900 + (empty($date['tm_year']) ? date('Y', $timestamp) : $date['tm_year']);

        return mktime($hour, $min, $sec, $month, $day, $year);
    }

    /**
     * @param $offset
     * @return false|string
     */
    public function dateFromOffset($offset)
    {
        if ($offset >= 0) {
            return date('Y-m-d', strtotime(date('Y-m-d') . " + $offset days"));
        }

        $offset = abs($offset);
        return date('Y-m-d', strtotime(date('Y-m-d') . " - $offset days"));
    }

    /**
     * @param string $timestamp
     * @return string|null
     */
    public function dayFromTimestamp(string $timestamp): ?string
    {
        $dateTime = $this->dateTimeFromTimestamp($timestamp);
        return $dateTime ? $dateTime->format(self::DATE_FORMAT) : null;
    }

    /**
     * @param string $timestamp
     * @return string|null
     */
    public function timeFromTimestamp(string $timestamp): ?string
    {
        $dateTime = $this->dateTimeFromTimestamp($timestamp);
        return $dateTime ? $dateTime->format(self::TIME_FORMAT) : null;
    }

    /**
     * @param string $timestamp
     * @return DateTime|null
     */
    public function dateTimeFromTimestamp(string $timestamp): ?DateTime
    {
        try {
            return (new DateTime())->setTimestamp($timestamp);
        } catch (Exception $e) {
            $this->logger->error("Could not create DateTime object from timestamp {$timestamp}");
            return null;
        }
    }
}