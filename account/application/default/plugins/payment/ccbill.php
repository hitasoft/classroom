<?php
/**
 * @table paysystems
 * @id ccbill
 * @title ccBill
 * @visible_link http://www.ccbill.com/
 * @recurring paysystem
 * @logo_url ccbill.png
 * @country US
 * @fixed_products 1
 * @adult 1
 */
class Am_Paysystem_Ccbill extends Am_Paysystem_Abstract
{

    const PLUGIN_STATUS = self::STATUS_PRODUCTION;
    const PLUGIN_REVISION = '4.3.4';

    protected $defaultTitle = 'ccBill';
    protected $defaultDescription = 'Pay by credit card/debit card';

    const CCBILL_CC = 'ccbill_cc';
    const CCBILL_900 = 'ccbill_900';
    const CCBILL_CHECK = 'ccbill_check';
    const URL = 'https://bill.ccbill.com/jpost/signup.cgi';
    const CCBILL_LAST_RUN = 'ccbill_datalink_last_run';
    const DATALINK_URL = 'https://datalink.ccbill.com/data/main.cgi';
    protected $currency_codes = array(
        'USD' => 840,
        'AUD' => 036,
        'EUR' => 978,
        'GBP' => 826,
        'JPY' => 392,
        'CAD' => 124
    );

    public function init()
    {
        parent::init();
        $this->getDi()->billingPlanTable->customFields()
            ->add(new Am_CustomFieldText('ccbill_product_id', "ccBill Product ID",
                    "you must create the same product in ccbill for CC billing. Enter pricegroup here"));
        $this->getDi()->billingPlanTable->customFields()
            ->add(new Am_CustomFieldText('ccbill_subaccount_id', "ccBill Subaccount ID",
                    "keep empty to use default value (from config)"));
        $this->getDi()->billingPlanTable->customFields()
            ->add(new Am_CustomFieldText('ccbill_form_id', "ccBill Form ID",
                    "enter ccBill Form ID"));
        $this->getDi()->billingPlanTable->customFields()
            ->add(new Am_CustomFieldSelect('ccbill_form_type', "ccBill Form Type",
                    "", null, array('options' => array(
                        self::CCBILL_CC => 'Credit Card',
                        self::CCBILL_CHECK => 'Online Check',
                        self::CCBILL_900 => 'ccBill 900'
                    ))));
    }

    public function _initSetupForm(Am_Form_Setup $form)
    {
        $form->addText('account')->setLabel(array('Your Account Id in ccbill', 'your account number on ccBill, like 112233'));
        $form->addText('subaccount_id')->setLabel(array('Subaccount number', 'like 0001 or 0002'));
        $form->addText('datalink_user')->setLabel(array('DataLink Username', 'read ccBill plugin readme (11) about'));
        $form->addText('datalink_pass')->setLabel(array('DataLink Password', 'read ccBill plugin readme (11) about'));
    }

    public function _process(Invoice $invoice, Am_Request $request, Am_Paysystem_Result $result)
    {
        $user = $invoice->getUser();

        $subaccount_id = $invoice->getItem(0)->getBillingPlanData("ccbill_subaccount_id") ?
            $invoice->getItem(0)->getBillingPlanData("ccbill_subaccount_id") : $this->getConfig('subaccount_id');

        $a = new Am_Paysystem_Action_Redirect(self::URL);

        $a->clientAccnum = $this->getConfig('account');
        $a->clientSubacc = $subaccount_id;
        $a->subscriptionTypeId = $invoice->getItem(0)->getBillingPlanData("ccbill_product_id");
        $a->allowedTypes = $invoice->getItem(0)->getBillingPlanData("ccbill_product_id");
        $a->username = $user->login;
        $a->email = $invoice->getEmail();
        $a->customer_fname = $invoice->getFirstName();
        $a->customer_lname = $invoice->getLastName();
        $a->address1 = $invoice->getStreet();
        $a->city = $invoice->getCity();
        $a->state = $invoice->getState();
        $a->zipcode = $invoice->getZip();
        $a->country = $invoice->getCountry();
        $a->phone_number = $invoice->getPhone();
        $a->payment_id = $invoice->public_id;
        $a->formName = $invoice->getItem(0)->getBillingPlanData("ccbill_form_id");
        $a->customVar1 = $invoice->public_id;
        $a->allowedCurrencies = $this->currency_codes[$invoice->currency];
        $result->setAction($a);
    }

