<?php
declare(strict_types=1);

return [
  'driver'   => $_ENV['DB_DRIVER']   ?? 'oci',
  'host'     => $_ENV['DB_HOST']     ?? 'localhost',
  'port'     => $_ENV['DB_PORT']     ?? '1521',
  'sid'      => $_ENV['DB_SID']      ?? 'xe',
  'username' => $_ENV['DB_USERNAME'] ?? '',
  'password' => $_ENV['DB_PASSWORD'] ?? '',
  'charset'  => $_ENV['DB_CHARSET']  ?? 'AL32UTF8',
];