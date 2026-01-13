<?php

namespace App\Http\Controllers;

use App\Models\Like;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class LikeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Like::get();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $like = Like::find($id);

        if (!$like) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Like with id: ' . $id . ' doesnt exist in the base!'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'post_data' => $like,

        ], 200);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Like $like)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Like $like)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Like $like)
    {
        //
    }

    public function react(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'post_id' => 'required|integer|exists:posts,id',
            'status' => 'required|integer|in:1,2'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'fail',
                'message' => $validator->errors()
            ], 400);
        }

        //proveravamo da li postoji post sa zadatim id-jem
        $post = Post::find($request->post_id);

        //onaj koji ostavlja lajk je ondaj koji se i ulogovao 
        $user = Auth::user();

        //popunjavamo podatke iz requesta
        $post_id = $request->post_id;
        $user_id = $user->id;
        $status = $request->status;



        //proveravamo da li je vec bilo reakcija na post
        $like = Like::where('post_id', $post_id)->where('user_id', $user_id)->first();

        //ako je vec postojala reakcija
        if ($like != null) {
            //ako je reakcija ista kao sto je i bila, uklanjamo je
            if ($like->status == $status) {
                $like->delete();
                return response()->json([
                    'status' => 'success',
                    'message' => 'Reaction on post with id: ' . $post_id . ' from user id: ' . $user_id . ' successfully removed!'
                ], 200);
            } else {
                $like->status = $request->status;
                $like->save();
                return response()->json([
                    'status' => 'success',
                    'message' => 'Reaction on post with id: ' . $post_id . ' from user id: ' . $user_id . ' successfully updated!',
                    'reaction' => $like->status == 1 ? 'like' : 'dislike'
                ], 201);
            }
        } else {
            $like = Like::create([
                'post_id' => $post_id,
                'user_id' => $user_id,
                'status' => $status
            ]);
            return response()->json([
                'status' => 'success',
                'message' => 'Reaction on post with id: ' . $post_id . ' from user id: ' . $user_id . ' successfully added!',
                'reaction' => $like->status == 1 ? 'like' : 'dislike'
            ], 200);
        }
    }
}
