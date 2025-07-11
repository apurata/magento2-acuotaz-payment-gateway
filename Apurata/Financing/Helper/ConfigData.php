<?php

namespace Apurata\Financing\Helper;

class ConfigData
{
    const PAYMENT_CODE = 'apurata_financing';
    const SECRET_TOKEN_CONFIG_PATH = 'payment/apurata_financing/secret_token';
    const KEY_APURATA_CLIENT_ID = 'apurata_client_id';
    const KEY_ACTIVE = 'active';
    const GENERATE_INVOICE_CONFIG_PATH = 'payment/apurata_financing/generate_invoice';

    const APURATA_DOMAIN = 'https://apurata.com';
    const APURATA_STATIC_DOMAIN = 'https://static.apurata.com';
    const APURATA_LANDING_CONFIG = '/pos/client/landing_config';
    const APURATA_ADD_ON = '/pos/pay-with-apurata-add-on/';
    const APURATA_LANDING_VERSION = 'pos_generic';
    const APURATA_CREATE_ORDER_URL = '/pos/order/create';

    const FINANCING_INTENT_PATH = 'apuratafinancing/order/intent';
    const FINANCING_ADD_ON_PATH = 'apuratafinancing/order/requestaddon';
    const FINANCING_FAIL_URL = 'apuratafinancing/order/cancelation';
    const FINANCING_SUCCESS_URL = 'apuratafinancing/order/success';
    const ALLOW_HTTP = True;
}
