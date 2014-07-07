<?php

abstract class Am_Grid_Filter_Aff_Abstract extends Am_Grid_Filter_Abstract
{

    protected $varList = array('filter', 'dat1', 'dat2');
    protected $datField, $filterMap;

    protected function applyFilter()
    {
        if ($filter = $this->getParam('filter')) {
            foreach ($this->filterMap as $alias => $fields) {
                foreach ($fields as $field) {
                    $c = new Am_Query_Condition_Field($field, 'LIKE', '%' . $filter . '%', $alias);
                    if (!$condition)
                        $condition = $c;
                    else
                        $condition->_or($c);
                }
            }
            $this->grid->getDataSource()->getDataSourceQuery()
                ->add($condition);
        }
        if ($filter = $this->getParam('dat1')) {
            $this->grid->getDataSource()->getDataSourceQuery()
                ->addWhere("t.date >= ?", Am_Form_Element_Date::createFromFormat(null, $filter)->format('Y-m-d 00:00:00'));
        }
        if ($filter = $this->getParam('dat2')) {
            $this->grid->getDataSource()->getDataSourceQuery()
                ->addWhere("t.date <= ?", Am_Form_Element_Date::createFromFormat(null, $filter)->format('Y-m-d 23:59:59'));
        }
    }

    abstract protected function getPlaceholder();

    function renderInputs()
    {

        $prefix = $this->grid->getId();

        $dat1 = @$this->vars['dat1'];
        $dat2 = @$this->vars['dat2'];
        $filter = @$this->vars['filter'];

        $start = ___('Start Date');
        $end = ___('End Date');

        $text_filter_title = $this->getPlaceholder();

        return <<<CUT
<input type="text" placeholder="$start" name="{$prefix}_dat1" class='datepicker' style="width:80px" value="{$dat1}" />
<input type="text" placeholder="$end" name="{$prefix}_dat2" class='datepicker' style="width:80px" value="{$dat2}" />
<input type="text" placeholder="$text_filter_title" name="{$prefix}_filter" value="{$filter}" style="width:190px" />
CUT;
    }

    function getTitle()
    {
        return '';
    }

}

class Am_Grid_Filter_Commission extends Am_Grid_Filter_Aff_Abstract
{

    protected $datField = 'date';
    protected $filterMap = array(
        'a' => array('name_f', 'name_l', 'login'),
        'u' => array('name_f', 'name_l', 'login'),
        'p' => array('title')
    );

    protected function getPlaceholder()
    {
        return ___('Filter by Affiliate/User/Product');
    }

}

class Am_Grid_Filter_Clicks extends Am_Grid_Filter_Aff_Abstract
{

    protected $datField = 'time';
    protected $filterMap = array(
        't' => array('remote_addr'),
        'a' => array('name_f', 'name_l', 'login'),
        'b' => array('title')
    );

    protected function getPlaceholder()
    {
        return ___('Filter by Affiliate/Banner/IP');
    }

}

class Am_Grid_Filter_Leads extends Am_Grid_Filter_Aff_Abstract
{

    protected $datField = 'time';
    protected $filterMap = array(
        'a' => array('name_f', 'name_l', 'login'),
        'u' => array('name_f', 'name_l', 'login'),
        'b' => array('title')
    );

    protected function getPlaceholder()
    {
        return ___('Filter by Affiliate/User/Banner');
    }

}

class Aff_AdminCommissionController extends Am_Controller_Pages
{

    public function checkAdminPermissions(Admin $admin)
    {
        return $admin->hasPermission(Bootstrap_Aff::ADMIN_PERM_ID);
    }

    public function initPages()
    {
        $this->addPage(array($this, 'createGrid'), 'grid', ___('Commissions'));
        $this->addPage(array($this, 'createClicksGrid'), 'clicks', ___('Clicks'));
        $this->addPage(array($this, 'createLeadsGrid'), 'leads', ___('Leads'));
    }

