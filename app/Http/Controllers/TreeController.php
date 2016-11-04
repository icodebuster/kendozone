<?php

namespace App\Http\Controllers;

use App\PreliminaryTree;
use Illuminate\Http\Request;

class TreeController extends Controller
{
    /**
     * Display a listing of trees.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

        $championships = PreliminaryTree::getChampionships($request);
        dd($championships);
        foreach ($championships as $championship) {
            $hasPreliminary = $championship->settings->hasPreliminary;
            if ($hasPreliminary) {
                $preliminaryTree = PreliminaryTree::where('championship_id', $championship->id)->get();
                $championship->tree = $preliminaryTree;
            } else {
                // RoundRobin or Direct Elimination

            }

        }

        return view('preliminaryTree.index');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response|string
     */
    public function store(Request $request)
    {

        $championships = PreliminaryTree::getChampionships($request);

        foreach ($championships as $championship) {
            // If no settings has been defined, take default


            $hasPreliminary = $championship->settings->hasPreliminary;
            $preliminaryTree = PreliminaryTree::where('championship_id', $championship->id)->get();

            if ($hasPreliminary) {
                // Check if PT has already been generated
                if ($preliminaryTree != null && $preliminaryTree->count() > 0) {
                    return view('preliminaryTree.index', compact('preliminaryTree'));
                } else {
                    $generation = PreliminaryTree::getGenerationStrategy($championship);
                    $preliminaryTree = $generation->run();
                    return view('preliminaryTree.index', compact('preliminaryTree'));
                }
            } else {
                if ($preliminaryTree != null && $preliminaryTree->count() > 0) {
                    return view('preliminaryTree.index', compact('preliminaryTree'));
                } else {
                    $generation = PreliminaryTree::getGenerationStrategy($championship);
                    $preliminaryTree = $generation->run();
                    return view('preliminaryTree.index', compact('preliminaryTree'));
                }
            }

        }
    }
}