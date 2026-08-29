<?php

namespace App\Console\Commands;

use App\Models\Category;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Folds duplicate categories into one, keeping the picture.
 *
 * A taxonomy import created a second row for names that already existed —
 * 47 of them on production, 106 becoming 153. Nothing was deleted and no
 * picture was lost: for each doubled name one row still carries the uploaded
 * photo and the other, newer one, carries the bundled artwork or nothing. The
 * listing shows both, so every event type appears twice and half of them look
 * blank.
 *
 * The keeper is the row with a picture. Everything pointing at the other one —
 * events, bookings, packages, bids, relevance rows, a professional's chosen
 * categories, child categories — is moved across FIRST, then the empty
 * duplicate is removed. Nothing is orphaned.
 *
 * Dry by default. Run it, read it, then run it again with --apply.
 */
class MergeDuplicateCategories extends Command
{
    protected $signature = 'categories:merge-duplicates
                            {--apply : Write the changes. Without this, nothing is saved}
                            {--kind= : Limit to one kind, e.g. event_type}';

    protected $description = 'Merge duplicate categories into the one that has the picture';

    /** Every table that points at a category, and the column that does it. */
    private const REFERENCES = [
        'events'             => 'category_id',
        'bookings'           => 'category_id',
        'packages'           => 'category_id',
        'bids'               => 'category_id',
        'category_relevance' => 'category_id',
        'category_user'      => 'category_id',
    ];

    /** Where moving a row could collide with one that is already there. */
    private const UNIQUE_WITH = [
        'category_user'      => 'user_id',
        'category_relevance' => 'archetype',
    ];

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        $groups = Category::selectRaw('name, kind, COUNT(*) as n')
            ->when($this->option('kind'), fn ($q, $k) => $q->where('kind', $k))
            ->groupBy('name', 'kind')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($groups->isEmpty()) {
            $this->info('No duplicate categories.');

            return self::SUCCESS;
        }

        $rows = [];
        $merged = 0;
        $movedRefs = 0;
        $skipped = [];

        foreach ($groups as $group) {
            $copies = Category::where('name', $group->name)
                ->where('kind', $group->kind)
                ->orderBy('id')
                ->get();

            $keeper = $this->pickKeeper($copies);
            $losers = $copies->reject(fn ($c) => $c->id === $keeper->id);

            foreach ($losers as $loser) {
                // Refuse to fold away a row that holds the only picture.
                if ($loser->thumbnail && ! $keeper->thumbnail) {
                    $skipped[] = [$group->name, $loser->id, 'the duplicate has the picture and the keeper does not'];
                    continue;
                }

                $refs = $this->countReferences($loser->id);
                $rows[] = [
                    $group->kind,
                    $group->name,
                    $keeper->id . ($keeper->thumbnail ? ' (has picture)' : ' (no picture)'),
                    $loser->id . ($loser->thumbnail ? ' (has picture)' : ' (no picture)'),
                    $refs,
                ];

                if ($apply) {
                    DB::transaction(function () use ($loser, $keeper, &$movedRefs) {
                        $movedRefs += $this->movePointers($loser->id, $keeper->id);

                        // Children follow their parent.
                        Category::where('parent_id', $loser->id)->update(['parent_id' => $keeper->id]);

                        $loser->delete();
                    });
                }

                $merged++;
            }
        }

        $this->table(['Kind', 'Name', 'Keeping', 'Removing', 'Things pointing at it'], array_slice($rows, 0, 25));
        if (count($rows) > 25) {
            $this->line('… and ' . (count($rows) - 25) . ' more.');
        }

        $this->newLine();
        $this->line($apply
            ? "Merged: <fg=green>{$merged}</>   References moved: <fg=green>{$movedRefs}</>"
            : "Would merge: <fg=yellow>{$merged}</>  (nothing saved — add --apply)");

        if ($skipped !== []) {
            $this->newLine();
            $this->warn('Left alone, needs a human:');
            $this->table(['Name', 'ID', 'Why'], $skipped);
        }

        return self::SUCCESS;
    }

    /**
     * The one with the picture wins; then the one more things point at; then
     * the older row, because that is the one that has been linked to longest.
     */
    private function pickKeeper($copies): Category
    {
        return $copies->sortBy([
            fn ($a, $b) => ($b->thumbnail ? 1 : 0) <=> ($a->thumbnail ? 1 : 0),
            fn ($a, $b) => $this->countReferences($b->id) <=> $this->countReferences($a->id),
            fn ($a, $b) => $a->id <=> $b->id,
        ])->first();
    }

    private function countReferences(int $id): int
    {
        $n = 0;
        foreach (self::REFERENCES as $table => $column) {
            $n += DB::table($table)->where($column, $id)->count();
        }

        return $n + Category::where('parent_id', $id)->count();
    }

    /** Moves every pointer from one category to another. Returns how many moved. */
    private function movePointers(int $from, int $to): int
    {
        $moved = 0;

        foreach (self::REFERENCES as $table => $column) {
            // Where a unique pair exists, a row that would collide is dropped
            // rather than moved — the keeper already has that exact link.
            if ($partner = self::UNIQUE_WITH[$table] ?? null) {
                $taken = DB::table($table)->where($column, $to)->pluck($partner);

                DB::table($table)
                    ->where($column, $from)
                    ->whereIn($partner, $taken)
                    ->delete();
            }

            $moved += DB::table($table)->where($column, $from)->update([$column => $to]);
        }

        return $moved;
    }
}
