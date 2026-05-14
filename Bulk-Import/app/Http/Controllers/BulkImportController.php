<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ParserConfig;
use App\Services\BulkImport\DocumentParserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class BulkImportController extends Controller
{
    public function __construct(
        private readonly DocumentParserService $parser,

    ) {}

    public function index(): Response
    {
        return Inertia::render('BulkImportStart');
    }

    public function uploadDataset(Request $request): RedirectResponse
    {
        $request->validate([
            'dataset' => ['required', 'file', 'mimes:pdf,docx', 'max:10240'],
        ]);

        $file       = $request->file('dataset');
        $storedPath = $file->storeAs('temp-datasets', $file->getClientOriginalName(), 'local');

        // already in session — or not
        $existing = getSession('bulk_import');
        if ($existing && $existing['path'] === $storedPath) {
            return redirect()->route('bulk-import.wizard');
        }

        try {
            $parsed = $this->parseFile($storedPath, $file->getClientOriginalExtension());
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($storedPath);
            return back()->withErrors(['dataset' => 'Could not parse file: ' . $e->getMessage()]);
        }

        saveSession('bulk_import', $parsed['items'], $parsed['blocks'], $parsed['fields'], $storedPath);

        return redirect()->route('bulk-import.datasets');
    }


    public function loadDatasetP(Request $request): RedirectResponse
    {
        $request->validate([
            'filename' => ['required', 'string'],
        ]);

        $storedPath = 'temp-datasets/' . $request->filename;
        abort_unless(Storage::disk('local')->exists($storedPath), 404);

        try {
            $blocks =  [];
            $items  = $this->parser->parse(Storage::disk('local')->path($storedPath), pathinfo($request->filename, PATHINFO_EXTENSION));
            $fields = $this->parser->getFields();

        } catch (\Throwable $e) {
            return back()->withErrors(['dataset' => 'Could not parse file: ' . $e->getMessage()]);
        }

        saveSession('bulk_import', $items, $blocks, $fields, $storedPath);
        return redirect()->route('bulk-import.wizard');
    }

    public function wizard()
    {
        $session = getSession('bulk_import');

        if (!$session || empty($session['items'])) {
            return redirect()->route('bulk-import.index')
                ->withErrors(['session' => 'No active import.']);
        }

        $firstItem = $session['items'][$session['index']] ?? $this->parser->getItemTemplate();

        $importedCount = $this->getImportedCount($session['items'] ?? [], 'slug');

        return Inertia::render('BulkImport', [
            'total_items'   => count($session['items']),
            'current_index' => $session['index'],
            'item'          => $firstItem,
            'imported_count' => $importedCount,
        ]);
    }

    public function getCurrentItem()
    {
        $session = getSession('bulk_import');

        if (!$session || $session['index'] >= count($session['items'])) {
            return response()->json(['finished' => true]);
        }

        return response()->json($session['items'][$session['index']]);
    }

    public function getItemByIndex(int $index)
    {
        $session = getSession('bulk_import');

        if (!$session) {
            return response()->json(['error' => 'No active import session.'], 422);
        }

        if (!isset($session['items'][$index])) {
            return response()->json(['error' => "No item found at index {$index}."], 404);
        }

        $importedCount = $this->getImportedCount($session['items'] ?? [], 'slug');
        return response()->json([
            'index' => $index,
            'total' => count($session['items']),
            'item'  => $session['items'][$index],
            'imported_count' => $importedCount,
        ]);
    }


    private function getImportedCount(array $sessionItems, string $compareBy = 'slug'): int
    {
        if (empty($sessionItems)) {
            return 0;
        }

        $values = collect($sessionItems)
            ->pluck($compareBy)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($values)) {
            return 0;
        }

        return Item::whereIn($compareBy, $values)->count();
    }
    public function saveItem(Request $request)
    {
        $session = getSession('bulk_import');

        if (!$session) {
            return response()->json(['error' => 'Session expired. Please restart.'], 422);
        }

        $request->merge(['is_bulk' => true]);

        return app(ItemController::class)->store($request);
    }


    public function cancel(): RedirectResponse
    {
        $session = getSession('bulk_import');

        if ($session) {
            clearSession();
        }
        return redirect()->route('bulk-import.datasets');
    }


    public function loadDataset(Request $request): RedirectResponse
    {
        $request->validate([
            'filename' => ['required', 'string'],
        ]);

        $storedPath =DatasetPath($request->filename);
        $ext        = pathinfo($request->filename, PATHINFO_EXTENSION);

        try {
            $parsed = $this->parseFile($storedPath, $ext);
        } catch (\Throwable $e) {
            return back()->withErrors(['dataset' => 'Could not parse file: ' . $e->getMessage()]);
        }

        saveSession('bulk_import', $parsed['items'], $parsed['blocks'], $parsed['fields'], $storedPath);

        return redirect()->route('bulk-import.wizard');
    }

    public function previewDataset(string $filename)
    {
        validator(
            ['filename' => $filename],
            [
                'filename' => ['required', 'string']
            ]
        )->validate();


        $storedPath = DatasetPath($filename);
        $ext        = pathinfo($filename, PATHINFO_EXTENSION);

        try {
            $parsed = $this->parseFile($storedPath, $ext);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Could not parse file: ' . $e->getMessage()], 500);
        }

        return response()->json([
            'items' => $parsed['items'],
            'raw'   => $parsed['blocks'],
        ]);
    }




    public function datasets(): Response
    {
        $files = Storage::disk('local')->files('temp-datasets');

        $datasets = collect($files)->map(fn($file) => [
            'name'     => basename($file),
            'size'     => round(Storage::disk('local')->size($file) / 1024, 1) . ' KB',
            'uploaded' => date('Y-m-d H:i', Storage::disk('local')->lastModified($file)),
            'path'     => $file,
        ])->values();

        return Inertia::render('BulkImport/Datasets', ['datasets' => $datasets]);
    }




    private function parseFile(string $storedPath, string $ext): array
    {
        $fullPath    = Storage::disk('local')->path($storedPath);
        $parseResult = $this->parser->parse($fullPath, $ext);

        return [
            'items'  => $parseResult['items']  ?? [],
            'blocks' => $parseResult['blocks'] ?? [],
            'fields' => $this->parser->getFields(),
        ];
    }


    public function deleteDataset(string $filename): RedirectResponse
    {
        Storage::disk('local')->delete('temp-datasets/' . $filename);
        return back();
    }


    public function getParserConfigs()
    {
        return response()->json([
            'configs' => ParserConfig::all(),
            'active'  => ParserConfig::where('is_active', true)->first(),
        ]);
    }

    public function saveParserConfig(Request $request)
    {
        $request->validate([
            'pattern' => ['required', 'string'],
            'name'    => ['required', 'string'],
        ]);
        ParserConfig::query()->update(['is_active' => false]);
        $config = ParserConfig::updateOrCreate(
            ['pattern' => $request->pattern],
            ['name'    => $request->name, 'is_active' => true]
        );

        return response()->json(['success' => true, 'config' => $config]);
    }
}
