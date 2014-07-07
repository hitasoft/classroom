<?php

if (!defined('INCLUDED_AMEMBER_CONFIG')) 
    die("Direct access to this location is not allowed");
  
/*
*  aMember Pro site customization file
*
*  Rename this file to site.php and put your site customizations, 
*  such as fields additions, custom hooks and so on to this file
*  This file will not be overwritten during upgrade
*                                                                               
*/


function onThanksPage(Am_Event $event){
    /* @var $di Am_Di */  
    $di = Am_Di::getInstance();
    /* @var $controller ThanksController */ 
    $controller = $event->getController();
 
    $controller->redirectLocation('/setprofile');
}

function siteUserMenu(Am_Event $event){
      // $user = $event->getUser(); // if required
      $menu = $event->getMenu();

       // Remove a tab
       $page = $menu->findOneBy('id', 'add-renew');
       $menu->removePage($page);
       $page = $menu->findOneBy('id', 'payment-history');
       $menu->removePage($page);
       $page = $menu->findOneBy('id', 'profile');
       $menu->removePage($page);
 
      // Add a tab
      $menu->addPage(array(
              'id' => 'help',
              'label' => ___('Help'),
              'uri' => '/help',
              'order' => 2,
       ));
      $menu->addPage(array(
              'id' => 'profile',
              'label' => ___('Password'),
              'uri' => '/password',
              'order' => 3,
       ));
       $menu->addPage(array(
              'id' => 'privacy',
              'label' => ___('Privacy'),
              'uri' => '/privacy',
              'order' => 4,
       ));
 
}

Am_Di::getInstance()->hook->add(Am_Event::THANKS_PAGE, 'onThanksPage');
Am_Di::getInstance()->hook->add('userMenu', 'siteUserMenu');
