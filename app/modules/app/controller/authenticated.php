<?php

namespace yxorP\app\modules\app\Controller;

/**
 * Class Controller
 * @package App
 */
class authenticated extends base
{

    protected $user;

    public function unlockResource($resourceId)
    {

        $meta = $this->helper('admin')->isResourceLocked($resourceId);
        $success = false;

        if ($meta) {

            $canUnlock = $this->isAllowed('app/resources/unlock');

            if (!$canUnlock) {
                $canUnlock = $meta['sid'] === md5(session_id());
            }

            if ($canUnlock) {
                $this->helper('admin')->unlockResourceId($resourceId);
                $success = true;
            }
        }

        return ['success' => $success];
    }

    protected function isAllowed(string $permission): bool
    {
        return $this->helper('acl')->isAllowed($permission);
    }

    protected function initialize()
    {

        $user = $this->app->helper('auth')->getUser();

        if (!$user) {
            $route = $this->app->request->route;
            $this->app->reroute("/auth/login?to={$route}");
        }

        $this->user = $user;
        $this->app->set('user', $user);

        parent::initialize();
    }

    protected function checkAndLockResource($resourceId)
    {

        $meta = null;

        if (!$this->helper('admin')->isResourceEditableByCurrentUser($resourceId, $meta)) {
            return $this->stop($this->render('app:views/lockedResouce.php', compact('meta', 'resourceId')), 200);
        }

        $this->helper('admin')->lockResourceId($resourceId);
    }
}
