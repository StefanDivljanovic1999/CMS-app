<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class ImageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index() {}


    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'data' => $validator->errors()
            ], 422);
        }

        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            // sačuvaj u storage/app/public/pages
            $path = $request->file('image')->store('pages', 'public');

            // vrati pun URL koji frontend može koristiti
            $url = url('storage/' . $path);

            return response()->json([
                'url' => $url
            ]);
        }

        return response()->json([
            'url' => null
        ]);
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