    function getCancelUrl(Zend_Controller_Request_Abstract $request = null)
    {
        return "https://www.ccbill.com";
    }

    public function getSupportedCurrencies()
    {
        return array_keys($this->currency_codes);
    }
    public function createTransaction(Am_Request $request, Zend_Controller_Response_Http $response, array $invokeArgs)
    {
        return new Am_Paysystem_Transaction_Ccbill($this, $request, $response, $invokeArgs);
    }
    public function createThanksTransaction(Am_Request $request, Zend_Controller_Response_Http $response, array $invokeArgs)
    {
        return new Am_Paysystem_Transaction_Ccbill_Thanks($this, $request, $response, $invokeArgs);
    }

    public function getRecurringType()
    {
        return self::REPORTS_REBILL;
    }

    function getReadme()
    {
        return <<<EOT

<b>ccBill plugin setup</b>

NOTE: If you are using this plugin, you don't need ccBill script to manage
.htpasswd file for protected area. aMember will handle all these things for 
your site.

1. Login into your ccBill account https://webadmin.ccbill.com/
2. Click QuickLinks: Account Setup : Account Admin
3. Choose an existing Subaccount, or create new one, then return to this step.
4. Create the same subscription types as you have in aMember control panel,
   make sure that all settings are the same.
5. Create a form for your subscription types.
6. Goto Modify Subaccount - Advanced.
   Set Background Post Information: 
    Approval Post URL:
     %root_url%/payment/ccbill/ipn
    Denial Post URL:
     %root_url%/payment/ccbill/ipn
    Click "save" button.
    
   Goto Modify Subaccount - Basic
   Set Approval URL:
     %root_url%/payment/ccbill/thanks?customVar1=%%customVar1%%

7. Click on "User Management" link and scroll down to "Username settings". Set:
   "Username Type" : "USER DEFINED"
   "Collect Username/Password" : "Display Username, Show Password Text Field"
   "Min Username Length" : 4
   "Max Username Length" : 16
   "Min Password Length" : 4
   "Max Password Length" : 16
   Click "update" button.
 
8. Click "View Subaccount Info" in left menu to return to subaccount review
   screen. 
  Remember or write down the following parameters:
  In top left menu, you will see number, like "911399-0001"
  Here, 911399 - is your Account ID, and 0001 - is SubAccount ID.
  Have a look to "Forms" square: you will see form numbers.
  Write down form numbers with type "CREDIT". "Form name" looks like "22cc"
  and "Sub. Type ID" looks like "19".

9. Return back to aMember CP admin panel (most possible you're already here).
  Go to aMember CP -> Setup -> ccBill
  Enter your account and subaccount id. Click Save.
  Then go to aMember CP -> Edit Products, create or edit your products
  and don't forget to enter neccessary ccBill configuration parameters
  (form ID, ccbill Product ID) for each your aMember Product.

10. Try to run test payments. 
You may setup a testing account here:
     https://webadmin.ccbill.com/tools/accountMaintenance/testSignupSettings.cgi
And you may find test credit card numbers here:
     http://ccbillhelp.ccbill.com/content/test_numb_card_tls.htm

11. Contact suport@ccbill.com to obtain username and password for CCBill
Data Link System.  You will need to send them IP address of your site. If you
don't know it, ask your hosting support.

CCBill has two options when you create a datalink user.  You can make one
for a specific subaccount OR for "ALL" sub accounts.  
They need to make the datalink user for the
specific subaccount, and not use the "ALL" option.

12. Enter datalink username and pasword into ccBill plugin settings.        

13. To test datalink you can click on the following <a href="%root_url%/payment/ccbill/debug" class="ccbill_debug">link</a>
<div id="ccbill_debug"></div>
<script type="text/javascript">
$(".ccbill_debug").click(function(event)
{
    event.stopPropagation();
    var link = this;
    $("#ccbill_debug").dialog({
        autoOpen: true
        ,width: 500
        ,title: "Sending test request to ccbill"
        ,modal: true
        ,buttons: {
            "OK" : function(){
                $(this).dialog("close");
            }
        }
    });
    $.ajax({
      type: 'GET'
      ,url: link.href
      ,success: function(data, textStatus, request)
      {
        if (data.ok)
        {
            $("#ccbill_debug").html('<font color="green">No any problems found</font>');
        } else {
            $("#ccbill_debug").html(data.msg);        
        }
      }
    });
                
    return false;
});
</script>
EOT;
    }

    function dateToSQL($date)
    {
        if (preg_match('/^\d{14}$/', $date))
        {
            $s = substr($date, 0, 4) . '-' .
                substr($date, 4, 2) . '-' .
                substr($date, 6, 2);
            return $s;
        }
        else
        {
            $tm = strtotime($date);
            return date('Y-m-d', $tm);
        }
    }

    function timeToSQL($date)
    {
        $s = substr($date, 0, 4) . '-' .
            substr($date, 4, 2) . '-' .
            substr($date, 6, 2) . ' ' .
            substr($date, 8, 2) . ':' .
            substr($date, 10, 2) . ':' .
            substr($date, 12, 2) . '';
        return $s;
    }

// Datalink request here;
    function onHourly()
    {
        if (!$this->getConfig('datalink_user') || !$this->getConfig('datalink_pass'))
        {
            $this->getDi()->errorLogTable->log("ccBill plugin error: Datalink is not configured!");
            return;
        }
        define('CCBILL_TIME_OFFSET', -8 * 3600);
        $last_run = $this->getDi()->store->get(self::CCBILL_LAST_RUN);

        if (!$last_run || ($last_run < 19700101033324 ))
            $last_run = gmdate('YmdHis', time() - 15 * 3600 * 24 + CCBILL_TIME_OFFSET);

        $now_run = gmdate('YmdHis', time() + CCBILL_TIME_OFFSET);
        $last_run_tm = strtotime($this->timeToSQL($last_run));
        $now_run_tm = strtotime($this->timeToSQL($now_run));

        //ccBill allows to query data for last 24 hours only; 
        if (($now_run_tm - $last_run_tm) > 3600 * 24)
            $now_run_tm = $last_run_tm + 3600 * 24;

        $now_run = date('YmdHis', $now_run_tm);

        //ccBill allow to execute datalink once in a hour only. 
        if (($now_run_tm - $last_run_tm) <= 3600)
            return;
        $vars = array(
            'startTime' => $last_run,
            'endTime' => $now_run,
            'transactionTypes' => 'REBILL,REFUND,EXPIRE,CHARGEBACK',
            'clientAccnum' => $this->getConfig('account'),
            'clientSubacc' => $this->getConfig('subaccount_id'),
            'username' => $this->getConfig('datalink_user'),
            'password' => $this->getConfig('datalink_pass')
        );

        $r = new Am_HttpRequest($requestString = self::DATALINK_URL . '?' . http_build_query($vars, '', '&'));
        $response = $r->send();
        if (!$response)
        {
            $this->getDi()->errorLogTable->log('ccBill Datalink error: Unable to contact datalink server');
            return;
        }

        $resp = $response->getBody();

        // Log datalink requests; 
        $this->getDi()->errorLogTable->log(sprintf("ccBill Datalink debug (%s, %s):\n%s\n%s", $last_run, $now_run, $requestString, $resp));

        if (preg_match('/Error:(.+)/m', $resp, $regs))
        {
            $e = $regs[1];
            $this->getDi()->errorLogTable->log('ccBill Datalink error: ' . $e);
            return;
        }

        if ($resp == 1)
        {
            // Nothing to handle;
        }
        else
        {
            foreach (preg_split('/[\r\n]+/', $resp) as $line_orig)
            {
                $line = trim($line_orig);

                if (!strlen($line))
                    continue;

                $line = preg_split('/,/', $line);
                foreach ($line as $k => $v)
                    $line[$k] = preg_replace('/^\s*"(.+?)"\s*$/', '\1', $v);

                $public_id = $line[3];
                $invoice = $this->getDi()->invoiceTable->findByReceiptIdAndPlugin($line[3], $this->getId());
                if (!$invoice)
                {
                    $this->getDi()->errorLogTable->log('ccBill Datalink error: unable to find invoice for this record:  ' . $line_orig);
                    continue;
                }
// "REBILL","434344","0001","0312112601000035671","2012-05-21","0112142105000024275","5.98" 
// "REBILL","545455","0001","0312112601000035867","2012-05-21","0112142105000024293","6.10"                
                $transaction = null;
                switch ($line[0])
                {
                    case 'EXPIRE':
                        $transaction = new Am_Paysystem_Transaction_Ccbill_Datalink_Expire($this, $line);
                        break;
                    case 'REFUND':
                    case 'CHARGEBACK':
                        $transaction = new Am_Paysystem_Transaction_Ccbill_Datalink_Refund($this, $line);
                        break;
                    case 'RENEW':
                    case 'REBILL':
                    case 'REBill':
                        $transaction = new Am_Paysystem_Transaction_Ccbill_Datalink_Rebill($this, $line);
                        break;
                    default:
                        $this->getDi()->errorLogTable->log('ccBill Datalink error: unknown record: ' . $line_orig);
                }
                if (is_null($transaction))
                    continue;

                $transaction->setInvoice($invoice);
                try
                {
                    $transaction->process();
                }
                catch (Am_Exception $e)
                {
                    $this->getDi()->errorLogTable->log(sprintf('ccBill Datalink Error: %s while handling line: %s', $e->getMessage(), $line_orig));
                }
            }
        }
        $this->getDi()->store->set(self::CCBILL_LAST_RUN, $now_run);
    }
    function sendTest()
    {
        define('CCBILL_TIME_OFFSET', -8 * 3600);
        $last_run = $this->getDi()->store->get(self::CCBILL_LAST_RUN);
        if (!$last_run || ($last_run < 19700101033324 ))
            $last_run = gmdate('YmdHis', time() - 15 * 3600 * 24 + CCBILL_TIME_OFFSET);
        $now_run = gmdate('YmdHis', time() + CCBILL_TIME_OFFSET);
        $last_run_tm = strtotime($this->timeToSQL($last_run));
        $now_run_tm = strtotime($this->timeToSQL($now_run));
        if (($now_run_tm - $last_run_tm) > 3600 * 24)
            $now_run_tm = $last_run_tm + 3600 * 24;
        $now_run = date('YmdHis', $now_run_tm);
        $vars = array(
            'startTime' => $last_run,
            'endTime' => $now_run,
            'transactionTypes' => 'REBILL,REFUND,EXPIRE,CHARGEBACK',
            'clientAccnum' => $this->getConfig('account'),
            'clientSubacc' => $this->getConfig('subaccount_id'),
            'username' => $this->getConfig('datalink_user'),
            'password' => $this->getConfig('datalink_pass')
        );
        $r = new Am_HttpRequest($requestString = self::DATALINK_URL . '?' . http_build_query($vars, '', '&'));
        $response = $r->send();
        //global problems with connection
        if (!$response)
        {
            return '<font color="red">ccBill Datalink error: Unable to contact datalink server</font>';
        }
        $resp = $response->getBody();
        $this->getDi()->errorLogTable->log(sprintf("ccBill Datalink debug (%s, %s):\n%s\n%s", $last_run, $now_run, $requestString, $resp));
        if (preg_match('/Error:(.+)/m', $resp, $regs))
        {
            $e = $regs[1];
            //some useful instruction if error like 'authentication error'
            if(preg_match('/auth/i',$e))
            {
                $r_ip = new Am_HttpRequest('https://www.amember.com/get_ip.php');
                $ip = $r_ip->send();
                return '<font color="red">ccBill Datalink error: ' . $e.'</font><br><br>
                    Usually it happens because ccBill has wrongly <br>
                    configured your server IP address.<br><br>
                    IP of your webserver is:'.$ip->getBody().'<br><br>
                    Please copy it down, contact ccBill support <br>
                    and provide them with this IP as a correct IP for your website.<br>
                    Once ccBill reports everything is fixed<br> 
                    click on the link again and make sure the change was actually applied.';
            }
            else
                return '<font color="red">ccBill Datalink error: ' . $e. '</font>';
        }
    }
    public function debugAction(Am_Request $request, Zend_Controller_Response_Http $response, array $invokeArgs)
    {
        //requires admin to use this tool
        $admin = $this->getDi()->authAdmin->getUser();
        if (!$admin)
            return;
        //plugin is not configured
        if (!$this->getConfig('datalink_user') || !$this->getConfig('datalink_pass'))
        {
            Am_Controller::ajaxResponse(array('ok' => false, 'msg' => '<font color="red">ccBill plugin error: Datalink is not configured!</font>'));
            return;
        }
        $error = $this->sendTest();
        if($request->isXmlHttpRequest())
        {
            if(empty($error))
                Am_Controller::ajaxResponse(array('ok' => true));
            else
                Am_Controller::ajaxResponse(array('ok' => false, 'msg' => $error));
        }
        else
            echo $error;
    }
    public function directAction(Am_Request $request, Zend_Controller_Response_Http $response, array $invokeArgs)
    {
        $actionName = $request->getActionName();
        if($actionName=='debug')
        {
            $this->debugAction($request, $response, $invokeArgs);
        }
        else
            parent::directAction($request, $response, $invokeArgs);
    }
}

class Am_Paysystem_Transaction_Ccbill_datalink extends Am_Paysystem_Transaction_Abstract
{

