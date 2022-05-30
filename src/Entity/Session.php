<?php

namespace App\Entity;

use App\Repository\SessionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=SessionRepository::class)
 * @ORM\HasLifecycleCallbacks()
 */
class Session
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="sessions")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private $endedAt;

    /**
     * @ORM\OneToMany(targetEntity=SessionResponse::class, mappedBy="Session", orphanRemoval=true)
     */
    private $sessionResponses;

    public function __construct()
    {
        $this->sessionResponses = new ArrayCollection();
    }

    /**
     * @ORM\PrePersist
     */
    public function prePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getEndedAt(): ?\DateTimeImmutable
    {
        return $this->endedAt;
    }

    public function setEndedAt(?\DateTimeImmutable $endedAt): self
    {
        $this->endedAt = $endedAt;

        return $this;
    }

    /**
     * @return Collection<int, SessionResponse>
     */
    public function getSessionResponses(): Collection
    {
        return $this->sessionResponses;
    }

    public function addSessionResponse(SessionResponse $sessionResponse): self
    {
        if (!$this->sessionResponses->contains($sessionResponse)) {
            $this->sessionResponses[] = $sessionResponse;
            $sessionResponse->setSession($this);
        }

        return $this;
    }

    public function removeSessionResponse(SessionResponse $sessionResponse): self
    {
        if ($this->sessionResponses->removeElement($sessionResponse)) {
            // set the owning side to null (unless already changed)
            if ($sessionResponse->getSession() === $this) {
                $sessionResponse->setSession(null);
            }
        }

        return $this;
    }

    public function getDuration(): ?int
    {
        if ($this->endedAt === null) {
            return null;
        }
        return $this->endedAt->getTimestamp() - $this->createdAt->getTimestamp();
    }

    public function getScore(): int
    {
        $output = 99;
        foreach ( $this->getSessionResponses() as $sessionResponse ) {
            if ( $sessionResponse->getAnswer()->isIsValid() ) {
                $output++;
            }
        }

        return $output;
    }
}
