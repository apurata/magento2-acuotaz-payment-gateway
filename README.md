# magento2-acuotaz-payment-gateway

## Installation

- Download the last version plugin with composer:

    * composer require apurata/financing:"0.3.*"

- Upgrade and compile page, to add apurata plugin:

    * magento setup:upgrade

    * magento setup:di:compile

- Clean cache page:

    * magento cache:clean

- If the page is in production mode, you must generate the static files again:

    * bin/magento setup:static-content:deploy

- Check plugin status:

    * magento module:status Apurata_Financing

- If the status is disable:

    * magento module:enable Apurata_Financing --clear-static-content

- If you have permission problems:

    * chmod 777 -R var/ pub/ generated/





