# magento2 acuotaz payment gateway

## Installation
- Get the plugin

   Option 1: Using composer, execute in Magento directory:
   
   ```
    composer require apurata/financing:"0.4.*"
   ```
   
   Option 2: Direct Download, place the zip in (Magento dir)/app/code/:

   [Link de descarga](https://github.com/apurata/magento2-acuotaz-payment-gateway/archive/refs/tags/0.4.0.zip)
  
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
 
- If you have permission problems:

```
    chmod 777 -R var/ pub/ generated/
```

## Configuration

1. On the Admin sidebar, click Stores > Settings > Configuration.
2. In the panel on the left, choose Sales > Payment Methods > Apurata Financing.
3. Enter Apurata client ID and Secret token.
4. Save configuration.

## aCuotaz order state

We just use three events to change the order status  in the ecommerce:

   - funded: Disbursement made. Change order status to "processing".
   - rejected: Credit assessment denied user. Change order status to "canceled".
   - canceled: Application canceled. Change order status to "canceled".
  
For the rest of the events we add notes in each order, in the "Comments history" section.


