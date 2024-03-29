<?php

/**
 * View helper to display admin menu in collapsed state
 * @package Am_View
 */
class Am_View_Helper_AdminMenuCollapsed extends Zend_View_Helper_Abstract {
    protected $activePageId = null;
    protected $acl = null;

    public function setAcl($acl)
    {
        $this->acl = $acl;
    }

    public function adminMenuCollapsed() {
        return $this;
    }

    public function renderMenu(Zend_Navigation_Container $container, $options = array()) {
        $html = '';
        foreach ($container as $page)
        {
            /* @var $page Zend_Navigation_Page */
            if ($this->acl && ($recources = $page->getResource())) {
                $hasPermission = false;
                foreach ((array)$recources as $recource) {
                    if ($this->acl->hasPermission($recource, $page->getPrivilege()))
                        $hasPermission = true;
                }
                if (!$hasPermission) continue;
            }

            if ($page->isActive() ) {
                $this->activePageId = $this->getId($page);
            }
            if (!$page->isVisible(true)) continue;
            if (!$page->getHref()) continue;
            $subMenu = $this->renderSubMenu($page);

            if (!$page->hasChildren() || ($page->hasChildren() && $subMenu)) {
                $html .= sprintf('<li><div class="menu-glyph"%s><a id="%s" href="%s" class="%s" target="%s">&nbsp;</a></div>' .
                        '<ul><li class="caption"><div class="menu-glyph-delimeter">%s</div>%s</ul></li>',
                        $this->getInlineStyle($page->getId()),
                        'menu-collapse-' . $this->getId($page),
                        $page->hasChildren() ? 'javascript:;' : $page->getHref(),
                        'folder ' . $page->getClass(),
                        $page->getTarget(),
                        $this->view->escape($page->getLabel()),
                        $subMenu
                );
            }
        }
        return sprintf('<ul class="admin-menu-collapsed">%s</ul>%s',
                $html, "\n");
    }

    protected function renderSubMenu(Zend_Navigation_Page $page) {
        $html = '';
        foreach ($page as $subPage)
        {
            if ($this->acl && ($recources = $subPage->getResource())) {
                $hasPermission = false;
                foreach ((array)$recources as $recource) {
                    if ($this->acl->hasPermission($recource, $subPage->getPrivilege()))
                        $hasPermission = true;
                }
                if (!$hasPermission) continue;
            }
            if ($subPage->isActive()) {
                $this->activePageId = $this->getId($subPage);
            }
            if (!$subPage->isVisible(true)) continue;
            if (!$subPage->getHref()) continue;
            $html .= sprintf('<li><a id="%s" href="%s" class="%s" target="%s">%s</a></li>',
                    'menu-collapse-' . $this->getId($subPage),
                    $subPage->getHref(),
                    $subPage->getClass(),
                    $subPage->getTarget(),
                    $this->view->escape($subPage->getLabel())
            );
        }

        return $html;
    }

    protected function getInlineStyle($id, $offset = 10) {

        $spriteOffset = Am_View::getSpriteOffset($id);
        if ($spriteOffset === false) return '';

        $realOffset = $offset - $spriteOffset;

        return sprintf(' style="background-position: %spx center;" ', $realOffset);
    }

    protected function getId(Zend_Navigation_Page $page) {
        $id = $page->getId();
        if (!empty($id)) return $id;
        if ($page instanceof Zend_Navigation_Page_Mvc)
            return sprintf('%s-%s', $page->getController(), $page->getAction());
        elseif ($page instanceof Zend_Navigation_Page_Uri)
            return crc32($page->getUri);
    }
}

