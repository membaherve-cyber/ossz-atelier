<?php

// OSSZ ATELIER — EN flash messages for auth edge cases (rate limiting and
// inactive accounts). Referenced by app/Http/Controllers/Auth/LoginController.

return [
    'loginRateLimited' => 'Too many attempts. Please wait a minute and try again.',
    'loginInactive' => 'This account has been deactivated. Contact Management.',
];
