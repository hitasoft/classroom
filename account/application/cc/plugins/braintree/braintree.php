<?php
/**
 * @table paysystems
 * @id braintree
 * @title Braintree
 * @visible_link https://www.braintreepayments.com/
 * @recurring cc
 */
class Am_Paysystem_Braintree extends Am_Paysystem_CreditCard
{

    const PLUGIN_STATUS = self::STATUS_BETA;
    const PLUGIN_DATE = '$Date$';
    const PLUGIN_REVISION = '4.3.4';
    const CUSTOMER_ID = 'braintree_customer_id';

    protected $defaultTitle = "Pay with your Credit Card";
    protected $defaultDescription = "accepts all major credit cards";

    public function _process(Invoice $invoice, Am_Request $request, Am_Paysystem_Result $result)
    {
        $action = new Am_Paysystem_Action_Redirect($this->getPluginUrl(self::ACTION_CC));
        $action->cc_id = $invoice->getSecureId($this->getId());
        $result->setAction($action);
    }

    protected function ccActionValidateSetInvoice(Am_Request $request, array $invokeArgs)
    {
        $invoiceId = $request->getFiltered('cc_id');
        if (!$invoiceId)
            throw new Am_Exception_InputError("invoice_id is empty - seems you have followed wrong url, please return back to continue");

        $invoice = $this->getDi()->invoiceTable->findBySecureId($invoiceId, $this->getId());
        if (!$invoice)
            throw new Am_Exception_InputError('You have used wrong link for payment page, please return back and try again');

        if ($invoice->isCompleted())
            throw new Am_Exception_InputError(sprintf(___('Payment is already processed, please go to %sMembership page%s'), "<a href='" . htmlentities($this->getDi()->config->get('root_url')) . "/member'>", "</a>"));

        if ($invoice->paysys_id != $this->getId())
            throw new Am_Exception_InputError("You have used wrong link for payment page, please return back and try again");

        if ($invoice->tm_added < sqlTime('-1 days'))
            throw new Am_Exception_InputError("Invoice expired - you cannot open invoice after 24 hours elapsed");

        $this->invoice = $invoice; // set for reference 
    }

    public function getRecurringType()
    {
        return self::REPORTS_CRONREBILL;
    }

    function createForm($actionName)
    {
        return new Am_Form_CreditCard_Braintree($this);
    }

    function createController(Am_Request $request, Zend_Controller_Response_Http $response, array $invokeArgs)
    {
        return new Am_Controller_CreditCard_Braintree($request, $response, $invokeArgs);
    }

    public function init()
    {
        parent::init();
        if ($this->isConfigured())
        {
            require_once('lib/Braintree.php');
            Braintree_Configuration::merchantId($this->getConfig('merchant_id'));
            Braintree_Configuration::privateKey($this->getConfig('private_key'));
            Braintree_Configuration::publicKey($this->getConfig('public_key'));
            Braintree_Configuration::environment($this->getConfig('sandbox') ? 'sandbox' : 'production');
        }
    }

    public function isConfigured()
    {
        return $this->getConfig('merchant_id') && $this->getConfig('public_key')
            && $this->getConfig('private_key') && $this->getConfig('client_side_key');
    }

    public function _initSetupForm(Am_Form_Setup $form)
    {
        $form->addText('merchant_id')->setLabel('Your BrainTree Merchant ID');
        $form->addText('public_key')->setLabel('Your BrainTree Public Key');
        $form->addText('private_key')->setLabel('Your BrainTree Private Key');
        $form->addTextArea('client_side_key', array('rows' => 10, 'cols' => 40))->setLabel('Client-Side Encryption Key');
        $form->addAdvCheckbox('sandbox')->setLabel('Sandbox testing');
    }

    // We do not store CC info.
    public function storesCcInfo()
    {
        return false;
    }

