<?php

namespace wmlib\fork;

class Manager
{
    private $threads = [];

    private $childs = [];

    public function __construct()
    {
        $this->threads = [];
    }


    public function child(callable $func)
    {
        $this->childs[] = $func;
    }


    public function loop($threadsNumber = 5)
    {
        $threads = $this->threads;

        while(sizeof($this->threads) < $threadsNumber) {
            $pid = pcntl_fork();

            $callbacks = $this->childs;
            if ($pid == -1) {
                throw new \Exception("Failed to fork");
            }

            # If this is the child process, then run the requested function
            if (!$pid) {
                foreach($callbacks as $func) {
                    try {
                        call_user_func($func);
                    } catch (\Exception $e) {

                        exit(1);
                    }
                }

                # Then we must exit or else we will end up the child process running the parent processes code
                die();
            }

            $this->threads[$pid] = $pid;
        }


        $error = false;
        $status = 0;
        foreach ($threads as $pid) {
            pcntl_waitpid($pid, $status);
            if ($status > 0) {
                $error = $status;
            }
            unset($this->threads[$pid]);
        }



        return $status;
    }

    public function __destruct()
    {
        $this->loop(0);
    }
}
