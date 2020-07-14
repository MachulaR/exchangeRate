<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use GuzzleHttp\Client;

class InquiryController extends AbstractController
{
    const NBP_API = 'http://api.nbp.pl/api/';
    const MAX_DAYS_FOR_QUERY = 367;
    const DATA_FORMAT_API = '/?format=json';
    const FIRST_DATA_IN_API = '2002-01-02';

    public function __construct()
    {
    }

    /**
     * @Route("/", name="inquiry")
     */
    public function mainPage()
    {
        $client = new Client([
            'base_uri' => self::NBP_API,
        ]);

        $response = $client->request('GET', 'exchangerates/rates/c/USD/2020-01-02/2020-01-08/?format=json');
        $responseAPI = json_decode($response->getBody()->getContents());
//        dump($responseAPI);die;

        $viewData = [
            'data' => $responseAPI,
        ];


        return $this->render('inquiry.html.twig', $viewData);
    }
}