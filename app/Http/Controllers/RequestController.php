<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User;
use App\Models\FriendRequest;
use App\Services\tokenService;
use Illuminate\Support\Facades\DB;
use Throwable;

class RequestController extends Controller
{
    public function getAllusers(Request $request)
    {
        try {
            $request->validate([]);
            $user = User::where('name', 'LIKE', '%' . $request->friend_name . '%')->get();
            if (json_decode($user)) {
                return response([
                    'Searched user' => $user,
                ]);
            } else {
                return response([
                    'message' => 'No User Found',
                ]);
            }
        } catch (Throwable $e) {
            return response(['message' => $e->getMessage()]);
        }
    }

    public function sendRequest(Request $request)
    {
        try {
            $request->validated();

            if ($request->user_id == $request->reciever_id) {
                return response([
                    "message" => "You are not allow to Send a Friend Request to yourself",
                ]);
            }

            $user = User::where('id', '=', $request->reciever_id)->first();
            if (isset($user)) {
                $alreadySent = FriendRequest::where('sender_id', '=', $request->user_id, 'AND', 'reciever_id', '=', $request->reciever_id)->first();
                if (isset($alreadySent)) {
                    return response([
                        "message" => "You have already Sent the Friend Request. Please Wait for Request Acceptance",
                    ]);
                } else {
                    $sendRequest = FriendRequest::create([
                        'sender_id' => $request->user_id,
                        'reciever_id' => $request->reciever_id,
                    ]);
                    if (isset($sendRequest)) {
                        return response([
                            "message" => "The Request has been Successfully Sent",
                        ]);
                    } else {
                        return response([
                            "message" => "Something Went Wrong",
                        ]);
                    }
                }
            } else {
                return response([
                    "message" => "No User Found",
                ]);
            }
        } catch (Throwable $e) {
            return response(['message' => $e->getMessage()]);
        }
    }

    public function getRequests(Request $request)
    {
        try {
            $friendsRequests = FriendRequest::where('reciever_id', '=', $request->user_id, 'AND', 'status', '=', 'Pending')->get();
            if (json_decode($friendsRequests)) {
                return response([
                    "All Requests" => $friendsRequests,
                ]);
            } else {
                return response([
                    "message" => 'No Request Found',
                ]);
            }
        } catch (Throwable $e) {
            return response(['message' => $e->getMessage()]);
        }
    }

    public function recieveRequest(Request $request)
    {
        try {
            $request->validate([
                'sender_id' => 'required|integer'
            ]);

            if ($request->user_id == $request->sender_id) {
                return response([
                    "message" => "You cannot receive a Request of yourself"
                ]);
            }

            $recieveRequest = FriendRequest::where('sender_id', '=', $request->sender_id, 'AND', 'reciever_id', $request->user_id)->first();
            if (isset($recieveRequest)) {
                if ($recieveRequest->status == 'Accept') {
                    return response([
                        "Message" => "You are already Accept the Request"
                    ]);
                } else {
                    $recieveRequest->status = 'Accept';
                    $acceptRequest = $recieveRequest->save();
                    if (isset($acceptRequest)) {
                        return response([
                            "message" => "The request has been Accepted Successfully"
                        ]);
                    } else {
                        return response([
                            "message" => "Something Went Wrong"
                        ]);
                    }
                }
            } else {
                return response([
                    "message" => "No User Found"
                ]);
            }
        } catch (Throwable $e) {
            return response(['message' => $e->getMessage()]);
        }
    }

    public function remove(Request $request, $id)
    {
        try {
            if ($id == $request->user_id) {
                return response([
                    "message" => "You cannot Unfriend to Yourself"
                ]);
            }

            $friendExist = DB::select('select * from friend_requests where ((sender_id = ? AND reciever_id = ?) OR (sender_id = ? AND reciever_id = ?))', [$id, $request->user_id, $request->user_id, $id]);
            if (!empty($friendExist)) {
                $removeFriend = DB::table('friend_requests')->where('id', $friendExist[0]->id)->delete();
                if (isset($removeFriend)) {
                    return response([
                        "message" => "You Successfully Remove Friend"
                    ]);
                } else {
                    return response([
                        "message" => "Something Went Wrong"
                    ]);
                }
            } else {
                return response([
                    "message" => "No Friend Found"
                ]);
            }
        } catch (Throwable $e) {
            return response(['message' => $e->getMessage()]);
        }
    }
}
