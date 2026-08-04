<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Service::query();

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $services = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $services,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $imagePaths = [];

        if ($request->hasFile('images')) {
            $uploaded = $request->file('images');
            $files = is_array($uploaded) ? $uploaded : [$uploaded];
            foreach ($files as $file) {
                if ($file->isValid()) {
                    $imagePaths[] = $file->store('services', 'public');
                }
            }
        } elseif ($request->hasFile('image')) {
            $file = $request->file('image');
            if ($file->isValid()) {
                $imagePaths[] = $file->store('services', 'public');
            }
        }

        $service = Service::create([
            'title' => $request->title,
            'description' => $request->description,
            'price' => $request->price,
            'is_active' => $request->has('is_active') ? filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN) : true,
            'images' => $imagePaths,
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $service,
        ], 201);
    }

    public function show(Service $service)
    {
        return response()->json([
            'status' => 'success',
            'data' => $service,
        ]);
    }

    public function update(Request $request, Service $service)
    {
        $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $data = [];
        if ($request->has('title')) $data['title'] = $request->title;
        if ($request->has('description')) $data['description'] = $request->description;
        if ($request->has('price')) $data['price'] = $request->price;
        if ($request->has('is_active')) $data['is_active'] = filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN);

        $service->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Servicio actualizado correctamente.',
            'data' => $service->fresh(),
        ]);
    }

    public function destroy(Service $service)
    {
        $service->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Servicio eliminado correctamente.',
        ]);
    }

    /**
     * Endpoint independiente para subir una o varias imágenes de un servicio
     * POST /api/admin/services/{service}/images
     */
    public function uploadImages(Request $request, Service $service)
    {
        if (!$request->hasFile('images') && !$request->hasFile('image')) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se ha adjuntado ningún archivo de imagen.',
            ], 422);
        }

        $currentImages = is_array($service->images) ? $service->images : [];

        if ($request->hasFile('images')) {
            $uploaded = $request->file('images');
            $files = is_array($uploaded) ? $uploaded : [$uploaded];
            foreach ($files as $file) {
                if ($file->isValid()) {
                    $currentImages[] = $file->store('services', 'public');
                }
            }
        } elseif ($request->hasFile('image')) {
            $file = $request->file('image');
            if ($file->isValid()) {
                $currentImages[] = $file->store('services', 'public');
            }
        }

        $service->update(['images' => array_values($currentImages)]);

        return response()->json([
            'status' => 'success',
            'message' => 'Imágenes subidas y agregadas al servicio correctamente.',
            'data' => $service->fresh(),
        ]);
    }

    /**
     * Endpoint independiente para eliminar una imagen específica del servicio
     * DELETE /api/admin/services/{service}/images
     */
    public function deleteImage(Request $request, Service $service)
    {
        $request->validate([
            'image_path' => 'required|string',
        ]);

        $targetPath = ltrim($request->image_path, '/');
        $currentImages = is_array($service->images) ? $service->images : [];

        // Buscar si existe la ruta en las imágenes del servicio
        $key = array_search($targetPath, $currentImages);

        if ($key === false) {
            return response()->json([
                'status' => 'error',
                'message' => 'La imagen especificada no fue encontrada en este servicio.',
            ], 404);
        }

        // Eliminar el archivo del disco de almacenamiento si existe
        if (Storage::disk('public')->exists($targetPath)) {
            Storage::disk('public')->delete($targetPath);
        }

        // Remover de la lista de imágenes del servicio
        unset($currentImages[$key]);
        $service->update(['images' => array_values($currentImages)]);

        return response()->json([
            'status' => 'success',
            'message' => 'Imagen eliminada correctamente del servicio.',
            'data' => $service->fresh(),
        ]);
    }
}
