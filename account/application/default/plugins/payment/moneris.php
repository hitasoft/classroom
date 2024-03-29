<?php
/**
 * @table paysystems
 * @id moneris
 * @title Moneris
 * @visible_link http://www.moneris.com/
 * @recurring paysystem
 * @logo_url moneris.png
 */
class Am_Paysystem_Moneris extends Am_Paysystem_Abstract{
    const PLUGIN_STATUS = self::STATUS_BETA;
    const PLUGIN_REVISION = '4.3.4';

    protected $defaultTitle = 'Moneris';
    protected $defaultDescription = 'Credit Card Payment';
   
    const LIVE_URL = 'https://www3.moneris.com/HPPDP/index.php';
    const SANDBOX_URL = 'https://esqa.moneris.com/HPPDP/index.php';

    public function _initSetupForm(Am_Form_Setup $form)
    {
        $form->addText("ps_store_id")->setLabel(array(
            'Merchant ps_store_id', 
            'Provided by Moneris Solutions – Hosted Paypage Configuration Tool.<br>
            Identifies the configuration for the Hosted Paypage.'
            ));
        $form->addText("hpp_key")->setLabel(array(
            'Merchant hpp_key', 
            'Provided by Moneris Solutions – Hosted Paypage Configuration Tool.<br>
            This is a security key that corresponds to the ps_store_id.'
            ));
        $form->addAdvCheckbox('testing')->setLabel('Test Mode');
    }
    
    function getSupportedCurrencies()
    {
        return array('CAD');
    }    
    
    public function _process(Invoice $invoice, Am_Request $request, Am_Paysystem_Result $result)
    {
        $u = $invoice->getUser();
        $a = new Am_Paysystem_Action_Redirect($this->getConfig('testing') ? self::SANDBOX_URL : self::LIVE_URL);
        $a->ps_store_id = $this->getConfig('ps_store_id');
        $a->hpp_key = $this->getConfig('hpp_key');
        
        $a->charge_total = sprintf('%.2f', $invoice->first_total);
        $a->cust_id = $invoice->public_id;
        $a->email = $u->email;
        
        $a->bill_first_name = $u->name_f;
        $a->bill_last_name = $u->name_l;
        $a->bill_address_one = $u->street;
        $a->bill_city = $u->city;
        $a->bill_state_or_province = $u->state;
        $a->bill_postal_code = $u->zip;
        $a->bill_country = $u->country;
        if($invoice->second_total>0){
            $periods = array('m' => 'month','y' => 'year', 'd' => 'day', 'w' => 'week');
            $second_period = new Am_Period($invoice->second_period);
            $a->recurUnit = $periods[$second_period->getUnit()];
            $a->recurPeriod = $second_period->getCount();
            $a->recurStartNow = ($invoice->first_total>0) ? 'true' : 'false';
            $a->doRecur = 1;
            $a->recurStartDate = date('Y/m/d',strtotime($invoice->calculateRebillDate(1)));
            $a->recurAmount = sprintf('%.2f', $invoice->second_total);
            $a->recurNum = $invoice->rebill_times;        
        }
        $result->setAction($a);        
    }
    public function createTransaction(Am_Request $request, Zend_Controller_Response_Http $response, array $invokeArgs)
    {
        return new Am_Paysystem_Transaction_Moneris($this, $request, $response, $invokeArgs);
    }
    public function createThanksTransaction(Am_Request $request, Zend_Controller_Response_Http $response, array $invokeArgs)
    {
        return new Am_Paysystem_Transaction_Moneris_Thanks($this, $request, $response, $invokeArgs);
    }
    public function getRecurringType()
    {
        return self::REPORTS_REBILL;
    }
    function getReadme(){
        return <<<CUT
<b>Moneris payment plugin configuration</b>
CONFIGURATION OF ACCOUNT 

1. Login into your Moneris account.

2. Once you have successfully logged in, click on the “ADMIN” menu item on the left and then in
   the submenu that appears click on “HOSTED CONFIG”.

3. Set 
   Response method to Sent to your server as a POST  
   Approved URL to %root_surl%/payment/moneris/thanks
   Declined URL to %root_surl%/cancel

4. Click on "Configure Response Fields".

5. Enable "Return other customer fields. (cust_id, client_email, note . . .)".
   Enable "Perform asynchronous data post.".
   Set "Async Response URL" to %root_surl%/payment/moneris/ipn
CUT;
        
    }
}

class Am_Paysystem_Transaction_Moneris_Thanks extends Am_Paysystem_Transaction_Incoming{

    public function findInvoiceId()
    {
        return $this->request->get('cust_id');
    }
    
    public function getUniqId()
    {
        return $this->request->get('bank_transaction_id');
    }
    
    public function validateSource()
    {
        return true;
    }
    
    public function validateStatus()
    {
        return intval($this->request->get('response_code'))<50;
    }
    
    public function validateTerms()
    {
        return $this->request->get('charge_total')==$this->invoice->first_total;
    }
    
    public function getInvoice()
    {
        return $this->invoice;
    }
}

class Am_Paysystem_Transaction_Moneris extends Am_Paysystem_Transaction_Incoming{
    
    protected $xml;
    
    public function __construct(Am_Paysystem_Abstract $plugin, Am_Request $request, Zend_Controller_Response_Http $response, $invokeArgs)
    {
        $this->xml = simplexml_load_string($request->get('xml_response'));
        parent::__construct($plugin, $request, $response, $invokeArgs);
    }
    
    public function findInvoiceId()
    {
        return $this->xml->od_other->cust_id;
    }
        
    public function getUniqId()
    {
        return $this->xml->bank_transaction_id;
    }
    
    public function validateSource()
    {
        if (!$this->xml)
            return false;
        return true;
    }
    
    public function validateStatus()
    {
        return (intval($this->xml->response_code)<50 && strtolower($this->xml->response_code!='null'));
    }
    
    public function validateTerms()
    {        
        return $this->xml->charge_total==($this->invoice->isFirstPayment() ? $this->invoice->first_total : $this->invoice->second_total);
    }

    public function processValidated()
    {
        parent::processValidated();
        if ($this->invoice->status == RECURRING_ACTIVE)
            $this->invoice->extendAccessPeriod(Am_Period::RECURRING_SQL_DATE);
    }
    
}
