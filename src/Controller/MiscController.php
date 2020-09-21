<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class MiscController extends AbstractController
{

    private function httpPost($url, $data){
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    } 

    /**
     * @Route("/misc/sms", name="sms_misc")
     */
    public function sms()
    {
        $errors = [];
        $safe = [];

        // Je m'assure que le formulaire est envoyé
        if(!empty($_POST)){
            // Permet de retirer les tags HTML et les espaces en début et fin de chaine sur l'ensemble du post ('trim')
            // C'est donc du nettoyage de données
            // ma variable $safe contiendra exactement les mêmes données que $_POST (mais clean)
            $safe = array_map('trim', array_map('strip_tags', $_POST));

            // On vérifie la syntaxe du numéro de telephone
            if(!preg_match("#[0][- \.?]?[6-7][- \.?]?([0-9][- \.?]?){8}$#", $safe['phone'])){
                $errors[] = 'Votre numero de '.$safe['phone']. 'n\'est pas valide';
            }

            if(strlen($safe['phone']) < 1 || strlen($safe['phone']) > 120){
                $errors[] = 'Votre message doit comporter entre 1 et 120 caractères';
            }

            if(count($errors) === 0){
                
                $phone = str_replace([' ', '-', '.'], '', $safe['phone']);
                $token = md5(date("dmY") . 'AxWeb6731@');

                $this->httpPost('https://axessweb.io/api/sendSMS',
                [
                    'receiver' 	=> $phone,
                    'message'	=> $safe['content'],
                    'passphrase'=> $token,
                ]);

                $this->addFlash('success', 'Message envoyé');
                
            }
            else {
                $this->addFlash('danger', implode('<br>', $errors));
            }
            
            

        }
        return $this->render('misc/sms.html.twig');
    }
}
