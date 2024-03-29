<?php

class Am_Newsletter_Plugin_Arpreach extends Am_Newsletter_Plugin
{
    protected function _initSetupForm(Am_Form_Setup $form)
    {
        $el = $form->addText('url', array('size' => 60))->setLabel(array('ArpReach Pro Url',
        		''));
        $el->addRule('required');
        $el->addRule('regex', 'URL must start with http:// or https://', '/^(http|https):\/\//');
        
        $ef = $form->addSelect('email_field')->setLabel('Choose Alternative E-Mail Field');
        $fields = $this->getDi()->userTable->getFields(true);
        $ef->loadOptions(array_combine($fields, $fields));
        $ef->addRule('required', true);
        $form->setDefault('email_field', 'email');
        
        $ff = $form->addMagicSelect('fields')->setLabel('Pass additional fields to ARP');
        $ff->loadOptions(array_combine($fields, $fields));
    }
    
    function doRequest(array $vars, $dif_url)
    {
        $req = new Am_HttpRequest($this->getConfig('url').$dif_url, Am_HttpRequest::METHOD_POST);
        $req->addPostParameter($vars);
        return $req->send();
    }
    
    public function isConfigured()
    {
        return strlen($this->getConfig('url'));
    }

    public function changeSubscription(User $user, array $addLists, array $deleteLists)
    {
        $email = $user->get($this->getConfig('email_field', 'email'));
        if (empty($email)) return true;
        // add custom fields info
        $fields = array();
        foreach ($this->getConfig('fields', array()) as $fn)
            $fields['custom_'.$fn] = $user->get($fn);
        
        foreach ($addLists as $listId)
        {
            $ret = $this->doRequest(array(
                //'id' => $listId,
                'first_name' => $user->name_f,
                'last_name' => $user->name_l,
                'email_address' => $email,
                //'subscription_type' => 'E',
            ) + $fields, $listId);
            if (!$ret) return false;
        }
        foreach ($deleteLists as $listId)
        {
            $ret = $this->doRequest(array(
                //'id' => $listId,
                'first_name' => $user->name_f,
                'last_name' => $user->name_l,
                'email_address' => $email,
                //'subscription_type' => 'E',
                'action' => 'UNS',
            ), $listId);
            if (!$ret) return false;
        }
        return true;
    }
    function onUserAfterUpdate(Am_Event_UserAfterUpdate $event)
    {
        $ef = $this->getConfig('email_field', 'email');
        if ($ef != 'email') // else changeEmail will be called by Bootstrap
        {
            $oldEmail = $event->getOldUser()->get($ef);
            $newEmail = $event->getUser()->get($ef);
            if ($oldEmail != $newEmail)
                $this->changeEmail($event->getUser(), $oldEmail, $newEmail);
        }
    }
    public function changeEmail(User $user, $oldEmail, $newEmail)
    {
        $ef = $this->getConfig('email_field', 'email');
        // fetch all user subscribed ARP lists, unsubscribe
        $list_ids = $this->getDi()->newsletterUserSubscriptionTable->getSubscribedIds($user->pk());
        $lists = array();
        foreach ($this->getDi()->newsletterListTable->loadIds($list_ids) as $list)
        {
            if ($list->plugin_id != $this->getId()) continue;
            $lists[] = $list->plugin_list_id;
        }
        $user->set($ef, $oldEmail)->toggleFrozen(true);
        $this->changeSubscription($user, array(), $lists); 
        // subscribe again
        $user->set($ef, $newEmail)->toggleFrozen(false);
        $this->changeSubscription($user, $lists, array()); 
    }
    
    public function getReadme()
    {
    	return <<<CUT
ArpReach plugin readme
    
This module allows aMember Pro users to subscribe/unsubscribe from e-mail lists
created in ArpReach. To configure the module:
    
 - Create and save a form in the "subscription forms" section of the autoresponder. Then generate the form html
code there too. Note that "POST" url is different for every form, but you must to copy static part of form's url in ArpReach Pro Url
   Example: http://localhost/arpreach/a.php/sub/

 - go to aMember CP -> Protect Content -> Newsletters , you will be able to define Plugin List Id with dinamic part of form's url
   Example: 1/b8x9y7 	
CUT;
    } 
   
}
