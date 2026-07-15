<?php

namespace App\Http\Controllers;

use App\Models\Member;
use Illuminate\Http\Response;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class MemberQrController extends Controller
{
    public function show(Member $member): Response
    {
        abort_unless($member->gym_id === auth()->user()->gym_id, 404);

        return response(
            QrCode::size(200)->generate($member->qr_code),
            200,
            ['Content-Type' => 'image/svg+xml']
        );
    }
}
