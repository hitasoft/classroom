<?php

abstract class MediaController extends Am_Controller
{

    protected $id;
    protected $media;
    protected $type;

    abstract function getFlowplayerParams(ResourceAbstractFile $media);

    function getMedia()
    {
        if (!$this->media) {
            $this->id = $this->_request->getInt('id');
            if (!$this->id)
                throw new Am_Exception_InputError("Wrong URL - no media id passed");
            $this->media = $this->getDi()->videoTable->load($this->id, false);
            if (!$this->media)
                throw new Am_Exception_InputError("This media has been removed");
        }
        return $this->media;
    }

    function dAction()
    {
        $id = $this->_request->get('id');
        $this->validateSignedLink($id);
        $id = intval($id);
        $media = $this->getDi()->videoTable->load($id);
        set_time_limit(600);

        while (@ob_end_clean());
        Zend_Session::writeClose();

        if ($path = $media->getFullPath()) {
            $this->_helper->sendFile($path, $media->getMime());
        } else
            $this->redirectLocation($media->getProtectedUrl($this->getDi()->config->get('storage.s3.expire', 15) * 60));
    }

    function pAction()
    {
        $this->view->title = $this->getMedia()->title;
        $this->view->content =
            '<script type="text/javascript" id="am-' . $this->type . '-' . $this->id . '">' . "\n" .
            $this->renderJs() .
            "\n</script>";
        $this->view->display('layout.phtml');
    }

    function getSignedLink(ResourceAbstract $media)
    {
        $rel = $media->pk() . '-' . ($this->getDi()->time + 3600 * 24);
        return ($this->getRequest()->isSecure() ? ROOT_SURL : ROOT_URL) . '/' . $this->type . '/d/id/' .
        $rel . '-' .
        $this->getDi()->app->getSiteHash('am-' . $this->type . '-' . $rel, 10);
    }

    function validateSignedLink($id)
    {
        @list($rec_id, $time, $hash) = explode('-', $id, 3);
        if ($rec_id <= 0)
            throw new Am_Exception_InputError('Wrong media id#');
        if ($time < Am_Di::getInstance()->time)
            throw new Am_Exception_InputError('Media Link Expired');
        if ($hash != $this->getDi()->app->getSiteHash("am-" . $this->type . "-$rec_id-$time", 10))
            throw new Am_Exception_InputError('Media Link Error - Wrong Sign');
    }

    function renderJs()
    {
        $params = $this->getFlowplayerParams($this->getMedia());
        $this->view->id = $this->id;
        $this->view->type = $this->type;
        $this->view->width = $this->_request->getInt('width', isset($params['width']) ? $params['width'] : 520);
        $this->view->height = $this->_request->getInt('height', isset($params['height']) ? $params['height'] : 330);
        unset($params['width']);
        unset($params['height']);

        $guestAccess = false;
        $media = $this->getMedia();
        $guestAccess = $media->hasAccess(null);
        if (!$this->getDi()->auth->getUserId() && !$guestAccess) {
            try {
                if ($media->mime == 'audio/mpeg') throw new Exception; //skip it for audio files
                $m = $this->getDi()->videoTable->load($this->getDi()->config->get('video_non_member'));
                $this->view->media = $this->getSignedLink($m);
                $this->view->mime = $m->mime;
            } catch (Exception $e) {
                $this->view->error = ___("You must be logged-in to open this media");
                $this->view->link = REL_ROOT_URL . "/login";
            }
        } elseif (!$guestAccess && !$media->hasAccess($this->getDi()->user)) {
            try {
                if ($media->mime == 'audio/mpeg') throw new Exception; //skip it for audio files
                $m = $this->getDi()->videoTable->load($this->getDi()->config->get('video_not_proper_level'));
                $this->view->media = $this->getSignedLink($m);
                $this->view->mime = $m->mime;
            } catch (Exception $e) {
                $this->view->error = ___("Your subscription does not allow access to this media");
                $this->view->link = REL_ROOT_URL . sprintf('/no-access/content/' . '?id=%d&type=%s',
                        $media->pk(), $media->getTable()->getName(true));
            }
        } else {
            $this->view->media = $this->getSignedLink($media);
            $this->view->mime = $media->mime;
        }

        $this->view->flowPlayerParams = array_merge(array('key' => $this->getDi()->config->get('flowplayer_license')), $params);
        $this->view->isSecure = $this->getRequest()->isSecure();
        return $this->view->render('_media.flowplayer.phtml');
    }

    function jsAction()
    {
        $this->_response->setHeader('Content-type', 'text/javascript');
        $this->getMedia();
        echo $this->renderJs();
    }

}