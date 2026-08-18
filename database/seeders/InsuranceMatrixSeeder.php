<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

/**
 * Loads the reconciled 27 + 241 insurance proposal onto v2 category rows.
 *
 * These columns are a broker conversation, not a live gate. InsuranceRequirement
 * still reads config('compliance.insurance_required_categories') until
 * insurance_matrix_signed_off is flipped after broker and attorney sign off.
 *
 * Source: GigResource_Insurance_Review_RECONCILED_20260813.xlsx
 * (Insurance - Category Rules + Insurance - Service Matrix).
 */
class InsuranceMatrixSeeder extends Seeder
{
    public const PATH = 'database/seeders/data/insurance_matrix_v2.json';

    public function run(): void
    {
        $stats = self::apply();

        if ($this->command) {
            $this->command->info(
                "Draft insurance matrix: {$stats['updated']} v2 rows filled"
                . ($stats['missing'] ? ', '.$stats['missing'].' names not found' : '')
                . '.'
            );
        }
    }

    /**
     * @return array{updated: int, missing: int, required: int, conditional: int, not_required: int}
     */
    public static function apply(): array
    {
        $path = base_path(self::PATH);
        $data = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        $updated = 0;
        $missing = 0;

        foreach ($data['categories'] as $row) {
            $n = Category::anyTaxonomy()
                ->where('taxonomy_version', 'v2')
                ->where('kind', Category::SERVICE_CATEGORY)
                ->where('name', $row['name'])
                ->update(['insurance_requirement' => $row['requirement']]);

            $n > 0 ? $updated += $n : $missing++;
        }

        foreach ($data['services'] as $row) {
            $n = Category::anyTaxonomy()
                ->where('taxonomy_version', 'v2')
                ->where('kind', Category::SERVICE)
                ->where('name', $row['name'])
                ->update([
                    'insurance_requirement' => $row['requirement'],
                    'insurance_type'        => $row['type'],
                ]);

            $n > 0 ? $updated += $n : $missing++;
        }

        $of = fn (string $req) => Category::anyTaxonomy()
            ->where('taxonomy_version', 'v2')
            ->where('kind', Category::SERVICE)
            ->where('insurance_requirement', $req)
            ->count();

        return [
            'updated'      => $updated,
            'missing'      => $missing,
            'required'     => $of('required'),
            'conditional'  => $of('conditional'),
            'not_required' => $of('not_required'),
        ];
    }
}
