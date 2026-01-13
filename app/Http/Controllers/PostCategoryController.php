<?php

namespace App\Http\Controllers;

use App\Models\PostCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PostCategoryController extends Controller
{

    public function index()
    {
        return PostCategory::get();
    }


    public function store(Request $request)
    {
        //prvo cemo proveriti unos 
        $validator = Validator::make($request->all(), [
            'title' => 'required|min:2',
            'context' => 'required|min:2'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'fail',
                'message' => $validator->errors()
            ], 400);
        }

        $category = PostCategory::create([
            'title' => $request->title,
            'slug' => Str::slug($request->title),
            'context' => $request->context
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Post category successfully created!',
            'data' => $category
        ], 201);
    }

    public function show(PostCategory $postCategory)
    {
        //
    }

    public function update(Request $request, $id)
    {
        //proveravamo unos
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|min:2',
            'context' => 'sometimes|min:2'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'fail',
                'message' => $validator->errors()
            ], 400);
        }

        //ako post sa zadatim id-jem ne postoji, prekidamo izvrsavanje
        $post_category = PostCategory::find($id);

        if (!$post_category) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Post category with id: ' . $id . ' doesnt exist in the base!'
            ], 404);
        }

        if ($request->has('title')) {
            $post_category->title = $request->title;
            $post_category->slug = Str::slug($request->title);
        }

        if ($request->has('context')) {
            $post_category->context = $request->context;
        }

        //cuvamo izmene u bazi
        $post_category->save();
        return response()->json([
            'status' => 'success',
            'message' => 'Post category successfully updated!',
            'data' => $post_category
        ], 201);
    }

    public function destroy($id)
    {
        //proveravamo da li postoji kategorija sa zadatim id-jem
        $post_category = PostCategory::find($id);

        if (!$post_category) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Post category with id: ' . $id . ' doesnt exist in the base!'
            ], 404);
        }

        $post_category->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Post category successfully deleted!',
            'data' => $post_category
        ], 201);
    }
}
