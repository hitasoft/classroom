<?php
/**
 * Class represents records from table aff_commission_rule
 * {autogenerated}
 * @property int $rule_id 
 * @property string $comment 
 * @property int $sort_order 
 * @property mixed $conditions
 * @property double $free_signup_c 
 * @property string $free_signup_t 
 * @property double $first_payment_c 
 * @property string $first_payment_t 
 * @property double $recurring_c 
 * @property string $recurring_t 
 * @property int $type
 * @property int $tier
 * @property float $multi
 * @property bool $is_disabled
 * @see Am_Table
 */
class AffCommissionRule extends Am_Record 
{
    const TYPE_MULTI    = 'multi';
    const TYPE_CUSTOM   = 'custom';
    const TYPE_GLOBAL   = 'global';
    
    const COND_AFF_SALES_COUNT = 'aff_sales_count';
    const COND_AFF_ITEMS_COUNT = 'aff_items_count';
    const COND_AFF_SALES_AMOUNT = 'aff_sales_amount';
    const COND_AFF_GROUP_ID    = 'aff_group_id';
    const COND_PRODUCT_ID      = 'product_id';
    const COND_PRODUCT_CATEGORY_ID = 'product_category_id';
    const COND_COUPON = 'coupon';
    
    static function getTypes()
    {
        return array(
            self::TYPE_CUSTOM   => ___("Custom Commission"),
            self::TYPE_MULTI    => ___("Multiplier"),
        );
    }

