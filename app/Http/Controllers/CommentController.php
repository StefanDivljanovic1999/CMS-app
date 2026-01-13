<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\Post;

class CommentController extends Controller
{

    public function index()
    {
        return Comment::get();
    }

    public function store(Request $request)
    {
        //prvo cemo proveriti da li je nas unos validan
        $validator = Validator::make($request->all(), [
            'post_id' => 'required|numeric|exists:posts,id',
            'comment' => 'required',
            'comment_picture'=>'nullable'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'fail',
                'message' => $validator->errors()
            ], 400);
        }

        //ako ne postoji komentar sa zadatim id-em, baci gresku
        $post= Post::find($request->post_id);

        
        //user id ce biti od onoga koji se i ulogovao da komentar postavi
        $loggedInUser = Auth::user();

        //ako se uz komentar prilaze i slika
         $imagePath = null;
        if ($request->hasFile('comment_picture') && $request->file('comment_picture')) {
            $picture = $request->file('comment_picture');
            $picture_name = time() . '_' . $picture->getClientOriginalName();
            //cuvanje fajla u storage-u
            $picture->move(public_path('storage/posts/comment-pictures'), $picture_name);
            //cuvanje fajla u bazi
            $imagePath = "storage/posts/comment-pictures/" . $picture_name;
        }

        //kreiramo komentar
        $comment=Comment::create([
            'post_id'=> $request->post_id,
            'user_id'=> $loggedInUser->id,
            'comment'=> $request->comment,
            'comment_picture'=> $imagePath ?  $imagePath:null
        ]);

          return response()->json([
                'status' => 'success',
                'message' => 'Comment successfully posted!',
                'data'=> $comment
            ], 201);
        
    }

    public function show(Comment $comment)
    {
        return Comment::findOrFail($comment);
    }

    public function update(Request $request, $id)
    {
        //proveravamo unos prvo
          $validator = Validator::make($request->all(), [
            'comment' => 'sometimes',
            'comment_picture'=>'nullable'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'fail',
                'message' => $validator->errors()
            ], 400);
        }

        //nakon toga cemo proveriti da li postoji komentar sa zadatim id-jem
        $comment= Comment::find($id);

        if(!$comment){
             return response()->json([
                'status' => 'fail',
                'message' => 'Comment with id: ' .$id. ' doesnt exist in the base!'
            ], 404);
        }

        //ako nije user_id od kreatora komentara isti kao i od ulogovanog, prekidamo izvrsavanje
        $user= Auth::user();

        if($user->id != $comment->user_id){
             return response()->json([
                'status' => 'fail',
                'message' => 'Unauthorized access!',
                'id_violation'=> 'Your id: '. $user->id. ', author id: ' . $comment->user_id
            ], 403);
        }


        if($request->has('comment')){
            $comment->comment= $request->comment;
        }

         if ($request->hasFile('comment_picture')) {

            // Ako komentar već ima staru sliku — brisemo je
            if ($comment->comment_picture && file_exists(public_path($comment->comment_picture))) {
                unlink(public_path($comment->comment_picture));
            }

            // Sada obrađujemo novu sliku
            $picture = $request->file('comment_picture');
            $picture_name = time() . '_' . $picture->getClientOriginalName();

            // Cuvamo novu sliku
            $picture->move(public_path('storage/posts/comment-pictures'), $picture_name);

            // Azuriramo putanju u bazi
            $comment->comment_picture = "storage/posts/comment-pictures/" . $picture_name;
        }

        //ako request nema sliku, a orignalni komentar je imao sliku, neka se slika izbrise
        if (!$request->hasFile('comment_picture') && $comment->comment_picture) {
            if (file_exists(public_path($comment->comment_picture))) {
                unlink(public_path($comment->comment_picture));
            }
            $comment->comment_picture = null;
        }

        //cuvamo azurirani komentar

        $comment->save();

         return response()->json([
                'status' => 'success',
                'message' => 'Comment successfully updated!',
                'data'=> $comment
            ], 200);


    }


    public function destroy($id)
    {
        
        //proveravamo da li postoji fajl sa zadatim id-jem
        $comment = Comment::find($id);

        if (!$comment) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Comment with id: ' . $id . ' doesnt exist in the base!'
            ], 404);
        }

        //ako je ulogovani user kreator posta, on ima pravo da ga obrise, ili ako je admin 
        $loggedInUser = Auth::user();

        if ($loggedInUser->id != $comment->user_id && $loggedInUser->role != 'admin') {
            return response()->json([
                'status' => 'fail',
                'message' => 'You are not allowed to perform this operation!!!',
                'id_violaton' => 'Request id: ' . $loggedInUser->id . ', author id: ' . $comment->user_id,
            ], 403);
        } else {
            //ako je post sadrzao sliku, izbrisi je i iz baze i iz storage-a
            if ($comment->comment_picture && file_exists(public_path($comment->comment_picture))) {
                unlink(public_path($comment->comment_picture));
            }
            //izbrisi post
            $comment->delete();
            return response()->json([
                'status' => 'success',
                'message' => 'Comment successfully deleted!'
            ], 200);
        }
    }
}
