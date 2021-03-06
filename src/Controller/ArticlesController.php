<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use App\Entity\Articles; // Appelle le modèle pour insérer dans la table articles


class ArticlesController extends AbstractController
{
    /**
     * Page d'ajout d'article
     * @Route("/articles/add", name="articles_add")
     * @IsGranted("ROLE_ADMIN")
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
                        $errors[] = 'Le type de l\'image est incorrect (PNG ou JPG uniquement)';
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

                $this->addFlash('success', 'Bravo votre article a bien été enregistré');

            }
            else {
                // $this->addFlash('Niveau d'alert', 'message qu'on veut afficher' );
                /**
                 * La méthode addFlash est l'équivalent de $_SESSION['message']
                 * @see https://symfony.com/doc/current/controller.html#flash-messages
                 * On peut utiliser les niveaux d'alertes suivants. Ces niveaux d'alerte me permette de faire facilement du css
                 * danger
                 * warning
                 * info
                 * success
                 */
                // La fonction implode() permet de réunir les élements d'un tableau en une chaine s"parée ii par un <br>
                $this->addFlash('danger', implode('<br>', $errors));
            }
        }

        return $this->render('articles/add.html.twig', [
            'controller_name' => 'ArticlesController',
        ]);
    }

    /**
     * Page liste des articles
     * @Route("/articles/list", name="articles_list")
     */
    public function list()
    {
        // J'appelle ma base de données
        $em = $this->getDoctrine()->getManager();

        // J'accède à la table
        // La variable $articles, contient tout mes articles

        // Rappelle de la fonction findBy
        $articles = $em->getRepository(Articles::class)
                        ->findBy([], ['created_at' => 'DESC']);

        // Méthode findBy alternative
        // $em = $this->getDoctrine()->getRepository(Articles::class)->findBy([],['created_at' => 'desc']);
        
        return $this->render('articles/list.html.twig', [
            'articles' => $articles,
        ]);
    }

    /**
     * Page de detail d'un article
     * @Route("/article/view/{id_article}", name="article_view")
     */
    public function view(int $id_article)
    {
        // J'appelle ma base de données
        $em = $this->getDoctrine()->getManager();

        // J'accède à la table
        // La variable $article, contient mon article (by id)
        $article = $em->getRepository(Articles::class)
                        ->find($id_article);

        return $this->render('articles/view.html.twig', [
            'article' => $article,
        ]);
    }

    /**
     * Page de modification d'un article
     * @Route("/article/update/{id_article}", name="article_update")
     */
    public function update(int $id_article)
    {
        $em = $this->getDoctrine()->getManager();
        
        $update = $em->getRepository(Articles::class)
                        ->find($id_article);

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
                        $errors[] = 'Le type de l\'image est incorrect (PNG ou JPG uniquement)';
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
                // $errors[] = 'Vous devez sélectionner une image';
            }
            
            // Compte le nombre d'éléments dans le tableau $errors
            // Je suis sur que l'utilisateur n'a pas fait d'erreur
            if(count($errors) === 0){
                
                if(isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK && isset($filename)){
                    $folderDestination = $_SERVER['DOCUMENT_ROOT'].'assets/uploads/'.$filename;
                    // C:/xampp/htdocs/symfony-blog/public/assets/uploads/nom_image.jpg

                    if(move_uploaded_file($_FILES['image']['tmp_name'], $folderDestination)){
                        // L'image est bien sauvegardée
                        // Je mets le chemin à partir de public/
                        $filenameInDb = 'assets/uploads/'.$filename;

                        // Je supprime l'ancien fichier qui ne sera plus utiisé:
                        $old_picture = $_SERVER['DOCUMENT_ROOT'].$update->getPicture();
                        if(file_exists($old_picture) && !is_dir($old_picture)){
                            unlink($old_picture);
                        }
                    };
                }
                // On appelle le modèle (donc la DB)


                // La variable $em permet de se connecter à la base de données c'est un peu l'équivalent du "new PDO()"
                $em = $this->getDoctrine()->getManager();

                // Je sélectionne la table dans la quelle je travaille
                $update = new Articles();
                $update->setTitle($safe['title']); // Je défini le titre
                $update->setContent($safe['content']); // Le contenu
                $update->setPicture($filenameInDb ?? $update->getPicture() ?? null); // L'image, le ($filenameInDb ?? null) => operateur logique, renvoi soit $filenameInDb si, si n'existe renvoi null

                // Equivalent du "prepare()" que vous faisiez en PHP classique
                $em->persist($update);  // ligne optionnelle dans le cas dans un update
                $em->flush(); // Equivalent de notre "execute()"

                $this->addFlash('success', 'Bravo votre article a bien été mis à jour');

                return $this->redirectToRoute('article_view', ['id_article' => $update->getId()]);

            }
            else {
                // $this->addFlash('Niveau d'alert', 'message qu'on veut afficher' );
                /**
                 * La méthode addFlash est l'équivalent de $_SESSION['message']
                 * @see https://symfony.com/doc/current/controller.html#flash-messages
                 * On peut utiliser les niveaux d'alertes suivants. Ces niveaux d'alerte me permette de faire facilement du css
                 * danger
                 * warning
                 * info
                 * success
                 */
                // La fonction implode() permet de réunir les élements d'un tableau en une chaine séparée par un <br>
                $this->addFlash('danger', implode('<br>', $errors));
            }
        }
        return $this->render('articles/update.html.twig', [
            'update' => $update,
        ]);
    }

    /**
     * Page de suppression d'un article
     * @Route("/article/delete/{id_article}", name="article_delete")
     */
    public function delete(int $id_article)
    {
        // J'appelle ma base de données
        $em = $this->getDoctrine()->getManager();
        // J'accède à la table
        // La variable $article, contient mon article (by id)
        $article = $em->getRepository(Articles::class)
                        ->find($id_article);

        if(!empty($_POST)){

            if(isset($_POST['delete']) && $_POST['delete'] == 'yes'){
                // Je supprime
                $em->remove($article); // On supprime l'article
                $em->flush();

                $this->addFlash('success', 'Votre article a bien été supprimé');
                return $this->redirectToRoute('articles_list');
            }
        }

        return $this->render('articles/delete.html.twig', [
            'article' => $article,
        ]);
    }

}
