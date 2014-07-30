<?php

namespace PayPal\Test;

use PayPal\Api\Address;
use PayPal\Api\CreditCard;
use PayPal\Api\FundingInstrument;
use PayPal\Api\Payer;
use PayPal\Api\Amount;
use PayPal\Api\Payment;
use PayPal\Api\Transaction;
use PayPal\Api\Authorization;
use PayPal\Api\Links;
use PayPal\Api\Details;

use PayPal\Exception\PPConnectionException;

class PayPalTestCase extends \PHPUnit_Framework_TestCase
{
    protected $authorizations = array();
    protected $create_time = "2013-02-28T00:00:00Z";
    protected $id = "AUTH-123";
    protected $status = "Created";
    protected $parent_payment = "PAY-12345";

    /* OK */
    protected function authorize()
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

    protected function buildCallMock($json)
    {
        $call = $this->getMockBuilder('PayPal\Transport\PPRestCall')
            ->disableOriginalConstructor()
            ->getMock();
        $call->expects($this->atLeastOnce())
            ->method('execute')
            ->will($this->returnValue($json));

        return $call;
    }

    protected function buildCallExceptionMock($json)
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
    protected function createAuthorization($call)
    {
        $authorization = new Authorization($call);
        $authorization->setCreateTime($this->create_time);
        $authorization->setId($this->id);
        $authorization->setState($this->status);
        
        $authorization->setAmount($this->createAmount());
        $authorization->setLinks(array($this->createLinks()));  
        
        return $authorization;
    }

    public $currency = "USD";
    public $total = "1.12";  
    protected function createAmount($currency = null, $total = null)
    {
        $currency = is_null($currency) ? $this->currency : $currency;
        $total = is_null($total) ? $this->total : $total;

        $amount = new Amount();
        $amount->setCurrency($currency);
        $amount->setTotal($total);
        
        return $amount;
    }
    
    protected $href = "USD";
    protected $rel = "1.12";
    protected $method = "1.12";
    protected function createLinks()
    {
        $links = new Links();
        $links->setHref($this->href);
        $links->setRel($this->rel);
        $links->setMethod($this->method);
        
        return $links;
    }

    public $subtotal = "2.00";
    public $tax = "1.12";
    public $shipping = "3.15";
    public $fee = "4.99";
    protected function createAmountDetails() 
    {
        $amountDetails = new Details();
        $amountDetails->setSubtotal($this->subtotal);
        $amountDetails->setTax($this->tax);
        $amountDetails->setShipping($this->shipping);
        $amountDetails->setFee($this->fee);
        
        return $amountDetails;
    }

    protected $line1 = "3909 Witmer Road";
    protected $line2 = "Niagara Falls"; 
    protected $city = "Niagara Falls";
    protected $state = "NY";
    protected $postalCode = "14305";
    protected $countryCode = "US";
    protected $phone = "716-298-1822";
    protected $type = "Billing";
    protected function createAddress()
    {
        $addr = new Address();
        $addr->setLine1($this->line1);
        $addr->setLine2($this->line2);
        $addr->setCity($this->city);
        $addr->setState($this->state);
        $addr->setPostalCode($this->postalCode);
        $addr->setCountryCode($this->countryCode);
        $addr->setPhone($this->phone);
        return $addr;
    }
}