<?php

namespace App\Controller;

use App\Entity\DTO\CurrencyInquiryDTO;
use App\Form\CurrencyInquiryType;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
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
    public function mainPage(Request $request)
    {

        $currencyInquiryDTO = new CurrencyInquiryDTO();
        $form = $this->createForm(CurrencyInquiryType::class, $currencyInquiryDTO);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid())
        {

            $currencyInquiry = $form->getData();

            $startDate = $currencyInquiry->getStartAt()->format('Y-m-d');
            $endDate = $currencyInquiry->getEndAt()->format('Y-m-d');
            $currency = $currencyInquiry->getCurrency();

            if(strtotime($startDate)<strtotime(self::FIRST_DATA_IN_API)){
                return null;
            }

            $today = new DateTime('NOW');
            $today = $today->format('Y-m-d');
            if(strtotime($startDate)>strtotime($endDate) || strtotime($endDate)>strtotime($today)){
                return null;
            }

            $client = new Client([
                'base_uri' => self::NBP_API,
            ]);

            $uri = 'exchangerates/rates/c/'.$currency.'/'.$startDate.'/'.$endDate.self::DATA_FORMAT_API;
            $response = $client->request('GET', $uri);
            $responseAPI = json_decode($response->getBody()->getContents());

            if($responseAPI != NULL)
            {
                $viewData = [
                    'form' => $form->createView(),
                    'data' => $responseAPI,
                ];

                return $this->render('inquiry.html.twig', $viewData);

            }
        }

        $viewData = [
            'form' => $form->createView(),
        ];


        return $this->render('inquiry.html.twig', $viewData);
    }
}