<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Routing\Annotation\Route;

class MiscController extends AbstractController
{
    // Cette fonction envoi les informations de mon formulaire vers l'api et me renvoi une réponse
    private function httpPost($url, $data)
    {
        try {
            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($curl);
            curl_close($curl);
            return $response;
        } catch (Exception $e) {
            echo "Error caught: " . $e->getMessage();
        }
    }

    /**
     * @Route("/misc/sms", name="sms_send")
     */
    public function sms()
    {
        $errors = [];
        $safe = [];

        // Je m'assure que le formulaire est envoyé
        if (!empty($_POST)) {
            // Permet de retirer les tags HTML et les espaces en début et fin de chaine sur l'ensemble du post ('trim')
            // C'est donc du nettoyage de données
            // ma variable $safe contiendra exactement les mêmes données que $_POST (mais clean)
            $safe = array_map('trim', array_map('strip_tags', $_POST));

            // On vérifie la syntaxe du numéro de telephone
            if (preg_match("#[0][- \.?]?[6-7][- \.?]?([0-9][- \.?]?){8}$#", $safe['phone'])) {
                $phone = str_replace(['.', '-', ' '], '', $safe['phone']);
            } elseif (preg_match("#[+][- \.?]?[3][3][- \.?]?[6-7][- \.?]?([0-9][- \.?]?){8}$#", $safe['phone'])) {
                $phone = str_replace(['.', '-', ' ', '+33'], ['', '', '', '0'], $safe['phone']);
            } else {
                $errors[] = 'Votre numéro de téléphone doit être valide';
            }

            if (strlen($safe['phone']) < 1 || strlen($safe['phone']) > 120) {
                $errors[] = 'Votre message doit comporter entre 1 et 120 caractères';
            }

            if (count($errors) === 0) {

                $phone = str_replace([' ', '-', '.'], '', $safe['phone']);
                $token = md5(date("dmY") . 'AxWeb6731@');

                $errorApi = $this->httpPost(
                    'https://axessweb.io/api/sendSMS',
                    [
                        'receiver'   => $phone,
                        'message'    => $safe['content'],
                        'passphrase' => $token,
                    ]
                );

                if (json_decode($errorApi,TRUE) == 'OK') {
                    $this->addFlash('success', 'Message envoyé');
                    return $this->redirectToRoute('default_index');
                } else {
                    $this->addFlash('danger', 'Problème d\'envoi message coté serveur');
                }
                
            } else {
                $this->addFlash('danger', implode('<br>', $errors));
            }
        }
        return $this->render('misc/sms.html.twig');
    }
}
