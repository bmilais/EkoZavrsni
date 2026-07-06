<?php
declare(strict_types=1);

return [
  'driver'       => getenv('DB_DRIVER')        ?: 'oci',
  'host'         => getenv('DB_HOST')          ?: 'localhost',
  'port'         => getenv('DB_PORT')          ?: '1521',
  'service_name' => getenv('DB_SERVICE_NAME')  ?: 'XEPDB1',
  'username'     => getenv('DB_USERNAME')      ?: 'EkoZavrsni',
  'password'     => getenv('DB_PASSWORD')      ?: '',
  'charset'      => getenv('DB_CHARSET')       ?: 'AL32UTF8',
];