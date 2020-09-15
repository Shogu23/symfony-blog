<?php

namespace App\Entity;

use App\Repository\PlaylistRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=PlaylistRepository::class)
 */
class Playlist
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=150)
     */
    private $name;

    /**
     * @ORM\OneToMany(targetEntity=Song::class, mappedBy="playlist")
     */
    private $playlist;

    public function __construct()
    {
        $this->playlist = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    /**
     * @return Collection|Song[]
     */
    public function getPlaylist(): Collection
    {
        return $this->playlist;
    }

    public function addPlaylist(Song $playlist): self
    {
        if (!$this->playlist->contains($playlist)) {
            $this->playlist[] = $playlist;
            $playlist->setPlaylist($this);
        }

        return $this;
    }

    public function removePlaylist(Song $playlist): self
    {
        if ($this->playlist->contains($playlist)) {
            $this->playlist->removeElement($playlist);
            // set the owning side to null (unless already changed)
            if ($playlist->getPlaylist() === $this) {
                $playlist->setPlaylist(null);
            }
        }

        return $this;
    }
}
