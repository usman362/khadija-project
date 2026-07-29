<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\PageSection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Admin — Website Content.
 *
 * Edits the words and pictures on the public pages. The form for each section
 * is generated from config/page-sections.php, so adding an editable field is a
 * config change rather than a new screen, and nothing an admin submits can
 * alter the page's layout.
 */
class AdminContentController extends Controller
{
    private const PAGE = 'landing';

    public function index(): View
    {
        $schema   = config('page-sections.' . self::PAGE, []);
        $sections = PageSection::where('page', self::PAGE)
            ->orderBy('sort_order')
            ->get()
            ->keyBy('key');

        return view('dashboard.admin.content.index', [
            'schema'   => $schema,
            'sections' => $sections,
        ]);
    }

    public function edit(string $key): View
    {
        $definition = $this->definition($key);
        $section    = $this->section($key);

        return view('dashboard.admin.content.edit', [
            'key'        => $key,
            'definition' => $definition,
            'section'    => $section,
        ]);
    }

    public function update(Request $request, string $key): RedirectResponse
    {
        $definition = $this->definition($key);
        $section    = $this->section($key);

        $rules = [
            'heading'    => ['nullable', 'string', 'max:255'],
            'subheading' => ['nullable', 'string', 'max:500'],
            'body'       => ['nullable', 'string', 'max:5000'],
            'is_active'  => ['nullable', 'boolean'],
            'image'      => ['nullable', 'image', 'max:4096'],
            'remove_image' => ['nullable', 'boolean'],
        ];

        // The video link is the one free-text field that is fetched by a
        // browser, so it is the one that has to be a real URL.
        if (($definition['fields']['body']['type'] ?? null) === 'url') {
            $rules['body'] = ['nullable', 'url', 'max:2000'];
        }

        foreach ($definition['repeaters'] ?? [] as $name => $spec) {
            $rules["payload.$name"] = ['nullable', 'array', 'min:' . ($spec['min'] ?? 0), 'max:' . ($spec['max'] ?? 20)];
            foreach ($spec['fields'] as $f => $fs) {
                $rules["payload.$name.*.$f"] = $fs['type'] === 'image'
                    ? ['nullable', 'string', 'max:2000']
                    : ['nullable', 'string', 'max:2000'];
            }
            $rules["images.$name.*"] = ['nullable', 'image', 'max:4096'];
        }

        foreach ($definition['extra'] ?? [] as $f => $fs) {
            $rules["payload.$f"] = ['nullable', 'string', 'max:500'];
        }

        $data = $request->validate($rules);

        $payload = $section->payload ?? [];

        // Repeaters: keep the stored row and overlay the submitted text, so a
        // field the form does not render (an image the admin did not touch)
        // survives the save instead of being blanked.
        foreach ($definition['repeaters'] ?? [] as $name => $spec) {
            $rows = $data['payload'][$name] ?? [];
            foreach ($rows as $i => $row) {
                $existing = $payload[$name][$i] ?? [];
                $payload[$name][$i] = array_merge($existing, array_filter(
                    $row,
                    fn ($v) => $v !== null,
                ));

                $upload = $request->file("images.$name.$i");
                if ($upload) {
                    $payload[$name][$i]['image'] = $this->store($upload);
                }
            }
        }

        foreach (array_keys($definition['extra'] ?? []) as $f) {
            $payload[$f] = $data['payload'][$f] ?? null;
        }

        $attrs = [
            'heading'    => $data['heading']    ?? $section->heading,
            'subheading' => $data['subheading'] ?? $section->subheading,
            'body'       => array_key_exists('body', $data) ? $data['body'] : $section->body,
            'payload'    => $payload,
            'is_active'  => (bool) ($data['is_active'] ?? false),
        ];

        if ($request->boolean('remove_image')) {
            $attrs['image_path'] = null;
        } elseif ($request->hasFile('image')) {
            $attrs['image_path'] = $this->store($request->file('image'));
        }

        $section->fill($attrs)->save();

        return redirect()
            ->route('app.admin.content.edit', $key)
            ->with('status', ($definition['name'] ?? $key) . ' updated. Reload the homepage to see it.');
    }

    // ── internals ───────────────────────────────────────────────────

    private function definition(string $key): array
    {
        $d = config('page-sections.' . self::PAGE . '.' . $key);
        abort_unless($d, 404, 'No such section');

        return $d;
    }

    /** The row, created on first edit if the seeder never ran. */
    private function section(string $key): PageSection
    {
        return PageSection::firstOrCreate(
            ['page' => self::PAGE, 'key' => $key],
            ['is_active' => true],
        );
    }

    private function store(\Illuminate\Http\UploadedFile $file): string
    {
        return $file->store('cms', 'public');
    }
}
