<?php

use App\Models\ServiceRequest;

/**
 * How much a reference gives away.
 *
 * It used to count up by one, so anybody holding two of them — a customer and a
 * partner comparing notes, or one person who enquired twice — could subtract
 * and read off exactly how much work DKGZ had taken in between. That is nobody's
 * business but DKGZ's.
 */
describe('the step between references', function () {
    it('is never one', function () {
        $steps = collect(range(1, 25))->map(function () {
            $reference = ServiceRequest::nextReference();
            ServiceRequest::factory()->create(['reference' => $reference]);

            return (int) substr($reference, 8);
        })->sliding(2)->map(fn ($pair) => $pair->last() - $pair->first());

        expect($steps->min())->toBeGreaterThanOrEqual(5)
            ->and($steps->max())->toBeLessThanOrEqual(12);
    });

    it('varies rather than settling on one figure', function () {
        // A constant step of eight would be as readable as a step of one.
        $steps = collect(range(1, 30))->map(function () {
            $reference = ServiceRequest::nextReference();
            ServiceRequest::factory()->create(['reference' => $reference]);

            return (int) substr($reference, 8);
        })->sliding(2)->map(fn ($pair) => $pair->last() - $pair->first());

        expect($steps->unique()->count())->toBeGreaterThan(2);
    });

    it('does not start the month at one', function () {
        // Starting at 0001 announces both that it is the first and where the
        // counting began, which is the fixed point the arithmetic needs.
        $first = (int) substr(ServiceRequest::nextReference(), 8);

        expect($first)->toBeGreaterThanOrEqual(5);
    });
});

describe('what a reference still has to be', function () {
    it('keeps the month in it', function () {
        $reference = ServiceRequest::nextReference(now()->setDate(2026, 9, 4));

        expect($reference)->toStartWith('DKGZ2609');
    });

    it('starts again in a new month', function () {
        ServiceRequest::factory()->create(['reference' => 'DKGZ26080900']);

        expect(ServiceRequest::nextReference(now()->setDate(2026, 9, 1)))
            ->toStartWith('DKGZ2609')
            ->and((int) substr(ServiceRequest::nextReference(now()->setDate(2026, 9, 1)), 8))
            ->toBeLessThan(20);
    });

    it('is unique across a long run', function () {
        $references = collect(range(1, 40))->map(function () {
            $reference = ServiceRequest::nextReference();
            ServiceRequest::factory()->create(['reference' => $reference]);

            return $reference;
        });

        expect($references->unique()->count())->toBe(40);
    });

    it('carries on counting once a month passes four digits', function () {
        // "DKGZ26089999" sorts above "DKGZ260810005" as text, so reading the
        // last one by string order alone would start the month again.
        ServiceRequest::factory()->create(['reference' => 'DKGZ26089995']);
        ServiceRequest::factory()->create(['reference' => 'DKGZ260810004']);

        expect((int) substr(ServiceRequest::nextReference(), 8))->toBeGreaterThan(10004);
    });

    it('never reuses one belonging to a deleted request', function () {
        $deleted = ServiceRequest::factory()->create(['reference' => ServiceRequest::nextReference()]);
        $reference = $deleted->reference;
        $deleted->delete();

        expect(ServiceRequest::nextReference())->not->toBe($reference);
    });
});

describe('invoice numbers', function () {
    it('still run in an unbroken sequence', function () {
        // Deliberately untouched. An invoice number has to be traceable for a
        // tax audit, and a randomly jumping series invites the question of
        // which invoices are missing.
        $source = file_get_contents(app_path('Models/Commission.php'));

        expect($source)->toContain('$sequence = $last ? ((int) substr($last, -4)) + 1 : 1;')
            ->and($source)->not->toContain('random_int');
    });
});
