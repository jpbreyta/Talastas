<?php

namespace App\Services;

/** Keep the existing service name for callers; all verification now uses email. */
class MfaService extends EmailMfaService {}
