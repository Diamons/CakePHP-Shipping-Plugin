<?php


class ShippingBehavior extends ModelBehavior{
	protected $carriers;
	
    protected $config;
	public function setup(Model $Model){
		$this->Model = $Model; 

	}


    /**
     * Detect which carrier a particular tracking number belongs to.
     *
     * @param $trackingNumber string The tracking number to detect.
     * @return string|boolean The array key of the carrier, as defined in the
     *    carriers configuration setting or false if no match was found.
     */
    public function detectCarrier(Model $Model=NULL, $trackingNumber) {
		if($this->isFedExGround($trackingNumber) || $this->isFedExExpress($trackingNumber))
			return "fedex";
		elseif($this->isUPS($trackingNumber))
			return "ups";
		elseif($this->isUSPSUSS128($trackingNumber) || $this->isUSPSUSS39($trackingNumber))
			return "usps";

		return false;
    }
	
	    public function isFedExGround($trackingNumber) {
        if (!ctype_digit($trackingNumber) || strlen($trackingNumber) < 15 || strlen($trackingNumber) > 22) {
            return false;
        }

        $trackingNumber = strrev($trackingNumber);

        if (substr($trackingNumber, -2) == '00') {
            // Possible SSCC-18
            $numDigits = 16;
            $testDigits = substr($trackingNumber, 1, $numDigits);
        } else {
            // Possible 96 (with or without service/ucc/ean/scnc identifiers)
            $numDigits = 14;
            $testDigits = substr($trackingNumber, 1, $numDigits);
        }

        $weightings = array(3, 1);
        $numWeightings = 2;

        $sum = 0;
        for ($i=0; $i<$numDigits; $i++) {
            $sum += ($weightings[$i % $numWeightings] * $testDigits[$i]);
        }

        $checkDigit = ((ceil($sum / 10) * 10) - $sum);

        return ($checkDigit == $trackingNumber[0]);
    }

	public function isUPS($trackingNumber) {
        $trackingNumber = strtoupper($trackingNumber);

        if (!ctype_alnum($trackingNumber) || strpos($trackingNumber, '1Z') !== 0 || strlen($trackingNumber) != 18) {
            return false;
        }

        $testDigits = strtr(substr($trackingNumber, 2), 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', '23456789012345678901234567');

        $sum = 0;
        for ($i=0; $i<15; $i++) {
            if ($i % 2) {
                $sum += $testDigits[$i];
            }
            $sum += $testDigits[$i];
        }

        $checkDigit = ($sum % 10);
        $checkDigit = ($checkDigit == 0) ? $checkDigit : (10 - $checkDigit);

        return ($checkDigit == $testDigits[15]);
    }
	
    public function isFedExExpress($trackingNumber) {
        if (!ctype_digit($trackingNumber) || strlen($trackingNumber) != 12) {
            return false;
        }

        $weightings = array(1, 3, 7);
        $numWeightings = 3;

        $sum = 0;
        for ($i=10; $i>=0; $i--) {
            $sum += ($weightings[(10 - $i) % $numWeightings] * $trackingNumber[$i]);
        }

        $checkDigit = (($sum % 11) % 10);

        return ($checkDigit == $trackingNumber[11]);
    }
	
    public function isUSPSUSS128($trackingNumber) {
        $trackingNumberLen = strlen($trackingNumber);

        if (!ctype_digit($trackingNumber) || ($trackingNumberLen != 20 && $trackingNumberLen != 22 && $trackingNumberLen != 30)) {
            return false;
        }

        $weightings = array(3, 1);
        $numWeightings = 2;

        if ($trackingNumberLen == 20) {
            // Add service code to shortened number. This passes known test cases but need
            // to verify that this is always a correct assumption.
            $trackingNumber = '91' . $trackingNumber;
        } elseif ($trackingNumberLen == 30) {
            // Truncate extra information
            $trackingNumber = substr($trackingNumber, 8, 30);
        }

        $sum = 0;
        for ($i=20; $i>=0; $i--) {
            $sum += ($weightings[$i % $numWeightings] * $trackingNumber[$i]);
        }

        $checkDigit = ((ceil($sum / 10) * 10) - $sum);

        return ($checkDigit == $trackingNumber[21]);
    }


    public function isUSPSUSS39($trackingNumber) {
        if (strlen($trackingNumber) != 13) {
            return false;
        }

        $trackingPrefix = substr($trackingNumber, 0, 2);
        $trackingSuffix = substr($trackingNumber, -2);
        $trackingNumber = substr($trackingNumber, 2, -2);

        if (!ctype_alpha($trackingPrefix) || !ctype_alpha($trackingSuffix) || !ctype_digit($trackingNumber)) {
            return false;
        }

        $weightings = array(8, 6, 4, 2, 3, 5, 9, 7);
        $numWeightings = 8;

        $sum = 0;
        for ($i=0; $i<8; $i++) {
            $sum += ($weightings[$i % $numWeightings] * $trackingNumber[$i]);
        }

        $checkDigit = ($sum % 11);
        $checkDigit = ($checkDigit == 0) ? 5 : (($checkDigit == 1) ? 0 : (11 - $checkDigit));

        return ($checkDigit == $trackingNumber[8]);
    }
}
