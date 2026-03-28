<?php

declare(strict_types=1);

namespace Tests;

/**
 * Class OrderTracker
 *
 * Simple value collector used in tests that verify call ordering.
 *
 * @package Tests
 */
final class OrderTracker
{
    /** @var list<string> */
    public array $calls = [];

    /**
     * Record a call with the given label.
     *
     * @param string $label
     *
     * @return void
     */
    public function record(string $label): void
    {
        $this->calls[] = $label;
    }
}
