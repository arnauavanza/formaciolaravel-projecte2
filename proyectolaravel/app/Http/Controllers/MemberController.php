<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMemberRequest;
use App\Http\Requests\UpdateMemberRequest;
use App\Http\Resources\MemberResource;
use App\Models\Member;

class MemberController extends Controller
{
    public function index()
    {
        $members = Member::query()
            ->orderBy('name')
            ->get();

        return MemberResource::collection($members);
    }

    public function store(StoreMemberRequest $request)
    {
        $member = Member::create($request->validated());

        return (new MemberResource($member))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Member $member)
    {
        return new MemberResource($member);
    }

    public function update(UpdateMemberRequest $request, Member $member)
    {
        $member->update($request->validated());

        return new MemberResource($member->fresh());
    }

    public function destroy(Member $member)
    {
        if ($member->loans()->exists()) {
            return response()->json([
                'message' => 'Cannot delete a member with loan history.',
            ], 409);
        }

        $member->delete();

        return response()->noContent();
    }
}
