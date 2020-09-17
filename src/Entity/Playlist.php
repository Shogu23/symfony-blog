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
    private $song;

    public function __construct()
    {
        $this->song = new ArrayCollection();
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
    public function getSong(): Collection
    {
        return $this->song;
    }

    public function addSong(Song $song): self
    {
        if (!$this->song->contains($song)) {
            $this->song[] = $song;
            $song->setPlaylist($this);
        }

        return $this;
    }

    public function removeSong(Song $song): self
    {
        if ($this->song->contains($song)) {
            $this->song->removeElement($song);
            // set the owning side to null (unless already changed)
            if ($song->getPlaylist() === $this) {
                $song->setPlaylist(null);
            }
        }

        return $this;
    }
}
