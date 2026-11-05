<?php

namespace App\Services;

use App\Rules\ValidPasswordInput;
use Closure;

class PasswordPolicy
{
    public static function rules(): array
    {
        return ['required', 'string', 'min:12', 'max:4096', 'confirmed', new ValidPasswordInput(forHashing: true),
            function (string $attribute, mixed $value, Closure $fail) {
                // Small local denylist: no passwords are disclosed to an external service.
                $common = ['password1234', 'password12345', 'password123456', '123456789012', 'qwertyuiop12', 'abcdefghijkl', 'letmein123456', 'talastas12345', 'passwordpassword'];
                if (in_array(strtolower((string) $value), $common, true) || preg_match('/^(.)\1+$/us', (string) $value)) {
                    $fail('Choose a less common password or a longer passphrase.');
                }
            }];
    }
}
