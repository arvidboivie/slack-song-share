#!/usr/bin/env php
<?php

require_once '../vendor/autoload.php';

use SlackSongShare\Command\UpdateCommand;
use Noodlehaus\Config;

$jobby = new \Jobby\Jobby();
$config = Config::load('../config.yml');
$jobbyConfig = $config->get('jobby');

$jobby->add('UpdateCommand', array(
    'closure' => function () use ($config) {
        return (new UpdateCommand($config))->run();
    },
    'schedule' => $jobbyConfig['schedule'],
    'output' => $jobbyConfig['log'],
    'debug' => $jobbyConfig['debug'],
    'enabled' => true,
));

$jobby->run();
