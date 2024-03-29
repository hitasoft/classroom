<?php

include_once('AdminCommissionController.php');

class Am_Grid_Filter_AffCommission extends Am_Grid_Filter_Aff_Abstract
{

    protected $datField = 'date';
    protected $filterMap = array(
        'u' => array('name_f', 'name_l', 'login'),
        'p' => array('title')
    );

    protected function getPlaceholder()
    {
        return ___('Filter by User/Product');
    }

}

class Aff_AdminController extends Am_Controller
{

    public function checkAdminPermissions(Admin $admin)
    {
        return $admin->hasPermission(Bootstrap_Aff::ADMIN_PERM_ID);
    }

    function preDispatch()
    {
        $this->user_id = $this->getInt('user_id');
        if (!$this->user_id &&
            $this->getRequest()->getActionName() != 'autocomplete')
            throw new Am_Exception_InputError("Wrong URL specified: no member# passed");

        $this->view->user_id = $this->user_id;
    }

    function subaffTabAction()
    {
        $ds = new Am_Query($this->getDi()->userTable);
        $ds = $ds->addField("concat(name_f, ' ', name_l)", 'name')
                ->addWhere('is_affiliate=?', 1)
                ->addWhere('aff_id=?', $this->getParam('user_id'));
        $grid = new Am_Grid_ReadOnly('_subaff', ___('Subaffiliate'), $ds, $this->getRequest(), $this->getView(), $this->getDi());
        $grid->addField(new Am_Grid_Field('login', ___('Username'), true));
        $grid->addField(new Am_Grid_Field('name', ___('Name'), true));
        $grid->addField(new Am_Grid_Field('email', ___('E-Mail Address'), true));
        $grid->runWithLayout('admin/user-layout.phtml');
    }

    function payoutTabAction()
    {
        $ds = new Am_Query($this->getDi()->affPayoutDetailTable);
        $ds->leftJoin('?_aff_payout', 'p', 'p.payout_id=t.payout_id');
        $ds->addField('p.*')
            ->addWhere('aff_id=?', $this->getParam('user_id'));

        $grid = new Am_Grid_ReadOnly('_d', ___('Payouts'), $ds, $this->_request, $this->view);
        $grid->setPermissionId(Bootstrap_Aff::ADMIN_PERM_ID);

        $grid->addField(new Am_Grid_Field_Date('date', ___('Payout Date')))->setFormatDate();
        $grid->addField(new Am_Grid_Field_Date('thresehold_date', ___('Thresehold Date')))->setFormatDate();
        $grid->addField('type', ___('Payout Method'));
        $grid->addField('amount', ___('Amount'))->setGetFunction(array($this, 'getAmount'));
        $grid->addField(new Am_Grid_Field_Enum('is_paid', ___('Is Paid?')))
            ->setTranslations(array(
                0 => ___('No'),
                1 => ___('Yes')
            ));
        $grid->addField('_action', '', true)->setRenderFunction(array($this, 'renderLink'));
        $grid->addCallback(Am_Grid_ReadOnly::CB_RENDER_STATIC, array($this, 'renderStatic'));
        $grid->runWithLayout('admin/user-layout.phtml');
    }

    public function renderLink(Am_Record $obj)
    {
        $iconDetail = $this->getDi()->view->icon('view', ___('Details'));
        return "<td width='1%' nowrap><a href='javascript:;' data-payout_detail_id='{$obj->payout_detail_id}' class='payout-detail-link'>$iconDetail</a></td>";
    }

    public function renderStatic(& $out)
    {
        $title = ___('Commissions Included to Payout');
        $user_id = $this->getParam('user_id');
        $out .= <<<CUT
<script type="text/javascript">
$('.payout-detail-link').live('click', function(){
    var div = $('<div class="grid-wrap" id="grid-affcomm"></div>');
    div.load(window.rootUrl + "/aff/admin/payout-detail/user_id/$user_id/payout_detail_id/" + $(this).data('payout_detail_id'),
        {},
        function(){
            div.dialog({
                autoOpen: true
                ,width: 800
                ,buttons: {}
                ,closeOnEscape: true
                ,title: "$title"
                ,modal: true,
                open : function(){
                    div.ngrid();
                }
            });
        });
})
</script>
CUT;
    }

    function payoutDetailAction()
    {
        $ds = new Am_Query($this->getDi()->affCommissionTable);
        $ds->leftJoin('?_invoice', 'i', 'i.invoice_id=t.invoice_id');
        $ds->leftJoin('?_user', 'u', 'u.user_id=i.user_id');
        $ds->leftJoin('?_product', 'p', 't.product_id=p.product_id');
        $ds->addField('u.user_id', 'user_id')
            ->addField('CONCAT(u.login, \' (\',u.name_f, \' \',u.name_l,\') [#\', u.user_id, \'] \')', 'user_name')
            ->addField('u.email', 'user_email')
            ->addField('p.title', 'product_title');
        $ds->addWhere('t.aff_id=?', $this->getParam('user_id'));
        $ds->addWhere('payout_detail_id=?', $this->getParam('payout_detail_id'));
        $ds->setOrder('commission_id', 'desc');

        $grid = new Am_Grid_ReadOnly('_affcomm', ___('Affiliate Commission'), $ds, $this->_request, $this->view);
        $grid->setPermissionId(Bootstrap_Aff::ADMIN_PERM_ID);
        $grid->setCountPerPage(10);

        $userUrl = new Am_View_Helper_UserUrl();
        $grid->addField(new Am_Grid_Field_Date('date', ___('Date')))->setFormatDate();
        $grid->addField('user_name', ___('User'))
            ->addDecorator(new Am_Grid_Field_Decorator_Link($userUrl->userUrl('{user_id}'), '_top'));
        $grid->addField('product_title', ___('Product'));
        $grid->addField('record_type', ___('Type'));
        $fieldAmount = $grid->addField('amount', ___('Commission'))->setGetFunction(array($this, 'getAmount'));
        $grid->addField('tier', ___('Tier'));
        $grid->runWithLayout('admin/user-layout.phtml');
    }

