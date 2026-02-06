<?php

namespace App\Http\Controllers;

class ItemController extends Controller
{
    public function index()
    {
        $items = Item::with('section')
            ->where('is_deleted', 0)
            ->orderBy('label')
            ->paginate(15);
        
        return view('items.index', compact('items'));
    }
<<<<<<< Updated upstream

    public function create()
    {
        $sections = Section::active()->orderBy('label')->get(['section_id', 'label']);
        return view('items.create', compact('sections'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'section_id' => 'required|exists:section,section_id',
            'label' => 'required|string|max:20',
            'unit' => 'nullable|string|max:20',
            'reorder_level' => 'nullable|integer|min:0',
        ]);

        // Get or create a default item type
        $defaultItemType = \DB::table('item_type')->first();
        if (!$defaultItemType) {
            $itemTypeId = \DB::table('item_type')->insertGetId(['label' => 'General']);
        } else {
            $itemTypeId = $defaultItemType->item_type_id;
        }

        $validated['item_type_id'] = $itemTypeId;
        $validated['status_code'] = 1;

        Item::create($validated);
        return redirect()->route('items.index')->with('success', 'Item created successfully.');
    }

    public function edit(Item $item)
    {
        if ($item->is_deleted) abort(404);
        $sections = Section::active()->orderBy('label')->get(['section_id', 'label']);
        $item->load('section');
        return view('items.edit', compact('item', 'sections'));
    }

    public function update(Request $request, Item $item)
    {
        if ($item->is_deleted) abort(404);
        
        $validated = $request->validate([
            'section_id' => 'required|exists:section,section_id',
            'label' => 'required|string|max:20',
            'unit' => 'nullable|string|max:20',
            'reorder_level' => 'nullable|integer|min:0',
        ]);

        $item->update($validated);
        return redirect()->route('items.index')->with('success', 'Item updated successfully.');
    }

    public function destroy(Item $item)
    {
        if ($item->is_deleted) abort(404);
        $item->softDelete();
        return redirect()->route('items.index')->with('success', 'Item deleted successfully.');
    }
=======
>>>>>>> Stashed changes
}
