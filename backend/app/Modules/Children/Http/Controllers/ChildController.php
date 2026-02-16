<?php
declare(strict_types=1);

namespace Modules\Children\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Children\Services\ChildService;

final class ChildController extends Controller
{
    public function __construct(private readonly ChildService $service) {}

    public function index(Request $request)
    {
        $parentId = $request->user()->id;
        return response()->json($this->service->getChildrenByParent($parentId));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            "first_name" => "required|string|max:255",
            "last_name" => "required|string|max:255",
            "middle_name" => "nullable|string|max:255",
            "gender" => "nullable|string|in:male,female",
            "birth_date" => "nullable|date",
        ]);
        $data["user_id"] = (string) $request->user()->id;

        $child = $this->service->createChild($data);
        return response()->json($child, 201);
    }

    public function show(string $id)
    {
        $child = $this->service->getChildById($id);
        if (!$child || (string) $child->user_id !== (string) request()->user()->id) {
            abort(404, "Not found");
        }

        return response()->json($child);
    }

    public function update(Request $request, string $id)
    {
        $child = $this->service->getChildById($id);
        if (!$child || (string) $child->user_id !== (string) $request->user()->id) {
            abort(404, "Not found");
        }

        $data = $request->validate([
            "first_name" => "sometimes|string|max:255",
            "last_name" => "sometimes|string|max:255",
            "middle_name" => "nullable|string|max:255",
            "gender" => "nullable|string|in:male,female",
            "birth_date" => "nullable|date",
        ]);
        $updated = $this->service->updateChild($child, $data);
        return response()->json($updated);
    }

    public function destroy(string $id)
    {
        $child = $this->service->getChildById($id);
        if (!$child || (string) $child->user_id !== (string) request()->user()->id) {
            abort(404, "Not found");
        }

        $this->service->delete($child);
        return response()->json(null, 204);
    }
}
