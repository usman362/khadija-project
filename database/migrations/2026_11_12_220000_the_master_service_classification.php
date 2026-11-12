<?php

use App\Domain\Taxonomy\ServiceEligibility;
use Illuminate\Database\Migrations\Migration;

/**
 * Sir Peter's Master Service Classification, 8 November.
 *
 * The rules and the two lists live in App\Domain\Taxonomy\ServiceEligibility,
 * because this is not the only thing that has to apply them. On an existing
 * database this migration finds the 241 services and marks them; on a fresh
 * one it runs before the tree exists and finds nothing, so
 * `taxonomy:import-v2` calls the same class after it builds the tree. Either
 * route ends in the same place, which a list written out here could not
 * promise.
 */
return new class extends Migration
{
    public function up(): void
    {
        ServiceEligibility::apply();
    }

    public function down(): void
    {
        ServiceEligibility::remove();
    }
};
