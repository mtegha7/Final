<?php

class TrustScoreService
{

    public function calculateInitialScore($status)
    {
        if ($status === 'verified') {
            return 8.5;
        }
        return 0;
    }
}
