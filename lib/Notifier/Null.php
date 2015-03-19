<?php
namespace wmlib\fork\Notifier;

use wmlib\fork\INotifier;

class Null implements INotifier
{
    public function __construct()
    {

    }

    public function notify($data)
    {
        // nothing todo
    }

    /**
     * @return mixed|null
     */
    public function shift()
    {
        return null;
    }
}