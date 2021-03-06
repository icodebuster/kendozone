<?php

namespace App\Http\Controllers;

use App\Http\Requests\InviteRequest;
use App\Invite;
use App\Notifications\InviteCompetitor;
use App\Tournament;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Maatwebsite\Excel\Facades\Excel;

class InviteController extends Controller
{
    protected $emailBadFormat;
    protected $wrongEmail;


    /**
     * Display a listing of all invitations.
     *
     * @return View
     */
    public function index()
    {
        $invites = Auth::user()
            ->invites()
            ->with('tournament.owner')
            ->paginate(config('constants.PAGINATION'));
        return view('invitation.index', compact('invites'));
    }

    /**
     * Display the UI for inviting competitors
     *
     * @param Tournament $tournament
     * @return View
     */
    public function create(Tournament $tournament)
    {
        // Should dd $tournament
        return view('invitation.show', compact('tournament'));
    }


    /**
     * Send an email to competitor and store invitation.
     *
     * @param InviteRequest|Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //TODO check that recipient is list of emails
        $this->validate($request, [
            'recipients' => 'required'
        ]);

        $tournament = Tournament::where('slug', $request->tournamentSlug)->first();
        $recipients = json_decode($request->get("recipients"));
        foreach ($recipients as $recipient) {
            // Mail to Recipients
            $code = resolve(Invite::class)->generateTournamentInvite($recipient, $tournament);
            $user = new User();
            $user->email = $recipient;
            $user->notify(new InviteCompetitor($user, $tournament, $code, null));
        }
        flash()->success(trans('msg.invitation_sent'));
        return redirect(URL::action('TournamentController@edit', $tournament->slug));
    }


    /**
     * Send an email to competitor and store invitation.
     *
     * @param InviteRequest|Request $request
     * @return \Illuminate\Http\Response
     */
    public function upload(Request $request)
    {
        $this->emailBadFormat = false;
        $file = $request->file('invites')->store('invites');
        $tournament = Tournament::where('slug', $request->tournamentSlug)->first();
        // Parse Csv File

        $reader = Excel::load("storage/app/" . $file, function ($reader) {
        })->get();

        // Checking if malformed email
        resolve(Invite::class)->checkBadEmailsInExcel($reader, $tournament);
        if ($this->emailBadFormat == true) {
            flash()->error(trans('msg.email_not_valid', ['email' => $this->wrongEmail]));
            return redirect(URL::action('InviteController@create', $tournament->slug));
        }
        Invite::sendInvites($reader, $tournament);
        flash()->success(trans('msg.invitation_sent'));
        return redirect(URL::action('InviteController@create', $tournament->slug));
    }
}