    function commTabAction()
    {
        $ds = new Am_Query($this->getDi()->affCommissionTable);
        $ds->leftJoin('?_invoice', 'i', 'i.invoice_id=t.invoice_id');
        $ds->leftJoin('?_user', 'u', 'u.user_id=i.user_id');
        $ds->leftJoin('?_product', 'p', 't.product_id=p.product_id')
            ->addField('u.user_id', 'user_id')
            ->addField('CONCAT(u.login, \' (\',u.name_f, \' \',u.name_l,\') [#\', u.user_id, \'] \')', 'user_name')
            ->addField('p.title', 'product_title')
            ->addField('IF(payout_detail_id IS NULL, \'no\', \'yes\')', 'is_paid');
        $ds->setOrder('date', 'desc')
            ->addWhere('t.aff_id=?', $this->getParam('user_id'));

        $grid = new Am_Grid_Editable('_affcomm', ___('Affiliate Commission'), $ds, $this->_request, $this->view);
        $grid->setPermissionId(Bootstrap_Aff::ADMIN_PERM_ID);
        $grid->actionsClear();

        $userUrl = new Am_View_Helper_UserUrl();
        $grid->addField(new Am_Grid_Field_Date('date', ___('Date')))->setFormatDate();
        $grid->addField('user_name', ___('User'))
            ->addDecorator(new Am_Grid_Field_Decorator_Link($userUrl->userUrl('{user_id}'), '_top'));
        $grid->addField('product_title', ___('Product'));
        $grid->addField('record_type', ___('Type'));
        $fieldAmount = $grid->addField('amount', ___('Commission'))->setGetFunction(array($this, 'getAmount'));
        $grid->addField('is_paid', ___('Paid'));
        $grid->addField('tier', ___('Tier'));

        $grid->setFilter(new Am_Grid_Filter_AffCommission());
        $grid->actionAdd(new Am_Grid_Action_Total())->addField($fieldAmount, "IF(record_type='void', -1*%1\$s, %1\$s)");
        $grid->runWithLayout('admin/user-layout.phtml');
    }

    function infoTabAction()
    {
        require_once APPLICATION_PATH . '/default/controllers/AdminUsersController.php';
        require_once 'Am/Report.php';
        require_once 'Am/Report/Standard.php';
        include_once APPLICATION_PATH . '/aff/library/Reports.php';
        $this->setActiveMenu('users-browse');

        $rs = new Am_Report_AffStats();
        $rs->setAffId($this->user_id);
        $rc = new Am_Report_AffClicks();
        $rc->setAffId($this->user_id);

        $form = $rs->getForm();
        if (!$form->isSubmitted()) {
            $form->addDataSource(new HTML_QuickForm2_DataSource_Array(array('period' => Am_Interval::PERIOD_LAST_30_DAYS)));
        }

        if ($form->isSubmitted() && $form->validate()) {
            $rs->applyConfigForm($this->_request);
        } else {
            $rs->setInterval('-30 days', 'now')->setQuantity(new Am_Report_Quant_Day());
            $form->addDataSource(new Am_Request(array('start' => $rs->getStart(), 'stop' => $rs->getStop())));
        }
        $rc->setInterval($rs->getStart(), $rs->getStop())->setQuantity(clone $rs->getQuantity());

        $result = $rs->getReport();
        $rc->getReport($result);

        $result->sortPoints();

        $this->view->form = $form;
        $this->view->form->setAction($this->_request->getRequestUri());

        $output = new Am_Report_Graph_Line($result);
        $output->setSize('100%', 300);
        $this->view->report = $output->render();

        $this->view->result = $result;

        $this->view->display('admin/aff/info-tab.phtml');
    }

    function infoTabDetailAction()
    {
        $date_from = $this->getFiltered('from');
        $date_to = $this->getFiltered('to');

        $this->view->commissions = $this->getDi()->affCommissionTable->fetchByDateInterval($date_from, $date_to, $this->user_id);
        $this->view->clicks = $this->getDi()->affClickTable->fetchByDateInterval($date_from, $date_to, $this->user_id);
        $this->view->display('admin/aff/info-tab-detail.phtml');
    }

    public function autocompleteAction()
    {
        $term = '%' . $this->getParam('term') . '%';
        $exclude = $this->getInt('exclude');
        if (!$term)
            return null;
        $q = new Am_Query($this->getDi()->userTable);
        $q->addWhere('((t.login LIKE ?) OR (t.email LIKE ?) OR (t.name_f LIKE ?) OR (t.name_l LIKE ?))',
            $term, $term, $term, $term);
        if ($exclude)
            $q->addWhere('user_id<>?', $exclude);
        $q->addWhere('is_affiliate>?', 0);

        $qq = $q->query(0, 10);
        $ret = array();
        while ($r = $this->getDi()->db->fetchRow($qq)) {
            $ret[] = array
                (
                'label' => sprintf('%s / "%s" <%s>', $r['login'], $r['name_f'] . ' ' . $r['name_l'], $r['email']),
                'value' => $r['login']
            );
        }
        if ($q->getFoundRows() > 10)
            $ret[] = array(
                'label' => sprintf("... %d more rows found ...", $q->getFoundRows() - 10),
                'value' => null,
            );
        $this->ajaxResponse($ret);
    }

    public function getAmount($record, $grid, $field)
    {
        return Am_Currency::render($record->{$field});
    }

}