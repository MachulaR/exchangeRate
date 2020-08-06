<?php

namespace App\Repository;

use App\Entity\DTO\CurrencyInquiryDTO;
use App\Exception\DateException;
use DateTime;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

class CurrencyInquiryRepository
{
    const NBP_API = 'http://api.nbp.pl/api/';
    const MAX_DAYS_FOR_QUERY = 367;
    const DATA_FORMAT_API = '/?format=json';
    const FIRST_DATA_IN_API = '2002-01-02';

    /**
     * @var FlashBagInterface
     */
    private $flashBag;

    /**
     * MainController constructor.
     * @param FlashBagInterface $flashBag
     */
    public function __construct(FlashBagInterface $flashBag)
    {
        $this->flashBag = $flashBag;
    }

    public function getDataFromApi(CurrencyInquiryDTO $currencyInquiryDTO)
    {
        $currencyInquiryDTO->setStartAt($currencyInquiryDTO->getStartAt()->format('Y-m-d'));
        $currencyInquiryDTO->setEndAt($currencyInquiryDTO->getEndAt()->format('Y-m-d'));

        try {
            $this->checkIfDatesAreCorrect($currencyInquiryDTO);
        } catch (DateException $dateException) {
            $this->flashBag->add('danger', 'Date must be between 2002-01-02 and today.');
            return null;
        }

        $this->setStartDateAtFinancialExchangeDay($currencyInquiryDTO);

        $responseAPI = $this->sendRequestToApi($currencyInquiryDTO);

        return $responseAPI;
    }

    private function setStartDateAtFinancialExchangeDay(CurrencyInquiryDTO $currencyInquiryDTO)
    {
        if (strtotime($currencyInquiryDTO->getStartAt()) != strtotime(self::FIRST_DATA_IN_API)) {
            $client = new Client([
                'base_uri' => self::NBP_API,
            ]);

            $break = false;
            $startDayChanged = false;
            while ($break == false) {
                $uri = 'exchangerates/rates/c/' . $currencyInquiryDTO->getCurrency() . '/' . $currencyInquiryDTO->getStartAt() . self::DATA_FORMAT_API;

                try {
                    $client->request('GET', $uri);
                    $break = true;
                    if ($startDayChanged) {
                        $this->flashBag->add(
                            'warning',
                            'The query started from the day the Financial exchange is closed. On that day, the data is identical to that for the last business day, it is '.
                            $currencyInquiryDTO->getStartAt().', which was displayed.'
                        );
                    }
                } catch (ClientException $exception) {
                    if ($exception->getCode() == 404) {
                        $currencyInquiryDTO->setStartAt(date('Y-m-d', strtotime($currencyInquiryDTO->getStartAt() . ' -1day')));
                        $startDayChangedage = true;
                    } else {
                        $this->flashBag->add('danger', 'Problem occurred. Try again later.');
                        $break = true;
                    }
                }
            }
        }
    }

    private function checkIfDatesAreCorrect(CurrencyInquiryDTO $currencyInquiryDTO)
    {
        if (strtotime($currencyInquiryDTO->getStartAt())<strtotime(self::FIRST_DATA_IN_API)) {
            throw new DateException();
        }

        $today = new DateTime('NOW');
        $today = $today->format('Y-m-d');
        if (strtotime($currencyInquiryDTO->getStartAt())>strtotime($currencyInquiryDTO->getEndAt()) || strtotime($currencyInquiryDTO->getEndAt())>strtotime($today)) {
            throw new DateException();
        }
    }

    private function sendRequestToApi(CurrencyInquiryDTO $currencyInquiryDTO)
    {
        $client = new Client([
            'base_uri' => self::NBP_API,
        ]);

        $numberOfQueriesToApi = $this->divideQuery($currencyInquiryDTO);
        $rates = [];
        for ($i =0; $i<$numberOfQueriesToApi; $i++) {
            $helpData = date('Y-m-d', strtotime($currencyInquiryDTO->getStartAt() .' +'.self::MAX_DAYS_FOR_QUERY.'day'));

            if (strtotime($currencyInquiryDTO->getEndAt())-(strtotime($helpData)) > 0) {
                $uri = 'exchangerates/rates/c/'.$currencyInquiryDTO->getCurrency().'/'.$currencyInquiryDTO->getStartAt().'/'.$helpData.self::DATA_FORMAT_API;
                $currencyInquiryDTO->setStartAt(date('Y-m-d', strtotime($helpData .' +1day')));
            } else {
                $uri = 'exchangerates/rates/c/'.$currencyInquiryDTO->getCurrency().'/'.$currencyInquiryDTO->getStartAt().'/'.$currencyInquiryDTO->getEndAt().self::DATA_FORMAT_API;
            }

            $response = $client->request('GET', $uri);
            $responseAPI = json_decode($response->getBody()->getContents());
            $rates = array_merge($rates, $responseAPI->rates);
        }
        $responseAPI->rates = $rates;
        return $responseAPI;
    }

    private function divideQuery(CurrencyInquiryDTO $currencyInquiryDTO)
    {
        $diff = strtotime($currencyInquiryDTO->getEndAt())-strtotime($currencyInquiryDTO->getStartAt());
        $diff = $diff / (60*60*24);
        if ($diff>self::MAX_DAYS_FOR_QUERY) {
            $numberOfLoops = ceil($diff/self::MAX_DAYS_FOR_QUERY);
        } else {
            $numberOfLoops = 1;
        }

        return $numberOfLoops;
    }
}
