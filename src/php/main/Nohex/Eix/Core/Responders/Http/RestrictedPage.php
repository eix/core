<?php
/**
 * A Page responder which requires an authenticated user.
 */

namespace Nohex\Eix\Core\Responders\Http;

use Nohex\Eix\Core\Responders\Http\Page;
use Nohex\Eix\Core\Responders\Restricted;

class RestrictedPage extends Page implements Restricted
{
}
