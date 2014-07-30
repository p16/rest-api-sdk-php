<?php
namespace PayPal\Test\Api;

use PayPal\Api\Capture;
use PayPal\Api\Refund;
use PayPal\Api\Authorization;
use PayPal\Api\Amount;
use PayPal\Test\Constants;

use PayPal\Test\PayPalTestCase;

class CaptureTest extends PayPalTestCase
{
	private $captures;

	public $authorization_id = "AUTH-123";
	public $create_time = "2013-02-28T00:00:00Z";
	public $id = "C-5678";
	public $parent_payment = "PAY-123";
	public $state = "Created";

	public function createCapture()
	{
		$capture = new Capture();
		$capture->setCreateTime($this->create_time);
		$capture->setId($this->id);
		$capture->setParentPayment($this->parent_payment);
		$capture->setState($this->state);		
		
		return $capture;
	}
	
	public function setup()
	{
		$this->captures['partial'] = $this->createCapture();
		
		$capture = $this->createCapture();
		$capture->setAmount($this->createAmount());
		$capture->setLinks(array($this->createLinks()));
		$this->captures['full'] = $capture;
	}

	public function testGetterSetter()
	{
		$this->assertEquals($this->create_time, $this->captures['partial']->getCreateTime());
		$this->assertEquals($this->id, $this->captures['partial']->getId());
		$this->assertEquals($this->parent_payment, $this->captures['partial']->getParentPayment());
		$this->assertEquals($this->state, $this->captures['partial']->getState());
		
		$this->assertEquals($this->currency, $this->captures['full']->getAmount()->getCurrency());
		$links = $this->captures['full']->getLinks();
		$this->assertEquals($this->href, $links[0]->getHref());
	}
	
	public function testSerializeDeserialize()
	{
		$c1 = $this->captures['partial'];
		
		$c2 = new Capture();
		$c2->fromJson($c1->toJson());
		
		$this->assertEquals($c1, $c2);
	}
	
	public function testOperations()
	{
		$authId = $this->authorize();

		$json = '{"id":"8UT01073UD7060640","create_time":"2014-07-29T19:50:35Z","update_time":"2014-07-29T19:50:51Z","amount":{"total":"1.00","currency":"USD","details":{"subtotal":"1.00"}},"state":"captured","parent_payment":"PAY-6HS16308RF326473TKPL7WCY","valid_until":"2014-08-27T19:50:35Z","links":[{"href":"https://api.sandbox.paypal.com/v1/payments/authorization/8UT01073UD7060640","rel":"self","method":"GET"},{"href":"https://api.sandbox.paypal.com/v1/payments/payment/PAY-6HS16308RF326473TKPL7WCY","rel":"parent_payment","method":"GET"}]}';
		$call = $this->buildCallMock($json);
        
		$auth = Authorization::get($authId, $call);
		
		$amount = $this->createAmount();
		
		$captr = new Capture($call);
		$captr->setId($authId);
		$captr->setAmount($amount);
		
		$capt = $auth->capture($captr);
		$captureId = $capt->getId();
		$this->assertNotNull($captureId);
		
		$refund = new Refund();
		$refund->setId($captureId);
		$refund->setAmount($amount);
		
		$call = $this->buildCallMock($json);
		$capture = Capture::get($captureId, $call);
		$this->assertNotNull($capture->getId());
		
		$retund = $capture->refund($refund);
		$this->assertNotNull($retund->getId());
	}
}