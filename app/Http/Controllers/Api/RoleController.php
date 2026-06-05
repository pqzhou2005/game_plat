<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RoleReport;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RoleController extends Controller
{
    public function report(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'game_id' => 'required|exists:games,id',
            'submit_type' => 'required|integer|in:1,2,3,4',
            'server_id' => 'required|integer',
            'server_name' => 'nullable|string|max:100',
            'role_id' => 'required|string|max:100',
            'role_name' => 'nullable|string|max:100',
            'role_level' => 'nullable|integer',
            'zone_id' => 'nullable|integer',
            'zone_name' => 'nullable|string|max:100',
            'create_time' => 'nullable|integer',
        ]);

        $validated['user_id'] = $request->user()->id;
        $validated['raw_data'] = $request->all();
        $validated['create_time'] = isset($validated['create_time']) ? date('Y-m-d H:i:s', $validated['create_time']) : now();

        RoleReport::create($validated);

        return response()->json(['status' => 0, 'msg' => '上报成功']);
    }

    public function batchReport(Request $request): JsonResponse
    {
        $records = $request->validate([
            '*.game_id' => 'required|exists:games,id',
            '*.submit_type' => 'required|integer|in:1,2,3,4',
            '*.server_id' => 'required|integer',
            '*.role_id' => 'required|string|max:100',
            '*.role_level' => 'nullable|integer',
            '*.create_time' => 'nullable|integer',
        ]);

        $userId = $request->user()->id;

        foreach ($records as $record) {
            $record['user_id'] = $userId;
            $record['raw_data'] = $record;
            $record['create_time'] = isset($record['create_time']) ? date('Y-m-d H:i:s', $record['create_time']) : now();
            RoleReport::create($record);
        }

        return response()->json(['status' => 0, 'msg' => '批量上报成功', 'count' => count($records)]);
    }
}
