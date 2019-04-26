<?php

namespace App\Service;

use App\Entity\FoodCache;
use App\Parser\TimeService;
use App\Parser\Venue\FoodGetterVenue;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class VenueService
{
    private const VENUE_NAMESPACE = 'App\Parser\Venue';

    private $timeService;
    private $entityManager;
    private $logger;
    private $validator;

    public function __construct(TimeService $timeService, EntityManagerInterface $entityManager, LoggerInterface $logger, ValidatorInterface $validator)
    {
        $this->timeService = $timeService;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->validator = $validator;
    }

    /**
     * @param string $name
     * @return FoodGetterVenue|null
     */
    public function venueFromName(string $name): ?FoodGetterVenue
    {
        if (!$this->isValidVenue($name)) {
            return null;
        }

        $nameFqcn = $this->nameFqcn($name);
        $venue = new $nameFqcn();
        if (!$venue instanceof FoodGetterVenue) {
            $this->logger->error("{$name} => {$nameFqcn} is not a valid venue");
            return null;
        }

        return $venue;
    }

    private function nameFqcn(string $name): string
    {
        return self::VENUE_NAMESPACE . '\\' . $name;
    }

    public function isValidVenue(string $name): bool
    {
        $nameFqcn = $this->nameFqcn($name);
        return class_exists($nameFqcn)
            && in_array(FoodGetterVenue::class, class_parents($nameFqcn), true);
    }

    /**
     * @param string $timestamp
     * @param string $venue
     * @return FoodCache|null
     */
    public function getCache(string $timestamp, string $venue): ?FoodCache
    {
        return $this->entityManager->getRepository(FoodCache::class)->findOneBy([
            'venue' => $venue,
            'date' => $this->timeService->dayFromTimestamp($timestamp)
        ]);
    }

    /**
     * @param FoodCache $foodCache
     */
    public function deleteFromCache(FoodCache $foodCache): void
    {
        $this->entityManager->remove($foodCache);
        $this->entityManager->flush();
    }

    /**
     * @param string $timestamp
     * @param string $venue
     * @param string $data
     * @param $price
     * @return \Symfony\Component\Validator\ConstraintViolationListInterface|null
     */
    public function saveToCache(string $timestamp, string $venue, string $data, $price): ?\Symfony\Component\Validator\ConstraintViolationListInterface
    {
        $priceEncoded = json_encode($price);
        if ($priceEncoded === false) {
            $this->logger->error('Could not JSON encode price: ' . json_last_error_msg(), $price);
            $priceEncoded = null;
        }

        $foodCache = (new FoodCache())
            ->setVenue($venue)
            ->setDate($this->timeService->dayFromTimestamp($timestamp))
            ->setChanged($this->timeService->dateTimeFromTimestamp(time()))
            ->setData($data)
            ->setPrice($priceEncoded);

        $errors = $this->validator->validate($foodCache);
        if (count($errors)) {
            return $errors;
        }

        $this->entityManager->persist($foodCache);
        $this->entityManager->flush();
        return null;
    }
}