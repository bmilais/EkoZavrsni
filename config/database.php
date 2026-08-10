<?php
return [
  'driver'   => getenv('DB_DRIVER')   ?: 'oci',
  'host'     => getenv('DB_HOST')     ?: 'localhost',
  'port'     => getenv('DB_PORT')     ?: '1521',
  'sid'      => getenv('DB_SID')      ?: 'xe',
  'username' => getenv('DB_USERNAME') ?: '',
  'password' => getenv('DB_PASSWORD') ?: '',
  'charset'  => getenv('DB_CHARSET')  ?: 'AL32UTF8',
];