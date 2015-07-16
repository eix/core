<?php
/**
 * A Page responder which requires an authenticated user.
 */

namespace Eix\Core\Responders\Http;

use Eix\Core\Responders\Http\Page;
use Eix\Core\Responders\Restricted;

class RestrictedPage extends Page implements Restricted
{
}
