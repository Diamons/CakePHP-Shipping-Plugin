CakePHP-Shipping-Plugin
=======================
Work in progress, for now only detects the carrier.

1) Add ```CakePlugin::load('Shipping', array('bootstrap' => false, 'routes' => false));``` to your bootstrap file
2) Add ```var $components = array('Shipping.Shipping');``` to your controller.
3) Do ```$this->Shipping->getTracker(trackingId)``` to receive your tracker (UPS, FedEx, USPS).