    public function _doBill(Invoice $invoice, $doFirst, CcRecord $cc, Am_Paysystem_Result $result)
    {

        if ($doFirst)
        {
            try
            {
                // We was redirected from Braintree so need to get result;
                $res = Braintree_TransparentRedirect::confirm($_SERVER['QUERY_STRING']);
                if ($res instanceof Braintree_Result_Error)
                {
                    $result->setFailed($res->message);
                    return;
                }
                else
                {
                    $invoice->getUser()->data()->set(self::CUSTOMER_ID, $res->customer->id)->update();
                }
            }
            catch (Braintree_Exception_NotFound $e)
            {
                
            }
            catch (Exception $e)
            {
                $result->setFailed($e->getMessage());
                return;
            }
        }

        if (!($customer_id = $invoice->getUser()->data()->get(self::CUSTOMER_ID)))
        {
            $result->setFailed('Empty customer ID. Please update CC info');
            return;
        }

        // Now attempt to submit transaction if required; 
        if (doubleval($invoice->first_total) > 0)
        {
            $transaction = new Am_Paysystem_Transaction_CreditCard_Braintree_Sale($this, $invoice, null, $doFirst);
            $transaction->run($result);
        }
        else
        {
            $transaction = new Am_Paysystem_Transaction_Free($this);
            $transaction->setInvoice($invoice);
            $transaction->process();
            $result->setSuccess($transaction);
        }
    }

    public function processRefund(InvoicePayment $payment, Am_Paysystem_Result $result, $amount)
    {
        $trans = new Am_Paysystem_Transaction_CreditCard_Braintree_Refund($this, $payment->getInvoice(), null, null, $payment);
        $trans->run($result);
        return $result;
    }

}

class Am_Form_CreditCard_Braintree extends Am_Form_CreditCard
{

    public function __construct(Am_Paysystem_CreditCard $plugin, $formType = self::PAYFORM)
    {
        $this->plugin = $plugin;
        $this->formType = $formType;
        $this->payButtons = array(
            self::PAYFORM => ___('Subscribe And Pay'),
            self::ADMIN_UPDATE => ___('Update Credit Card Info'),
            self::USER_UPDATE => ___('Update Credit Card Info'),
            self::ADMIN_INSERT => ___('Update Credit Card Info'),
        );
        Am_Form::__construct('cc', array('action' => Braintree_TransparentRedirect::url()));
    }

