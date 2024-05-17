<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;    

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function AjouterRole(Request $request)
    {
        //dd($request->all());

        $user = auth()->user();
        
        $request->validate([
            'role' => 'required|string',
        ]);

        $role = new Role([
            'role' => $request->input('role'),
            'id_user' => $user->id,
        ]);

        $role->save();

        return response()->json(['message' => 'Role ajouté avec succès']);
    
    }

    public function listerRole()
    {
        // Vérifier si l'utilisateur est authentifié
        if (auth()->check()) {
            $user = auth()->user();
    
            $roles = Role::where('id_user', $user->id)->get();
                return response()->json($roles);
        }
    
        return response()->json(['error' => 'Unauthorized'], 401);
    }
    

    public function modifierRole(Request $request, $id)
   {
    $role = Role::findOrFail($id);

    $request->validate([
        'role' => 'required|string|max:255',
    ]);

    $role->role = $request->role;
    $role->id_user = auth()->id(); // Ou récupérez l'ID de l'utilisateur connecté de votre façon

    $role->save();

    return response()->json(['message' => 'Role modifiée avec succès', 'role' => $role]);
   }

   public function supprimerRole($id)
   {    
       $role = Role::findOrFail($id); // Rechercher la role par son ID
       // Supprimer la role
       $role->delete();
   
       return response()->json(['message' => 'role supprimée avec succès']);
   }
    
}
