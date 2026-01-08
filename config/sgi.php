<?php

return [
    'fee_rate' => (float) env('SGI_FEE_RATE', 0.006),
    'fee_min'  => (int) env('SGI_FEE_MIN', 500),
];