    public function init()
    {
        Am_Form::init();

        $name = $this->addGroup()->setLabel(array(___('Cardholder Name'), sprintf(___('cardholder first and last name, exactly as%son the card'), '<br/>')));
        $name->addRule('required', ___('Please enter credit card holder name'));

        $name->addText('customer__credit_card__cardholder_name', array('size' => 30))
            ->addRule('required', ___('Please enter cardholder name exactly as on card'))
            ->addRule('regex', ___('Please enter credit card holder name'), '|^[a-zA-Z_\' -]+$|');

        $this->addText('customer__credit_card__number', array('autocomplete' => 'off', 'size' => 22, 'maxlength' => 22))
            ->setLabel(___('Credit Card Number'), ___('for example: 1111222233334444'))
            ->addRule('required', ___('Please enter Credit Card Number'))
            ->addRule('regex', ___('Invalid Credit Card Number'), '/^[0-9]+$/')
            ->addRule('callback2', 'Invalid CC#', array($this->plugin, 'validateCreditCardNumber'));

        $gr = $this->addGroup()
            ->setLabel(array(___('Card Expire'), ___('Select card expiration date - month and year')));
        $gr->addSelect('customer__credit_card__expiration_month')
            ->loadOptions($this->getMonthOptions());
        $gr->addSelect('customer__credit_card__expiration_year')
            ->loadOptions($this->getYearOptions());


        $this->addPassword('customer__credit_card__cvv', array('autocomplete' => 'off', 'size' => 4, 'maxlength' => 4))
            ->setLabel(___('Credit Card Code'), sprintf(___('The "Card Code" is a three- or four-digit security code%sthat is printed on the back of credit cards in the card\'s%ssignature panel (or on the front for American Express cards).'), '<br>', '<br>'))
            ->addRule('required', ___('Please enter Credit Card Code'))
            ->addRule('regex', ___('Please enter Credit Card Code'), '/^\s*\d{3,4}\s*$/');


        $fieldSet = $this->addFieldset(___('Address Info'))
            ->setLabel(array(___('Address Info'), ___('(must match your credit card statement delivery address)')));

        $bname = $fieldSet->addGroup()->setLabel(array(___('Billing Name'), sprintf(___('Billing Address First and Last name'), '<br/>')));
        $bname->addRule('required', ___('Please enter billing  name'));

        $bname->addText('customer__credit_card__billing_address__first_name', array('size' => 15))
            ->addRule('required', ___('Please first enter name'))
            ->addRule('regex', ___('Please enter first name'), '|^[a-zA-Z_\' -]+$|');

        $bname->addText('customer__credit_card__billing_address__last_name', array('size' => 15))
            ->addRule('required', ___('Please enter last name'))
            ->addRule('regex', ___('Please enter last name'), '|^[a-zA-Z_\' -]+$|');

        $fieldSet->addText('customer__credit_card__billing_address__street_address')
            ->setLabel(___('Street Address'))
            ->addRule('required', ___('Please enter Street Address'));

        $fieldSet->addText('customer__credit_card__billing_address__extended_address')
            ->setLabel(___('Street Address (Second Line)'));

        $fieldSet->addText('customer__credit_card__billing_address__locality')
            ->setLabel(___('City'));

        $fieldSet->addText('customer__credit_card__billing_address__region')
            ->setLabel(___('State'));


        $fieldSet->addText('customer__credit_card__billing_address__postal_code')
            ->setLabel(___('Zipcode'))
            ->addRule('required', ___('Please enter ZIP code'));

        $fieldSet->addText('customer__credit_card__billing_address__country_name')
            ->setLabel(___('Country'))
            ->addRule('required', ___('Please enter Country'));

        // if free trial set _TPL_CC_INFO_SUBMIT_BUT2
        $buttons = $this->addGroup();
        $buttons->addSubmit('_cc_', array('value' =>
            '    '
            . $this->payButtons[$this->formType]
            . '    '));
        $this->plugin->onFormInit($this);
    }

    public function getDefaultValues(User $user)
    {
        return array(
            'customer__credit_card__cardholder_name' => strtoupper($user->name_f . ' ' . $user->name_l),
            'customer__credit_card__billing_address__first_name' => $user->name_f,
            'customer__credit_card__billing_address__last_name' => $user->name_l,
            'customer__credit_card__billing_address__street_address' => $user->street,
            'customer__credit_card__billing_address__extended_address' => $user->street2,
            'customer__credit_card__billing_address__locality' => $user->city,
            'customer__credit_card__billing_address__region' => Am_Di::getInstance()->stateTable->getTitleByCode($user->country, $user->state),
            'customer__credit_card__billing_address__postal_code' => $user->zip,
            'customer__credit_card__billing_address__country_name' => Am_Di::getInstance()->countryTable->getTitleByCode($user->country),
        );
    }

    private function getMonthOptions()
    {
        $locale = Zend_Registry::get('Am_Locale');
        $months = array();

        foreach ($locale->getMonthNames('wide', false) as $k => $v)
            $months[sprintf('%02d', $k)] = sprintf('(%02d) %s', $k, $v);
        $months[''] = '';
        ksort($months);
        return $months;
    }

    private function getYearOptions()
    {
        $years4 = range(date('Y'), date('Y') + 10);
        $years2 = range(date('y'), date('y') + 10);
        array_unshift($years4, '');
        array_unshift($years2, '');
        return array_combine($years2, $years4);
    }

}

class Am_Controller_CreditCard_Braintree extends Am_Controller_CreditCard
{

