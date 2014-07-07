<?php

class AdminProductCategoriesController extends Am_Controller
{
    public function checkAdminPermissions(Admin $admin)
    {
        return $admin->hasPermission('grid_product');
    }
    function indexAction()
    {
        $this->view->categories = $this->getDi()->productCategoryTable->getTree();
        $this->view->display('admin/product-categories.phtml');
    }
    function saveAction()
    {
        $this->_response->setHeader('Content-type', 'text/plain; charset=utf-8');
        $id = $this->getInt('product_category_id');
        if ($id) {
            $pc = $this->getDi()->productCategoryTable->load($id);
        } else {
            $pc = $this->getDi()->productCategoryRecord;
        }
        $pc->title = $this->getParam('title');
        $pc->description = $this->getParam('description');
        $pc->parent_id = $this->getInt('parent_id');
        $pc->code = $this->getFiltered('code');
        $pc->sort_order = $this->getInt('sort_order');
        $pc->save();
        echo $this->getJson($pc->toArray());
    }
    function delAction()
    {
        $id = $this->getInt('id');
        if (!$id) throw new Am_Exception_InputError(___('Wrong id'));
        $pc = $this->getDi()->productCategoryTable->load($id);
        $this->getDi()->productCategoryTable->moveNodes($pc->pk(), $pc->parent_id);
        $pc->delete();
        echo ___('OK');
    }
}