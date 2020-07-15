<?php

namespace App\Repository;

use App\Entity\DTO\CurrencyInquiryDTO;
use App\Exception\DateException;
use DateTime;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;

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
        $currencyInquiryDTO->setStartAt($currencyInquiryDTO->getStartAt()->format('Y-m-d'));
        $currencyInquiryDTO->setEndAt($currencyInquiryDTO->getEndAt()->format('Y-m-d'));

        try
        {
            $this->checkIfDatesAreCorrect($currencyInquiryDTO);
        }
        catch (DateException $dateException)
        {
            return null;
        }

        $this->setStartDateAtFinancialExchangeDay($currencyInquiryDTO);

        $client = new Client([
            'base_uri' => self::NBP_API,
        ]);

        $uri = 'exchangerates/rates/c/'.$currencyInquiryDTO->getCurrency().'/'.$currencyInquiryDTO->getStartAt().'/'.$currencyInquiryDTO->getEndAt().self::DATA_FORMAT_API;
        $response = $client->request('GET', $uri);
        $responseAPI = json_decode($response->getBody()->getContents());

        return $responseAPI;
    }

    private function setStartDateAtFinancialExchangeDay(CurrencyInquiryDTO $currencyInquiryDTO)
    {
        if (strtotime($currencyInquiryDTO->getStartAt()) != strtotime(self::FIRST_DATA_IN_API)) {

            $client = new Client([
                'base_uri' => self::NBP_API,
            ]);

            $break = false;
            while ($break == false) {
                $uri = 'exchangerates/rates/c/' . $currencyInquiryDTO->getCurrency() . '/' . $currencyInquiryDTO->getStartAt() . self::DATA_FORMAT_API;

                try
                {
                    $response = $client->request('GET', $uri);
                    $break = true;
                }
                catch (ClientException $exception)
                {
                    if ($exception->getCode() == 404)
                    {
                        $currencyInquiryDTO->setStartAt(date('Y-m-d', strtotime($currencyInquiryDTO->getStartAt() . ' -1day')));
                    }
                    else
                    {
                        //todo error occured (to handle)
                    }
                }
            }
        }
    }

    private function checkIfDatesAreCorrect(CurrencyInquiryDTO $currencyInquiryDTO)
    {
        if(strtotime($currencyInquiryDTO->getStartAt())<strtotime(self::FIRST_DATA_IN_API)){
            throw new DateException();
        }

        $today = new DateTime('NOW');
        $today = $today->format('Y-m-d');
        if(strtotime($currencyInquiryDTO->getStartAt())>strtotime($currencyInquiryDTO->getEndAt()) || strtotime($currencyInquiryDTO->getEndAt())>strtotime($today)){
            throw new DateException();
        }
    }
}
