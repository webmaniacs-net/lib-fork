<?php

namespace wmlib\fork;

use wmlib\fork\Notifier\Null;
use wmlib\fork\Notifier\Shm;

class Manager
{
    /**
     * @var callable
     */
    private $onError;

    /**
     * @var INotifier
     */
    private $notifier;

    /**
     * @var INotifier
     */
    private $errors;

    private $threads = [];

    private $childs = [];

    public function __construct(callable $onError = null)
    {
        $this->onError = $onError;
        $this->threads = [];

        $this->notifier = new Null();

        if ($this->onError) {
            $this->errors = new Shm(rand());
        } else {
            $this->errors = new Null();
        }
    }


    public function child(callable $func, array $params = [])
    {
        $this->childs[] = [$func, $params];
    }

    /**
     * @return INotifier
     */
    public function getNotifier()
    {
        return $this->notifier;
    }

    /**
     * @param INotifier $notifier
     */
    public function setNotifier(INotifier $notifier)
    {
        $this->notifier = $notifier;
    }




    public function loop($threadsNumber = 5)
    {
        foreach ($this->threads as $pid) {
            $status = pcntl_waitpid($pid, $status, WNOHANG);

            if ($status === $pid) {
                unset($this->threads[$pid]);
            } elseif ($status === -1) {
                pcntl_waitpid($pid, $status);
                unset($this->threads[$pid]);
            }
        }

        $to_start = $threadsNumber - sizeof($this->threads);
        if ($to_start > 0) {
            for($i=0; $i < $to_start; $i++) {
                $pid = pcntl_fork();


                if ($pid == -1) {
                    throw new \Exception("Failed to fork");
                }

                # If this is the child process, then run the requested function
                if (!$pid) {
                    foreach ($this->childs as list($callback, $params)) {
                        try {
                            call_user_func_array($callback, array_merge([$this->notifier], $params));
                        } catch (\Exception $e) {

                            $this->errors->notify([
                                'message' => $e->getMessage(),
                                'trace' => $e->getTraceAsString(),
                                'class' => get_class($e)
                            ]);
                            exit(1);
                        }
                    }

                    # Then we must exit or else we will end up the child process running the parent processes code
                    die();
                }
                $this->threads[$pid] = $pid;
            }
        }

        // track errors
        if ($this->onError) {
            while($e_data = $this->errors->shift()) {
                call_user_func($this->onError, $e_data);
            }
        }


        return sizeof($this->threads);
    }

    public function run($threadsNumber = 5)
    {
        $this->loop($threadsNumber);

        while(($run = $this->loop(0)) > 0) {
            // wait for all process run once
        }
    }

    public function __destruct()
    {
        while($this->loop(0) > 0) {
            // wait
        }
    }
}