    function getTypeTitle()
    {
        $level = $this->tier + 1;
        $types = self::getTypes();
        
        return $this->type == self::TYPE_GLOBAL ? ($this->tier > 0 ? ___('%d-Tier Affiliates Commission
', $level) : ___('Global Commission')) : $types[$this->type];
    }
    
    function isGlobal()
    {
        if (empty($this->type)) return false;
        return self::isGlobalType($this->type);
    }
    static function isGlobalType($type)
    {
        return $type == self::TYPE_GLOBAL;
    }
    function match(Invoice $invoice, InvoiceItem $item, User $aff, $paymentNumber = 0, $tier = 0, $paymentDate = 'now')
    {
        if ($this->type == self::TYPE_GLOBAL)
            return $tier == $this->tier;

        if ($tier != 0) return false; // no custom rules for 2-tier

        // check conditions
        foreach ($this->getConditions() as $conditionType => $vars)
        {
            switch ($conditionType)
            {
                case self::COND_AFF_SALES_COUNT:;
                case self::COND_AFF_ITEMS_COUNT:;
                case self::COND_AFF_SALES_AMOUNT:;
                    if (empty($vars['count']) || empty($vars['days'])) return false;
                    $e = sqlDate($paymentDate);
                    $b = sqlDate($e . '-' . $vars['days'] . ' days');
                    $stats = $this->getDi()->affCommissionTable->getAffStats($aff->pk(), $b, $e);
                    switch ($conditionType)
                    {
                        case self::COND_AFF_ITEMS_COUNT: $key = 'items_count'; break;
                        case self::COND_AFF_SALES_COUNT: $key = 'count'; break;
                        default: $key = 'amount'; 
                    }
                    if ($stats[$key] < $vars['count']) 
                        return false;
                    break;
                case self::COND_AFF_GROUP_ID:
                    if (!array_intersect($aff->getGroups(), (array)$vars))
                        return false;
                    break;
                case self::COND_PRODUCT_ID:
                    if (($item->item_type != 'product') || !in_array($item->item_id, (array)$vars))
                        return false;
                    break;
                case self::COND_PRODUCT_CATEGORY_ID:
                    if ($item->item_type != 'product') return false;
                    $pr = $item->tryLoadProduct();
                    if (!$pr) return false;
                    if (!array_intersect($pr->getCategories(), (array)$vars))
                        return false;
                    break;
                case self::COND_COUPON:
                    $coupon = $invoice->getCoupon();
                    switch ($vars['type']) {
                        case 'any' :
                            $coupon_cond_match = (bool)$coupon;
                            break;
                        case 'coupon':
                            $coupon_cond_match = $coupon && ($vars['code'] == $coupon->code);
                            break;
                        case 'batch' :
                            $coupon_cond_match = $coupon && ($vars['batch_id'] == $coupon->batch_id);
                            break;
                    }
                    if ($vars['used'] ? !$coupon_cond_match : $coupon_cond_match)
                        return false;
                    break;
                default: 
                    return false;
            }
        }
        return true;
    }
    function getConditions()
    {
        return (array)json_decode($this->conditions, true);
    }
    function setConditions(array $conditions)
    {
        $this->conditions = json_encode($conditions);
        return $this;
    }
    function render($pad = "")
    {
        $condition = $this->renderConditions();
        if ($condition) $condition = $pad . "  conditions: " . $condition. "\n";
        return 
            sprintf("%s%s (%s)\n", $pad, $this->comment, $this->renderCommission()).
            $condition;
    }
    function renderConditions()
    {
        $ret = array();
        foreach ($this->getConditions() as $conditionType => $vars)
        {
            switch ($conditionType)
            {
                case self::COND_AFF_ITEMS_COUNT:;
                    $ret[] = ___("affiliate generated %d item sales last %d days",
                        $vars['count'], $vars['days']);
                    break;
                case self::COND_AFF_SALES_COUNT:;
                    $ret[] = ___("affiliate generated %d sales last %d days",
                        $vars['count'], $vars['days']);
                    break;
                case self::COND_AFF_SALES_AMOUNT:;
                    $ret[] = ___("affiliate generated %d%s in commissions last %d days",
                        $vars['count'], Am_Currency::getDefault(), $vars['days']);
                    break;
                case self::COND_AFF_GROUP_ID:
                    $v = array();
                    foreach ($this->getDi()->userGroupTable->loadIds((array)$vars) as $group)
                        $v[] = $group->title;
                    $ret[] = ___("affiliate group IN (%s)", implode(", ", $v));
                    break;
                case self::COND_PRODUCT_ID:
                    $v = array();
                    foreach ($this->getDi()->productTable->loadIds((array)$vars) as $product)
                        $v[] = $product->title;
                    $ret[] = ___("products IN (%s)", implode(", ", $v));
                    break;
                case self::COND_PRODUCT_CATEGORY_ID:
                    $v = array();
                    foreach ($this->getDi()->productCategoryTable->loadIds((array)$vars) as $product)
                        $v[] = $product->title;
                    $ret[] = ___("product category IN (%s)", implode(", ", $v));
                    break;
                case self::COND_COUPON:
                    $end = '';
                    switch ($vars['type']) {
                            case 'any' :
                                $end = ___('Any Coupon');
                                break;
                            case 'coupon' :
                                $end = ___('Coupon with Code : %s', $vars['code']);
                                break;
                            case 'batch' :
                                $options = $this->getDi()->couponBatchTable->getOptions(false);
                                $end = ___('Coupon from Batch : %s', $options[$vars['batch_id']]);
                                break;
                        }
                    $ret[] = ($vars['used'] ? ___('Used ') : ___("Did't Use ")) . $end;
                    break;
                default: 
                    return false;
            }
        }
        return implode(" AND ", $ret);
    }
    /**
     * Return human-readable representation of commission
     */
    function renderCommission()
    {
        $text = "";
        if ($this->type == AffCommissionRule::TYPE_MULTI)
            return ___('commission found by next rules') . '&times; ' . $this->multi;
        if ($this->type == AffCommissionRule::TYPE_GLOBAL && $this->tier>0)
            return $this->first_payment_c . '%';
        foreach (array('first_payment' => ___('First Payment'), 'recurring' => ___('Second and Subsequent Payments'), 'free_signup' => ___('Free Signup')) as $fieldName => $label)
        {
            if ($this->get($fieldName . '_c') <= 0) continue;
            $v = $this->get($fieldName . '_c') . $this->get($fieldName . '_t');
            $text .= $label . ":" . $v . ", ";
        }
        return trim($text, ', ');
    }
}

class AffCommissionRuleTable extends Am_Table 
{
    protected $_key = 'rule_id';
    protected $_table = '?_aff_commission_rule';
    protected $_recordClass = 'AffCommissionRule';
    
    protected $rulesCache = array();
    
    /**
     * @return Am_Query with correct order set
     */
    public function createQuery()
    {
        $q = new Am_Query($this);
        $q->setOrderRaw("IF(type='multi', 1, type+0), sort_order");
        return $q;
    }
    public function _resetCache()
    {
        $this->rulesCache = array();
    }
    
    /**
     * @param Invoice $invoice
     * @param int $paymentNumber number of payment for given invoice, staring from 0
     * @param int $tier Affiliate level - starting from 0
     * @return array<AffCommissionRule> matching the invoice
     */
    public function findRules(Invoice $invoice, InvoiceItem $item, User $aff, $paymentNumber = 0, $tier = 0, $paymentDate = 'now')
    {
        if (!$this->rulesCache)
        {
            $this->rulesCache = $this->createQuery()->selectPageRecords(0, 99999);
        }
        $ret = array();
        foreach ($this->rulesCache as $rule)
        {
            /* @var $rule AffCommissionRule */
            if ($rule->match($invoice, $item, $aff, $paymentNumber, $tier, $paymentDate)) 
            {
                $ret[] = $rule;
                if($rule->type == AffCommissionRule::TYPE_GLOBAL && $rule->tier > 0) $rule->first_payment_t = '%';
                if ($rule->type != AffCommissionRule::TYPE_MULTI)  
                    break; // last rule
            }
        }
        return $ret;
    }
    public function calculate(Invoice $invoice, InvoiceItem $item, User $aff, $paymentNumber = 0, $tier = 0, $paymentAmount = 0.0, $paymentDate = 'now')
    {
        // take aff.commission_days in account for 1-tier only
        if ($tier == 0 && ($commissionDays = $this->getDi()->config->get('aff.commission_days')))
        {
            $signupDays = $this->getDi()->time - strtotime($invoice->getUser()->aff_added ? $invoice->getUser()->aff_added : $invoice->getUser()->added);
            $signupDays = intval($signupDays / (3600*24)); // to days
            if ($commissionDays < $signupDays)
                return; // no commission for this case, affiliate<->user relation is expired
        }
        
        $multi = 1.0;
        $isFirst = $paymentNumber<=1;
        $prefix = $isFirst && (float)$item->first_total ? 'first' : 'second';

        if ($tier == 0) // for tier 0 get amount paid for given item
        {
            if ($invoice->get("{$prefix}_total") == 0)
                $paidForItem = 0; // avoid division by zero
            else
                $paidForItem = $paymentAmount * $item->get("{$prefix}_total") / $invoice->get("{$prefix}_total");
        } else { // for higher tier just take amount paid to previous tier
            $paidForItem = $paymentAmount;
        }
        foreach ($this->findRules($invoice, $item, $aff, $paymentNumber, $tier, $paymentDate) as $rule)
        {
            // Second tier commission have to be calculated as percent from First tier commission. 
            if ($tier > 0)
                return moneyRound($rule->first_payment_c * $paidForItem / 100);

            if ($rule->type == AffCommissionRule::TYPE_MULTI)
                $multi *= $rule->multi;
            else {
                if ($paidForItem == 0)
                {
                    // free signup?
                    if (($paymentNumber == 0) && $rule->free_signup_c) return moneyRound($multi * $rule->free_signup_c);
                } elseif ($isFirst) {
                    // first payment
                    if ($rule->first_payment_t == '%')
                        return moneyRound($multi * $rule->first_payment_c * $paidForItem / 100);
                    else
                        return moneyRound($multi * $rule->first_payment_c);
                } else {
                    // recurring payment
                    if ($rule->recurring_t == '%')
                        return moneyRound($multi * $rule->recurring_c * $paidForItem / 100);
                    else
                        return moneyRound($multi * $rule->recurring_c);
                }
            }
        }
    }

    public function getMaxTier()
    {
        return $this->getDi()->db->selectCell("SELECT MAX(tier) FROM ?_aff_commission_rule");
    }
    
    /**
     * Process invoice and insert necessary commissions for it
     *
     * External code should guarantee that this method with $payment = null will be called
     * only once for each user for First user invoice
     */
    public function processPayment(Invoice $invoice, InvoicePayment $payment = null)
    {
        $aff_id = $invoice->aff_id;
        /* @var $coupon Coupon */
        if (!$aff_id && $coupon = $invoice->getCoupon()) { // try to find affiliate by coupon
            $aff_id = $coupon->aff_id ?
                        $coupon->aff_id :
                        $coupon->getBatch()->aff_id;
        }
        if (empty($aff_id))
            $aff_id = $invoice->getUser()->aff_id;

        if ($aff_id && empty($invoice->aff_id)) // set aff_id to invoice for quick access next time
            $invoice->updateQuick('aff_id', $aff_id);
        
        // run event to get plugins chance choose another affiliate
        $event = new Am_Event(Bootstrap_Aff::AFF_FIND_AFFILIATE, array(
            'invoice' => $invoice, 
            'payment' => $payment,
        ));
        $event->setReturn($aff_id);
        $this->getDi()->hook->call($event);
        $aff_id = $event->getReturn();

        if (empty($aff_id)) return ; // no affiliate id registered
        if ($aff_id == $invoice->getUser()->pk()) return; //strange situation
        
        // load affiliate and continue
        $aff = $this->getDi()->userTable->load($aff_id, false);
        if (!$aff || !$aff->is_affiliate) return; // affiliate not found
        // try to load other tier affiliate
        $aff_tier = $aff;
        $aff_tiers = array();
        $aff_tiers_exists = array($aff->pk());
        for ($tier=1; $tier<=$this->getMaxTier(); $tier++) {
            if (!$aff_tier->aff_id || ($aff_tier->pk() == $invoice->getUser()->pk()))
                break;

            $aff_tier = $this->getDi()->userTable->load($aff_tier->aff_id, false);
            if (!$aff_tier || //not exists
                !$aff_tier->is_affiliate || //not affiliate
                ($aff_tier->pk() == $invoice->getUser()->pk()) || //original user
                in_array($aff_tier->pk(), $aff_tiers_exists))  //already in chain
                break;

            $aff_tiers[$tier] = $aff_tier;
            $aff_tiers_exists[] = $aff_tier->pk();
        }

        $isFirst = !$payment || $payment->isFirst(); //to define price field
        $paymentNumber = is_null($payment) ? 0 : $invoice->getPaymentsCount();
        if (!$payment)
            $tax = 0;
        else
            $tax = $this->getDi()->config->get('aff.commission_include_tax', false) ? 0 : doubleval($payment->tax);
        $amount  = $payment ? ($payment->amount - $tax): 0;
        $date    = $payment ? $payment->dattm : 'now';
        // now calculate commissions
        $items = is_null($payment) ? array_slice($invoice->getItems(), 0, 1) : $invoice->getItems();
        foreach ($items as $item)
        {

            //we do not calculate commission for free items in invoice
            $prefix = $isFirst ? 'first' : 'second';
            if (!is_null($payment) && !(float)$item->get("{$prefix}_total")) continue;

            $comm = $this->getDi()->affCommissionRecord;
            $comm->date  = sqlDate($date);
            $comm->record_type = AffCommission::COMMISSION;
            $comm->invoice_id = $invoice->invoice_id;
            $comm->invoice_item_id = $item->invoice_item_id;
            $comm->invoice_payment_id  = $payment ? $payment->pk() : null;
            $comm->receipt_id  = $payment ? $payment->receipt_id : null;
            $comm->product_id  = $item->item_id;
            $comm->is_first    =  $paymentNumber<=1;

            $comm->_setPayment($payment);
            $comm->_setInvoice($invoice);
            $comm_tier = clone $comm;

            $topay_this = $topay = $this->calculate($invoice, $item, $aff, $paymentNumber, 0, $amount, $date);
            if ($topay>0)
            {
                $comm->aff_id = $aff->pk();
                $comm->amount = $topay;
                $comm->tier = 0;
                $comm->_setAff($aff);
                $comm->insert();
            }

            foreach ($aff_tiers as $tier => $aff_tier) {
                $topay_this = $this->calculate($invoice, $item, $aff_tier, $paymentNumber, $tier, $topay_this, $date);
                if ($topay_this>0)
                {
                    $comm_this = clone $comm_tier;
                    $comm_this->aff_id = $aff_tier->pk();
                    $comm_this->amount = $topay_this;
                    $comm_this->tier = $tier;
                    $comm_this->_setAff($aff_tier);
                    $comm_this->insert();
                }
            }
        }
    }
    
    /**
     * Process refund to rollback existing commissions
     */
    public function processRefund(Invoice $invoice, InvoiceRefund $refund)
    {
        if ($refund->invoice_payment_id)
            $toRefund = $this->getDi()->affCommissionTable->findByInvoicePaymentId($refund->invoice_payment_id);
        else
            $toRefund = $this->getDi()->affCommissionTable->findLastRecordsByInvoiceId($refund->invoice_id);
        foreach ($toRefund as $affCommission)
        {
            $void = $this->getDi()->affCommissionRecord;
            $void->fromRow($affCommission->toRow());
            $void->date = $refund ? $refund->dattm : sqlDate('now');
            $void->commission_id = null;
            $void->record_type = AffCommission::VOID;
            try {
                $void->insert();
            } catch (Am_Exception_Db_NotUnique $e) {
                // already handled? keep silence
            }
        }
    }
    
    public function hasCustomRules()
    {
        return $this->_db->selectCell("SELECT COUNT(*) FROM $this->_table 
            WHERE `type` NOT IN (?a)", 
            array(AffCommissionRule::TYPE_GLOBAL,));
    }
}