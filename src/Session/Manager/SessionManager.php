<?php

namespace Rose\Session\Manager;

use Rose\Support\Manager;

class SessionManager extends Manager
{
    public function getDefaultDriver()
    {
        return $this->config->get('session.driver');
    }

    public function setDefeaultDriver($driver)
    {
        $this->config->set('session.driver', $driver);
    }

}
