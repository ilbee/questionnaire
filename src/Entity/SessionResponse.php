<?php

namespace App\Entity;

use App\Repository\SessionResponseRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=SessionResponseRepository::class)
 */
class SessionResponse
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Session::class, inversedBy="sessionResponses")
     * @ORM\JoinColumn(nullable=false)
     */
    private $Session;

    /**
     * @ORM\ManyToOne(targetEntity=Question::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $Question;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private $displayedAt;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private $answeredAt;

    /**
     * @ORM\Column(type="integer")
     */
    private $position;

    /**
     * @ORM\ManyToOne(targetEntity=Answer::class)
     */
    private $Answer;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSession(): ?Session
    {
        return $this->Session;
    }

    public function setSession(?Session $Session): self
    {
        $this->Session = $Session;

        return $this;
    }

    public function getQuestion(): ?Question
    {
        return $this->Question;
    }

    public function setQuestion(?Question $Question): self
    {
        $this->Question = $Question;

        return $this;
    }

    public function getDisplayedAt(): ?\DateTimeImmutable
    {
        return $this->displayedAt;
    }

    public function setDisplayedAt(?\DateTimeImmutable $displayedAt): self
    {
        $this->displayedAt = $displayedAt;

        return $this;
    }

    public function getAnsweredAt(): ?\DateTimeImmutable
    {
        return $this->answeredAt;
    }

    public function setAnsweredAt(?\DateTimeImmutable $answeredAt): self
    {
        $this->answeredAt = $answeredAt;

        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function getAnswer(): ?Answer
    {
        return $this->Answer;
    }

    public function setAnswer(?Answer $Answer): self
    {
        $this->Answer = $Answer;

        return $this;
    }
}
