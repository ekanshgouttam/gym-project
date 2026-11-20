<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Tells the mobile app whether it is up to date. Called on app launch:
 * update "required" → block the app with an update screen; "optional" → show a dismissible prompt.
 */
class AppVersionController extends Controller
{
    public function show(Request $request)
    {
        $data = $request->validate([
            'platform' => ['required', Rule::in(['android', 'ios'])],
            'version' => ['nullable', 'regex:/^\d+(\.\d+){0,2}$/'],
        ]);

        $config = config('mobile.' . $data['platform']);
        $response = [
            'platform' => $data['platform'],
            'latest_version' => $config['latest'],
            'minimum_version' => $config['minimum'],
            'store_url' => $config['store_url'],
        ];

        if (isset($data['version'])) {
            $response['update'] = version_compare($data['version'], $config['minimum'], '<') ? 'required'
                : (version_compare($data['version'], $config['latest'], '<') ? 'optional' : 'none');
        }

        return response()->json($response);
    }
}
