<?php
namespace PayPal\Test\Api;

use PayPal\Api\Details;
use PayPal\Test\Constants;

use PayPal\Test\PayPalTestCase;

class DetailsTest extends PayPalTestCase
{
	private $amountDetails;

	public function setup()
	{
		$this->amountDetails = $this->createAmountDetails();
	}
	
	public function testGetterSetters() {
		$this->assertEquals($this->subtotal, $this->amountDetails->getSubtotal());
		$this->assertEquals($this->tax, $this->amountDetails->getTax());
		$this->assertEquals($this->shipping, $this->amountDetails->getShipping());
		$this->assertEquals($this->fee, $this->amountDetails->getFee());		
	}
	
	public function testSerializeDeserialize() {
		$a1 = $this->amountDetails;
		
		$a2 = new Details();
		$a2->fromJson($a1->toJson());
		
		$this->assertEquals($a1, $a2);
	}
}