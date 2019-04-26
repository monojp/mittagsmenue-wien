<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\FoodVoteRepository")
 */
class FoodVote
{

    /**
     * @ORM\Id()
     * @ORM\Column(type="string", length=16)
     * @Assert\Date()
     */
    private $day;

    /**
     * @ORM\Id()
     * @ORM\Column(type="string", length=128)
     * @Assert\NotBlank()
     */
    private $category;

    /**
     * @ORM\Column(type="string", length=128)
     * @Assert\NotBlank()
     */
    private $vote;

    /**
     * @ORM\Id()
     * @ORM\ManyToOne(targetEntity="FoodUser")
     * @ORM\JoinColumn(name="ip", referencedColumnName="ip")
     * @Assert\NotBlank()
     */
    private $user;

    /**
     * @param FoodUser $user
     * @return FoodVote
     */
    public function setUser(FoodUser $user): self
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return FoodUser
     */
    public function getUser(): FoodUser
    {
        return $this->user;
    }

    public function getDay(): ?string
    {
        return $this->day;
    }

    public function setDay(string $day): self
    {
        $this->day = $day;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getVote(): ?string
    {
        return $this->vote;
    }

    public function setVote(string $vote): self
    {
        $this->vote = $vote;

        return $this;
    }
}
