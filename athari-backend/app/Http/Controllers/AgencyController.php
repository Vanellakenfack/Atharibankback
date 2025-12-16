<?php
namespace App\Http\Controllers;

use App\Models\Agency;
use App\Http\Requests\StoreAgencyRequest;
use App\Http\Resources\AgencyResource;
use Illuminate\Http\JsonResponse;

class AgencyController extends Controller
{
    public function index()
    {
        return AgencyResource::collection(Agency::paginate(10));
    }

    public function store(StoreAgencyRequest $request): JsonResponse
    {
        $agency = Agency::create($request->validated());

        return (new AgencyResource($agency))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Agency $agency): AgencyResource
    {
        return new AgencyResource($agency);
    }

    public function update(StoreAgencyRequest $request, Agency $agency): AgencyResource
    {
        // Note: Pour l'update, attention à l'unique sur le 'code'
        $agency->update($request->validated());
        return new AgencyResource($agency);
    }

    public function destroy(Agency $agency): JsonResponse
    {
        $agency->delete();
        return response()->json(['message' => 'Agence supprimée'], 204);
    }
}