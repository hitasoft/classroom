<?php
/**
 * Class represents records from table invoice_payment
 * {autogenerated}
 * @property int $invoice_payment_id 
 * @property int $invoice_id 
 * @property int $user_id 
 * @property string $paysys_id 
 * @property string $receipt_id 
 * @property string $transaction_id 
 * @property datetime $dattm 
 * @property string $currency 
 * @property double $amount 
 * @property double $discount 
 * @property double $tax 
 * @property double $shipping 
 * @property datetime $refund_dattm 
 * @property double $base_currency_multi 
 * @see Am_Table
 * @package Am_Invoice
 */
class InvoicePayment extends Am_Record {
    /** @var User */
    protected $_user;
    /** @var Invoice */
    protected $_invoice;
    /** @var InvoiceRefund */
    protected $_refund;

    public function isFirst()
    {
        if ($this->getInvoice()->first_total == 0) return false; // if there is free trial
        return ! $this->getAdapter()->selectCell("
            SELECT COUNT(*) FROM ?_invoice_payment
            WHERE invoice_id=?d AND 
                invoice_payment_id <> ?d AND dattm <= ? AND invoice_payment_id < ?d",
            $this->invoice_id, 
            $this->invoice_payment_id, $this->dattm, $this->invoice_payment_id);
    }
    public function setFromTransaction(Invoice $invoice, Am_Paysystem_Transaction_Abstract $transaction)
    {
        $this->dattm  = $transaction->getTime()->format('Y-m-d H:i:s');
        $this->invoice_id       = $invoice->invoice_id;
        $this->invoice_public_id = $invoice->public_id;
        $this->user_id        = $invoice->user_id;
        $this->currency         = $invoice->currency;
        $this->paysys_id        = $transaction->getPaysysId();
        $this->receipt_id       = $transaction->getReceiptId();
        $this->transaction_id   =  $transaction->getUniqId();
        /// get from invoice
        $isFirst = ! ((doubleval($invoice->first_total) === 0.0) || $invoice->getPaymentsCount());
        $amount = $transaction->getAmount();
        if ($amount<=0)
            $amount = $isFirst ? $invoice->first_total : $invoice->second_total;
        $this->amount   = (double)$amount;
        $this->discount = $isFirst ? $invoice->first_discount : $invoice->second_discount;
        $this->tax      = $isFirst ? $invoice->first_tax : $invoice->second_tax;
        $this->shipping = $isFirst ? $invoice->first_shipping : $invoice->second_shipping;
        return $this;
    }
    public function fromRow(array $vars)
    {
        if (!empty($vars['refund_dattm']) && $vars['refund_dattm'] == '0000-00-00 00:00:00')
            $vars['refund_dattm'] = null;
        return parent::fromRow($vars);
    }
    public function isRefunded(){
        return (bool)$this->refund_dattm;
    }
    public function refund(DateTime $dattm){
        $this->updateQuick('refund_dattm', $dattm->format('Y-m-d H:i:s'));
    }
    public function insert($reload = true)
    {
        if ($this->currency == Am_Currency::getDefault())
            $this->base_currency_multi = 1.0;
        else
            $this->base_currency_multi = $this->getDi()->currencyExchangeTable->getRate($this->currency, sqlDate($this->dattm));
        
        $this->getDi()->hook->call(new Am_Event(Am_Event::PAYMENT_BEFORE_INSERT, 
            array('payment' => $this, 
                  'invoice' => $this->getInvoice(),
                  'user'    => $this->getInvoice()->getUser())));

        parent::insert($reload);
        
        $this->getDi()->hook->call(new Am_Event_PaymentAfterInsert(null, 
            array('payment' => $this, 
                  'invoice' => $this->getInvoice(),
                  'user'    => $this->getInvoice()->getUser())));
        
        return $this;
    }
    /**
     * @return InvoiceRefund|null
     */
    public function getRefund(){
        if (!$this->isRefunded()) return null;
        if (empty($this->_refund))
            $this->_refund = $this->getDi()->invoiceRefundTable->findFirstBy(array(
                'invoice_payment_id' => $this->pk()));
        return $this->_refund;
    }
    /**
     * @return User
     */
    public function getUser(){
        if (empty($this->_user))
            $this->_user = $this->getDi()->userTable->load($this->user_id);
        return $this->_user;
    }
    /**
     * @return Invoice
     */
    public function getInvoice(){
        if (empty($this->_invoice))
            $this->_invoice = $this->getDi()->invoiceTable->load($this->invoice_id);
        return $this->_invoice;
    }
    public function _setInvoice(Invoice $invoice)
    {
        $this->_invoice = $invoice;
        return $this;
    }
    /**
     * @return Am_Currency
     */
    function getCurrency($value = null)
    {
        $c = new Am_Currency($this->currency);
        if ($value) $c->setValue($value);
        return $c;
    }
}

/**
 * @package Am_Invoice
 */
class InvoicePaymentTable extends Am_Table {
    protected $_key = 'invoice_payment_id';
    protected $_table = '?_invoice_payment';
    protected $_recordClass = 'InvoicePayment';

    function getPaymentsCount($invoiceId){
        ///// NEED REAL ATTENTON HERE: Added to handle imported payments correctly.
        ///// Import3 script create payments with zero amount, and these payments are counted as real payments 
        ///// so updateRebillDate does not work if imported invoice have free trial period.
        return $this->_db->selectCell("SELECT COUNT(*) FROM ?_invoice_payment WHERE invoice_id=?d and amount>0", $invoiceId);
    }
    /** @return string */
    function getLastReceiptId($invoiceId)
    {
        return $this->_db->selectCell("SELECT receipt_id 
                FROM ?_invoice_payment
                WHERE invoice_id=?d 
                ORDER BY dattm DESC 
                LIMIT 1", $invoiceId);
    }
    public function insert(array $values, $returnInserted = false)
    {
        if (empty($values['dattm']))
            $values['dattm'] = $this->getDi()->sqlDateTime;
        return parent::insert($values, $returnInserted);
    }
    function selectLast($num)
    {
        return $this->selectObjects("SELECT ip.*, ir.amount refund_amount, ir.dattm refund_dattm,
            (SELECT GROUP_CONCAT(item_title SEPARATOR ', ') FROM ?_invoice_item WHERE invoice_id=ip.invoice_id) AS items,
            u.login, u.email, CONCAT(u.name_f, ' ', u.name_l) AS name,
            ip.invoice_public_id AS public_id
            FROM ?_invoice_payment ip
            LEFT JOIN ?_user u USING (user_id)
            LEFT JOIN ?_invoice_refund ir USING (invoice_payment_id)
            ORDER BY ip.invoice_payment_id DESC LIMIT ?d", $num);
    }
}
