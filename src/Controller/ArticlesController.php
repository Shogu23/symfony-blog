<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Articles; // Appelle le modèle pour insérer dans la table articles


class ArticlesController extends AbstractController
{
    /**
     * Page d'ajout d'article
     * @Route("/articles/add", name="articles_add")
     */
    public function add()
    {
        $errors = [];
        $safe = [];
        // Je m'assure que le formulaire ai été envoyé
        if(!empty($_POST)){

            // Permet de retirer les tags HTML et les espaces en début et fin de chaine sur l'ensemble du post ('trim')
            // C'est donc du nettoyage de données
            // ma variable $safe contiendra exactement les mêmes données que $_POST (mais clean)
            $safe = array_map('trim', array_map('strip_tags', $_POST));

            // On Valide les champs
            if(strlen($safe['title']) < 3 || strlen($safe['title']) > 100){
                $errors[] = 'Votre titre doit comporter entre 3 et 100 caractères';
            }
            if(strlen($safe['content']) < 20 || strlen($safe['content']) > 1500){
                $errors[] = 'Votre contenu doit comporter entre 20 et 1500 caractères';
            }
            if(isset($_FILES['image']) && !empty($_FILES['image']) && $_FILES['image']['error'] != UPLOAD_ERR_NO_FILE){
                
                if($_FILES['image']['error'] != UPLOAD_ERR_OK){
                    $errors[] = 'Une erreur est survenue lors du transfert de fichier';
                }
                else{
                    // On génère un nouveau nom de fichier
                    $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                    $filename = md5(uniqid()).'.'.$extension;

                    $allowExtensions = ['png', 'jpg', 'jpeg', 'jfif', 'pjpeg', 'pjp'];
                    $allowMimeTypes = ['image/png', 'image/jpeg', 'image/pjpeg'];

                    // On vérifie si l'extension et le type sont absents des tableaux
                    if(!in_array(strtolower($extension), $allowExtensions) || !in_array($_FILES['image']['type'], $allowMimeTypes)){
                        $errors[] = 'Le type de l\'image est incorrect (PNG ou JPG uniquement';
                    }
                    else{
                        $sizeMax = 3 * 1024 * 1024; // en octet
                        // On vérifie si la taille dépasse le maximum
                        if($_FILES['image']['size'] > $sizeMax){
                            $errors[] = 'L\'image est trop volumineuse (3mo maximum)';
                        }
                    }
                }
                
            } // Fin isset($_FILES['image']) && !empty($_FILES['image']) && $_FILES['image']['error'] != UPLOAD_ERR_NO_FILE
            else {
                $errors[] = 'Vous devez sélectionner une image';
            }
            
            // Compte le nombre d'éléments dans le tableau $errors
            // Je suis sur que l'utilisateur n'a pas fait d'erreur
            if(count($errors) === 0){
                
                // Je peux donoc sauvegarder mon image
                // Mais également faire mon insertion en base de données
                // Pour l'image, en bdd je ne sauvegarde que le nom ($filename)
                // Et le fichier je l'enregistre dans un répertoire /uploads/
                
                // $_SERVER['DOCUMENT_ROOT'] : donne le chemin d'accès jusqu'au répertoire / public
                // C'est me répertoire de mon site web pour les internautes

                $folderDestination = $_SERVER['DOCUMENT_ROOT'].'assets/uploads/'.$filename;
                // C:/xampp/htdocs/symfony-blog/public/assets/uploads/nom_image.jpg

                if(move_uploaded_file($_FILES['image']['tmp_name'], $folderDestination)){
                    // L'image est bien sauvegardée
                    // Je mets le chemin à partir de public/
                    $filenameInDb = 'assets/uploads/'.$filename;
                };

                // On appelle le modèle (donc la DB)


                // La variable $em permet de se connecter à la base de données c'est un peu l'équivalent du "new PDO()"
                $em = $this->getDoctrine()->getManager();

                // Je sélectionne la table dans la quelle je travaille
                $article = new Articles();
                $article->setTitle($safe['title']); // Je défini le titre
                $article->setContent($safe['content']); // Le contenu
                $article->setPicture($filenameInDb ?? null); // L'image, le ($filenameInDb ?? null) => operateur logique, renvoi soit $filenameInDb si, si n'existe pas renvoi null
                $article->setCreatedAt(new \DateTime('now'));

                // Equivalent du "prepare()" que vous faisiez en PHP classique
                $em->persist($article);
                $em->flush(); // Equivalent de notre "execute()"

            }
            else {
                dd($errors);
            }
        }

        return $this->render('articles/add.html.twig', [
            'controller_name' => 'ArticlesController',
        ]);
    }

    /**
     * Page listant tous les articles
     */
    public function list()
    {
        return $this->render('articles/list.html.twig', [
            'controller_name' => 'ArticlesController',
        ]);
    }

    /**
     * Page de detail d'un article
     */
    public function view()
    {
        return $this->render('articles/view.html.twig', [
            'controller_name' => 'ArticlesController',
        ]);
    }

    /**
     * Page mise a jour d'article
     */
    public function update()
    {
        return $this->render('articles/update.html.twig', [
            'controller_name' => 'ArticlesController',
        ]);
    }

    /**
     * Page de suppression d'un article
     */
    public function delete()
    {
        return $this->render('articles/delete.html.twig', [
            'controller_name' => 'ArticlesController',
        ]);
    }

}
