<?php
declare(strict_types=1);

return [
  'name'  => 'Sustav za biranje završnog rada',
  'env'   => getenv('APP_ENV') ?: 'local',
  'debug' => (bool)(getenv('APP_DEBUG') ?: false),
  'url'   => getenv('APP_URL') ?: 'http://localhost/EkoZavrsni/public',
];