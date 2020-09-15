<?php

namespace App\Controller;

use App\Entity\Playlist;
use App\Entity\Song;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class SongController extends AbstractController
{
    /**
     * @Route("/song/add", name="song_add")
     */
    public function add()
    {
        $errors = [];
        $safe = [];

        $playlists = $this->getDoctrine()->getRepository(Playlist::class)->findBy([],['id' => 'desc']);

        // Je m'assure que le formulaire est envoyé
        if(!empty($_POST)){
            // Permet de retirer les tags HTML et les espaces en début et fin de chaine sur l'ensemble du post ('trim')
            // C'est donc du nettoyage de données
            // ma variable $safe contiendra exactement les mêmes données que $_POST (mais clean)
            $safe = array_map('trim', array_map('strip_tags', $_POST));
            
            if(strlen($safe['title']) < 3 || strlen($safe['title']) > 100){
                $errors[] = 'Votre chanson doit comporter entre 3 et 100 caractères';
            }

            // Compte le nombre d'éléments dans le tableau $errors
            // Je suis sur que l'utilisateur n'a pas fait d'erreur
            if(count($errors) === 0){
                // Je me connecte à la base de données
                $em = $this->getDoctrine()->getManager();

                // Je selectionne la table dans la quelle je travaille
                $song = new Song();
                $song->setTitle($safe['title']);
                // Je vais chercher les "playlists" dans le controller Playlist.
                $playlist = $em->getRepository(Playlist::class)->find($safe['playlist']);
                $song->setPlaylist($playlist);
                $em->persist($song);
                $em->flush();
                $this->addFlash('success', 'Bravo votre playlist a été modifiée');
            }
            else {
                $this->addFlash('danger', implode('<br>', $errors));
            }

        }
        return $this->render('song/add.html.twig', [
            'playlists' => $playlists,
        ]);
    }
}