    function createForm()
    {
        $form = $this->plugin->createForm($this->_request->getActionName(), $this->invoice);
        $form->addHidden(Am_Controller::ACTION_KEY)->setValue($this->_request->getActionName());
        $form->addHidden('cc_id')->setValue($this->getFiltered('cc_id'));

        $user = $this->invoice->getUser();
        $form->setDataSources(array(
            $this->_request,
            new HTML_QuickForm2_DataSource_Array($form->getDefaultValues($user))
        ));

        $form->addHidden('tr_data')->setValue(
            Braintree_TransparentRedirect::createCustomerData(array(
                'redirectUrl' => $this->plugin->getPluginUrl(Am_Paysystem_Braintree::ACTION_CC) . "?cc_id=" . $this->getFiltered('cc_id'),
                'customer' => array(
                    'firstName' => $this->invoice->getUser()->name_f,
                    'lastName' => $this->invoice->getUser()->name_l,
                    'email' => $this->invoice->getUser()->email,
                    'phone' => $this->invoice->getUser()->phone,
                )
            ))
        );

        return $form;
    }

    public function ccAction()
    {
        // invoice must be set to this point by the plugin
        if (!$this->invoice)
            throw new Am_Exception_InternalError('Empty invoice - internal error!');
        $this->form = $this->createForm();

        if ($this->getParam('http_status') && $this->getParam('hash'))
        {
            if ($this->processCc())
                return;
        }
        $this->view->form = $this->form;
        $this->view->invoice = $this->invoice;
        $this->view->display_receipt = true;
        $this->view->display('cc/info.phtml');
    }

}

class Am_Paysystem_Transaction_CreditCard_Braintree extends Am_Paysystem_Transaction_CreditCard
{

    protected $paysystemResponse;

    public function getUniqId()
    {
        return $this->paysystemResponse->transaction->id;
    }

    public function getRequest()
    {
        
    }

    public function submitTransaction($request)
    {
        
    }

    public function validate()
    {
        
    }

    public function run(Am_Paysystem_Result $result)
    {
        $this->result = $result;
        $log = $this->getInvoiceLog();

        $request = $this->getRequest();
        $log->add($request);
        $this->paysystemResponse = $this->submitTransaction($request);
        $log->add($this->paysystemResponse);

        if ($this->paysystemResponse->success)
        {
            try
            {
                $result->setSuccess();
                $this->processValidated();
            }
            catch (Exception $e)
            {
                if ($e instanceof PHPUnit_Framework_Error)
                    throw $e;
                if ($e instanceof PHPUnit_Framework_Asser)
                    throw $e;
                if (!$result->isFailure())
                    $result->setFailed(___("Payment failed"));
                $log->add($e);
            }
        }else
        {
            if ($errors = $this->paysystemResponse->errors->deepAll())
            {
                $result->setFailed("Error: " . $errors);
            }
            else if ($this->paysystemResponse->transaction->status == 'processor_declined')
            {
                $result->setFailed("Declined: " . $this->paysystemResponse->transaction->processorResponseText);
            }
            else
            {
                $result->setFailed("Gateway Rejected: " . $this->paysystemResponse->transaction->gatewayRejectionReason);
            }
        }
    }

    public function parseResponse()
    {
        
    }

}

class Am_Paysystem_Transaction_CreditCard_Braintree_Sale extends Am_Paysystem_Transaction_CreditCard_Braintree
{

    function getRequest()
    {
        return array(
            'amount' => ($this->doFirst ? $this->invoice->first_total : $this->invoice->second_total),
            'customerId' => $this->invoice->getUser()->data()->get(Am_Paysystem_Braintree::CUSTOMER_ID),
            'orderId' => $this->invoice->public_id . '-' . time(),
            'options' => array(
                'submitForSettlement' => true
            )
        );
    }

    function submitTransaction($request)
    {
        return Braintree_Transaction::sale($request);
    }

}

class Am_Paysystem_Transaction_CreditCard_Braintree_Refund extends Am_Paysystem_Transaction_CreditCard_Braintree
{

    protected $payment;

    function __construct(Am_Paysystem_Abstract $plugin, Invoice $invoice, $request, $doFirst, $payment)
    {
        $this->payment = $payment;
        parent::__construct($plugin, $invoice, $request, $doFirst);
    }

    function getRequest()
    {
        return $this->payment->transaction_id;
    }

    function submitTransaction($request)
    {
        return Braintree_Transaction::refund($request);
    }

    function processValidated()
    {
        $this->invoice->addRefund($this, $this->payment->transaction_id);
    }

}