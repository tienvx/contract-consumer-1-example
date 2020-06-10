<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/../vendor/autoload.php';

$dotEnv = new DotEnv();
$dotEnv->usePutenv();
$dotEnv->bootEnv(dirname(__DIR__).'/../.env.pact');
