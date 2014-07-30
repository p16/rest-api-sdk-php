<?php
namespace PayPal\Test\Api;

use PayPal\Api\Address;
use PayPal\Api\CreditCard;
use PayPal\Test\Constants;

use PayPal\Transport\PPRestCall;
use PayPal\Rest\ApiContext;

use PayPal\Test\PayPalTestCase;

class CreditCardTest extends PayPalTestCase
{
	private $cards;

	public $id = "id";
	public $validUntil = "2013-02-28T00:00:00Z";
	public $state = "created";
	public $payerId = "payer-id";
	public $cardType = "visa";
	public $cardNumber = "4417119669820331";
	public $expireMonth = 11;
	public $expireYear = "2019";
	public $cvv = "012";
	public $firstName = "V";
	public $lastName = "C";

	public function buildCreditCardCallMock()
	{
		$json = '{"id":"'.$this->id.'",'.
        		'"type":"'.$this->cardType.'",'.
        		'"number":"'.$this->cardNumber.'",'.
        		'"expire_month":"'.$this->expireMonth.'",'.
        		'"expire_year":"'.$this->expireYear.'",'.
        		'"cvv2":"'.$this->cvv.'",'.
        		'"first_name":"'.$this->firstName.'",'.
        		'"last_name":"'.$this->lastName.'",'.
        		'"payer_id":"'.$this->payerId.'",'.
        		'"state": "'.$this->state.'"'.
        		'}';

        return $this->buildCallMock($json);
	}

	public function createCreditCard($call = null)
	{
		$card = new CreditCard($call);
		$card->setType($this->cardType);
		$card->setNumber($this->cardNumber);
		$card->setExpireMonth($this->expireMonth);
		$card->setExpireYear($this->expireYear);
		$card->setCvv2($this->cvv);
		$card->setFirstName($this->firstName);
		$card->setLastName($this->lastName);
		$card->setPayerId($this->payerId);

		return $card;
	}

	public function testGetterSetters()
	{
		$c = $this->createCreditCard();
		$this->assertEquals($this->cardType, $c->getType());
		$this->assertEquals($this->cardNumber, $c->getNumber());
		$this->assertEquals($this->expireMonth, $c->getExpireMonth());
		$this->assertEquals($this->expireYear, $c->getExpireYear());
		$this->assertEquals($this->cvv, $c->getCvv2());
		$this->assertEquals($this->firstName, $c->getFirstName());
		$this->assertEquals($this->lastName, $c->getLastName());
		$this->assertEquals($this->payerId, $c->getPayerId());

		$c = $this->createCreditCard();
		$c->setBillingAddress($this->createAddress());
		$c->setLinks(array($this->createLinks()));
		$this->assertEquals($this->line1, $c->getBillingAddress()->getLine1());
		$link = $c->getLinks();
		$this->assertEquals($this->href, $link[0]->getHref());
	}

	public function testSerializeDeserialize()
	{
		$card = $this->createCreditCard();
		$card->setBillingAddress($this->createAddress());
		$card->setLinks(array($this->createLinks()));
		$this->cards['full'] = $card;
		$c1 = $this->cards['full'];
		$json = $c1->toJson();

		$c2 = new CreditCard();
		$c2->fromJson($json);

		$this->assertEquals($c1, $c2);
	}

	public function testOperations()
	{
		$c1 = $this->createCreditCard($this->buildCreditCardCallMock());
		$c1->setBillingAddress($this->createAddress());
		$c1->setLinks(array($this->createLinks()));
		$c1->create();

		$this->assertNotNull($c1->getId());

        $json = $json = '{"id":"'.$this->id.'",'.
        		'"type":"'.$this->cardType.'",'.
        		'"number":"'.$this->cardNumber.'",'.
        		'"expire_month":"'.$this->expireMonth.'",'.
        		'"expire_year":"'.$this->expireYear.'",'.
        		'"cvv2":"'.$this->cvv.'",'.
        		'"first_name":"'.$this->firstName.'",'.
        		'"last_name":"'.$this->lastName.'",'.
        		'"payer_id":"'.$this->payerId.'",'.
        		'"billing_address": '.$this->createAddress()->toJson().','.
        		'"links": '.$this->createLinks()->toJson().','.
        		'"state": "'.$this->state.'"'.
        		'}';

        $call = $this->buildCallMock($json);

		$c2 = CreditCard::get($c1->getId(), $call);
		$this->assertEquals($c1->getBillingAddress(), $c2->getBillingAddress());
		$this->assertGreaterThan(0, count($c2->getLinks()));
		$this->assertEquals($this->cardType, $c2->getType());
		$this->assertNotNull($c2->getState());
 		$this->assertEquals(true, $c2->delete());
	}
}