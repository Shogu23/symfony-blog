<?php

namespace App\Controller;

use App\Entity\Playlist;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class PlaylistController extends AbstractController
{
    /**
     * @Route("/playlist/add", name="playlist_add")
     */
    public function add()
    {
        $errors = [];
        $safe = [];

        // Je m'assure que le formulaire est envoyé
        if(!empty($_POST)){
            // Permet de retirer les tags HTML et les espaces en début et fin de chaine sur l'ensemble du post ('trim')
            // C'est donc du nettoyage de données
            // ma variable $safe contiendra exactement les mêmes données que $_POST (mais clean)
            $safe = array_map('trim', array_map('strip_tags', $_POST));
            
            if(strlen($safe['name']) < 3 || strlen($safe['name']) > 50){
                $errors[] = 'Votre playlist doit comporter entre 3 et 50 caractères';
            }

            // Compte le nombre d'éléments dans le tableau $errors
            // Je suis sur que l'utilisateur n'a pas fait d'erreur
            if(count($errors) === 0){
                // Je me connecte à la base de données
                $em = $this->getDoctrine()->getManager();

                // Je selectionne la table dans la quelle je travaille
                $playlist = new Playlist();
                $playlist->setName($safe['name']);

                $em->persist($playlist);
                $em->flush();

                $this->addFlash('success', 'Bravo votre playlist a été créée');
                
            }
            else {
                $this->addFlash('danger', implode('<br>', $errors));
            }

        }

        return $this->render('playlist/add.html.twig');
    }
}
