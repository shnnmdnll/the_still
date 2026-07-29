<?php
// Dragonpay Merchant Credentials
// Palitan mo ito ng totoong Merchant ID at Secret Key mula sa Dragonpay sandbox account mo
define('DRAGONPAY_MERCHANT_ID', 'YOUR_MERCHANT_ID');
define('DRAGONPAY_SECRET_KEY', 'YOUR_SECRET_KEY');

// Sandbox URLs (palitan papuntang production URLs kapag live na kayo)
define('DRAGONPAY_PAYMENT_URL', 'https://test.dragonpay.ph/Pay.aspx');

// MOCK MODE: kapag TRUE, hindi tayo pupunta sa totoong Dragonpay —
// gagamit tayo ng sarili nating simulation page. I-set papuntang FALSE
// kapag meron ka nang totoong Merchant ID at Secret Key.
define('DRAGONPAY_MOCK_MODE', true);