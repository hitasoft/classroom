<?php
/**
 * @table paysystems
 * @id payflow-link
 * @title PayFlow Link
 * @recurring none
 */
class Am_Paysystem_PayflowLink extends Am_Paysystem_Abstract
{
    const PLUGIN_STATUS = self::STATUS_BETA;
    const PLUGIN_REVISION = '4.3.4';

    protected $defaultTitle = 'PayFlow Link';
    protected $defaultDescription = 'Credit Card Payment';
    const URL = 'https://payments.verisign.com/payflowlink';
    
    public function _initSetupForm(Am_Form_Setup $form)
    {
        $form->addText("login")->setLabel('Your PayFlow username');
        $form->addText("partner")->setLabel('Your PayFlow Partner Name');
    }
    
    public function _process(Invoice $invoice, Am_Request $request, Am_Paysystem_Result $result)
    {
        $a = new Am_Paysystem_Action_Redirect(self::URL);
        $a->LOGIN = $this->getConfig('login');
        $a->PARTNER = $this->getConfig('partner');
        $a->AMOUNT = sprintf('%.2f', $invoice->first_total);
        $a->TYPE = 'S';
        $a->INVOICE = $invoice->public_id;
        $a->DESCRIPTION = $invoice->getLineDescription();
        $a->NAME = $invoice->getName();
        $a->ADDRESS = $invoice->getStreet();
        $a->CITY = $invoice->getCity();
        $a->STATE = $invoice->getState();
        $a->COUNTRY = $invoice->getCountry();
        $a->ZIP = $invoice->getZip();
        $a->EMAIL = $invoice->getEmail();
        $a->PHONE = $invoice->getPhone();
        $result->setAction($a);
    }

    public function createTransaction(Am_Request $request, Zend_Controller_Response_Http $response, array $invokeArgs)
    {
        return new Am_Paysystem_Transaction_PayflowLink($this, $request, $response, $invokeArgs);
    }

    public function getRecurringType()
    {
        return self::REPORTS_NOT_RECURRING;
    }
    public function getReadme()
    {
        $rootURL = $this->getDi()->config->get('root_url');
        return <<<CUT
<b>Payfloe link payment plugin configuration</b>
        
Set "IPN URL" for your payflow account to $rootURL/payment/payflow-link/ipn

CUT;
    }
    
    public function directAction(Am_Request $request, Zend_Controller_Response_Http $response, array $invokeArgs)
    {
        if($request->getFiltered('INVOICE')=='')
            $response->setRedirect($this->getRootUrl() . '/thanks');
        else
            parent::directAction($request, $response, $invokeArgs);
    }
}
class Am_Paysystem_Transaction_PayflowLink extends Am_Paysystem_Transaction_Incoming
{
    public function findInvoiceId()
    {
        return $this->request->getFiltered('INVOICE');
    }

    public function getUniqId()
    {
        return $this->request->getFiltered('PNREF');
    }

    public function validateSource()
    {
        return true;
    }

    public function validateStatus()
    {
        return (!(in_array($this->request->getFiltered('RESPMSG'), array('AVSDECLINED','CSCDECLINED')) && 
            ($this->request->getInt('RESULT')==0)));
    }

    public function validateTerms()
    {
        return (doubleval($this->request->get('AMOUNT')) == doubleval($this->invoice->first_total));
    }
}