<?php

return [
    'mail_enabled' => env('SECURITY_MAIL_ENABLED', false),
    'mail_test_recipient' => env('SECURITY_MAIL_TEST_RECIPIENT'),
    'reply_to' => env('SECURITY_MAIL_REPLY_TO'),
    // Browser-specific inactivity; an independent 30-day maximum still applies.
    'mfa_inactivity_days' => env('MFA_TRUST_INACTIVITY_DAYS', 7),
    // Temporarily pause the extra OTP challenge for role/scope edits only.
    // Sign-in MFA, the administrator password check, and other actions still apply.
    'role_updates_require_otp' => env('ROLE_UPDATES_REQUIRE_OTP', false),
];
