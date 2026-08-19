<?php

namespace App\Support;

use App\Models\Assignment;

/**
 * Fee validation rules, kept beside the money helpers so the bounds live in
 * one place rather than being restated in every FormRequest.
 */
class Money
{
    /** Fees below this are almost certainly a typo, e.g. euros entered as cents. */
    public const MIN_FEE_CENTS = Assignment::FEE_MIN_CENTS;

    public const MAX_FEE_CENTS = Assignment::FEE_MAX_CENTS;

    /** Above this the commission is flagged for a human to look at. */
    public const REVIEW_THRESHOLD_CENTS = Assignment::FEE_REVIEW_THRESHOLD_CENTS;

    public static function isValidFee(?int $cents): bool
    {
        return $cents !== null
            && $cents >= self::MIN_FEE_CENTS
            && $cents <= self::MAX_FEE_CENTS;
    }

    public static function needsReview(?int $cents): bool
    {
        return $cents !== null && $cents > self::REVIEW_THRESHOLD_CENTS;
    }
}
