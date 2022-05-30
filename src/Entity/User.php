<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=UserRepository::class)
 */
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     */
    private $email;

    /**
     * @ORM\Column(type="json")
     */
    private $roles = [];

    /**
     * @var string The hashed password
     * @ORM\Column(type="string")
     * @Assert\NotCompromisedPassword
     * @Assert\Regex(
     *  pattern = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/m",
     *  match=true,
     *  message="Votre mot de passe doit comporter au moins huit caractères, dont des lettres majuscules et minuscules, un chiffre et un symbole."
     * )
     */
    private $password;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $firstName;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $lastName;

    /**
     * @ORM\Column(type="boolean")
     */
    private $needRenewPassword;

    /**
     * @ORM\OneToMany(targetEntity=UserPasswordToken::class, mappedBy="User", orphanRemoval=true)
     */
    private $userPasswordTokens;

    /**
     * @ORM\OneToMany(targetEntity=Session::class, mappedBy="user", orphanRemoval=true)
     */
    private $sessions;

    public function __construct()
    {
        $this->userPasswordTokens = new ArrayCollection();
        $this->sessions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @deprecated since Symfony 5.3, use getUserIdentifier instead
     */
    public function getUsername(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Returning a salt is only needed, if you are not using a modern
     * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function resetPassword(UserPasswordHasherInterface $passwordHasher, string $password = null): self
    {
        if ( !$password ) {
            $password = self::randomString();
        }
        $this->setPassword($passwordHasher->hashPassword($this, $password));

        return $this;
    }

    public static function randomString(int $length = 10): string
    {
        $output = '';
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789&é#{([-|è_ç^à@)]°+=}';
        while ( strlen($output) < $length ) {
            $output .= substr($chars, mt_rand(0, strlen($chars)), 1);
        }

        return $output;
    }

    public function isNeedRenewPassword(): ?bool
    {
        return $this->needRenewPassword;
    }

    public function setNeedRenewPassword(bool $needRenewPassword): self
    {
        $this->needRenewPassword = $needRenewPassword;

        return $this;
    }

    /**
     * @return Collection<int, UserPasswordToken>
     */
    public function getUserPasswordTokens(): Collection
    {
        return $this->userPasswordTokens;
    }

    public function addUserPasswordToken(UserPasswordToken $userPasswordToken): self
    {
        if (!$this->userPasswordTokens->contains($userPasswordToken)) {
            $this->userPasswordTokens[] = $userPasswordToken;
            $userPasswordToken->setUser($this);
        }

        return $this;
    }

    public function removeUserPasswordToken(UserPasswordToken $userPasswordToken): self
    {
        if ($this->userPasswordTokens->removeElement($userPasswordToken)) {
            // set the owning side to null (unless already changed)
            if ($userPasswordToken->getUser() === $this) {
                $userPasswordToken->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Session>
     */
    public function getSessions(): Collection
    {
        return $this->sessions;
    }

    public function addSession(Session $session): self
    {
        if (!$this->sessions->contains($session)) {
            $this->sessions[] = $session;
            $session->setUser($this);
        }

        return $this;
    }

    public function removeSession(Session $session): self
    {
        if ($this->sessions->removeElement($session)) {
            // set the owning side to null (unless already changed)
            if ($session->getUser() === $this) {
                $session->setUser(null);
            }
        }

        return $this;
    }
}
