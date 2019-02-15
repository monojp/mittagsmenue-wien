<?php

namespace App\Entity;

use Symfony\Component\Validator\Constraints as Assert;

class FoodVoteNote
{
    /**
     * @Assert\NotBlank()
     * @Assert\Length(max="128")
     */
    private $note;

    /**
     * @return string
     */
    public function getNote(): string
    {
        return $this->note;
    }

    /**
     * @param string $note
     * @return self
     */
    public function setNote(string $note): self
    {
        $this->note = $note;
        return $this;
    }
}