<?php
namespace PayPal\Test\Api;

use PayPal\Api\Address;
use PayPal\Test\Constants;

use PayPal\Test\PayPalTestCase;

class AddressTest extends PayPalTestCase
{
	private $address;

	public function setup()
	{
		$this->address = $this->createAddress();
	}

	public function testGetterSetter() {
		$this->assertEquals($this->line1, $this->address->getLine1());
		$this->assertEquals($this->line2, $this->address->getLine2());
		$this->assertEquals($this->city, $this->address->getCity());
		$this->assertEquals($this->state, $this->address->getState());
		$this->assertEquals($this->postalCode, $this->address->getPostalCode());
		$this->assertEquals($this->countryCode, $this->address->getCountryCode());
		$this->assertEquals($this->phone, $this->address->getPhone());
	}
	
	public function testSerializeDeserialize()
	{
		$a1 = $this->address;
		
		$a2 = new Address();
		$a2->fromJson($a1->toJson());
		
		$this->assertEquals($a1, $a2);
	}
}
