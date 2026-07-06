<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;

abstract class BaseController
{
  protected function view(Response $res, string $html, int $status = 200): void
  {
    $res->html($html, $status);
  }
}