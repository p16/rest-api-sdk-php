<?php

namespace PayPal\Test\Api;

use PayPal\Api\Amount;
use PayPal\Test\Constants;

use PayPal\Test\PayPalTestCase;

class AmountTest extends PayPalTestCase
{
	private $amounts;

	public function setup()
	{
		$this->amounts['partial'] = $this->createAmount();
		
		$amount = $this->createAmount();
		$amount->setDetails($this->createAmountDetails());
		$this->amounts['full'] = $amount;
	}

	public function testGetterSetter()
	{
		$this->assertEquals($this->currency, $this->amounts['partial']->getCurrency());
		$this->assertEquals($this->total, $this->amounts['partial']->getTotal());
		$this->assertEquals($this->fee, $this->amounts['full']->getDetails()->getFee());
	}
	
	public function testSerializeDeserialize()
	{
		$a1 = $this->amounts['partial'];
		
		$a2 = new Amount();
		$a2->fromJson($a1->toJson());
		
		$this->assertEquals($a1, $a2);
	}
}