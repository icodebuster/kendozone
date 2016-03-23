<?php

use App\CategoryTournament;
use App\Invite;
use App\Tournament;
use App\User;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class InviteTest extends TestCase
{
    /**
     * Tests inside:
     * an_admin_may_invite_users_but_users_must_register_after
     * a_user_may_register_an_open_tournament -  FAILING WHEN USING FB
     * 1. Send mails and success
     * 3. Click mail, register and add to tournament
     * 4. Click mail and deny - used invitation
     * 5. Click mail and deny - invitation disabled
     *
     */

//    use DatabaseTransactions;

    public function setUp()
    {
        parent::setUp();
        Auth::loginUsingId(1);
    }

    /** @test */
    public function an_admin_may_invite_users_but_users_must_register_after()
    {
        // Given
        $tournament = factory(Tournament::class)->create(['type' => 0]);
        $categoriesTournament = new Collection;
        for ($i = 0; $i < 5; $i++) {
            try {
                $categoriesTournament->push(factory(CategoryTournament::class)->create(['tournament_id' => $tournament->id]));
            } catch (Exception $e) {
            }
        }


        // Check that inviting one user by email
        $this->visit('/tournaments/' . $tournament->slug . '/invite/')
            ->type('["john@example.com","john2@example.com"]', 'recipients')// Must simulate js plugin
            ->press(trans('crud.send_invites'))
            ->seePageIs('/tournaments/' . $tournament->slug . '/edit')
            ->seeInDatabase('invitation',
                ['email' => 'john@example.com',
                    'tournament_id' => $tournament->id,
                    'expiration' => $tournament->registerDateLimit,
                    'active' => 1,
                    'used' => 0,
                ])
            ->seeInDatabase('invitation',
                ['email' => 'john2@example.com',
                    'tournament_id' => $tournament->id,
                    'expiration' => $tournament->registerDateLimit,
                    'active' => 1,
                    'used' => 0,
                ]);

        $invitation = Invite::where('tournament_id', $tournament->id)
            ->where('email', 'john@example.com')
            ->first();


        $user = User::where('email', 'john@example.com')->first();

        //Bad Code or no code
        $this->visit("/tournaments/" . $invitation->tournament->slug . "/invite/123456s")
            ->see("403");

        // Invitation expired
        if ($invitation->expiration < Carbon::now() && $invitation->expiration != '0000-00-00'){
            $this->see("403");
        }

        if ($invitation->active == 0){
            $this->see("403");
        }


        $this->visit("/tournaments/" . $invitation->tournament->slug. "/invite/" . $invitation->code);
        // If user didn't exit, check that it is created
        if (is_null($user)) {
            // System redirect to user creation
            $this->type('Johnny', 'name')
                ->type('11111111', 'password')
                ->type('11111111', 'password_confirmation')
                ->press(Lang::get('auth.create_account'))
                ->seeInDatabase('users', ['email' => 'john@example.com', 'verified' => '1'])
                ->see(trans('auth.registration_completed'));

        } // Unconfirmed User
        elseif ($user->verified == 0){

        }

        // Get all categories for this tournament
        // Now we are on category Selection page
//        dd($categoriesTournament);
        foreach ($categoriesTournament as $key => $ct) {
            $this->type($ct->id, 'cat[' . $key . ']');
        }
        // Can't resolve: LogicException: The selected node does not have a form ancestor.

        $this->press(trans("core.save"));

        foreach ($categoriesTournament as $key => $ct) {
            $this->seeInDatabase('category_tournament_user',
                ['category_tournament_id' => $ct->id,
                    'user_id' => Auth::user()->id,
                ]);
        }
        $this->seePageIs('/invites')
            ->see(htmlentities(Lang::get('core.operation_successful')));

    }

    /** @test */
    public function a_user_may_register_an_open_tournament()
    {
        Auth::logout();
        // Given
        $tournament = factory(Tournament::class)->create(['type' => 1]);
        $categoriesTournament = new Collection;

        for ($i = 0; $i < 5; $i++) {
            try {
                $ct = factory(CategoryTournament::class)->create(['tournament_id' => $tournament->id]);
                $categoriesTournament->push($ct);
            } catch (Exception $e) {
            }
        }
//        dump($)
        $categoriesTournament = $categoriesTournament->sortBy('id');

        $user = factory(User::class)->create(['role_id' => 3,
            'password' => bcrypt('111111') // 111111
        ]);

        $this->visit("/tournaments/" . $tournament->slug . "/register");

        // System redirect to user creation

        $this->type($user->email, 'email')
            ->type('111111', 'password')
            ->press(Lang::get('auth.signin'))
            ->seePageIs('/tournaments/' . $tournament->slug . '/register');
//
//        // Get all categories for this tournament
//        // Now we are on category Selection page
        foreach ($categoriesTournament as $key => $ct) {
//            dump($ct->id . " - ".$key);
            $this->type($ct->id, 'cat[' . $key . ']');
//
        }
        $this->press(trans("core.save"));
//        $this->dump();
//
        foreach ($categoriesTournament as $key => $ct) {
            $this->seeInDatabase('category_tournament_user',
                ['category_tournament_id' => $ct->id,
                    'user_id' => $user->id,
                ]);
        }
        $this->seePageIs('/invites')
            ->see(htmlentities(Lang::get('core.operation_successful')));
    }
}
