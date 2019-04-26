<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\FoodUserRepository")
 */
class FoodUser
{

    /**
     * @ORM\Id()
     * @ORM\Column(type="string", length=128)
     * @Assert\NotBlank()
     */
    private $ip;

    /**
     * @ORM\Column(type="string", length=128)
     * @Assert\NotBlank()
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=128, nullable=true)
     * @Assert\Email()
     */
    private $email;

    /**
     * @ORM\Column(type="boolean")
     */
    private $vote_reminder;

    /**
     * @ORM\Column(type="boolean")
     */
    private $voted_mail_only;

    /**
     * @ORM\Column(type="boolean")
     */
    private $vote_always_show;

    /**
     * FoodUser constructor.
     * @param $ip
     * @param $name
     * @param $email
     * @param $vote_reminder
     * @param $voted_mail_only
     * @param $vote_always_show
     */
    public function __construct(string $ip, string $name, ?string $email = null, bool $vote_reminder = false, bool $voted_mail_only = false, bool $vote_always_show = false)
    {
        $this->ip = $ip;
        $this->name = $name;
        $this->email = $email;
        $this->vote_reminder = $vote_reminder;
        $this->voted_mail_only = $voted_mail_only;
        $this->vote_always_show = $vote_always_show;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(string $ip): self
    {
        $this->ip = $ip;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getVoteReminder(): bool
    {
        return $this->vote_reminder;
    }

    public function setVoteReminder(bool $vote_reminder): self
    {
        $this->vote_reminder = $vote_reminder;

        return $this;
    }

    public function getVotedMailOnly(): bool
    {
        return $this->voted_mail_only;
    }

    public function setVotedMailOnly(bool $voted_mail_only): self
    {
        $this->voted_mail_only = $voted_mail_only;

        return $this;
    }

    public function getVoteAlwaysShow(): bool
    {
        return $this->vote_always_show;
    }

    public function setVoteAlwaysShow(bool $vote_always_show): self
    {
        $this->vote_always_show = $vote_always_show;

        return $this;
    }
}
