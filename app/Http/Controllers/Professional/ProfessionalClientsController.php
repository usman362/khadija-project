<?php

namespace App\Http\Controllers\Professional;

use App\Domain\WhoHiredMe;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Who has hired this professional.
 *
 * The last place the two sides were out of step. A client has had Hired
 * Talent since October — who they engaged and where the money stands — and
 * the professional on the other end of those same bookings had a list of
 * gigs and nothing that answered "who keeps coming back".
 *
 * Everything here is read from bookings that already exist. Nothing new is
 * recorded, and nothing is predicted: see the note on WhoHiredMe for what was
 * deliberately left out.
 */
class ProfessionalClientsController extends Controller
{
    public function index(Request $request): View
    {
        $rows = WhoHiredMe::rows($request->user());

        /*
         * Returning clients first when asked for, otherwise most recently
         * worked for. The filter is a view of the same rows rather than a
         * second query, so the summary above the list cannot disagree with
         * the list below it.
         */
        $show = in_array($request->query('show'), ['repeat', 'upcoming'], true)
            ? $request->query('show')
            : 'all';

        $shown = match ($show) {
            'repeat'   => $rows->where('returning', true),
            'upcoming' => $rows->where('ahead', '>', 0),
            default    => $rows,
        };

        return view('professional.clients.index', [
            'rows'    => $shown->values(),
            'summary' => WhoHiredMe::summary($rows),
            'show'    => $show,
        ]);
    }
}
