<?php

namespace App\Repository;

use App\Entity\DTO\CurrencyInquiryDTO;
use App\Exception\DateException;
use DateTime;
use GuzzleHttp\Client;

class CurrencyInquiryRepository
{
    const NBP_API = 'http://api.nbp.pl/api/';
    const MAX_DAYS_FOR_QUERY = 367;
    const DATA_FORMAT_API = '/?format=json';
    const FIRST_DATA_IN_API = '2002-01-02';

    public function __construct()
    {
    }

    public function getDataFromApi(CurrencyInquiryDTO $currencyInquiryDTO)
    {
        $startDate = $currencyInquiryDTO->getStartAt()->format('Y-m-d');
        $endDate = $currencyInquiryDTO->getEndAt()->format('Y-m-d');
        $currency = $currencyInquiryDTO->getCurrency();

        try
        {
            $this->checkDate($startDate, $endDate);
        }
        catch (DateException $dateException)
        {
            return null;
        }

        $client = new Client([
            'base_uri' => self::NBP_API,
        ]);

        $uri = 'exchangerates/rates/c/'.$currency.'/'.$startDate.'/'.$endDate.self::DATA_FORMAT_API;
        $response = $client->request('GET', $uri);
        $responseAPI = json_decode($response->getBody()->getContents());

        return $responseAPI;
    }

    private function checkDate($startDate, $endDate)
    {
        if(strtotime($startDate)<strtotime(self::FIRST_DATA_IN_API)){
            throw new DateException();
        }

        $today = new DateTime('NOW');
        $today = $today->format('Y-m-d');
        if(strtotime($startDate)>strtotime($endDate) || strtotime($endDate)>strtotime($today)){
            throw new DateException();
        }
    }
}
