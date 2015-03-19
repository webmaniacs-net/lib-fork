# lib-fork

Examples
--------

```php
$manager = new \wmlib\fork\Manager(function(array $error) {
    print_r($error);
    die('Child process error');
});
$manager->setNotifier($notifier = new \wmlib\fork\Notifier\Shm());

$manager->child(function (\wmlib\fork\INotifier $notifier, $var) {
    echo 'Start '.getmypid()."\n";
    echo 'Echo from child process with '.$var.' value!';
    sleep(1);
    echo 'End '.getmypid()."\n";
}, ['var' => 'value']);

while($manager->loop(10)) {
    // run 10 forked childs in infinite loop
    // Manager will handle childs count
    usleep(10000);
}
echo "End\n";
```
