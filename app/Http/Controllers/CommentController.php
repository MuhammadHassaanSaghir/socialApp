<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

use App\Models\Post;
use App\Models\Comment;
use App\Models\FriendRequest;

use Illuminate\Support\Facades\DB;


class CommentController extends Controller
{
    public function create(Request $request)
    {
        $curr_token = $request->bearerToken();
        $decode = JWT::decode($curr_token, new Key('socialApp_key', 'HS256'));

        $request->validate([
            'post_id' => 'integer',
            'comment' => 'string',
        ]);

        $post_exists = POST::where('id', '=', $request->post_id)->first();
        if (isset($post_exists)) {
            if ($post_exists->privacy == 'Public' or $post_exists->privacy == 'public') {
                $attachment = null;
                if ($request->file('attachment') != null) {
                    $attachment = $request->file('attachment')->store('commentFiles');
                }

                $comment = Comment::create([
                    'user_id' => $decode->data,
                    'post_id' => $request->post_id,
                    'comments' => $request->comments,
                    'attachment' => $attachment
                ]);

                if (isset($comment)) {
                    return response([
                        'message' => 'Comment Created Succesfully',
                        'Comment' => $comment,
                    ]);
                } else {
                    return response([
                        'message' => 'Something Went Wrong While added Comment',
                    ]);
                }
            } elseif ($post_exists->privacy == 'Private' or $post_exists->privacy == 'private') {
                $userSeen = DB::select('select * from friend_requests where ((sender_id = ? AND reciever_id = ?) OR (sender_id = ? AND reciever_id = ?)) AND status = ?', [$post_exists->user_id, $decode->data, $decode->data, $post_exists->user_id, 'Accept']);
                if (!empty($userSeen)) {
                    $attachment = null;
                    if ($request->file('attachment') != null) {
                        $attachment = $request->file('attachment')->store('commentFiles');
                    }

                    $comment = Comment::create([
                        'user_id' => $decode->data,
                        'post_id' => $request->post_id,
                        'comments' => $request->comments,
                        'attachment' => $attachment
                    ]);

                    if (isset($comment)) {
                        return response([
                            'message' => 'Comment Created Succesfully',
                            'Comment' => $comment,
                        ]);
                    } else {
                        return response([
                            'message' => 'Something Went Wrong While added Comment',
                        ]);
                    }
                } else {
                    return response([
                        'message' => 'This is Private Post. You are not authorize to Comment on this Post',
                    ]);
                }
            }
        } else {
            return response([
                'message' => 'No Post Found',
            ]);
        }
    }

    public function searchCommentbyPost(Request $request, $id)
    {
    }

    // public function update(Request $request, $id)
    // {
    //     $curr_token = $request->bearerToken();
    //     $decode = JWT::decode($curr_token, new Key('socialApp_key', 'HS256'));

    //     $comment = Comment::where('id', '=', $id, 'AND', 'user_id', '=', $decode->data)->first();
    //     if (isset($comment)) {
    //         $update = Comment::find($id);
    //         if (isset($request->privacy)) {
    //             if (($request->privacy == 'Public' or $request->privacy == 'public') or ($request->privacy == 'Private' or $request->privacy == 'private')) {
    //                 $update->privacy = $request->privacy;
    //                 $update->save();
    //                 if ($request->file('attachment') != null) {
    //                     unlink(storage_path('app/' . $update->attachment));
    //                 }

    //                 $update->update($request->all());

    //                 if ($request->file('attachment') != null) {
    //                     $update->attachment = $request->file('attachment')->store('postFiles');
    //                     $update->save();
    //                 }

    //                 return response([
    //                     'Post' => $update,
    //                     'message' => 'Post Updated Succesfully',
    //                 ]);
    //             } else {
    //                 return response([
    //                     'message' => 'You have to required place Public / Private in Privacy',
    //                 ]);
    //             }
    //         } else {
    //             if ($request->file('attachment') != null) {
    //                 unlink(storage_path('app/' . $update->attachment));
    //             }

    //             $update->update($request->all());

    //             if ($request->file('attachment') != null) {
    //                 $update->attachment = $request->file('attachment')->store('postFiles');
    //                 $update->save();
    //             }

    //             return response([
    //                 'Post' => $update,
    //                 'message' => 'Post Updated Succesfully',
    //             ]);
    //         }
    //     } else {
    //         return response([
    //             'message' => 'Unauthorize to Update Post',
    //         ]);
    //     }
    // }

    public function delete(Request $request, $id)
    {
        $curr_token = $request->bearerToken();
        $decode = JWT::decode($curr_token, new Key('socialApp_key', 'HS256'));

        $comment = Comment::where('id', '=', $id, 'AND', 'user_id', '=', $decode->data)->first();
        if (isset($comment)) {
            if ($comment->attachment != null) {
                unlink(storage_path('app/' . $comment->attachment));
            }
            $comment->delete();
            return response([
                'message' => 'Comment has been Deleted',
            ]);
        } else {
            return response([
                'message' => 'You Unauthorize to Delete Comment',
            ]);
        }
    }
}
