<?php

class DiscountService
{
    public static function calculateEducationalDiscount(float $fee): float
    {
        return $fee * 0.10;
    }
}
