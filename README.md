# magento2-fixerapi


As of March 6th 2018, the legacy Fixer API (api.fixer.io) is deprecated and a completely re-engineered API is now 
accessible at https://data.fixer.io/api/ The core structure of the old API has remained unchanged, 
and need to perform a few simple changes in integration.

Existing api format :  http://api.fixer.io/latest?base={{CURRENCY_FROM}}&symbols={{CURRENCY_TO}
its mentioned in core module, path : [root]/vendor/magento/module-directory/Model/Currency/Import/FixerIo.php
 
Updated api format : http://data.fixer.io/api/latest?access_key=FIXER_API_KEY={{CURRENCY_FROM}}&symbols={{CURRENCY_TO}

#After installing this module new API key is integrated in admin panel. Admin panel > Store > Configuration > Currency Setup > Fixer.io > Api key


The model file will rewrite the core model API structure to updated format.

Currency conversion API is based on the API plan purchased from https://apilayer.com/ 
If of the currency symbol value is not converting from fixer api, 
The list of unused currency symbol restricted from admin panel. 
Admin panel > Store > Configuration > Currency Setup >  Currency Options > Allowed currencies OR upgrade your plan.
