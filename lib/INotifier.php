<?php
namespace wmlib\fork;

interface INotifier {

    /**
     * @param mixed $data
     * @return boolean True if success
     */
    public function notify($data);

    /**
     * @return mixed|null
     */
    public function shift();
}