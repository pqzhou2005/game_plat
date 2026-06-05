<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ServerOpenRequest;
use App\Models\GameSsoConfig;
use App\Models\ServerOpenReport;
use Illuminate\Http\JsonResponse;

class ServerOpenController extends Controller
{
    public function autoOpen(ServerOpenRequest $request): JsonResponse
    {
        $config = GameSsoConfig::where('platform_id', $request->project)->first();
        if (!$config) {
            return response()->json(['errno' => 1, 'msg' => '项目不存在']);
        }

        $params = $request->except('sign');
        ksort($params);
        $expectedSign = strtolower(md5(http_build_query($params) . $config->login_key));

        if ($request->sign !== $expectedSign) {
            return response()->json(['errno' => 1, 'msg' => '签名校验失败']);
        }

        ServerOpenReport::create([
            'game_id' => $config->game_id,
            'project' => $request->project,
            'open_server' => $request->open_server,
            'open_server_time' => $request->open_server_time ?: null,
            'created_role_num' => (int)($request->created_role_num ?? 0),
            'preset_role_num' => (int)($request->preset_role_num ?? 0),
            'pay_num' => (int)($request->pay_num ?? 0),
            'preset_pay_num' => (int)($request->preset_pay_num ?? 0),
            'preset_open_server' => $request->preset_open_server ?? 0,
            'preset_open_server_time' => $request->preset_open_server_time ?: null,
            'sur_dep_not_ser_num' => $request->sur_dep_not_ser_num ?? 0,
            'raw_data' => $request->all(),
        ]);

        return response()->json(['errno' => 0, 'msg' => '成功', 'data' => []]);
    }
}
