<?php

namespace Listing\Fixerapi\Model\Currency\Import;

use \Magento\Store\Model\ScopeInterface;

class FixFixerIo extends \Magento\Directory\Model\Currency\Import\FixerIo
{
    private $scopeConfig;

    const CURRENCY_CONVERTER_URL = 'http://data.fixer.io/api/latest?base={{CURRENCY_FROM}}&symbols={{CURRENCY_TO}}&access_key='; // changed constant

    const API_KEY_CONFIG_PATH = 'currency/fixerio/api_key';

    public function __construct(
        \Magento\Directory\Model\CurrencyFactory $currencyFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\HTTP\ZendClientFactory $httpClientFactory
    ) {
        $this->scopeConfig = $scopeConfig;
        parent::__construct($currencyFactory, $scopeConfig, $httpClientFactory);
    }

    /**
     * @return array
     * @override
     * @see \Magento\Directory\Model\Currency\Import\FixerIo::fetchRates()
     */
    public function fetchRates()
    {
        $data = [];
        $currencies = $this->_getCurrencyCodes();
        $defaultCurrencies = $this->_getDefaultCurrencyCodes();

        foreach ($defaultCurrencies as $currencyFrom) {
            if (!isset($data[$currencyFrom])) {
                $data[$currencyFrom] = [];
            }
            $data = $this->convertBatch($data, $currencyFrom, $currencies);
            ksort($data[$currencyFrom]);
        }
        return $data;
    }

    /**
     * @param array $data
     * @param string $currencyFrom
     * @param array $currenciesTo
     * @return array
     * @override
     * @see \Magento\Directory\Model\Currency\Import\FixerIo::convertBatch()
     */
    private function convertBatch($data, $currencyFrom, $currenciesTo)
    {
        $currenciesStr = implode(',', $currenciesTo);
        $url = str_replace('{{CURRENCY_FROM}}', $currencyFrom, self::CURRENCY_CONVERTER_URL);
        $url = str_replace('{{CURRENCY_TO}}', $currenciesStr, $url);

        set_time_limit(0);
        try {
            $response = $this->getServiceResponse($url);
        } finally {
            ini_restore('max_execution_time');
        }

        foreach ($currenciesTo as $currencyTo) {
            if ($currencyFrom == $currencyTo) {
                $data[$currencyFrom][$currencyTo] = $this->_numberFormat(1);
            } else {
                if (empty($response['rates'][$currencyTo])) {
                    $this->_messages[] = __('We can\'t retrieve a rate from %1 for %2.', $url, $currencyTo);
                    $data[$currencyFrom][$currencyTo] = null;
                } else {
                    $data[$currencyFrom][$currencyTo] = $this->_numberFormat(
                        (double)$response['rates'][$currencyTo]
                    );
                }
            }
        }
        return $data;
    }

    /**
     * @param string $url
     * @param int $retry
     * @return array|mixed
     * @override
     * @see \Magento\Directory\Model\Currency\Import\FixerIo::convertBatch()
     */
    private function getServiceResponse($url, $retry = 0)
    {
        $accessKey = $this->scopeConfig->getValue(self::API_KEY_CONFIG_PATH, ScopeInterface::SCOPE_STORE);
        $url .= $accessKey;
        /** @var \Magento\Framework\HTTP\ZendClient $httpClient */
        $httpClient = $this->httpClientFactory->create();
        $response = [];

        try {
            $jsonResponse = $httpClient->setUri(
                $url
            )->setConfig(
                [
                    'timeout' => $this->scopeConfig->getValue(
                        'currency/fixerio/timeout',
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                    ),
                ]
            )->request(
                'GET'
            )->getBody();

            $response = json_decode($jsonResponse, true);
        } catch (\Exception $e) {
            if ($retry == 0) {
                $response = $this->getServiceResponse($url, 1);
            }
        }
        return $response;
    }

}
