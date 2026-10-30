<?php

namespace App\Logging;

use DateTimeZone;
use Illuminate\Log\Logger;

/**
 * Channel tap: write log timestamps in WIB (Asia/Jakarta) instead of the app timezone (UTC).
 */
class UseJakartaTime
{
    public function __invoke(Logger $logger): void
    {
        $logger->getLogger()->setTimezone(new DateTimeZone('Asia/Jakarta'));
    }
}
