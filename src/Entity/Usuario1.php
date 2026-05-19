<?php

namespace App\Entity;

use App\Repository\Usuario1Repository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use App\Entity\Grupo;
use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface; // <--- Importamos la interfaz del 2FA

#[ORM\Entity(repositoryClass: Usuario1Repository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class Usuario1 implements UserInterface, PasswordAuthenticatedUserInterface, TwoFactorInterface // <--- Implementamos la interfaz
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    /**
     * @var Collection<int, Equipo>
     */
    #[ORM\OneToMany(targetEntity: Equipo::class, mappedBy: 'propietario')]
    private Collection $equipos;

    #[ORM\ManyToOne(inversedBy: 'users')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Grupo $grupo = null;

    // --- NUEVO CAMPO PARA EL SECRETO DE GOOGLE AUTHENTICATOR ---
    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $totpSecret = null;

    public function __construct()
    {
        $this->equipos = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
    }

    /**
     * @return Collection<int, Equipo>
     */
    public function getEquipos(): Collection
    {
        return $this->equipos;
    }

    public function addEquipo(Equipo $equipo): static
    {
        if (!$this->equipos->contains($equipo)) {
            $this->equipos->add($equipo);
            $equipo->setPropietario($this);
        }

        return $this;
    }

    public function removeEquipo(Equipo $equipo): static
    {
        if ($this->equipos->removeElement($equipo)) {
            if ($equipo->getPropietario() === $this) {
                $equipo->setPropietario(null);
            }
        }

        return $this;
    }

    public function getGrupo(): ?Grupo
    {
        return $this->grupo;
    }

    public function setGrupo(?Grupo $grupo): static
    {
        $this->grupo = $grupo;
        return $this;
    }

    // =========================================================================
    // MÉTODOS OBLIGATORIOS PARA SCHEB TWO FACTOR BUNDLE (GOOGLE AUTHENTICATOR)
    // =========================================================================

    public function isTotpAuthenticationEnabled(): bool
    {
        // Se activa el 2FA si el usuario tiene un código secreto guardado
        return $this->totpSecret !== null;
    }

    public function getTotpAuthenticationUsername(): string
    {
        return (string) $this->email;
    }

    public function getTotpAuthenticationConfiguration(): ?\Scheb\TwoFactorBundle\Model\Totp\TotpConfigurationInterface
    {
        // Si no tiene secreto, no devolvemos configuración
        if (!$this->totpSecret) {
            return null;
        }
    
        return new \Scheb\TwoFactorBundle\Model\Totp\TotpConfiguration(
            $this->totpSecret,          // 1. Secreto
            'sha1',                    // 2. Algoritmo estándar
            30,                        // 3. Período (Entero obligatorio)
            6,                         // 4. Dígitos del código
            'Gestion Usuarios',        // 5. Nombre de tu App (Issuer)
            $this->getUserIdentifier() // 6. Identificador del usuario (Email)
        );
    }

    public function getTotpSecret(): ?string
    {
        return $this->totpSecret;
    }

    public function setTotpSecret(?string $totpSecret): self
    {
        $this->totpSecret = $totpSecret;
        return $this;
    }
}