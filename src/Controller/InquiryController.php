<?php

namespace App\Controller;

use App\Entity\DTO\CurrencyInquiryDTO;
use App\Form\CurrencyInquiryType;
use App\Repository\CurrencyInquiryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class InquiryController extends AbstractController
{
    private $currencyInquiryRepository;

    public function __construct(CurrencyInquiryRepository $currencyInquiryRepository)
    {
        $this->currencyInquiryRepository = $currencyInquiryRepository;
    }

    /**
     * @Route("/", name="inquiry")
     */
    public function mainPage(Request $request)
    {
        $currencyInquiryDTO = new CurrencyInquiryDTO();
        $form = $this->createForm(CurrencyInquiryType::class, $currencyInquiryDTO);
        $viewData = [
            'form' => $form->createView(),
        ];
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $currencyInquiryDTO = $form->getData();
            $responseAPI = $this->currencyInquiryRepository->getDataFromApi($currencyInquiryDTO);

            if ($responseAPI != null) {
                $viewData['data'] = $responseAPI;
            }
        }

        return $this->render('inquiry.html.twig', $viewData);
    }
}
