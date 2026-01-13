<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PageController extends Controller
{
    public function index()
    {
        return Page::get();
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|min:2|max:255',
            'template' => 'required|in:blog,landing',
            'layout' => 'sometimes|array'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'fail', 'message' => $validator->errors()], 400);
        }

        $user = Auth::user();

        $page = Page::create([
            'user_id' => $user->id,
            'title' => $request->title,
            'slug' => Str::slug($request->title),
            'template' => $request->template,
            'layout' => $request->layout ?? [],
            'status' => 'published',
        ]);

        return response()->json(['status' => 'success', 'message' => 'Page successfully created!', 'data' => $page], 201);
    }

    public function show($id)
    {
        $page = Page::find($id);
        if (!$page) return response()->json(['status' => 'fail', 'message' => "Page with id $id doesn't exist"], 404);

        return response()->json(['status' => 'success', 'data' => $page], 200);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|min:2|max:255',
            'template' => 'sometimes|in:blog,landing',
            'layout' => 'sometimes|array'
        ]);

        if ($validator->fails()) return response()->json(['status' => 'fail', 'message' => $validator->errors()], 400);

        $page = Page::find($id);
        if (!$page) return response()->json(['status' => 'fail', 'message' => "Page with id $id doesn't exist!"], 404);

        $user = Auth::user();
        if ($user->id !== $page->user_id) return response()->json(['status' => 'fail', 'message' => 'You are not allowed to perform this operation.'], 403);

        if ($request->has('title')) {
            $page->title = $request->title;
            $page->slug = Str::slug($request->title);
        }

        if ($request->has('template')) $page->template = $request->template;
        if ($request->has('layout')) $page->layout = $request->layout;

        $page->save();

        return response()->json(['status' => 'success', 'message' => 'Page successfully updated!', 'data' => $page], 200);
    }

    public function destroy($id)
    {
        $page = Page::find($id);
        if (!$page) return response()->json(['status' => 'fail', 'message' => "Page with id $id doesn't exist in the base!"], 404);

        $user = Auth::user();
        if ($user->id != $page->user_id) return response()->json(['status' => 'fail', 'message' => 'You are not allowed to perform this operation!!!'], 403);

        $page->delete();
        return response()->json(['status' => 'success', 'message' => 'Page successfully deleted!'], 200);
    }

    public function previewPage($slug)
    {
        $page = Page::where('slug', $slug)->firstOrFail();
        return response()->json([
            'id' => $page->id,
            'title' => $page->title,
            'slug' => $page->slug,
            'template' => $page->template,
            'layout' => $page->layout,
        ]);
    }
}
