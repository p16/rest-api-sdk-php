<?php
namespace PayPal\Test\Api;

use PayPal\Api\CreditCardHistory;

use PayPal\Api\Address;
use PayPal\Api\CreditCard;
use PayPal\Test\Constants;

use PayPal\Test\PayPalTestCase;

class CreditCardHistoryTest extends PayPalTestCase
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
	
	public function createCreditCard()
	{
		$card = new CreditCard();
		$card->setType($this->cardType);
		$card->setNumber($this->cardNumber);
		$card->setExpireMonth($this->expireMonth);
		$card->setExpireYear($this->expireYear);
		$card->setCvv2($this->cvv);
		$card->setFirstName($this->firstName);
		$card->setLastName($this->lastName);
		$card->setId($this->id);
		$card->setValidUntil($this->validUntil);
		$card->setState($this->state);
		$card->setPayerId($this->payerId);
		return $card;
	}
	
	public function setup()
	{
		$card = $this->createCreditCard();
		$card->setBillingAddress($this->createAddress());	
		$card->setLinks(array($this->createLinks()));
		$this->cards['full'] = $card;
		
		$card = $this->createCreditCard();	
		$this->cards['partial'] = $card;
	}
	
	public function testGetterSetters()
	{
		$cardHistory = new CreditCardHistory();
		$cardHistory->setCreditCards(array($this->cards['partial'], $this->cards['full']));
		$cardHistory->setCount(2);
		
		$this->assertEquals(2, count($cardHistory->getCreditCards()));
	}

	
	public function testSerializationDeserialization()
	{
		$cardHistory = new CreditCardHistory();
		$cardHistory->setCreditCards(array($this->cards['partial'], $this->cards['full']));
		$cardHistory->setCount(2);
	
		$cardHistoryCopy = new CreditCardHistory();
		$cardHistoryCopy->fromJson($cardHistory->toJSON());
		
		$this->assertEquals($cardHistory, $cardHistoryCopy);
	}
}