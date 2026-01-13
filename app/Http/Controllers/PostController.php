<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\Like;
use App\Models\User;

class PostController extends Controller
{

    public function index()
    {

        $posts = Post::with(['user', 'likes'])->get();


        $posts = $posts->map(function ($post) {
            return [
                'id' => $post->id,
                'title' => $post->title,
                'content' => $post->content,
                'picture' => $post->picture,
                'status' => $post->status,
                'user' => [
                    'id' => $post->user->id,
                    'email' => $post->user->email
                ],
                'likes_count' => $post->likes()->where('status', 1)->count(),
                'dislikes_count' => $post->likes()->where('status', 2)->count(),
            ];
        });

        return response()->json($posts);
    }


    public function store(Request $request)
    {
        //prvo cemo proveriti da li je nas unos validan
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|numeric',
            'category_id' => 'required|numeric',
            'title' => 'required|min:2',
            'content' => 'required|min:2',
            'picture' => 'nullable|image|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'fail',
                'message' => $validator->errors()
            ], 400);
        }

        //proveravamo da li je ulogovani user isti kao i onaj koji salje request
        $loggedInUser = Auth::user();

        if ($loggedInUser->id != $request->user_id) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Unauthorized access!!!',
                'id_violaton' => 'Your id: ' . $loggedInUser->id . ', author id: ' . $request->user_id
            ], 403);
        }

        //proveravamo da li postoji uneti category_id
        $category = PostCategory::find($request->category_id);

        if (!$category) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Category with id: ' . $request->category_id . ' doesnt exist in the base!!!'
            ], 404);
        }

        //ako request ima fajl
        $imagePath = null;
        if ($request->hasFile('picture') && $request->file('picture')) {
            $picture = $request->file('picture');
            $picture_name = time() . '_' . $picture->getClientOriginalName();
            //cuvanje fajla u storage-u
            $picture->move(public_path('storage/posts'), $picture_name);
            //cuvanje fajla u bazi
            $imagePath = "storage/posts/" . $picture_name;
        }

        $post['user_id'] = $request->user_id;
        $post['category_id'] = $request->category_id;
        $post['title'] = $request->title;
        $post['content'] = $request->content;
        $post['picture'] = $imagePath ? $imagePath : null;
        if ($loggedInUser->role == 'admin') {
            $post['status'] = 'published';
        }

        $data = Post::create($post);

        return response()->json([
            'status' => 'success',
            'message' => 'Post successfully created!',
            'data' => $data
        ], 201);
    }

    public function show($id)
    {
        $post = Post::find($id);

        if (!$post) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Post with id: ' . $id . ' doesnt exist in the base!'
            ], 404);
        }

        //uz svaki post ispisace se i broj reakcija ako ih ima
        $total_reactions = Like::where('post_id', $post->id)->count();
        //broj lajkova
        $num_of_likes =  Like::where('post_id', $post->id)->where('status', '1')->count();

        //broj dislajkova
        $num_of_dislikes =  Like::where('post_id', $post->id)->where('status', '2')->count();

        return response()->json([
            'status' => 'success',
            'post_data' => $post,
            'reactions' => [
                'total_reactions: ' => $total_reactions,
                'like_count: ' => $num_of_likes,
                'dislike_count' => $num_of_dislikes
            ]
        ], 200);
    }

    public function update(Request $request, $id)
    {
        //prvo proveravamo da li post sa zadatim id-jem postoji u bazi
        $post = Post::find($id);

        if (!$post) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Post with id: ' . $id . ' doesnt exist in the base!'
            ], 404);
        }
        //provervamo da li je nas unos validan
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|numeric',
            'title' => 'sometimes|min:2',
            'content' => 'sometimes|min:2',
            'picture' => 'nullable|image|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'fail',
                'message' => $validator->errors()
            ], 400);
        }

        //proveravamo da li je ulogovani user isti kao i onaj koji salje request za izmenu
        //samo user koji post i napravio moze da ga izmenjuje
        $loggedInUser = Auth::user();

        if ($loggedInUser->id != $request->user_id) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Unauthorized access!!!',
                'id_violaton' => 'Request id: ' . $request->user_id . ', author id: ' . $post->user_id,
            ], 403);
        }

        //proveravamo da li postoji uneti category_id ako ga korisnik menja
        if ($request->has('category_id')) {
            $category = PostCategory::find($request->category_id);

            if (!$category) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Category with id: ' . $request->category_id . ' doesnt exist in the base!!!'
                ], 404);
            }

            $post->category_id = $category->id;
        }

        if ($request->hasFile('picture')) {

            // Ako post već ima staru sliku — brišemo je
            if ($post->picture && file_exists(public_path($post->picture))) {
                unlink(public_path($post->picture));
            }

            // Sada obrađujemo novu sliku
            $picture = $request->file('picture');
            $picture_name = time() . '_' . $picture->getClientOriginalName();

            // Čuvamo novu sliku
            $picture->move(public_path('storage/posts'), $picture_name);

            // Ažuriramo putanju u bazi
            $post->picture = "storage/posts/" . $picture_name;
        }

        //ako request nema sliku, a orignalni post je imao sliku, neka se slika izbrise
        if (!$request->hasFile('picture') && $post->picture) {
            if (file_exists(public_path($post->picture))) {
                unlink(public_path($post->picture));
            }
            $post->picture = null;
        }
        if ($request->has('title')) {
            $post['title'] = $request->title;
        }

        if ($request->has('content')) {
            $post['content'] = $request->content;
        }

        if ($loggedInUser->role == 'admin') {
            $post['status'] = 'published';
        }

        $post->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Post successfully updated!',
            'data' => $post
        ], 201);
    }

    function destroy($id)
    {
        //proveravamo da li postoji fajl sa zadatim id-jem
        $post = Post::find($id);

        if (!$post) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Post with id: ' . $id . ' doesnt exist in the base!'
            ], 404);
        }

        //ako je ulogovani user kreator posta, on ima pravo da ga obrise, ili ako je admin 
        $loggedInUser = Auth::user();

        if ($loggedInUser->id != $post->user_id && $loggedInUser->role != 'admin') {
            return response()->json([
                'status' => 'fail',
                'message' => 'You are not allowed to perform this operation!!!',
                'id_violaton' => 'Request id: ' . $loggedInUser->id . ', author id: ' . $post->user_id,
            ], 403);
        } else {
            //ako je post sadrzao sliku, izbrisi je i iz baze i iz storage-a
            if ($post->picture && file_exists(public_path($post->picture))) {
                unlink(public_path($post->picture));
            }
            //izbrisi post
            $post->delete();
            return response()->json([
                'status' => 'success',
                'message' => 'Post successfully deleted!'
            ], 200);
        }
    }

    public function approve(Request $request, $id)
    {

        //prvo proveravamo nas unos
        $validator = Validator::make(
            $request->all(),
            [
                'status' => 'required|in:draft,approved,rejected'
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'status' => 'fail',
                'message' => $validator->errors()
            ], 400);
        }

        //pronalazimo post ako postoji u bazi
        $post = Post::find($id);

        if (!$post) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Post with id: ' . $id . ' doesnt exist in the base!!!'
            ], 404);
        }

        $post->status = $request->status;
        $post->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Status has been submited for post id: ' . $id,
            'status' => $post->status
        ], 200);
    }

    public function author($id)
    {
        $post = Post::find($id);

        if (!$post) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Post with id: ' . $id . ' doesnt exist in the base!'
            ], 404);
        }

        $user_id = $post->user_id;

        $user = User::find($user_id);

        return response()->json([
            'status' => 'success',
            'data' => $user
        ], 200);
    }
}
