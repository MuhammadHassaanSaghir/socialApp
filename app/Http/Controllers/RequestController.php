<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

use App\Models\User;
use App\Models\FriendRequest;

class RequestController extends Controller
{
    public function getAllusers(Request $request)
    {
        $request->validate([
            'friend_name' => 'required',
        ]);
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
    }

    public function sendRequest(Request $request)
    {
        $currToken = $request->bearerToken();
        $decode = JWT::decode($currToken, new Key('socialApp_key', 'HS256'));

        $request->validate([
            'reciever_id' => 'required|integer',
        ]);

        if ($decode->data == $request->reciever_id) {
            return response([
                "message" => "You are not allow to Send a Friend Request to yourself",
            ]);
        }

        $user = User::where('id', '=', $request->reciever_id)->first();
        if (isset($user)) {
            $alreadySent = FriendRequest::where('sender_id', '=', $decode->data, 'AND', 'reciever_id', '=', $request->reciever_id)->first();
            if (isset($alreadySent)) {
                return response([
                    "message" => "You have already Sent the Friend Request. Please Wait for Request Acceptance",
                ]);
            } else {
                $sendRequest = FriendRequest::create([
                    'sender_id' => $decode->data,
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
    }

    public function getRequests(Request $request)
    {
        $currToken = $request->bearerToken();
        $decode = JWT::decode($currToken, new Key('socialApp_key', 'HS256'));

        $friendsRequests = FriendRequest::where('reciever_id', '=', $decode->data, 'AND', 'status', '=', 'Pending')->get();
        if (json_decode($friendsRequests)) {
            return response([
                "All Requests" => $friendsRequests,
            ]);
        } else {
            return response([
                "message" => 'No Request Found',
            ]);
        }
    }

    public function recieveRequest(Request $request)
    {
        $currToken = $request->bearerToken();
        $decode = JWT::decode($currToken, new Key('socialApp_key', 'HS256'));

        $request->validate([
            'sender_id' => 'required|integer'
        ]);

        if ($decode->data == $request->sender_id) {
            return response([
                "message" => "You cannot receive a Request of yourself"
            ]);
        }

        $recieveRequest = FriendRequest::where('sender_id', '=', $request->sender_id, 'AND', 'reciever_id', $decode->data)->first();
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
    }
}
