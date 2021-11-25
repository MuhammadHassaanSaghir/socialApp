<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Post;
use App\Models\Comment;
use App\Services\tokenService;
use Illuminate\Support\Facades\DB;
use Throwable;


class CommentController extends Controller
{
    public function create(Request $request)
    {
        try {
            $request->validate([
                'post_id' => 'integer',
            ]);

            $post_exists = POST::where('id', '=', $request->post_id)->first();
            if (isset($post_exists)) {
                if ($post_exists->privacy == 'Public' or $post_exists->privacy == 'public') {
                    $attachment = null;
                    if ($request->file('attachment') != null) {
                        $attachment = $request->file('attachment')->store('commentFiles');
                    }

                    $comment = Comment::create([
                        'user_id' => $request->user_id,
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
                    $userSeen = DB::select('select * from friend_requests where ((sender_id = ? AND reciever_id = ?) OR (sender_id = ? AND reciever_id = ?)) AND status = ?', [$post_exists->user_id, $request->user_id, $request->user_id, $post_exists->user_id, 'Accept']);
                    if (!empty($userSeen)) {
                        $attachment = null;
                        if ($request->file('attachment') != null) {
                            $attachment = $request->file('attachment')->store('commentFiles');
                        }

                        $comment = Comment::create([
                            'user_id' => $request->user_id,
                            'post_id' => $request->post_id,
                            'comments' => $request->comment,
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
        } catch (Throwable $e) {
            return response(['message' => $e->getMessage()]);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $comment_exists = Comment::where('id', '=', $id)->first();
            if (isset($comment_exists)) {
                $post_privacy = POST::where('id', '=', $comment_exists->post_id)->first();
                if ($post_privacy->privacy == 'Public' or $post_privacy->privacy == 'public') {
                    if ($request->file('attachment') != null and $comment_exists->attachment != null) {
                        unlink(storage_path('app/' . $comment_exists->attachment));
                    }
                    $comment_exists->update($request->all());

                    if ($request->file('attachment') != null) {
                        $comment_exists->attachment = $request->file('attachment')->store('commentFiles');
                        $comment_exists->save();
                    }
                    return response([
                        'message' => 'Comment Updated Succesfully',
                        'Updated Comment' => $comment_exists,
                    ]);
                } elseif ($post_privacy->privacy == 'Private' or $post_privacy->privacy == 'private') {
                    if ($request->user_id == $post_privacy->user_id) {
                        if ($request->file('attachment') != null) {
                            unlink(storage_path('app/' . $comment_exists->attachment));
                        }
                        $comment_exists->update($request->all());
                        if ($request->file('attachment') != null) {
                            $comment_exists->attachment = $request->file('attachment')->store('commentFiles');
                            $comment_exists->save();
                        }
                        return response([
                            'message' => 'Comment Updated Succesfully',
                            'Updated Comment' => $comment_exists,
                        ]);
                    } else {
                        $userSeen = DB::select('select * from friend_requests where ((sender_id = ? AND reciever_id = ?) OR (sender_id = ? AND reciever_id = ?)) AND status = ?', [$post_privacy->user_id, $request->user_id, $request->user_id, $post_privacy->user_id, 'Accept']);
                        if (!empty($userSeen)) {
                            if ($request->file('attachment') != null) {
                                unlink(storage_path('app/' . $comment_exists->attachment));
                            }
                            $comment_exists->update($request->all());
                            if ($request->file('attachment') != null) {
                                $comment_exists->attachment = $request->file('attachment')->store('commentFiles');
                                $comment_exists->save();
                            }
                            return response([
                                'message' => 'Comment Updated Succesfully',
                                'Updated Comment' => $comment_exists,
                            ]);
                        } else {
                            return response([
                                'message' => 'This Post is Private and you are not a friend.',
                            ]);
                        }
                    }
                } else {
                }
            } else {
                return response([
                    'message' => 'No Post Found',
                ]);
            }
        } catch (Throwable $e) {
            return response(['message' => $e->getMessage()]);
        }
    }

    public function delete(Request $request, $id)
    {
        try {
            $comment = Comment::where('id', '=', $id, 'AND', 'user_id', '=', $request->user_id)->first();
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
        } catch (Throwable $e) {
            return response(['message' => $e->getMessage()]);
        }
    }
}
