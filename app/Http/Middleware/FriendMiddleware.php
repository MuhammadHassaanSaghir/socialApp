<?php

namespace App\Http\Middleware;

use App\Models\Post;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FriendMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $post_exists = Post::where('id', '=', $request->post_id)->first();
        if ($post_exists->privacy == 'Private' or $post_exists->privacy == 'private') {
            $userSeen = DB::select('select * from friend_requests where ((sender_id = ? AND reciever_id = ?) OR (sender_id = ? AND reciever_id = ?)) AND status = ?', [$post_exists->user_id, getToken($request), getToken($request), $post_exists->user_id, 'Accept']);
            if (!empty($userSeen)) {
                return response([
                    'message' => 'This is Private Post. You are not authorize to Comment on this Post',
                ]);
            } else {
                return $next($request);
            }
        }
    }
}
