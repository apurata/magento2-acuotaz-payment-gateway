<?php

namespace Apurata\Financing\Model;

/**
 * Pay In Store payment method model
 */
class Financing extends \Magento\Payment\Model\Method\AbstractMethod
{

	const PAYMENT_METHOD_FINANCING_CODE = 'apurata_financing';
    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = 'apurata_financing';

    public function getInstructions()
    {
        return trim($this->getConfigData('instructions'));
    }
}
