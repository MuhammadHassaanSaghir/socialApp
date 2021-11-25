<?php

namespace App\Http\Controllers;

use App\Http\Requests\PostRequest;
use App\Http\Resources\PostResource;
use Illuminate\Http\Request;

use App\Models\Post;
use App\Models\FriendRequest;
use App\Services\tokenService;
use Illuminate\Support\Facades\DB;
use Throwable;

class PostController extends Controller
{
    public function create(PostRequest $request)
    {
        try {
            $request->validated();

            if (($request->privacy == 'Public' or $request->privacy == 'public') or ($request->privacy == 'Private' or $request->privacy == 'private')) {
                $attachment = null;
                if ($request->file('attachment') != null) {
                    $attachment = $request->file('attachment')->store('postFiles');
                }

                $post = Post::create([
                    'user_id' => (new tokenService)->getToken($request),
                    'title' => $request->title,
                    'body' => $request->body,
                    'privacy' => $request->privacy,
                    'attachment' => $attachment
                ]);

                if (isset($post)) {
                    return response([
                        'message' => 'Post Created Succesfully',
                        'Post' => $post,
                    ]);
                } else {
                    return response([
                        'message' => 'Something Went Wrong While Creating Post',
                    ]);
                }
            } else {
                return response([
                    'message' => 'You have to required place Public / Private in Privacy',
                ]);
            }
        } catch (Throwable $e) {
            return response(['message' => $e->getMessage()]);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $post = Post::where('id', '=', $id, 'AND', 'user_id', '=', (new tokenService)->getToken($request))->first();
            if (isset($post)) {
                $update = Post::find($id);
                if (isset($request->privacy)) {
                    if (($request->privacy == 'Public' or $request->privacy == 'public') or ($request->privacy == 'Private' or $request->privacy == 'private')) {
                        $update->privacy = $request->privacy;
                        $update->save();
                        if ($request->file('attachment') != null and $update->attachment != null) {
                            unlink(storage_path('app/' . $update->attachment));
                        }

                        $update->update($request->all());

                        if ($request->file('attachment') != null) {
                            $update->attachment = $request->file('attachment')->store('postFiles');
                            $update->save();
                        }

                        return response([
                            'Post' => $update,
                            'message' => 'Post Updated Succesfully',
                        ]);
                    } else {
                        return response([
                            'message' => 'You have to required place Public / Private in Privacy',
                        ]);
                    }
                } else {
                    if ($request->file('attachment') != null) {
                        unlink(storage_path('app/' . $update->attachment));
                    }

                    $update->update($request->all());

                    if ($request->file('attachment') != null) {
                        $update->attachment = $request->file('attachment')->store('postFiles');
                        $update->save();
                    }

                    return response([
                        'Post' => $update,
                        'message' => 'Post Updated Succesfully',
                    ]);
                }
            } else {
                return response([
                    'message' => 'Unauthorize to Update Post',
                ]);
            }
        } catch (Throwable $e) {
            return response(['message' => $e->getMessage()]);
        }
    }

    public function delete(Request $request, $id)
    {
        try {
            $post = Post::where('id', '=', $id, 'AND', 'user_id', '=', (new tokenService)->getToken($request))->first();
            if (isset($post)) {
                if ($post->attachment != null) {
                    unlink(storage_path('app/' . $post->attachment));
                }
                $post->delete();
                return response([
                    'message' => 'Post has been Deleted',
                ]);
            } else {
                return response([
                    'message' => 'You Unauthorize to Delete Post',
                ]);
            }
        } catch (Throwable $e) {
            return response(['message' => $e->getMessage()]);
        }
    }

    public function getPublicposts()
    {
        try {
            $post = Post::whereIn('privacy', array('Public', 'public'))->get();
            if (json_decode($post)) {
                return PostResource::collection($post);
            } else {
                return response([
                    'message' => 'No Post Found',
                ]);
            }
        } catch (Throwable $e) {
            return response(['message' => $e->getMessage()]);
        }
    }

    public function getPrivateposts(Request $request)
    {
        try {
            $posts = Post::whereIn('privacy', array('Private', 'private'))->get();
            foreach ($posts as $post) {
                $post = json_decode($post->user_id);

                // DB::enableQueryLog();
                $userSeen = DB::select('select * from friend_requests where ((sender_id = ? AND reciever_id = ?) OR (sender_id = ? AND reciever_id = ?)) AND status = ?', [$post, (new tokenService)->getToken($request), (new tokenService)->getToken($request), $post, 'Accept']);

                // $userSeen = FriendRequest::where('sender_id', '$post')
                //     ->where('reciever_id', '(new tokenService)->getToken($request)')
                //     ->orWhere('sender_id', '(new tokenService)->getToken($request)')
                //     ->where('reciever_id', '$post')
                //     ->where('status', '=', 'Accept')
                //     // ->get()
                //     ->toSql()
                // ;
                // dd(!empty($userSeen));
                // dd(DB::getQueryLog());

                if (!empty($userSeen) and json_decode($posts)) {
                    return PostResource::collection($posts);
                } else {
                    return response([
                        'message' => 'No Post Found',
                    ]);
                }
            }
        } catch (Throwable $e) {
            return response(['message' => $e->getMessage()]);
        }
    }

    public function search(Request $request)
    {
        try {
            $request->validate([
                'title' => 'required|string',
            ]);
            $post = Post::where('title', 'LIKE', '%' . $request->title . '%', 'AND', 'user_id', '=', (new tokenService)->getToken($request))->get();
            if (json_decode($post)) {
                return PostResource::collection($post);
            } else {
                return response([
                    'message' => 'No Post Found',
                ]);
            }
        } catch (Throwable $e) {
            return response(['message' => $e->getMessage()]);
        }
    }
}
