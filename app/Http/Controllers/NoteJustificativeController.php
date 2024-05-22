<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Request;
use App\Models\NoteJustificative;
use App\Http\Requests\StoreNoteJustificativeRequest;
use App\Http\Requests\UpdateNoteJustificativeRequest;

class NoteJustificativeController extends Controller
{
    public function listerNotes()
    {
        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateurId = auth('apisousUtilisateur')->id();
            $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent
        } elseif (auth()->check()) {
            $userId = auth()->id();
        } else {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $notes = NoteJustificative::with(['sousUtilisateur', 'user', 'article'])
            ->where('user_id', $userId)
            ->orWhere('sousUtilisateur_id', $sousUtilisateurId ?? 0)
            ->get();

        return response()->json($notes);
    }

    public function modifierNote(Request $request, $id)
    {
        $request->validate([
            'note' => 'required|string|max:255',
            'quantite' => 'required|integer|min:0',
        ]);

        $noteJustificative = NoteJustificative::findOrFail($id);
        $article = Article::findOrFail($noteJustificative->article_id);

        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateurId = auth('apisousUtilisateur')->id();
            if ($noteJustificative->sousUtilisateur_id !== $sousUtilisateurId) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        } elseif (auth()->check()) {
            $userId = auth()->id();
            if ($noteJustificative->user_id !== $userId) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        } else {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Mettre à jour la note justificative
        $noteJustificative->note = $request->input('note');
        $noteJustificative->save();

        // Mettre à jour la quantité de l'article
        $article->quantite = $request->input('quantite');
        $article->save();

        return response()->json(['message' => 'Note  modifiée avec succès', 'note' => $noteJustificative, 'article' => $article]);
    }

    public function supprimerNote($id)
    {
        $note = NoteJustificative::findOrFail($id);

        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateurId = auth('apisousUtilisateur')->id();
            if ($note->sousUtilisateur_id !== $sousUtilisateurId) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        } elseif (auth()->check()) {
            $userId = auth()->id();
            if ($note->user_id !== $userId) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        } else {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $note->delete();

        return response()->json(['message' => 'Note supprimée avec succès']);
    }
}
