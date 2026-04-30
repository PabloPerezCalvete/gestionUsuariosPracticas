<?php

namespace App\Entity;

use App\Repository\GrupoRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GrupoRepository::class)]
class Grupo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nombre = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $descripcion = null;

    // Relación con Usuario1
    #[ORM\OneToMany(mappedBy: 'grupo', targetEntity: Usuario1::class)]
    private Collection $users;

    public function __construct()
    {
        $this->users = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getNombre(): ?string { return $this->nombre; }
    public function setNombre(string $nombre): static { $this->nombre = $nombre; return $this; }

    public function getDescripcion(): ?string { return $this->descripcion; }
    public function setDescripcion(?string $descripcion): static { $this->descripcion = $descripcion; return $this; }

    
    public function getUsers(): Collection { return $this->users; }

    public function addUser(Usuario1 $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->setGrupo($this); 
        }
        return $this;
    }

    public function removeUser(Usuario1 $user): static
    {
        if ($this->users->removeElement($user)) {
            
            if ($user->getGrupo() === $this) {
                $user->setGrupo(null);
            }
        }
        return $this;
    }
}