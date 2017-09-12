<?php

require_once '../vendor/autoload.php';

use SlackSongShare\Command\UpdateCommand;
use Noodlehaus\Config;

$jobby = new \Jobby\Jobby();
$config = Config::load('../config.yml');

$jobby->add('UpdateCommand', array(
    'closure' => function () use ($config) {
        return (new UpdateCommand($config))->run();
    },
    'schedule' => '* * * * *',
    'output' => '../logs/command.log',
    'debug' => true,
    'enabled' => true,
));

$jobby->run();
