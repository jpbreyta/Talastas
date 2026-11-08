<?php

return [
    'mail_enabled' => env('SECURITY_MAIL_ENABLED', false),
    'mail_test_recipient' => env('SECURITY_MAIL_TEST_RECIPIENT'),
    'reply_to' => env('SECURITY_MAIL_REPLY_TO'),
    // Browser-specific inactivity; an independent 30-day maximum still applies.
    'mfa_inactivity_days' => env('MFA_TRUST_INACTIVITY_DAYS', 7),
];
