<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class InquiryController extends AbstractController
{
    public function __construct()
    {
    }

    /**
     * @Route("/", name="inquiry")
     */
    public function mainPage(){

        return $this->render('inquiry.html.twig');
    }
}