<?php
namespace PayPal\Test\Api;

use PayPal\Api\Amount;
use PayPal\Api\Authorization;
use PayPal\Api\Links;
use PayPal\Test\Constants;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Address;

use PayPal\Api\Capture;
use PayPal\Api\CreditCard;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\FundingInstrument;
use PayPal\Api\Transaction;
use PayPal\Exception\PPConnectionException;

class AuthorizationTest extends \PHPUnit_Framework_TestCase
{
	private $authorizations = array();
	private $create_time = "2013-02-28T00:00:00Z";
	private $id = "AUTH-123";
	private $state = "Created";
	private $parent_payment = "PAY-12345";
	private $currency = "USD";
	private $total = "1.12";
	private $href = "USD";
	private $rel = "1.12";
	private $method = "1.12";
	
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
		$this->assertEquals(AmountTest::$currency, $authorization->getAmount()->getCurrency());
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

	/* FA CHIAMATA */
	private function authorize()
	{
		$addr = new Address();
		$addr->setLine1("3909 Witmer Road");
		$addr->setLine2("Niagara Falls");
		$addr->setCity("Niagara Falls");
		$addr->setState("NY");
		$addr->setPostalCode("14305");
		$addr->setCountryCode("US");
		$addr->setPhone("716-298-1822");
		
		$card = new CreditCard();
		$card->setType("visa");
		$card->setNumber("4417119669820331");
		$card->setExpireMonth("11");
		$card->setExpireYear("2019");
		$card->setCvv2("012");
		$card->setFirstName("Joe");
		$card->setLastName("Shopper");
		$card->setBillingAddress($addr);
		
		$fi = new FundingInstrument();
		$fi->setCreditCard($card);
		
		$payer = new Payer();
		$payer->setPaymentMethod("credit_card");
		$payer->setFundingInstruments(array($fi));
		
		$amount = new Amount();
		$amount->setCurrency("USD");
		$amount->setTotal("1.00");
		
		$transaction = new Transaction();
		$transaction->setAmount($amount);
		$transaction->setDescription("This is the payment description.");
		
		$json = '{"id":"PAY-6HS16308RF326473TKPL7WCY","create_time":"2014-07-29T19:50:35Z","update_time":"2014-07-29T19:50:51Z","state":"approved","intent":"authorize","payer":{"payment_method":"credit_card","funding_instruments":[{"credit_card":{"type":"visa","number":"xxxxxxxxxxxx0331","expire_month":"11","expire_year":"2019","first_name":"Joe","last_name":"Shopper","billing_address":{"line1":"3909 Witmer Road","line2":"Niagara Falls","city":"Niagara Falls","state":"NY","postal_code":"14305","country_code":"US","phone":"716-298-1822"}}}]},"transactions":[{"amount":{"total":"1.00","currency":"USD","details":{"subtotal":"1.00"}},"description":"This is the payment description.","related_resources":[{"authorization":{"id":"8UT01073UD7060640","create_time":"2014-07-29T19:50:35Z","update_time":"2014-07-29T19:50:51Z","amount":{"total":"1.00","currency":"USD","details":{"subtotal":"1.00"}},"state":"authorized","parent_payment":"PAY-6HS16308RF326473TKPL7WCY","valid_until":"2014-08-27T19:50:35Z","links":[{"href":"https://api.sandbox.paypal.com/v1/payments/authorization/8UT01073UD7060640","rel":"self","method":"GET"},{"href":"https://api.sandbox.paypal.com/v1/payments/authorization/8UT01073UD7060640/capture","rel":"capture","method":"POST"},{"href":"https://api.sandbox.paypal.com/v1/payments/authorization/8UT01073UD7060640/void","rel":"void","method":"POST"},{"href":"https://api.sandbox.paypal.com/v1/payments/payment/PAY-6HS16308RF326473TKPL7WCY","rel":"parent_payment","method":"GET"}]}}]}],"links":[{"href":"https://api.sandbox.paypal.com/v1/payments/payment/PAY-6HS16308RF326473TKPL7WCY","rel":"self","method":"GET"}]}';
		$call = $this->buildCallMock($json);

		$payment = new Payment($call);
		$payment->setIntent("authorize");
		$payment->setPayer($payer);
		$payment->setTransactions(array($transaction));
		
		$paymnt = $payment->create($call);
		$resArray = $paymnt->toArray();
		
		return $authId = $resArray['transactions'][0]['related_resources'][0]['authorization']['id'];
	}

