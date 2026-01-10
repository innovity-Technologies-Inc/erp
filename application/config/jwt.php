<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$config['jwt_secret_key'] = 'WeSh@ll0verC0me';
$config['jwt_algorithm'] = 'HS256';
$config['jwt_token_ttl'] = 3600;
$config['jwt_refresh_ttl'] = 86400;