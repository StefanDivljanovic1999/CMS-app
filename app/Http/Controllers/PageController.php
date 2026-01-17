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
            'template' => 'required|in:blog,landing,front',
            'layout' => 'sometimes|array'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'fail', 'message' => $validator->errors()], 400);
        }

        $user = Auth::user();

        //samo admin moze da kreira front stranicu, a author moze blog i landing
        if ($user->role === 'author' && $request->template === 'front') {
            return response()->json(['status' => 'fail', 'message' => 'Only admin can make front page!!!'], 403);
        }

        //ako je user role admin status je odmah published, ako nije onda je draft
        $status = $user->role === 'admin' ? 'published' : "draft";


        $page = Page::create([
            'user_id' => $user->id,
            'title' => $request->title,
            'slug' => Str::slug($request->title),
            'template' => $request->template,
            'layout' => $request->layout ?? [],
            'status' => $status,
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
        if ($user->id !== $page->user_id && $user->role !== 'admin') return response()->json(['status' => 'fail', 'message' => 'You are not allowed to perform this operation.'], 403);

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
        if ($user->id !== $page->user_id) return response()->json(['status' => 'fail', 'message' => 'You are not allowed to perform this operation!!!'], 403);

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
            'status' => $page->status
        ]);
    }

    public function approve(Request $request, $id)
    {

        //prvo proveravamo nas unos
        $validator = Validator::make(
            $request->all(),
            [
                'status' => 'required|in:draft,published,rejected'
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'status' => 'fail',
                'message' => $validator->errors()
            ], 400);
        }

        //pronalazimo page ako postoji u bazi
        $page = Page::find($id);

        if (!$page) {
            return response()->json([
                'status' => 'fail',
                'message' => 'page with id: ' . $id . ' doesnt exist in the base!!!'
            ], 404);
        }

        $page->status = $request->status;
        $page->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Status has been submited for page id: ' . $id,
            'status' => $page->status
        ], 200);
    }
}
