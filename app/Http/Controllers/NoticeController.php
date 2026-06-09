<?php
namespace App\Http\Controllers;

use App\Enums\CommonStatus;
use App\Models\Notice;
use Inertia\Inertia;

class NoticeController extends Controller
{
    public function index()
    {
        return Inertia::render('Notices/Index', [
            'notices' => Notice::active()->with('game')->paginate(20),
            'types' => [
                'platform' => '平台公告', 'game' => '游戏公告',
                'maintenance' => '维护公告', 'activity' => '活动公告', 'merge' => '合服公告',
            ],
        ]);
    }

    public function show(Notice $notice)
    {
        if ($notice->status !== CommonStatus::ACTIVE || $notice->published_at > now()) {
            abort(404);
        }

        return Inertia::render('Notices/Show', [
            'notice' => $notice->load('game'),
        ]);
    }
}
