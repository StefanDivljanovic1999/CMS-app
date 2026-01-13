<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;


class MenuController extends Controller
{

    public function index()
    {
        return Menu::get();
    }

    public function store(Request $request)
    {
        //prvo cemo da proverimo nas unos 
        $validator = Validator::make($request->all(), [
            'title' => 'required|string',
            'items' => 'required|array',
            'items.*.title' => 'required|string',
            'items.*.url' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'fail',
                'message' => $validator->errors()
            ], 400);
        }

        //"prelepljujemo" podatke sa forme i importujemo u bazu podataka
        $menu = Menu::create([
            'title' => $request->title,
            'slug' => Str::slug($request->title),
            'user_id' => $request->user()->id,
            'items' => $request->items
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Menu successfully created!',
            'data' => $menu
        ], 201);
    }


    public function show($id)
    {
        $menu = Menu::find($id);

        if (!$menu) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Menu with id ' . $id . ' not found in the base!'
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        $menu = Menu::find($id);

        if (!$menu) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Menu with id ' . $id . ' not found!'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|min:2',
            'items' => 'sometimes|array|min:1',
            'items.*.title' => 'required_with:items|string',
            'items.*.url' => 'required_with:items|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'fail',
                'message' => $validator->errors()
            ], 400);
        }

        // Ažuriranje samo onih polja koja su poslata
        if ($request->has('title')) {
            $menu->title = $request->title;
            $menu->slug = Str::slug($request->title);
        }

        if ($request->has('items')) {
            $menu->items = $request->items;
        }

        $menu->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Menu successfully updated!',
            'data' => $menu
        ], 200);
    }

    public function destroy($id)
    {
        $menu = Menu::find($id);

        if (!$menu) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Menu with id ' . $id . ' not found!'
            ], 404);
        }

        $menu->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Menu successfully deleted!'
        ], 200);
    }

    public function previewSite($slug)
    {
        $site = Menu::where('slug', $slug)->firstOrFail();
        return response()->json([
            'id' => $site->id,
            'title' => $site->title,
            'slug' => $site->slug,
            'user_id' => $site->user_id,
            'items' => $site->items,
        ]);
    }
}
