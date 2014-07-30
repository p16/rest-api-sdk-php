<?php
namespace PayPal\Test\Api;

use PayPal\Api\Authorization;
use PayPal\Api\Capture;
use PayPal\Exception\PPConnectionException;

use PayPal\Test\PayPalTestCase;

class AuthorizationTest extends PayPalTestCase
{
	/* OK */
	public function setup()
	{
		$call = $this->getMockBuilder('PayPal\Transport\PPRestCall')
			->disableOriginalConstructor()
			->getMock();

		$authorization = new Authorization($call);
		$authorization->setCreateTime($this->create_time);
		$authorization->setId($this->id);
		$authorization->setState($this->state);
		$authorization->setParentPayment($this->parent_payment);
		$this->authorizations['partial'] = $authorization;
		$this->authorizations['full'] = $this->createAuthorization($call);
	}

	/* OK */
	public function testGetterSetter()
	{
		$authorization = $this->authorizations['partial'];
		$this->assertEquals($this->create_time, $authorization->getCreateTime());
		$this->assertEquals($this->id, $authorization->getId());
		$this->assertEquals($this->state, $authorization->getState());
		$this->assertEquals($this->parent_payment, $authorization->getParentPayment());
		
		$authorization = $this->authorizations['full'];
		$this->assertEquals($this->currency, $authorization->getAmount()->getCurrency());
		$this->assertEquals(1, count($authorization->getLinks()));
	}
	
	/* OK */
	public function testSerializeDeserialize()
	{
		$a1 = $this->authorizations['partial'];

		$call = $this->getMockBuilder('PayPal\Transport\PPRestCall')
			->disableOriginalConstructor()
			->getMock();

		$a2 = new Authorization($call);
		$a2->fromJson($a1->toJson());
		$this->assertEquals($a1, $a2);
	}

	/* OK */
	public function testOperations()
	{
		$authId = $this->authorize();

		$json = '{"id":"8UT01073UD7060640","create_time":"2014-07-29T19:50:35Z","update_time":"2014-07-29T19:50:51Z","amount":{"total":"1.00","currency":"USD","details":{"subtotal":"1.00"}},"state":"captured","parent_payment":"PAY-6HS16308RF326473TKPL7WCY","valid_until":"2014-08-27T19:50:35Z","links":[{"href":"https://api.sandbox.paypal.com/v1/payments/authorization/8UT01073UD7060640","rel":"self","method":"GET"},{"href":"https://api.sandbox.paypal.com/v1/payments/payment/PAY-6HS16308RF326473TKPL7WCY","rel":"parent_payment","method":"GET"}]}';
		$call = $this->buildCallMock($json);

		$auth = Authorization::get($authId, $call);
		$this->assertNotNull($auth->getId());
		
		$amount = $this->createAmount("USD", "1.00");
		
		$captur = new Capture();
		$captur->setId($authId);
		$captur->setAmount($amount);	
		
		$capt = $auth->capture($captur);
		$this->assertNotNull( $capt->getId());
		
		$authId = $this->authorize();

		$json = '{"id":"8UT01073UD7060640","create_time":"2014-07-29T19:50:35Z","update_time":"2014-07-29T19:50:51Z","amount":{"total":"1.00","currency":"USD","details":{"subtotal":"1.00"}},"state":"captured","parent_payment":"PAY-6HS16308RF326473TKPL7WCY","valid_until":"2014-08-27T19:50:35Z","links":[{"href":"https://api.sandbox.paypal.com/v1/payments/authorization/8UT01073UD7060640","rel":"self","method":"GET"},{"href":"https://api.sandbox.paypal.com/v1/payments/payment/PAY-6HS16308RF326473TKPL7WCY","rel":"parent_payment","method":"GET"}]}';
		$call = $this->buildCallMock($json);
		$auth = Authorization::get($authId, $call);

		$void = $auth->void();

		$this->assertNotNull($void->getId());
	}
	
	/* OK */
	public function testReauthorize()
	{
		$json = '{"id":"7GH53639GA425732B","create_time":"2013-07-31T06:20:41Z","update_time":"2013-07-31T06:21:06Z","amount":{"total":"12.00","currency":"USD","details":{"subtotal":"12.00"}},"state":"expired","parent_payment":"PAY-98F50122XA6137358KH4KZOI","valid_until":"2013-08-29T06:20:41Z","links":[{"href":"https://api.sandbox.paypal.com/v1/payments/authorization/7GH53639GA425732B","rel":"self","method":"GET"},{"href":"https://api.sandbox.paypal.com/v1/payments/authorization/7GH53639GA425732B/capture","rel":"capture","method":"POST"},{"href":"https://api.sandbox.paypal.com/v1/payments/authorization/7GH53639GA425732B/void","rel":"void","method":"POST"},{"href":"https://api.sandbox.paypal.com/v1/payments/authorization/7GH53639GA425732B/reauthorize","rel":"reauthorize","method":"POST"},{"href":"https://api.sandbox.paypal.com/v1/payments/payment/PAY-98F50122XA6137358KH4KZOI","rel":"parent_payment","method":"GET"}]}';
		$call = $this->buildCallExceptionMock($json);

		$authorization = Authorization::get('7GH53639GA425732B', $call);
	
		$amount = $this->createAmount("USD", "1.00");
		
		$authorization->setAmount($amount);
		try{
			$reauthorization = $authorization->reauthorize();
		}catch (PPConnectionException $ex){
			$this->assertEquals(strpos($ex->getMessage(),"500"), false);
		}
	}
}
