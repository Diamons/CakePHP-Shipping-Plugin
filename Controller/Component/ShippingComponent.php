<?php
/**
 * USPSComponent
 *
 *
 * PHP version 5
 *
 */

App::uses('Component', 'Controller');

/**
 * USPS
 *
 * @package		usps
 */
class ShippingComponent extends Component {


	public function startup(Controller $controller) {
		$this->Controller = $controller;
		App::import('Vendor', 'Shipping.Shipping', array(
			'file' => 'parceltracker.class.php')
		);
		
		$this->shipping = new ParcelTracker();
		
		
	}
	
	public function trackUSPS($id){
	}
	
	public function trackFedex($id){
	}
	
	public function getTracker($id){
		return $this->shipping->detectCarrier($id);
	}



}