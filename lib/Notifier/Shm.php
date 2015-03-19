<?php
namespace wmlib\fork\Notifier;

use wmlib\fork\INotifier;

class Shm implements INotifier
{
    /**
     * @var int $memoryKey The key to use for the shared memory
     */
    private $memoryKey;

    private $memorySize;

    public function __construct($key = 1, $size = 10000)
    {
        $this->memoryKey = round($key * microtime(true) * 1000);
        $this->memorySize = $size;
    }

    public function notify($data)
    {
        $memory = shmop_open($this->memoryKey, "c", 0644, $this->memorySize);
        $pool_string = trim(shmop_read($memory, 0, $this->memorySize));
        if ($pool_string) {
            $pool = json_decode($pool_string, true);
        } else {
            $pool = [];
        }

        $pool[] = $data;

        $tostore = json_encode($pool);

        if (strlen($tostore) < $this->memorySize) {
            $tostore = str_pad($tostore, $this->memorySize, ' ', STR_PAD_RIGHT);

            shmop_write($memory, $tostore, 0);
            shmop_close($memory);

            return true;
        } else {
            return false;
        }


    }

    /**
     * @return mixed|null
     */
    public function shift()
    {
        $memory = shmop_open($this->memoryKey, "c", 0644, $this->memorySize);

        if ($memory) {
            $pool_string = trim(shmop_read($memory, 0, $this->memorySize));
            if ($pool_string) {
                $pool = json_decode($pool_string, true);

                $data = array_shift($pool);

                $tostore = json_encode($pool);
                $tostore = str_pad($tostore, $this->memorySize, ' ', STR_PAD_RIGHT);
                shmop_write($memory, $tostore, 0);
                shmop_close($memory);

                return $data;
            } else {
                return null;
            }
        } else return null;
    }
}