<?php

namespace App\Service;

use App\Entity\FoodUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserService
{

    private $entityManager;
    private $validator;

    public function __construct(EntityManagerInterface $entityManager, ValidatorInterface $validator)
    {
        $this->entityManager = $entityManager;
        $this->validator = $validator;
    }

    /**
     * @return FoodUser[]
     */
    public function getUsers(): array
    {
        return $this->entityManager->getRepository(FoodUser::class)->findAll();
    }

    /**
     * @param string $ip
     * @return FoodUser
     */
    public function getUser(string $ip): FoodUser
    {
        $foodUser = $this->entityManager->getRepository(FoodUser::class)->find($ip);
        if (!$foodUser) {
            // Create temporary user object if no user was found in DB
            return new FoodUser($ip, $ip);
        }

        return $foodUser;
    }

    /**
     * @param string $ip
     * @param string $name
     * @param string|null $email
     * @param bool $vote_reminder
     * @param bool $voted_mail_only
     * @param bool $vote_always_show
     * @return \Symfony\Component\Validator\ConstraintViolationListInterface|null
     */
    public function setUserConfig(string $ip, string $name, ?string $email = null, bool $vote_reminder = true, bool $voted_mail_only = false, bool $vote_always_show = false): ?\Symfony\Component\Validator\ConstraintViolationListInterface
    {
        $foodUser = $this->getUser($ip)
            ->setName($name)
            ->setEmail($email)
            ->setVoteReminder($vote_reminder)
            ->setVotedMailOnly($voted_mail_only)
            ->setVoteAlwaysShow($vote_always_show);

        $errors = $this->validator->validate($foodUser);
        if (count($errors)) {
            return $errors;
        }

        $this->entityManager->contains($foodUser) ? $this->entityManager->merge($foodUser)
            : $this->entityManager->persist($foodUser);
        $this->entityManager->flush();
        return null;
    }
}