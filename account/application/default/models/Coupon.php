<?php
/**
 * Class represents records from table coupon
 * {autogenerated}
 * @property int $coupon_id 
 * @property int $batch_id 
 * @property string $code 
 * @property int $used_count
 * @see Am_Table
 */

class Coupon extends Am_Record_WithData {
    
    const DISCOUNT_NUMBER  = 'number';
    const DISCOUNT_PERCENT = 'percent';

    /**
     * @return CouponBatch
     */
    function getBatch(){
        if (! $b = $this->getDi()->couponBatchTable->load($this->batch_id, false))
            throw new Am_Exception_InternalError("Database problem with coupon#{$this->coupon_id}: batch#{$this->batch_id} not found, orphaned record!");
        return $b;
    }
    /**
     * Validate if given coupon is applicable to a customer
     * @param int (optional)$user_id
     * @return string|null error message or null if all OK
     */
    function validate($user_id = null)
    {
        $batch = $this->getBatch();
        if ($batch->is_disabled)
            return ___('Coupon code disabled');
        if ($batch->use_count && ($batch->use_count <= $this->used_count))
            return ___('Coupon usage limit exceeded');
        if ($batch->user_id && $user_id && $batch->user_id != $user_id)
            return ___('This coupon belongs to another customer');
        if ($this->user_id && $this->user_id != $user_id)
            return ___('This coupon belongs to another customer');
        $tm = $this->getDi()->time;
        if ($batch->begin_date && strtotime($batch->begin_date) > $tm)
            return ___('Coupon is not yet active');
        if ($batch->expire_date && strtotime($batch->expire_date . ' 23:59:59') < $tm)
            return ___('Coupon code expired');
        if ($batch->user_use_count && $user_id)
        {
            $member_used_count = $this->getDi()->invoiceTable->findPaidCountByCouponId($this->coupon_id, $user_id); 
            if ($batch->user_use_count <= $member_used_count)
                return ___('Coupon usage limit exceeded');
        }
        return null;
    }
    
    /**
     * Mark coupon as used by payment (if payment with saved coupon was finished)
     * and saves the coupon record
     * @param Payment
     */
    function setUsed(){
        $this->used_count++;
        $this->updateSelectedFields('used_count');
    }

    /**
     * Return true if discount applies to current productId and 
     * and if that is first payment or coupon is recurring
     * @param string productType (default: 'product')
     * @param int $productId
     * @param bool $isFirstPayment
     * @return bool
     */
    function isApplicable($productType='product', $productId, $isFirstPayment=true)
    {
        $batch = $this->getBatch();
        if (!$isFirstPayment && !$batch->is_recurring)
            return false;

        $product_ids = $batch->getApplicableProductIds();
        if (!is_null($product_ids)) {
            if (($productType != 'product') || !in_array($productId, $product_ids))
                return false;
        }

        return true;
    }

    /**
     * Check if require_product prevent_if_product coupon batch settings are statisfied
     * for current purchase. We does not take into account products from current purchase,
     * only user does matter.
     *
     * @param array $products Product objects that are purchasing now
     * @param array $haveActiveIds int product# user has active subscriptions to
     * @param array $haveExpiredIds int product# user has expired subscriptions to
     * @return array empty array of OK, or an array full of error messages
     */
    function checkRequirements(array $products, array $haveActiveIds = array(), array $haveExpiredIds = array()){
        $batch = $this->getBatch();

        $error = array();
        $have = array_unique(array_merge(
                array_map(create_function('$id', 'return "ACTIVE-$id";'), $haveActiveIds),
                array_map(create_function('$id', 'return "EXPIRED-$id";'), $haveExpiredIds)
        ));
        $will_have = array_unique(array_merge(
                $have,
                array_map(create_function('Product $p', 'return "ACTIVE-".$p->product_id;'), $products)
        ));

        if ($rp = $batch->getRequireProduct()){
            if ($rp && !array_intersect($rp, $have)) {
                $ids = array();
                foreach ($rp as $s)
                    if (preg_match('/^ACTIVE-(\d+)$/', $s, $args)) $ids[] = $args[1];
                if ($ids){
                    $error[] = sprintf(___('Coupon can be used only if you have active subscription for these products: %s'), implode(',', $this->getDi()->productTable->getProductTitles($ids)));
                }
                $ids = array();
                foreach ($rp as $s)
                    if (preg_match('/^EXPIRED-(\d+)$/', $s, $args)) $ids[] = $args[1];
                if ($ids){
                    $error[] = sprintf(___('Coupon can be used only if you have expired subscription(s) for these products: %s'), implode(',', $this->getDi()->productTable->getProductTitles($ids)));
                }
            }
        }
        if ($rp = $batch->getPreventIfProduct()){
            if ($rp && array_intersect($rp, $have)) {
                $ids = array();
                foreach ($rp as $s)
                    if (preg_match('/^ACTIVE-(\d+)$/', $s, $args)) $ids[] = $args[1];

                $ids = array_intersect($ids, $haveActiveIds);
                if ($ids)
                {
                    $error[] = sprintf(___('Coupon cannot be used because you have active subscription(s) to: %s'), implode(',', $this->getDi()->productTable->getProductTitles($ids)));
                }
                $ids = array();
                foreach ($rp as $s)
                    if (preg_match('/^EXPIRED-(\d+)$/', $s, $args)) $ids[] = $args[1];

                $ids = array_intersect($ids, $haveExpiredIds);
                if ($ids)
                {
                    $error[] = sprintf(___('Coupon cannot be used because you have expired subscription(s) to: %s'), implode(',',$this->getDi()->productTable->getProductTitles($ids)));
                }
            }
        }
        return $error;
    }

    public function update()
    {
        $hm = $this->getDi()->hook;
        if ($hm->have(Am_Event::COUPON_BEFORE_UPDATE))
        {
            $old = $this->getTable()->load($this->pk());
            $old->toggleFrozen(true);
            $hm->call(Am_Event::COUPON_BEFORE_UPDATE, array('coupon' => $this, 'old' => $old));
        }
        if (!$this->user_id)
            $this->user_id = null;
        if (!is_numeric($this->user_id)) {
            $user = $this->getDi()->userTable->findFirstByLogin($this->user_id);
            $this->user_id = $user ? $user->pk() : null;
        }

        parent::update();
        return $this;
    }
}

class CouponTable extends Am_Table_WithData {
    protected $_key = 'coupon_id';
    protected $_table = '?_coupon';
    protected $_recordClass = 'Coupon';

    function generateCouponCode($length, &$new_length)
    {
       $attempt = 0;
       do {
            $code = strtoupper(md5(uniqid('', 1)));
            $code = substr($code, 0, $length);
            //increase lenth of coupon
            //if can not generate unique
            //code for long time
            $attempt++;
            if ($attempt>2) {
                $attempt = 0;
                $length++;
            }
        } while ($this->findByCode($code));

        $new_length = $length;
        return $code;
    }
}

