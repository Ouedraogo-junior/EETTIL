<?php

namespace App\Modules\Clients\Http\Controllers;

use App\Modules\Clients\Http\Requests\ClientRequest;
use App\Modules\Clients\Http\Resources\ClientResource;
use App\Modules\Clients\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ClientController
{
    public function index(Request $request)
    {
        $perPage = min(max($request->integer('per_page', 20), 1), 100);

        $clients = Client::query()
            ->search($request->query('q'))
            ->orderBy('name')
            ->paginate($perPage);

        return ClientResource::collection($clients);
    }

    public function store(ClientRequest $request): ClientResource
    {
        return new ClientResource(Client::create($request->validated()));
    }

    public function show(Client $client): ClientResource
    {
        return new ClientResource($client);
    }

    public function update(ClientRequest $request, Client $client): ClientResource
    {
        $client->update($request->validated());

        return new ClientResource($client);
    }

    public function destroy(Client $client): Response
    {
        // Étape 3 : on refusera la suppression si le client a des dossiers.
        $client->delete();

        return response()->noContent();
    }
}