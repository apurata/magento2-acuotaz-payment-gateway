# magento2 acuotaz payment gateway

## Installation
- Get the plugin

   Using composer, execute in Magento directory:
   
   ```
    composer require apurata/financing:"0.4.*"
   ```

- Upgrade and compile page, to add apurata plugin:

   ```
    magento setup:upgrade

    magento setup:di:compile
   ```

- Clean cache page:

   ```
    magento cache:clean
   ```

- If the page is in production mode, you must generate the static files again:

   ```
    bin/magento setup:static-content:deploy
   ```

- Check plugin status:

   ```
    magento module:status Apurata_Financing
   ```

- If the status is disable:

   ```
    magento module:enable Apurata_Financing --clear-static-content
   ```

## Update to the latest version

- Use composer:
```
composer update apurata/financing
```
## Configuration

1. On the Admin sidebar, click Stores > Settings > Configuration.
2. In the panel on the left, choose Sales > Payment Methods > Apurata Financing.
3. Enter Apurata client ID and Secret token.
4. Select the 'Generate invoice automatically' option if you want invoices to be generated automatically. This option is disabled by default.
5. Save configuration.

## aCuotaz order state

We just use three events to change the order status  in the ecommerce:

   - funded: Disbursement made. Change order status to "processing".
   - rejected: Credit assessment denied user. Change order status to "canceled".
   - canceled: Application canceled. Change order status to "canceled".
  
For the rest of the events we add notes in each order, in the "Comments history" section.


