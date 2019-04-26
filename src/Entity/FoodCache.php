<?php

namespace App\Entity;

use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\FoodCacheRepository")
 */
class FoodCache
{

    /**
     * @ORM\Id()
     * @ORM\Column(type="string", length=32)
     * @Assert\NotBlank()
     */
    private $venue;

    /**
     * @ORM\Id()
     * @ORM\Column(type="string", length=16)
     * @Assert\Date()
     */
    private $date;

    /**
     * @ORM\Column(type="datetime")
     */
    private $changed;

    /**
     * @ORM\Column(type="text")
     * @Assert\NotBlank()
     */
    private $data;

    /**
     * @ORM\Column(type="text")
     */
    private $price;

    public function getVenue(): ?string
    {
        return $this->venue;
    }

    public function setVenue(string $venue): self
    {
        $this->venue = $venue;

        return $this;
    }

    public function getDate(): ?string
    {
        return $this->date;
    }

    public function setDate(string $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getChanged(): ?DateTimeInterface
    {
        return $this->changed;
    }

    public function setChanged(DateTimeInterface $changed): self
    {
        $this->changed = $changed;

        return $this;
    }

    public function getData(): ?string
    {
        return $this->data;
    }

    public function setData(string $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): self
    {
        $this->price = $price;

        return $this;
    }
}
