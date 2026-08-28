<?php

use App\Models\FunnelEvent;
use App\Models\ServiceType;
use App\Models\User;
use Database\Seeders\ContentBlockSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;

/**
 * The request funnel, and whether it can be believed.
 *
 * Two things made it lie. The day a row belongs to was written in one format by
 * the counter and another by anything saved through the model, so half the rows
 * fell outside every range that contained their own day. And a visitor arriving
 * from a service page opens on the second step, so the browser never reported
 * reaching it — every one of them read as dropping out at step one, which is
 * most of the traffic now that every service page links that way.
 */
beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(SettingsSeeder::class);
    $this->seed(ContentBlockSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

describe('the day a count belongs to', function () {
    it('is written the same way whichever path wrote it', function () {
        FunnelEvent::record('begonnen');
        FunnelEvent::create(['step' => 'abgesendet', 'day' => today(), 'count' => 3]);

        $days = DB::table('funnel_events')->pluck('day')->unique();

        expect($days)->toHaveCount(1)
            ->and($days->first())->toBe(today()->toDateString());
    });

    it('counts today, which is the day most often looked at', function () {
        FunnelEvent::create(['step' => 'begonnen', 'day' => today(), 'count' => 40]);

        [$from, $to] = FunnelEvent::period('heute');

        expect(collect(FunnelEvent::funnel($from, $to))->firstWhere('step', 'begonnen')['count'])
            ->toBe(40);
    });

    it('finds a row even when it was stored with a time on it', function () {
        // Rows written before the formats were reconciled.
        DB::table('funnel_events')->insert([
            'step' => 'begonnen', 'day' => today()->toDateString().' 00:00:00', 'count' => 5,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        [$from, $to] = FunnelEvent::period('heute');

        expect(collect(FunnelEvent::funnel($from, $to))->firstWhere('step', 'begonnen')['count'])
            ->toBe(5);
    });
});

describe('the periods the dashboard offers', function () {
    beforeEach(function () {
        FunnelEvent::create(['step' => 'begonnen', 'day' => today(), 'count' => 40]);
        FunnelEvent::create(['step' => 'schritt_2', 'day' => today(), 'count' => 25]);
        FunnelEvent::create(['step' => 'abgesendet', 'day' => today(), 'count' => 9]);
        FunnelEvent::create(['step' => 'begonnen', 'day' => today()->subDay(), 'count' => 18]);
        FunnelEvent::create(['step' => 'begonnen', 'day' => today()->subDays(20), 'count' => 100]);
    });

    it('separates today from yesterday', function () {
        $today = collect(FunnelEvent::funnel(...FunnelEvent::period('heute')));
        $yesterday = collect(FunnelEvent::funnel(...FunnelEvent::period('gestern')));

        expect($today->firstWhere('step', 'begonnen')['count'])->toBe(40)
            ->and($yesterday->firstWhere('step', 'begonnen')['count'])->toBe(18);
    });

    it('reads yesterday as a finished day rather than the last 24 hours', function () {
        [$from, $to] = FunnelEvent::period('gestern');

        expect($from->toDateString())->toBe(today()->subDay()->toDateString())
            ->and($to->toDateString())->toBe(today()->subDay()->toDateString());
    });

    it('adds the week up', function () {
        expect(collect(FunnelEvent::funnel(...FunnelEvent::period('7tage')))
            ->firstWhere('step', 'begonnen')['count'])->toBe(58);
    });

    it('reaches further back over thirty days', function () {
        expect(collect(FunnelEvent::funnel(...FunnelEvent::period('30tage')))
            ->firstWhere('step', 'begonnen')['count'])->toBe(158);
    });

    it('offers all four on the dashboard, defaulting to thirty days', function () {
        $this->actingAs($this->admin)
            ->get('/admin')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('funnelPeriod', '30tage')
                ->has('funnelPeriods', 4));
    });

    it('redraws for the period asked for', function () {
        $this->actingAs($this->admin)
            ->get('/admin?zeitraum=heute')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('funnelPeriod', 'heute')
                ->where('funnel.0.count', 40));
    });

    it('falls back rather than breaking on a period it does not know', function () {
        $this->actingAs($this->admin)
            ->get('/admin?zeitraum=erfunden')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('funnelPeriod', '30tage'));
    });
});

describe('reaching the second step', function () {
    it('counts somebody who arrives from a service page already on it', function () {
        // They answered the first question by being on that page, so the
        // browser never reports reaching the second — it has to be counted
        // here or every one of them reads as a drop-out.
        $type = ServiceType::factory()->create(['name_de' => 'Unfallgutachten', 'is_active' => true]);

        $this->get("/anfrage?leistung={$type->slug}");

        [$from, $to] = FunnelEvent::period('heute');
        $rows = collect(FunnelEvent::funnel($from, $to));

        expect($rows->firstWhere('step', 'begonnen')['count'])->toBe(1)
            ->and($rows->firstWhere('step', 'schritt_2')['count'])->toBe(1);
    });

    it('does not count it for somebody who still has to choose', function () {
        $this->get('/anfrage');

        [$from, $to] = FunnelEvent::period('heute');
        $rows = collect(FunnelEvent::funnel($from, $to));

        expect($rows->firstWhere('step', 'begonnen')['count'])->toBe(1)
            ->and($rows->firstWhere('step', 'schritt_2')['count'])->toBe(0);
    });
});
