<?php

namespace App\Controller;

use App\Entity\Playlist;
use App\Entity\Song;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class SongController extends AbstractController
{

    public $allowLinks = ['www.youtube.com', 'youtu.be', 'm.youtube.com'];
    /**
     * @Route("/song/add", name="song_add")
     */
    public function add()
    {
        $errors = [];
        $safe = [];

        // Je me connecte à la base de données
        $em = $this->getDoctrine()->getManager();
        $playlists = $this->getDoctrine()->getRepository(Playlist::class)->findBy([], ['id' => 'desc']);

        // Je m'assure que le formulaire est envoyé
        if (!empty($_POST)) {
            // Permet de retirer les tags HTML et les espaces en début et fin de chaine sur l'ensemble du post ('trim')
            // C'est donc du nettoyage de données
            // ma variable $safe contiendra exactement les mêmes données que $_POST (mais clean)
            $safe = array_map('trim', array_map('strip_tags', $_POST));

            if (strlen($safe['title']) < 3 || strlen($safe['title']) > 100) {
                $errors[] = 'Votre chanson doit comporter entre 3 et 100 caractères';
            }

            // Cette erreur, dans une utilisation normale elle ne s'affichera pas
            // Je sélectionne la playlist dans la bas de données.. si elle n'existe pas $selected_playlist sera égal à "null"
            if (isset($safe['name']) && !empty($safe['name'])) {
                $selected_playlist = $this->getDoctrine()->getRepository(Playlist::class)->find($safe['name']);
                if (empty($selected_playlist)) {
                    $errors[] = 'La playlist sélectionnée n\'existe pas';
                }
            } else {
                $errors[] = 'La playlist sélectionnée n\'existe pas';
            }

            if(empty($safe['link'])){
                $errors[] = 'Saisissez un lien Youtube';
            } else {
                // Je valide l'url
                // le parse decoupe en plusieurs choses, host, port, user etc..
                if ((!filter_var($safe['link'], FILTER_VALIDATE_URL)) || !in_array(parse_url($safe['link'], PHP_URL_HOST), $this->allowLinks)) {
                    $errors[] = 'Ceci n\'est pas une url valide';
                }
            }

            if (isset($_FILES['file']) && !empty($_FILES['file']) && $_FILES['file']['error'] != UPLOAD_ERR_NO_FILE) {

                if ($_FILES['file']['error'] != UPLOAD_ERR_OK) {
                    $errors[] = 'Une erreur est survenue lors du transfert de fichier';
                } else {
                    // On génère un nouveau nom de fichier
                    $extension = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
                    $filename = md5(uniqid()) . '.' . $extension;
                    $allowExtensions = ['mp3'];
                    $allowMimeTypes = ['audio/mpeg', 'audio/mp3'];

                    // On vérifie si l'extension et le type sont absents des tableaux
                    if (!in_array(strtolower($extension), $allowExtensions) || !in_array($_FILES['file']['type'], $allowMimeTypes)) {
                        $errors[] = 'mp3 uniquement!';
                    } else {
                        $sizeMax = 10 * 1024 * 1024; // en octet
                        // On vérifie si la taille dépasse le maximum
                        if ($_FILES['file']['size'] > $sizeMax) {
                            $errors[] = 'Le mp3 est trop volumineuse (10mo maximum)';
                        }
                    }
                }
            } // Fin isset($_FILES['file']) && !empty($_FILES['file']) && $_FILES['file']['error'] != UPLOAD_ERR_NO_FILE
            else {
                $errors[] = 'Vous devez joindre un mp3';
            }

            // Compte le nombre d'éléments dans le tableau $errors
            // Je suis sur que l'utilisateur n'a pas fait d'erreur
            if (count($errors) === 0) {

                // Je sauvegarde mon mp3
                $folderDestination = $_SERVER['DOCUMENT_ROOT'] . 'assets/uploads/' . $filename;
                // C:/xampp/htdocs/symfony-blog/public/assets/uploads/nom_mp3.jpg

                if (move_uploaded_file($_FILES['file']['tmp_name'], $folderDestination)) {
                    // Le mp3 est bien sauvegardée
                    // Je mets le chemin à partir de public/
                    $filenameInDb = 'assets/uploads/' . $filename;
                };
                // Je selectionne la table dans la quelle je travaille
                $song = new Song();
                $song->setTitle($safe['title']);
                $song->setLink($identCodeYoutube ?? null);
                $song->setFile($filenameInDb ?? null); // L'image, le ($filenameInDb ?? null) => operateur logique, renvoi soit $filenameInDb si, si n'existe pas renvoi null
                // Je vais chercher les "playlists" dans le controller Playlist.
                $playlist = $em->getRepository(Playlist::class)->find($safe['name']);
                $song->setPlaylist($playlist);
                $em->persist($song);
                $em->flush();
                $this->addFlash('success', 'Bravo votre playlist a été modifiée');
            } else {
                $this->addFlash('danger', implode('<br>', $errors));
            }
        }
        return $this->render('song/add.html.twig', [
            'playlists' => $playlists,
        ]);
    }

    /**
     * @Route("/song/view/{id_song}", name="song_view")
     */
    public function view(int $id_song)
    {
        $em = $this->getDoctrine()->getManager();
        $song = $em->getRepository(Song::class)->find($id_song);

        return $this->render('song/view.html.twig', [
            'song' => $song,
        ]);
    }
}