    public function createGrid()
    {
        $ds = new Am_Query($this->getDi()->affCommissionTable);
        $ds->leftJoin('?_invoice', 'i', 'i.invoice_id=t.invoice_id');
        $ds->leftJoin('?_user', 'u', 'u.user_id=i.user_id');
        $ds->leftJoin('?_user', 'a', 't.aff_id=a.user_id');
        $ds->leftJoin('?_product', 'p', 't.product_id=p.product_id');
        $ds->addField('CONCAT(a.login, \' (\', a.name_f, \' \', a.name_l,\') [#\', a.user_id, \'] \')', 'aff_name')
            ->addField('u.user_id', 'user_id')
            ->addField('CONCAT(u.login, \' (\',u.name_f, \' \',u.name_l,\') [#\', u.user_id, \'] \')', 'user_name')
            ->addField('u.email', 'user_email')
            ->addField('p.title', 'product_title')
            ->addField('IF(payout_detail_id IS NULL, \'no\', \'yes\')', 'is_paid');
        $ds->setOrder('commission_id', 'desc');

        $grid = new Am_Grid_Editable('_affcomm', ___('Affiliate Commission'), $ds, $this->_request, $this->view);
        $grid->setPermissionId(Bootstrap_Aff::ADMIN_PERM_ID);
        $grid->actionsClear();

        $userUrl = new Am_View_Helper_UserUrl();
        $grid->addField(new Am_Grid_Field_Date('date', ___('Date')))->setFormatDate();
        $grid->addField('aff_name', ___('Affiliate'))
            ->addDecorator(new Am_Grid_Field_Decorator_Link($userUrl->userUrl('{aff_id}'), '_top'));
        $grid->addField('user_name', ___('User'))
            ->addDecorator(new Am_Grid_Field_Decorator_Link($userUrl->userUrl('{user_id}'), '_top'));
        $grid->addField('product_title', ___('Product'));
        $grid->addField('record_type', ___('Type'));
        $fieldAmount = $grid->addField('amount', ___('Commission'))->setGetFunction(array($this, 'getAmount'));
        $grid->addField('is_paid', ___('Paid'));
        $grid->addField('tier', ___('Tier'));

        $grid->setFilter(new Am_Grid_Filter_Commission());
        $grid->actionAdd(new Am_Grid_Action_Total())->addField($fieldAmount, "IF(record_type='void', -1*%1\$s, %1\$s)");
        return $grid;
    }

    public function createClicksGrid()
    {
        $ds = new Am_Query($this->getDi()->affClickTable);

        $ds->leftJoin('?_user', 'a', 't.aff_id=a.user_id');
        $ds->addField('CONCAT(a.login, \' (\', a.name_f, \' \', a.name_l,\') [#\', a.user_id, \'] \')', 'aff_name');

        $ds->leftJoin('?_aff_banner', 'b', 't.banner_id=b.banner_id');
        $ds->addField('b.title', 'banner');

        $grid = new Am_Grid_ReadOnly('_affclicks', ___('Clicks'), $ds, $this->_request, $this->view);
        $grid->setPermissionId(Bootstrap_Aff::ADMIN_PERM_ID);
        $userUrl = new Am_View_Helper_UserUrl();
        $grid->addField('time', ___('Date/Time'))->setFormatFunction('amDateTime');
        $grid->addField('remote_addr', ___('IP Address'));
        $grid->addField('banner', 'Banner');
        $grid->addField(new Am_Grid_Field_Expandable('referer', ___('Referer')));

        $grid
            ->addField('aff_name', ___('Affiliate'))
            ->addDecorator(new Am_Grid_Field_Decorator_Link($userUrl->userUrl('{aff_id}'), '_top'));

        $grid->setFilter(new Am_Grid_Filter_Clicks());
        return $grid;
    }

    public function createLeadsGrid()
    {
        $ds = new Am_Query($this->getDi()->affLeadTable);
        $ds->leftJoin('?_user', 'a', 't.aff_id=a.user_id');
        $ds->addField('CONCAT(a.login, \' (\', a.name_f, \' \', a.name_l,\') [#\', a.user_id, \'] \')', 'aff_name');

        $ds->leftJoin('?_aff_banner', 'b', 't.banner_id=b.banner_id');
        $ds->addField('b.title', 'banner');

        $ds->leftJoin('?_user', 'u', 'u.user_id=t.user_id');
        $ds->addField('CONCAT(u.login, \' (\',u.name_f, \' \',u.name_l,\') [#\', u.user_id, \'] \')', 'user_name')
            ->addField('u.email', 'user_email');

        $grid = new Am_Grid_ReadOnly('_affclicks', ___('Leads'), $ds, $this->_request, $this->view);
        $grid->setPermissionId(Bootstrap_Aff::ADMIN_PERM_ID);

        $userUrl = new Am_View_Helper_UserUrl();
        $grid->addField('aff_name', ___('Affiliate'))
            ->addDecorator(new Am_Grid_Field_Decorator_Link($userUrl->userUrl('{aff_id}'), '_top'));
        $grid->addField('user_name', ___('User'))
            ->addDecorator(new Am_Grid_Field_Decorator_Link($userUrl->userUrl('{user_id}'), '_top'));
        $grid->addField('banner', ___('Banner'));
        $grid->addField('time', ___('Date/Time'))->setFormatFunction('amDateTime');
        $grid->addField('first_visited', ___('First visited'))->setFormatFunction('amDateTime');
        $grid->setFilter(new Am_Grid_Filter_Leads());
        return $grid;
    }

    public function getAmount($record, $grid, $field)
    {
        return Am_Currency::render($record->{$field});
    }

}

