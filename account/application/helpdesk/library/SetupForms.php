<?php

class Am_Form_Setup_Helpdesk extends Am_Form_Setup
{

    function __construct()
    {
        parent::__construct('helpdesk');
        $this->setTitle(___('Helpdesk'));
        $this->data['help-id'] = 'Setup/Helpdesk';
    }

    function initElements()
    {
        $this->addTextarea('helpdesk.intro', array('rows' => 7, 'style' => "width:90%"), array('help-id' => '#Editing_Text_on_Helpdesk_Page'))
            ->setLabel(___("Intro Text on Helpdesk Page\n") . ___("It can contain html markup"));
        $this->setDefault('helpdesk.intro', 'We answer customer tickets Mon-Fri, 10am - 5pm EST. You can also call us by phone if you have an urgent question.');

        $fieldSetNotifications = $this->addFieldset()
                ->setLabel(___('Notifications'));

        $fieldSetNotifications->addElement('email_checkbox', 'helpdesk.notify_new_message', null, array('help-id' => '#Enabling.2FDisabling_Customer_Notifications'))
            ->setLabel(___("Send Notification about New Messages to Customer\n" .
                    "aMember will email a notification to user\n" .
                    "each time admin responds to a user ticket"));
        $this->setDefault('helpdesk.notify_new_message', 1);

        $fieldSetNotifications->addElement('email_checkbox', 'helpdesk.notify_new_message_admin')
            ->setLabel(___("Send Notification about New Messages to Admin\n" .
                    "aMember will email a notification to admin\n" .
                    "each time user responds to a ticket"));
        $this->setDefault('helpdesk.notify_new_message_admin', 1);

        $fieldSetNotifications->addElement('email_checkbox', 'helpdesk.new_ticket')
            ->setLabel(___("New Ticket Autoresponder to Customer\n" .
                    "aMember will email an autoresponder to user\n" .
                    "each time user create new ticket"));

        $fieldSetConversation = $this->addFieldset()
                ->setLabel(___('Conversation'));

        $fieldSetConversation->addAdvCheckbox('helpdesk.add_signature')
            ->setLabel(___('Add Signature to Response'));

        $fieldSetConversation->addTextarea('helpdesk.signature', array('rows' => 5, 'cols' => 50))
            ->setLabel(___("Signature Text\n" .
                    "You can use the following placeholders %name_f%, %name_l%\n" .
                    "it will be expanded to first and last name of admin in operation"));

        $this->addScript('script')
            ->setScript(<<<CUT
(function($){
    $(function(){
        $("[id='helpdesk.add_signature-0']").change(function(){
            $("[id='helpdesk.signature-0']").closest('div.row').toggle(this.checked);
        }).change()
    })
})(jQuery)
CUT
        );

        $fieldSetConversation->addAdvCheckbox('helpdesk.does_not_quote_in_reply')
            ->setLabel(___('Does Not Quote Message in Reply'));

        $fieldSetConversation->addAdvCheckbox('helpdesk.does_not_allow_attachments')
            ->setLabel(___('Does Not Allow to Upload Attachments for Users'));


        $fieldSetFeatures = $this->addFieldset()
                ->setLabel(___('Features'));

        $fieldSetFeatures->addAdvCheckbox('helpdesk.show_gravatar')
            ->setLabel(___('Show Gravatars in Ticket Conversation'));

        $fieldSetFeatures->addAdvCheckbox('helpdesk.does_not_require_login')
            ->setLabel(array(___('Does Not Require Login to Access FAQ Section'),
                ___('make it public')));

        $fieldSetFeatures->addAdvCheckbox('helpdesk.does_not_show_faq_tab')
            ->setLabel(___('Does Not Show FAQ Tab in Member Area'));

        $fieldSetFeatures->addAdvCheckbox('helpdesk.show_faq_search')
            ->setLabel(___('Show Search Function in FAQ'));
    }

}