    protected $vars;

    function __construct(Am_Paysystem_Abstract $plugin, $vars)
    {
        parent::__construct($plugin);
        $this->vars = $vars;
    }

    public function getAmount()
    {
        return $this->vars[6];
    }

    public function getUniqId()
    {
        return $this->vars[5];
    }

}

class Am_Paysystem_Transaction_Ccbill_Datalink_Rebill extends Am_Paysystem_Transaction_Ccbill_datalink
{

    public function processValidated()
    {
        $this->invoice->addPayment($this);
    }

}

class Am_Paysystem_Transaction_Ccbill_Datalink_Refund extends Am_Paysystem_Transaction_Ccbill_datalink
{

    public function getUniqId()
    {
        return $this->vars[3] . '-RFND';
    }

    public function getAmount()
    {
        return $this->vars[5];
    }

    public function processValidated()
    {
        $this->invoice->addRefund($this, $this->vars[3]);
    }

}

class Am_Paysystem_Transaction_Ccbill_Datalink_Expire extends Am_Paysystem_Transaction_Ccbill_datalink
{

    function processValidated()
    {
        $this->invoice->stopAccess($this);
    }

}

class Am_Paysystem_Transaction_Ccbill extends Am_Paysystem_Transaction_Incoming
{

    public function findInvoiceId()
    {
        return $this->request->get('payment_id');
    }