	/* FA CHIAMATA */
	public function testOperations()
	{
		$authId = $this->authorize();

		$json = '{"id":"8UT01073UD7060640","create_time":"2014-07-29T19:50:35Z","update_time":"2014-07-29T19:50:51Z","amount":{"total":"1.00","currency":"USD","details":{"subtotal":"1.00"}},"state":"captured","parent_payment":"PAY-6HS16308RF326473TKPL7WCY","valid_until":"2014-08-27T19:50:35Z","links":[{"href":"https://api.sandbox.paypal.com/v1/payments/authorization/8UT01073UD7060640","rel":"self","method":"GET"},{"href":"https://api.sandbox.paypal.com/v1/payments/payment/PAY-6HS16308RF326473TKPL7WCY","rel":"parent_payment","method":"GET"}]}';
		$call = $this->buildCallMock($json);

		$auth = Authorization::get($authId, $call);
		$this->assertNotNull($auth->getId());
		
		$amount = new Amount();
		$amount->setCurrency("USD");
		$amount->setTotal("1.00");
		
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
	
	/* FA CHIAMATA */
	public function testReauthorize()
	{
		$json = '{"id":"7GH53639GA425732B","create_time":"2013-07-31T06:20:41Z","update_time":"2013-07-31T06:21:06Z","amount":{"total":"12.00","currency":"USD","details":{"subtotal":"12.00"}},"state":"expired","parent_payment":"PAY-98F50122XA6137358KH4KZOI","valid_until":"2013-08-29T06:20:41Z","links":[{"href":"https://api.sandbox.paypal.com/v1/payments/authorization/7GH53639GA425732B","rel":"self","method":"GET"},{"href":"https://api.sandbox.paypal.com/v1/payments/authorization/7GH53639GA425732B/capture","rel":"capture","method":"POST"},{"href":"https://api.sandbox.paypal.com/v1/payments/authorization/7GH53639GA425732B/void","rel":"void","method":"POST"},{"href":"https://api.sandbox.paypal.com/v1/payments/authorization/7GH53639GA425732B/reauthorize","rel":"reauthorize","method":"POST"},{"href":"https://api.sandbox.paypal.com/v1/payments/payment/PAY-98F50122XA6137358KH4KZOI","rel":"parent_payment","method":"GET"}]}';
		$call = $this->buildCallExceptionMock($json);

		$authorization = Authorization::get('7GH53639GA425732B', $call);
	
		$amount = new Amount();
		$amount->setCurrency("USD");
		$amount->setTotal("1.00");
		
		$authorization->setAmount($amount);
		try{
			$reauthorization = $authorization->reauthorize();
		}catch (PPConnectionException $ex){
			$this->assertEquals(strpos($ex->getMessage(),"500"), false);
		}
	}

	private function buildCallMock($json)
	{
		$call = $this->getMockBuilder('PayPal\Transport\PPRestCall')
			->disableOriginalConstructor()
			->getMock();
		$call->expects($this->atLeastOnce())
			->method('execute')
			->will($this->returnValue($json));

		return $call;
	}

	private function buildCallExceptionMock($json)
	{
		$call = $this->getMockBuilder('PayPal\Transport\PPRestCall')
			->disableOriginalConstructor()
			->getMock();
		$call->expects($this->at(0))
			->method('execute')
			->will($this->returnValue($json));
		$call->expects($this->at(1))
			->method('execute')
			->will($this->throwException(new PPConnectionException('url', 'message')));

		return $call;
	}

	/* OK */
	private function createAuthorization($call)
	{
		$authorization = new Authorization($call);
		$authorization->setCreateTime($this->create_time);
		$authorization->setId($this->id);
		$authorization->setState($this->state);
		
		$authorization->setAmount(AmountTest::createAmount());
		$authorization->setLinks(array(LinksTest::createLinks()));	
		
		return $authorization;
	}
}
