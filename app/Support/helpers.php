<?php

if (! function_exists('money')) {
    /**
     * Format an integer TZS amount for display, e.g. "TZS 850,000".
     */
    function money(?int $amount): string
    {
        return 'TZS '.number_format((int) $amount);
    }
}
