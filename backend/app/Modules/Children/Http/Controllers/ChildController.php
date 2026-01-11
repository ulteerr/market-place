<?php
declare(strict_types=1);

namespace Modules\Children\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\Children\Services\ChildService;
use Modules\Children\Models\Child;

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
            'name' => 'required|string|max:255',
            'birth_date' => 'nullable|date',
            'parent_id' => 'required|uuid',
        ]);

        $child = $this->service->createChild($data);
        return response()->json($child, 201);
    }

    public function show(string $id)
    {
        $child = $this->service->getChildById($id);
        return response()->json($child);
    }

    public function update(Request $request, string $id)
    {
        $child = $this->service->getChildById($id);
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'birth_date' => 'nullable|date',
        ]);
        $updated = $this->service->updateChild($child, $data);
        return response()->json($updated);
    }

    public function destroy(string $id)
    {
        $child = $this->service->getChildById($id);
        $this->service->delete($child);
        return response()->json(null, 204);
    }
}