    public function getUniqId()
    {
        return $this->request->get('subscription_id');
    }

    public function validateSource()
    {
        if ($this->request->get('clientAccnum') != $this->getPlugin()->getConfig('account'))
            throw new Am_Exception_Paysystem_TransactionSource(sprintf('Incorrect CCBILL account number: [%s] instead of [%s]', $this->request->get('clientAccnum'), $this->getPlugin()->getConfig('account')));

        if ($host = gethostbyaddr($addr = $this->request->getClientIp()))
        {
            if (!strlen($host) || ($addr == $host))
            {
                //   ccbill_error("Cannot resolve host: ($addr=$host)\n");
                // let is go, as some hosts are just unable to resolve names
            }
            elseif (!preg_match('/ccbill\.com$/', $host))
                throw new Am_Exception_Paysystem_TransactionSource("POST is not from ccbill.com, it is from ($addr=$host)\n");
        }
        return true;
    }

    public function validateStatus()
    {
        if (strlen($this->request->get('reasonForDecline')) > 0)
            return false;
        return true;
    }

    public function validateTerms()
    {

        if (intval($this->invoice->getItem(0)->getBillingPlanData("ccbill_product_id")) != intval($this->request->get('typeId')))
        {
            throw new Am_Exception_Paysystem_TransactionInvalid(sprintf("Product ID doesn't match: %s and %s", intval($this->invoice->getItem(0)->getBillingPlanData("ccbill_product_id")), intval($this->request->get('typeId'))));
        }

        return true;
    }

}
class Am_Paysystem_Transaction_Ccbill_Thanks extends Am_Paysystem_Transaction_Incoming_Thanks
{
    public function process()
    {
        //redirect to thanks page only
        $this->invoice = $this->loadInvoice($this->request->get('customVar1'));
    }

    public function getUniqId()
    {
    }

    public function validateSource()
    {
    }

    public function validateStatus()
    {
    }

    public function validateTerms()
    {
    }
}