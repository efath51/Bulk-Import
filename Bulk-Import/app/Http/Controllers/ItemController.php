<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;
use App\Traits\HandlesBulkImport;
use Inertia\Inertia;
use Inertia\Response;

class ItemController extends Controller
{
    use HandlesBulkImport;

    private function validateItem(Request $request): void
    {
        $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'slug'        => ['required', 'string', 'unique:items,slug'],
            'status'      => ['required', 'in:active,inactive'],
            'description' => ['nullable', 'string'],
            'image'       => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ]);
    }


    public function create(): Response
    {
        return Inertia::render('Items/Create');
    }

    public function store(Request $request)
    {
        $this->validateItem($request);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('items', 'public');
        }

        $item = new Item();
        $item->name = $request->name;
        $item->slug = $request->slug;
        $item->status = $request->status;
        $item->description = $request->description;
        $item->image = $imagePath;

        $item->save();

        if ($request->boolean('is_bulk')) {

            return $this->handleBulkFlow('bulk_import');
        }

        return redirect()->route('bulk-import.index')->with('success', 'Item created successfully.');
    }

    public function index(): Response
    {
        return Inertia::render('Items/Index', [
            'items' => Item::latest()->paginate(20),
        ]);
    }
}
