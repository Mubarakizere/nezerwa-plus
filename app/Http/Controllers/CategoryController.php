<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories.
     */
    public function index()
    {
        $categories = Category::orderBy('id', 'desc')->paginate(10);
        return view('categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new category.
     */
    public function create()
    {
        return view('categories.create');
    }

    /**
     * Store a newly created category in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:255|unique:categories,name',
            'description' => 'nullable|string',
        ]);

        try {
            Category::create($request->only('name', 'description'));
            return redirect()
                ->route('categories.index')
                ->with('success', '✅ Category has been added successfully!');
        } catch (QueryException $e) {
            return back()->with('error', 'Something went wrong while creating the category.');
        }
    }

    /**
     * Show the form for editing the specified category.
     */
    public function edit(Category $category)
    {
        return view('categories.edit', compact('category'));
    }

    /**
     * Update the specified category in storage.
     */
    public function update(Request $request, Category $category)
    {
        $request->validate([
            'name'        => 'required|string|max:255|unique:categories,name,' . $category->id,
            'description' => 'nullable|string',
        ]);

        try {
            $category->update($request->only('name', 'description'));
            return redirect()
                ->route('categories.index')
                ->with('success', '✅ Category details updated successfully.');
        } catch (QueryException $e) {
            return back()->with('error', 'Unable to update this category. Please try again.');
        }
    }

    /**
     * Remove the specified category from storage.
     */
    public function destroy(Category $category)
    {
        try {
            $category->delete();
            return redirect()
                ->route('categories.index')
                ->with('success', '🗑️ Category deleted successfully.');
        } catch (\Throwable $e) {
            return back()->with('error', 'This category cannot be deleted because it is linked to other records.');
        }
    }
}
