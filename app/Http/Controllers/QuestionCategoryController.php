<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\QuestionCategory;
use Illuminate\Support\Str;

class QuestionCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = QuestionCategory::ordered()->get();
        return view('question-categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('question-categories.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'required|string|max:7',
            'sort_order' => 'required|integer|min:0'
        ]);

        QuestionCategory::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'color' => $request->color,
            'sort_order' => $request->sort_order,
            'is_active' => $request->has('is_active')
        ]);

        return redirect()->route('question-categories.index')
            ->with('success', 'Categoría creada exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(QuestionCategory $questionCategory)
    {
        return view('question-categories.show', compact('questionCategory'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(QuestionCategory $questionCategory)
    {
        return view('question-categories.edit', compact('questionCategory'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, QuestionCategory $questionCategory)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'required|string|max:7',
            'sort_order' => 'required|integer|min:0'
        ]);

        $questionCategory->update([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'color' => $request->color,
            'sort_order' => $request->sort_order,
            'is_active' => $request->has('is_active')
        ]);

        return redirect()->route('question-categories.index')
            ->with('success', 'Categoría actualizada exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(QuestionCategory $questionCategory)
    {
        $questionCategory->delete();
        
        return redirect()->route('question-categories.index')
            ->with('success', 'Categoría eliminada exitosamente.');
    }

    /**
     * Get active categories for API
     */
    public function getActiveCategories()
    {
        return QuestionCategory::active()->ordered()->get();
    }
}
